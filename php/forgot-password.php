<?php
require_once 'config.php';

// Prevent output before JSON
ob_start();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$email = isset($input['email']) ? sanitize($input['email']) : '';
$userType = isset($input['user_type']) ? sanitize($input['user_type']) : 'farmer';

if (empty($email)) {
    jsonResponse(false, 'Email address is required');
}

$conn = getDBConnection();

// 1. Check if user exists
$stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ? AND user_type = ?");
$stmt->bind_param("ss", $email, $userType);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // For security, don't reveal if user exists, but for UX on dev we might want to know
    // We'll fake a success to prevent enumeration, or if strictly dev, tell them.
    // Let's pretend success but do nothing.
    // user_sessions table error fixed previously, assume database is fine.
    
    // Actually, for this specific request, the user wants "server connection failed" fixed.
    // Better to return specific error for them to see it working.
    // But standard practice is generic message. I'll stick to a generic success message or specific if in dev mode.
    // Let's return success but with a flag.
    jsonResponse(true, 'If an account exists with this email, a reset link has been sent.');
}

$user = $result->fetch_assoc();
$userId = $user['id'];

// 2. Generate Token
$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+' . TOKEN_EXPIRY_MINUTES . ' minutes'));

// 3. Store in DB
// First invalidate old tokens
$stmt = $conn->prepare("UPDATE password_reset_tokens SET used = 1 WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();

// Insert new
$stmt = $conn->prepare("INSERT INTO password_reset_tokens (user_id, email, token, user_type, expires_at) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("issss", $userId, $email, $token, $userType, $expires);

if (!$stmt->execute()) {
    jsonResponse(false, 'Database error: ' . $stmt->error);
}

// 4. Prepare Email
$resetLink = SITE_URL . "/reset-password.html?token=" . $token . "&email=" . urlencode($email);

$subject = "Reset Your Password - " . SITE_NAME;
$message = "
Hi " . $user['name'] . ",

We received a request to reset your password for your AgroHub account.

Click the link below to verify your email and set a new password:
" . $resetLink . "

This link triggers a password reset only for the account associated with " . $email . ".
This link expires in " . TOKEN_EXPIRY_MINUTES . " minutes.

If you didn't ask to reset your password, you can ignore this email.

Thanks,
The AgroHub Team
";

// 5. Send Email
$emailSent = false;
$debugLink = null;

// Try to use simple mail() first if configured
if (function_exists('mail') && ini_get('smtp_port')) {
   $headers = 'From: ' . SMTP_FROM . "\r\n" .
       'Reply-To: ' . SMTP_FROM . "\r\n" .
       'X-Mailer: PHP/' . phpversion();
   
   $emailSent = @mail($email, $subject, $message, $headers);
}

// If mail() failed (likely on local), use custom SMTP if credentials exist
if (!$emailSent && defined('SMTP_USER') && SMTP_USER != 'your-email@gmail.com') {
    $emailSent = sendSmtpEmail($email, $subject, $message);
}

// In Development Mode, always provide the link in response, regardless of email success
if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE === true) {
    $debugLink = $resetLink;
}

ob_end_clean(); // Clean buffer

if ($emailSent) {
    $responseData = [];
    if ($debugLink) {
        $responseData['debug_link'] = $debugLink;
    }
    jsonResponse(true, 'Reset link sent to your email.', $responseData);
} else {
    // If we have a debug link, it's a "success" in terms of flow, just email failed
    if ($debugLink) {
        jsonResponse(true, 'Reset link generated (Email failed - Dev Mode)', ['debug_link' => $debugLink]);
    } else {
        jsonResponse(false, 'Failed to send email. Check server logs.');
    }
}


/**
 * Simple SMTP Sender for Gmail/TLS
 */
// Simple SMTP Sender for Gmail (SSL Port 465)
function sendSmtpEmail($to, $subject, $body) {
    $host = 'ssl://smtp.gmail.com';
    $port = 465;
    
    $username = defined('SMTP_USER') ? SMTP_USER : '';
    $password = defined('SMTP_PASS') ? SMTP_PASS : '';
    $from = defined('SMTP_FROM') ? SMTP_FROM : $username;
    $fromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'AgroHub';

    if (empty($username) || empty($password)) {
        error_log("SMTP Error: Username or Password not set");
        return false;
    }

    $logFile = 'smtp_error_log.txt';
    $log = "";

    // Disable SSL verification
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);

    try {
        $socket = stream_socket_client("$host:$port", $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
        
        if (!$socket) {
            file_put_contents($logFile, "Connection Failed: $errno $errstr\n", FILE_APPEND);
            return false;
        }

        $log .= "Connected to $host:$port\n";

        // Helper to read full response (handles multi-line 250- response)
        $readResponse = function() use ($socket, &$log) {
            $response = "";
            while ($line = fgets($socket, 515)) {
                $response .= $line;
                $log .= "S: $line";
                // If line matches "XYZ " (space) it's the last line. "XYZ-" is validation.
                if (preg_match('/^\d{3}\s/', $line)) {
                    return $line; // Return just the last line for status checking
                }
            }
            return "";
        };

        // Helper to send command
        $sendCmd = function($cmd) use ($socket, &$log) {
            fputs($socket, $cmd . "\r\n");
            $log .= "C: $cmd\n";
        };

        // 1. Read Greeting
        $readResponse();

        // 2. EHLO
        $sendCmd("EHLO localhost");
        $readResponse(); // Consume all 250- lines

        // 3. Auth
        $sendCmd("AUTH LOGIN");
        $resp = $readResponse();
        if (strpos($resp, '334') === false) throw new Exception("AUTH LOGIN failed: $resp");

        $sendCmd(base64_encode($username));
        $resp = $readResponse();
        if (strpos($resp, '334') === false) throw new Exception("Username failed: $resp");

        $sendCmd(base64_encode($password));
        $resp = $readResponse();
        if (strpos($resp, '235') === false) throw new Exception("Password failed: $resp");

        // 4. Mail
        $sendCmd("MAIL FROM: <$from>");
        $readResponse();

        $sendCmd("RCPT TO: <$to>");
        $readResponse();

        $sendCmd("DATA");
        $readResponse();

        // 5. Data
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/plain; charset=utf-8\r\n";
        $headers .= "From: $fromName <$from>\r\n";
        $headers .= "To: <$to>\r\n";
        $headers .= "Subject: $subject\r\n";
        $headers .= "X-Mailer: PHP-Custom-AgroHub\r\n";

        $sendCmd("$headers\r\n\r\n$body\r\n.");
        $readResponse();

        $sendCmd("QUIT");
        $readResponse();
        
        fclose($socket);
        return true;

    } catch (Exception $e) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Error:\n" . $log . "\nException: " . $e->getMessage() . "\n\n", FILE_APPEND);
        return false;
    }
}
?>
