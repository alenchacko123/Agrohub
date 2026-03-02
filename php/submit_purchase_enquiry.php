<?php
/**
 * Submit Purchase Enquiry for Second-Hand Equipment
 * 
 * - Saves the enquiry details to a DB table
 * - Creates an in-app notification for the equipment owner
 * - Sends an email to the owner with the farmer's contact details
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit;
}

// ── Validate required fields ──────────────────────────────────────────────────
$equipmentId  = isset($input['equipment_id'])  ? intval($input['equipment_id'])              : 0;
$buyerName    = isset($input['buyer_name'])    ? trim($input['buyer_name'])                  : '';
$buyerPhone   = isset($input['buyer_phone'])   ? trim($input['buyer_phone'])                 : '';
$buyerEmail   = isset($input['buyer_email'])   ? trim($input['buyer_email'])                 : '';
$deliveryAddr = isset($input['delivery_address']) ? trim($input['delivery_address'])         : '';
$paymentMethod = isset($input['payment_method']) ? trim($input['payment_method'])            : '';
$notes        = isset($input['additional_notes']) ? trim($input['additional_notes'])         : '';
$totalAmount  = isset($input['total_amount'])  ? trim($input['total_amount'])                : '';
$equipmentName = isset($input['equipment_name']) ? trim($input['equipment_name'])            : '';

if (!$equipmentId || !$buyerName || !$buyerPhone || !$buyerEmail) {
    echo json_encode(['success' => false, 'error' => 'Required fields are missing']);
    exit;
}

try {
    $conn = getDBConnection();

    // ── 1. Ensure purchase_enquiries table exists ─────────────────────────────
    $conn->query("CREATE TABLE IF NOT EXISTS purchase_enquiries (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        equipment_id  INT NOT NULL,
        owner_id      INT DEFAULT NULL,
        buyer_name    VARCHAR(255) NOT NULL,
        buyer_phone   VARCHAR(30)  NOT NULL,
        buyer_email   VARCHAR(255) NOT NULL,
        delivery_address TEXT,
        payment_method VARCHAR(50),
        additional_notes TEXT,
        total_amount  VARCHAR(50),
        status        ENUM('pending','contacted','closed') DEFAULT 'pending',
        created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_equipment_id (equipment_id),
        INDEX idx_owner_id (owner_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ── 2. Fetch the equipment + owner details ─────────────────────────────────
    $eqStmt = $conn->prepare("SELECT id, owner_id, owner_name, owner_email, owner_phone, equipment_name FROM secondhand_equipment WHERE id = ?");
    if (!$eqStmt) throw new Exception('Prepare failed: ' . $conn->error);
    $eqStmt->bind_param("i", $equipmentId);
    $eqStmt->execute();
    $eqResult = $eqStmt->get_result();

    if ($eqResult->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Equipment not found']);
        exit;
    }

    $equipment = $eqResult->fetch_assoc();
    $ownerId    = intval($equipment['owner_id']);
    $ownerName  = $equipment['owner_name'];
    $ownerEmail = $equipment['owner_email'];
    $eqName     = $equipment['equipment_name'] ?: $equipmentName;
    $eqStmt->close();

    // ── 3. Save the enquiry ────────────────────────────────────────────────────
    $insStmt = $conn->prepare(
        "INSERT INTO purchase_enquiries (equipment_id, owner_id, buyer_name, buyer_phone, buyer_email, delivery_address, payment_method, additional_notes, total_amount)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    if (!$insStmt) throw new Exception('Prepare insert failed: ' . $conn->error);
    $insStmt->bind_param("iisssssss",
        $equipmentId, $ownerId, $buyerName, $buyerPhone, $buyerEmail,
        $deliveryAddr, $paymentMethod, $notes, $totalAmount
    );
    $insStmt->execute();
    $enquiryId = $conn->insert_id;
    $insStmt->close();

    // ── 4. Create in-app notification for the owner ────────────────────────────
    if ($ownerId > 0) {
        // Ensure notification_type column exists (may not exist on older installs)
        $conn->query("ALTER TABLE notifications ADD COLUMN IF NOT EXISTS notification_type VARCHAR(50) DEFAULT 'info'");

        $notifMessage = "🛒 New Purchase Enquiry: {$buyerName} is interested in purchasing your \"{$eqName}\". Phone: {$buyerPhone} | Email: {$buyerEmail}";
        $notifType    = 'purchase_enquiry';

        $notifStmt = $conn->prepare(
            "INSERT INTO notifications (user_id, message, notification_type, is_read, created_at)
             VALUES (?, ?, ?, 0, NOW())"
        );
        if ($notifStmt) {
            $notifStmt->bind_param("iss", $ownerId, $notifMessage, $notifType);
            $notifStmt->execute();
            $notifStmt->close();
        }
    }

    // ── 5. Send email to the owner ─────────────────────────────────────────────
    $emailSent = false;

    if (!empty($ownerEmail)) {
        $paymentLabel = match($paymentMethod) {
            'full'    => 'Full Payment (5% discount applied)',
            'emi'     => 'EMI Plan',
            'finance' => 'Equipment Financing',
            default   => ucfirst($paymentMethod ?: 'Not specified'),
        };

        $subject = "New Purchase Enquiry: {$eqName} — AgroHub";

        $body = "Dear {$ownerName},

You have received a new purchase enquiry for your second-hand equipment listed on AgroHub.

===================================================
EQUIPMENT: {$eqName}
===================================================

INTERESTED BUYER DETAILS
------------------------
Name          : {$buyerName}
Phone         : {$buyerPhone}
Email         : {$buyerEmail}
Delivery Addr : {$deliveryAddr}
Payment Method: {$paymentLabel}
Asking Price  : {$totalAmount}
" . ($notes ? "Notes         : {$notes}" : "") . "

Please reach out to the buyer at your earliest convenience to finalise the sale.

You can also log in to your AgroHub Owner Dashboard to view and manage all enquiries:
" . SITE_URL . "/owner-dashboard.html

Thank you for listing with AgroHub!

Best regards,
The AgroHub Team
";

        // Try custom SMTP (same function used in forgot-password.php)
        $emailSent = sendSmtpEmail($ownerEmail, $subject, $body);
    }

    echo json_encode([
        'success'     => true,
        'message'     => 'Purchase enquiry submitted successfully',
        'enquiry_id'  => $enquiryId,
        'email_sent'  => $emailSent,
        'owner_name'  => $ownerName,
        'owner_email' => $ownerEmail,
    ]);

    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}


// ── SMTP Sender (mirrors forgot-password.php) ──────────────────────────────────
function sendSmtpEmail(string $to, string $subject, string $body): bool {
    $host     = 'ssl://smtp.gmail.com';
    $port     = 465;
    $username = defined('SMTP_USER') ? SMTP_USER : '';
    $password = defined('SMTP_PASS') ? SMTP_PASS : '';
    $from     = defined('SMTP_FROM') ? SMTP_FROM : $username;
    $fromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'AgroHub';

    if (empty($username) || empty($password)) return false;

    $context = stream_context_create([
        'ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true,
        ],
    ]);

    try {
        $socket = stream_socket_client("$host:$port", $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) return false;

        $read = function() use ($socket) {
            $resp = '';
            while ($line = fgets($socket, 515)) {
                $resp .= $line;
                if (preg_match('/^\d{3}\s/', $line)) return $line;
            }
            return '';
        };

        $send = function(string $cmd) use ($socket) {
            fputs($socket, $cmd . "\r\n");
        };

        $read();                                    // greeting
        $send("EHLO localhost"); $read();
        $send("AUTH LOGIN");
        $r = $read(); if (strpos($r, '334') === false) throw new Exception("AUTH failed");
        $send(base64_encode($username));
        $r = $read(); if (strpos($r, '334') === false) throw new Exception("Username rejected");
        $send(base64_encode($password));
        $r = $read(); if (strpos($r, '235') === false) throw new Exception("Password rejected");
        $send("MAIL FROM: <$from>"); $read();
        $send("RCPT TO: <$to>"); $read();
        $send("DATA"); $read();

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/plain; charset=utf-8\r\n";
        $headers .= "From: $fromName <$from>\r\n";
        $headers .= "To: <$to>\r\n";
        $headers .= "Subject: $subject\r\n";
        $headers .= "X-Mailer: PHP-Custom-AgroHub\r\n";

        $send("$headers\r\n\r\n$body\r\n."); $read();
        $send("QUIT"); $read();
        fclose($socket);
        return true;

    } catch (Exception $e) {
        error_log('SMTP error (purchase_enquiry): ' . $e->getMessage());
        return false;
    }
}
?>
