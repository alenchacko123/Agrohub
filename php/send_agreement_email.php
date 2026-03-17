<?php
/**
 * send_agreement_email.php
 * Shared email helper for AgroHub agreement notifications.
 *
 * Functions exported:
 *   sendAgreementEmail($to, $toName, $subject, $htmlBody)
 *   sendFarmerPaidEmail($ownerEmail, $ownerName, $farmerName, $equipmentName, $agreementId, $totalAmount, $signedAt)
 *   sendOwnerSignedEmail($farmerEmail, $farmerName, $ownerName, $equipmentName, $agreementId, $signedAt)
 */

// ─────────────────────────────────────────────────────────────────────────────
// LOW-LEVEL SMTP SENDER
// ─────────────────────────────────────────────────────────────────────────────
function sendAgreementEmail($to, $toName, $subject, $htmlBody) {
    $host     = 'ssl://smtp.gmail.com';
    $port     = 465;
    $username = defined('SMTP_USER') ? SMTP_USER : '';
    $password = defined('SMTP_PASS') ? SMTP_PASS : '';
    $from     = defined('SMTP_FROM') ? SMTP_FROM : $username;
    $fromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'AgroHub';

    if (empty($username) || empty($password)) {
        error_log('AgroHub Email: SMTP credentials not configured.');
        return false;
    }

    // Debug log — always written so we can see what happened
    $logFile = __DIR__ . '/../smtp_error_log.txt';
    $log     = date('[Y-m-d H:i:s]') . " To: $to | Subject: $subject\n";

    $context = stream_context_create([
        'ssl' => [
            'verify_peer'      => false,
            'verify_peer_name' => false,
            'allow_self_signed'=> true,
        ]
    ]);

    try {
        $socket = stream_socket_client("$host:$port", $errno, $errstr, 20,
                                       STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            $msg = "Connection failed: $errno $errstr";
            $log .= "ERROR: $msg\n";
            file_put_contents($logFile, $log, FILE_APPEND);
            return false;
        }
        stream_set_timeout($socket, 20);

        // Read one full SMTP response (handles multi-line 250- replies)
        $read = function () use ($socket, &$log) {
            $resp = '';
            while ($line = fgets($socket, 515)) {
                $resp .= $line;
                $log  .= "S: $line";
                if (preg_match('/^\d{3}[\s]/', $line)) return $line;
            }
            return '';
        };

        $send = function ($cmd) use ($socket, &$log) {
            fputs($socket, $cmd . "\r\n");
            $log .= "C: $cmd\n";
        };

        $read();                          // banner
        $send('EHLO localhost'); $read();

        // AUTH LOGIN
        $send('AUTH LOGIN');
        $r = $read();
        if (strpos($r, '334') === false) throw new Exception("AUTH LOGIN rejected: $r");

        $send(base64_encode($username));
        $r = $read();
        if (strpos($r, '334') === false) throw new Exception("Username rejected: $r");

        $send(base64_encode($password));
        $r = $read();
        if (strpos($r, '235') === false) throw new Exception("Password rejected: $r");

        // Envelope
        $send("MAIL FROM: <$from>");  $read();
        $send("RCPT TO: <$to>");      $read();
        $send('DATA');
        $r = $read();
        if (strpos($r, '354') === false) throw new Exception("DATA rejected: $r");

        // Build MIME multipart message
        $boundary = '----=_AgroHub_' . md5(uniqid('', true));

        $plainText = wordwrap(
            strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody)),
            76, "\n", true
        );

        // Base64-encode the subject for UTF-8 safety (avoids emoji issues)
        $encSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
        $headers .= "From: $fromName <$from>\r\n";
        $headers .= "To: $toName <$to>\r\n";
        $headers .= "Subject: $encSubject\r\n";
        $headers .= "X-Mailer: AgroHub-PHP/1.0\r\n";
        $headers .= "Date: " . date('r') . "\r\n";

        $msgBody  = "--$boundary\r\n";
        $msgBody .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $msgBody .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $msgBody .= quoted_printable_encode($plainText) . "\r\n\r\n";
        $msgBody .= "--$boundary\r\n";
        $msgBody .= "Content-Type: text/html; charset=UTF-8\r\n";
        $msgBody .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $msgBody .= quoted_printable_encode($htmlBody) . "\r\n\r\n";
        $msgBody .= "--$boundary--";

        // Send: headers + blank line + body + CRLF.CRLF (SMTP DATA terminator)
        fputs($socket, $headers . "\r\n" . $msgBody . "\r\n.\r\n");
        $log .= "C: [MIME message body sent]\n";

        $r = $read();
        if (strpos($r, '250') === false) throw new Exception("Message rejected after DATA: $r");

        $send('QUIT');
        $read();
        fclose($socket);

        $log .= "SUCCESS\n";
        file_put_contents($logFile, $log, FILE_APPEND);
        return true;

    } catch (Exception $e) {
        $log .= "EXCEPTION: " . $e->getMessage() . "\n";
        file_put_contents($logFile, $log, FILE_APPEND);
        error_log('AgroHub sendAgreementEmail: ' . $e->getMessage());
        if (!empty($socket)) { @fclose($socket); }
        return false;
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// EMAIL 1 — To OWNER: farmer has paid & signed
// ─────────────────────────────────────────────────────────────────────────────
function sendFarmerPaidEmail($ownerEmail, $ownerName, $farmerName, $equipmentName,
                              $agreementId, $totalAmount, $signedAt = null) {

    $siteUrl         = defined('SITE_URL')  ? SITE_URL  : 'http://localhost/Agrohub';
    $siteName        = defined('SITE_NAME') ? SITE_NAME : 'AgroHub';
    $year            = date('Y');  // define BEFORE heredoc
    $signedDate      = $signedAt ? date('d M Y, h:i A', strtotime($signedAt)) : date('d M Y, h:i A');
    $formattedAmount = '&#8377;' . number_format((float)$totalAmount, 2);
    $agreementUrl    = $siteUrl . '/agreements.html?id=' . urlencode($agreementId);
    $subject         = "[AgroHub] Action Required: {$farmerName} has paid and signed the rental agreement";

    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 0;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;">

        <tr><td style="background:linear-gradient(135deg,#2d6a4f,#1b4332);padding:32px 40px;text-align:center;">
          <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:700;">&#127807; AgroHub</h1>
          <p style="margin:6px 0 0;color:#a7f3d0;font-size:14px;">Equipment Rental Platform</p>
        </td></tr>

        <tr><td style="padding:0 40px;">
          <div style="background:#fef3c7;border-left:5px solid #f59e0b;border-radius:8px;padding:14px 18px;margin:24px 0 0;">
            <p style="margin:0;color:#92400e;font-weight:700;font-size:15px;">&#9888; Action Required: Your signature is needed</p>
          </div>
        </td></tr>

        <tr><td style="padding:20px 40px 10px;">
          <p style="color:#1a1a2e;font-size:15px;margin:0 0 8px;">Dear <strong>{$ownerName}</strong>,</p>
          <p style="color:#4a5568;font-size:14px;line-height:1.7;margin:0 0 20px;">
            <strong>{$farmerName}</strong> has successfully
            <span style="color:#059669;font-weight:700;">paid the full amount</span> and
            <span style="color:#2d6a4f;font-weight:700;">digitally signed</span>
            the rental agreement for your equipment. Please log in and sign the agreement to fully execute it.
          </p>
        </td></tr>

        <tr><td style="padding:0 40px 20px;">
          <table width="100%" cellpadding="6" cellspacing="0" style="background:#f0fdf4;border:1px solid #6ee7b7;border-radius:10px;">
            <tr><td colspan="2" style="padding:12px 18px;border-bottom:1px solid #d1fae5;">
              <strong style="font-size:12px;color:#059669;text-transform:uppercase;letter-spacing:1px;">Agreement Details</strong>
            </td></tr>
            <tr>
              <td style="padding:8px 18px;color:#64748b;font-size:13px;width:45%;">Agreement ID</td>
              <td style="padding:8px 18px;color:#1e293b;font-weight:700;font-size:13px;">{$agreementId}</td>
            </tr>
            <tr>
              <td style="padding:8px 18px;color:#64748b;font-size:13px;">Equipment</td>
              <td style="padding:8px 18px;color:#1e293b;font-weight:700;font-size:13px;">{$equipmentName}</td>
            </tr>
            <tr>
              <td style="padding:8px 18px;color:#64748b;font-size:13px;">Farmer (Lessee)</td>
              <td style="padding:8px 18px;color:#1e293b;font-weight:700;font-size:13px;">{$farmerName}</td>
            </tr>
            <tr>
              <td style="padding:8px 18px;color:#64748b;font-size:13px;">Amount Paid</td>
              <td style="padding:8px 18px;color:#059669;font-weight:700;font-size:14px;">{$formattedAmount}</td>
            </tr>
            <tr>
              <td style="padding:8px 18px;color:#64748b;font-size:13px;">Signed On</td>
              <td style="padding:8px 18px;color:#1e293b;font-weight:600;font-size:13px;">{$signedDate}</td>
            </tr>
          </table>
        </td></tr>

        <tr><td style="padding:0 40px 28px;text-align:center;">
          <a href="{$agreementUrl}"
             style="display:inline-block;background:#1b4332;color:#ffffff;text-decoration:none;font-weight:700;font-size:15px;padding:14px 36px;border-radius:50px;">
            Sign the Agreement Now
          </a>
          <p style="margin:12px 0 0;color:#94a3b8;font-size:12px;">
            Link: <a href="{$agreementUrl}" style="color:#2d6a4f;">{$agreementUrl}</a>
          </p>
        </td></tr>

        <tr><td style="background:#f8fafc;padding:18px 40px;text-align:center;border-top:1px solid #e2e8f0;">
          <p style="margin:0;color:#94a3b8;font-size:12px;">
            Sent by <strong>AgroHub</strong> &mdash; Digital Agricultural Services<br>
            &copy; {$siteName} {$year}. Please do not reply to this email.
          </p>
        </td></tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;

    return sendAgreementEmail($ownerEmail, $ownerName, $subject, $html);
}

// ─────────────────────────────────────────────────────────────────────────────
// EMAIL 2 — To FARMER: owner has signed (agreement fully executed)
// ─────────────────────────────────────────────────────────────────────────────
function sendOwnerSignedEmail($farmerEmail, $farmerName, $ownerName, $equipmentName,
                               $agreementId, $signedAt = null) {

    $siteUrl      = defined('SITE_URL')  ? SITE_URL  : 'http://localhost/Agrohub';
    $siteName     = defined('SITE_NAME') ? SITE_NAME : 'AgroHub';
    $year         = date('Y');  // define BEFORE heredoc
    $signedDate   = $signedAt ? date('d M Y, h:i A', strtotime($signedAt)) : date('d M Y, h:i A');
    $agreementUrl = $siteUrl . '/agreements.html?id=' . urlencode($agreementId) . '&download=1';
    $subject      = "[AgroHub] Rental Approved! {$ownerName} has signed your agreement";

    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 0;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;">

        <tr><td style="background:linear-gradient(135deg,#2d6a4f,#1b4332);padding:32px 40px;text-align:center;">
          <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:700;">&#127807; AgroHub</h1>
          <p style="margin:6px 0 0;color:#a7f3d0;font-size:14px;">Equipment Rental Platform</p>
        </td></tr>

        <tr><td style="padding:0 40px;">
          <div style="background:#d1fae5;border-left:5px solid #10b981;border-radius:8px;padding:14px 18px;margin:24px 0 0;text-align:center;">
            <p style="margin:0;color:#047857;font-weight:700;font-size:16px;">&#10003; Payment Verified & Agreement Approved!</p>
            <p style="margin:4px 0 0;color:#065f46;font-size:13px;">You are now cleared to rent the equipment.</p>
          </div>
        </td></tr>

        <tr><td style="padding:20px 40px 10px;">
          <p style="color:#1a1a2e;font-size:15px;margin:0 0 8px;">Dear <strong>{$farmerName}</strong>,</p>
          <p style="color:#4a5568;font-size:14px;line-height:1.7;margin:0 0 20px;">
            <strong>{$ownerName}</strong> (the equipment owner) has
            <span style="color:#2d6a4f;font-weight:700;">digitally signed</span> your rental agreement.
            The agreement is now <span style="color:#059669;font-weight:700;">fully signed by both parties</span>
            and is legally binding. You may download or view your agreement at any time from AgroHub.
          </p>
        </td></tr>

        <tr><td style="padding:0 40px 20px;">
          <table width="100%" cellpadding="6" cellspacing="0" style="background:#f0fdf4;border:1px solid #6ee7b7;border-radius:10px;">
            <tr><td colspan="2" style="padding:12px 18px;border-bottom:1px solid #d1fae5;">
              <strong style="font-size:12px;color:#059669;text-transform:uppercase;letter-spacing:1px;">Agreement Summary</strong>
            </td></tr>
            <tr>
              <td style="padding:8px 18px;color:#64748b;font-size:13px;width:45%;">Agreement ID</td>
              <td style="padding:8px 18px;color:#1e293b;font-weight:700;font-size:13px;">{$agreementId}</td>
            </tr>
            <tr>
              <td style="padding:8px 18px;color:#64748b;font-size:13px;">Equipment</td>
              <td style="padding:8px 18px;color:#1e293b;font-weight:700;font-size:13px;">{$equipmentName}</td>
            </tr>
            <tr>
              <td style="padding:8px 18px;color:#64748b;font-size:13px;">Owner (Lessor)</td>
              <td style="padding:8px 18px;color:#1e293b;font-weight:700;font-size:13px;">{$ownerName}</td>
            </tr>
            <tr>
              <td style="padding:8px 18px;color:#64748b;font-size:13px;">Owner Signed On</td>
              <td style="padding:8px 18px;color:#1e293b;font-weight:600;font-size:13px;">{$signedDate}</td>
            </tr>
            <tr>
              <td style="padding:8px 18px;color:#64748b;font-size:13px;">Agreement Status</td>
              <td style="padding:8px 18px;">
                <span style="background:#d1fae5;color:#065f46;font-weight:700;font-size:12px;padding:3px 10px;border-radius:20px;border:1px solid #6ee7b7;">
                  &#10003; Fully Signed
                </span>
              </td>
            </tr>
          </table>
        </td></tr>

        <tr><td style="padding:0 40px 28px;text-align:center;">
          <a href="{$agreementUrl}"
             style="display:inline-block;background:#1b4332;color:#ffffff;text-decoration:none;font-weight:700;font-size:15px;padding:14px 36px;border-radius:50px;">
            Download Final Agreement (PDF)
          </a>
          <p style="margin:12px 0 0;color:#94a3b8;font-size:12px;">
            Link: <a href="{$agreementUrl}" style="color:#2d6a4f;">{$agreementUrl}</a>
          </p>
        </td></tr>

        <tr><td style="padding:0 40px 24px;">
          <div style="background:#eff6ff;border-radius:8px;padding:14px 18px;">
            <p style="margin:0 0 8px;color:#1e40af;font-weight:700;font-size:13px;">What happens next?</p>
            <p style="margin:3px 0;color:#1e40af;font-size:13px;">&#8226; Your agreement is now legally binding.</p>
            <p style="margin:3px 0;color:#1e40af;font-size:13px;">&#8226; Download a PDF copy from the AgroHub Agreements page.</p>
            <p style="margin:3px 0;color:#1e40af;font-size:13px;">&#8226; Contact the owner using details in your agreement.</p>
            <p style="margin:3px 0;color:#1e40af;font-size:13px;">&#8226; Enjoy your rental and use the equipment responsibly!</p>
          </div>
        </td></tr>

        <tr><td style="background:#f8fafc;padding:18px 40px;text-align:center;border-top:1px solid #e2e8f0;">
          <p style="margin:0;color:#94a3b8;font-size:12px;">
            Sent by <strong>AgroHub</strong> &mdash; Digital Agricultural Services<br>
            &copy; {$siteName} {$year}. Please do not reply to this email.
          </p>
        </td></tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;

    return sendAgreementEmail($farmerEmail, $farmerName, $subject, $html);
}

// ─────────────────────────────────────────────────────────────────────────────
// EMAIL 3 — To OWNER: agreement fully signed by both parties (download now)
// ─────────────────────────────────────────────────────────────────────────────
function sendFullySignedOwnerEmail($ownerEmail, $ownerName, $farmerName, $equipmentName,
                                    $agreementId, $signedAt = null) {

    $siteUrl      = defined('SITE_URL')  ? SITE_URL  : 'http://localhost/Agrohub';
    $siteName     = defined('SITE_NAME') ? SITE_NAME : 'AgroHub';
    $year         = date('Y');  // define BEFORE heredoc
    $signedDate   = $signedAt ? date('d M Y, h:i A', strtotime($signedAt)) : date('d M Y, h:i A');
    $agreementUrl = $siteUrl . '/agreements.html?id=' . urlencode($agreementId) . '&download=1';
    $subject      = "[AgroHub] Agreement Fully Signed! Your rental agreement with {$farmerName} is complete";

    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 0;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;">

        <tr><td style="background:linear-gradient(135deg,#2d6a4f,#1b4332);padding:32px 40px;text-align:center;">
          <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:700;">&#127807; AgroHub</h1>
          <p style="margin:6px 0 0;color:#a7f3d0;font-size:14px;">Equipment Rental Platform</p>
        </td></tr>

        <tr><td style="padding:0 40px;">
          <div style="background:#d1fae5;border-left:5px solid #10b981;border-radius:8px;padding:14px 18px;margin:24px 0 0;text-align:center;">
            <p style="margin:0;color:#047857;font-weight:700;font-size:15px;">&#10003; Agreement Fully Executed &mdash; Both Parties Have Signed!</p>
          </div>
        </td></tr>

        <tr><td style="padding:20px 40px 10px;">
          <p style="color:#1a1a2e;font-size:15px;margin:0 0 8px;">Dear <strong>{$ownerName}</strong>,</p>
          <p style="color:#4a5568;font-size:14px;line-height:1.7;margin:0 0 20px;">
            Your rental agreement with <strong>{$farmerName}</strong> for <strong>{$equipmentName}</strong>
            is now <span style="color:#059669;font-weight:700;">fully signed by both parties</span> and is legally binding.
            You can download the fully executed agreement at any time from your AgroHub dashboard.
          </p>
        </td></tr>

        <tr><td style="padding:0 40px 20px;">
          <table width="100%" cellpadding="6" cellspacing="0" style="background:#f0fdf4;border:1px solid #6ee7b7;border-radius:10px;">
            <tr><td colspan="2" style="padding:12px 18px;border-bottom:1px solid #d1fae5;">
              <strong style="font-size:12px;color:#059669;text-transform:uppercase;letter-spacing:1px;">Agreement Summary</strong>
            </td></tr>
            <tr>
              <td style="padding:8px 18px;color:#64748b;font-size:13px;width:45%;">Agreement ID</td>
              <td style="padding:8px 18px;color:#1e293b;font-weight:700;font-size:13px;">{$agreementId}</td>
            </tr>
            <tr>
              <td style="padding:8px 18px;color:#64748b;font-size:13px;">Equipment</td>
              <td style="padding:8px 18px;color:#1e293b;font-weight:700;font-size:13px;">{$equipmentName}</td>
            </tr>
            <tr>
              <td style="padding:8px 18px;color:#64748b;font-size:13px;">Farmer (Lessee)</td>
              <td style="padding:8px 18px;color:#1e293b;font-weight:700;font-size:13px;">{$farmerName}</td>
            </tr>
            <tr>
              <td style="padding:8px 18px;color:#64748b;font-size:13px;">Fully Signed On</td>
              <td style="padding:8px 18px;color:#1e293b;font-weight:600;font-size:13px;">{$signedDate}</td>
            </tr>
            <tr>
              <td style="padding:8px 18px;color:#64748b;font-size:13px;">Status</td>
              <td style="padding:8px 18px;">
                <span style="background:#d1fae5;color:#065f46;font-weight:700;font-size:12px;padding:3px 10px;border-radius:20px;border:1px solid #6ee7b7;">
                  &#10003; Fully Signed
                </span>
              </td>
            </tr>
          </table>
        </td></tr>

        <tr><td style="padding:0 40px 28px;text-align:center;">
          <a href="{$agreementUrl}"
             style="display:inline-block;background:#1b4332;color:#ffffff;text-decoration:none;font-weight:700;font-size:15px;padding:14px 36px;border-radius:50px;">
            View &amp; Download Agreement
          </a>
          <p style="margin:12px 0 0;color:#94a3b8;font-size:12px;">
            Link: <a href="{$agreementUrl}" style="color:#2d6a4f;">{$agreementUrl}</a>
          </p>
        </td></tr>

        <tr><td style="padding:0 40px 24px;">
          <div style="background:#eff6ff;border-radius:8px;padding:14px 18px;">
            <p style="margin:0 0 8px;color:#1e40af;font-weight:700;font-size:13px;">What happens next?</p>
            <p style="margin:3px 0;color:#1e40af;font-size:13px;">&#8226; The agreement is legally binding for both parties.</p>
            <p style="margin:3px 0;color:#1e40af;font-size:13px;">&#8226; Download a PDF copy from your AgroHub Agreements page.</p>
            <p style="margin:3px 0;color:#1e40af;font-size:13px;">&#8226; The farmer will collect the equipment as per the agreed schedule.</p>
            <p style="margin:3px 0;color:#1e40af;font-size:13px;">&#8226; The security deposit will be refunded on safe return of the equipment.</p>
          </div>
        </td></tr>

        <tr><td style="background:#f8fafc;padding:18px 40px;text-align:center;border-top:1px solid #e2e8f0;">
          <p style="margin:0;color:#94a3b8;font-size:12px;">
            Sent by <strong>AgroHub</strong> &mdash; Digital Agricultural Services<br>
            &copy; {$siteName} {$year}. Please do not reply to this email.
          </p>
        </td></tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;

    return sendAgreementEmail($ownerEmail, $ownerName, $subject, $html);
}

// ─────────────────────────────────────────────────────────────────────────────
// EMAIL 4 — To OWNER: farmer submitted equipment feedback
// ─────────────────────────────────────────────────────────────────────────────
function sendFeedbackEmail($ownerEmail, $ownerName, $farmerName, $equipmentName,
                            $bookingId,
                            $q1, $q2, $q3, $q4, $q5,
                            $overall, $additionalComments = '') {

    $siteUrl   = defined('SITE_URL')  ? SITE_URL  : 'http://localhost/Agrohub';
    $siteName  = defined('SITE_NAME') ? SITE_NAME : 'AgroHub';
    $year      = date('Y');
    $subject   = "[AgroHub] Feedback Received: {$farmerName} reviewed {$equipmentName}";

    // Helper: render star blocks as coloured text
    $stars = function($rating) {
        $filled = str_repeat('&#9733;', $rating);
        $empty  = str_repeat('&#9734;', 5 - $rating);
        $colour = $rating >= 4 ? '#059669' : ($rating >= 3 ? '#f59e0b' : '#ef4444');
        return "<span style=\"color:{$colour};font-size:18px;\">{$filled}</span>"
             . "<span style=\"color:#cbd5e1;font-size:18px;\">{$empty}</span>"
             . " <strong style=\"color:{$colour};font-size:13px;\">{$rating}/5</strong>";
    };

    // Overall badge colour
    $overallColour = $overall >= 4 ? '#059669' : ($overall >= 3 ? '#f59e0b' : '#ef4444');
    $overallLabel  = $overall >= 4.5 ? 'Excellent' : ($overall >= 3.5 ? 'Good' : ($overall >= 2.5 ? 'Average' : 'Poor'));

    $q1s = $stars($q1); $q2s = $stars($q2); $q3s = $stars($q3);
    $q4s = $stars($q4); $q5s = $stars($q5);

    $commentsHtml = !empty($additionalComments)
        ? "<tr><td style=\"padding:0 40px 24px;\">
             <div style=\"background:#f8fafc;border-left:4px solid #6366f1;border-radius:8px;padding:16px 20px;\">
               <p style=\"margin:0 0 6px;color:#6366f1;font-weight:700;font-size:13px;\">&#128172; Additional Comments from {$farmerName}</p>
               <p style=\"margin:0;color:#374151;font-size:14px;line-height:1.7;font-style:italic;\">&ldquo;" . htmlspecialchars($additionalComments) . "&rdquo;</p>
             </div>
           </td></tr>"
        : '';

    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 0;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;">

        <!-- Header -->
        <tr><td style="background:linear-gradient(135deg,#2d6a4f,#1b4332);padding:32px 40px;text-align:center;">
          <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:700;">&#127807; AgroHub</h1>
          <p style="margin:6px 0 0;color:#a7f3d0;font-size:14px;">Equipment Rental Platform</p>
        </td></tr>

        <!-- Feedback badge -->
        <tr><td style="padding:0 40px;">
          <div style="background:#ede9fe;border-left:5px solid #7c3aed;border-radius:8px;padding:14px 18px;margin:24px 0 0;text-align:center;">
            <p style="margin:0;color:#5b21b6;font-weight:700;font-size:15px;">&#11088; New Feedback Received for Your Equipment!</p>
          </div>
        </td></tr>

        <!-- Intro -->
        <tr><td style="padding:20px 40px 10px;">
          <p style="color:#1a1a2e;font-size:15px;margin:0 0 8px;">Dear <strong>{$ownerName}</strong>,</p>
          <p style="color:#4a5568;font-size:14px;line-height:1.7;margin:0 0 20px;">
            <strong>{$farmerName}</strong> has submitted feedback for your equipment
            <strong>{$equipmentName}</strong> (Booking #AGR-{$bookingId}).
            Here is their detailed review:
          </p>
        </td></tr>

        <!-- Overall Score -->
        <tr><td style="padding:0 40px 20px;text-align:center;">
          <div style="background:#f0fdf4;border:2px solid {$overallColour};border-radius:12px;padding:20px;display:inline-block;min-width:200px;">
            <div style="font-size:42px;font-weight:800;color:{$overallColour};line-height:1;">{$overall}</div>
            <div style="font-size:22px;color:{$overallColour};margin:4px 0;">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
            <div style="font-size:14px;font-weight:700;color:{$overallColour};">{$overallLabel}</div>
            <div style="font-size:12px;color:#64748b;margin-top:2px;">Overall Rating (out of 5)</div>
          </div>
        </td></tr>

        <!-- 5 Question Breakdown -->
        <tr><td style="padding:0 40px 20px;">
          <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;">
            <tr><td colspan="2" style="padding:12px 20px;border-bottom:1px solid #e2e8f0;">
              <strong style="font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:1px;">Detailed Ratings</strong>
            </td></tr>
            <tr>
              <td style="padding:12px 20px;color:#374151;font-size:13px;border-bottom:1px solid #f1f5f9;width:52%;">
                <strong>1.</strong> Equipment Condition
              </td>
              <td style="padding:12px 20px;border-bottom:1px solid #f1f5f9;">{$q1s}</td>
            </tr>
            <tr>
              <td style="padding:12px 20px;color:#374151;font-size:13px;border-bottom:1px solid #f1f5f9;">
                <strong>2.</strong> Performance &amp; Reliability
              </td>
              <td style="padding:12px 20px;border-bottom:1px solid #f1f5f9;">{$q2s}</td>
            </tr>
            <tr>
              <td style="padding:12px 20px;color:#374151;font-size:13px;border-bottom:1px solid #f1f5f9;">
                <strong>3.</strong> Value for Money
              </td>
              <td style="padding:12px 20px;border-bottom:1px solid #f1f5f9;">{$q3s}</td>
            </tr>
            <tr>
              <td style="padding:12px 20px;color:#374151;font-size:13px;border-bottom:1px solid #f1f5f9;">
                <strong>4.</strong> Owner Communication
              </td>
              <td style="padding:12px 20px;border-bottom:1px solid #f1f5f9;">{$q4s}</td>
            </tr>
            <tr>
              <td style="padding:12px 20px;color:#374151;font-size:13px;">
                <strong>5.</strong> Would Recommend to Others
              </td>
              <td style="padding:12px 20px;">{$q5s}</td>
            </tr>
          </table>
        </td></tr>

        {$commentsHtml}

        <!-- CTA -->
        <tr><td style="padding:0 40px 28px;text-align:center;">
          <a href="{$siteUrl}/owner-dashboard.html"
             style="display:inline-block;background:#1b4332;color:#ffffff;text-decoration:none;font-weight:700;font-size:15px;padding:14px 36px;border-radius:50px;">
            View Your Dashboard
          </a>
        </td></tr>

        <!-- Footer -->
        <tr><td style="background:#f8fafc;padding:18px 40px;text-align:center;border-top:1px solid #e2e8f0;">
          <p style="margin:0;color:#94a3b8;font-size:12px;">
            Sent by <strong>AgroHub</strong> &mdash; Digital Agricultural Services<br>
            &copy; {$siteName} {$year}. Please do not reply to this email.
          </p>
        </td></tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;

    return sendAgreementEmail($ownerEmail, $ownerName, $subject, $html);
}
?>
