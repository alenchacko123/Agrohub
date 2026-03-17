<?php
// save_signature.php
// Saves the digital signature for a rental agreement

// Start output buffering to catch any unwanted output/warnings
ob_start();

// Disable error display, log only
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once 'config.php';

$response = ['success' => false, 'error' => 'Unknown error'];

try {
    $conn = getDBConnection();
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // 1. Robust Schema Migration (Check & Add Columns One by One)
    // This runs BEFORE any insert attempt to ensure table is ready.
    $required_columns = [
        'signature_type' => "ENUM('text', 'image') NOT NULL DEFAULT 'text'",
        'signature_data' => "LONGTEXT",
        'ip_address' => "VARCHAR(45)",
        'signed_at' => "DATETIME DEFAULT CURRENT_TIMESTAMP",
        'status' => "VARCHAR(20) DEFAULT 'signed'"
    ];

    foreach ($required_columns as $col => $def) {
        // Check if column exists
        $check = $conn->query("SHOW COLUMNS FROM agreements LIKE '$col'");
        if ($check && $check->num_rows == 0) {
            // Column missing, add it using standard SQL (no IF NOT EXISTS to support older MySQL)
            if (!$conn->query("ALTER TABLE agreements ADD COLUMN $col $def")) {
                // Ignore 'Duplicate column' error (1060) just in case of race condition
                if ($conn->errno != 1060) {
                     // Log but don't strictly fail yet, maybe another query fixed it? 
                     // But usually this is fatal.
                     file_put_contents('debug_log.txt', date('[Y-m-d H:i:s] ') . "Migration failed for $col: " . $conn->error . "\n", FILE_APPEND);
                }
            }
        }
    }

    // 2. Get input
    $rawInput = file_get_contents('php://input');
    if (!$rawInput) throw new Exception("No input data");
    
    $json = json_decode($rawInput, true);
    if (!$json) throw new Exception("Invalid JSON");

    // Extract vars
    $rental_request_id = isset($json['rental_request_id']) ? intval(str_replace('REQ-', '', $json['rental_request_id'])) : 0;
    $farmer_id = isset($json['farmer_id']) ? intval($json['farmer_id']) : 0;
    $signature_type_raw = $json['signature_type'] ?? 'text';
    // Map UI signature types to DB ENUM types ('text', 'image')
    $signature_type = ($signature_type_raw === 'draw' || $signature_type_raw === 'image') ? 'image' : 'text';
    
    $signature_data = $json['signature_data'] ?? '';
    $ip_address = $_SERVER['REMOTE_ADDR'];

    if (!$rental_request_id || !$signature_data) {
        throw new Exception("Missing required fields");
    }

    // Status Check & fetch owner_id for notification
    $owner_id = null;
    $equipment_name = '';
    $checkSql = "SELECT rr.status, rr.owner_id, e.equipment_name 
                 FROM rental_requests rr 
                 LEFT JOIN equipment e ON rr.equipment_id = e.id 
                 WHERE rr.id = ?";
    $stmt = $conn->prepare($checkSql);
    if ($stmt) {
        $stmt->bind_param("i", $rental_request_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            if (in_array($row['status'], ['paid', 'active', 'cancelled', 'completed'])) {
                throw new Exception("Agreement locked (status: " . $row['status'] . ")");
            }
            $owner_id = $row['owner_id'];
            $equipment_name = $row['equipment_name'] ?? 'equipment';
        }
        $stmt->close();
    }

    // If farmer_id not provided in payload, try to get it from rental_requests
    if (!$farmer_id) {
        $farmerSql = "SELECT farmer_id FROM rental_requests WHERE id = ?";
        $stmt = $conn->prepare($farmerSql);
        if ($stmt) {
            $stmt->bind_param("i", $rental_request_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows > 0) {
                $row = $res->fetch_assoc();
                $farmer_id = $row['farmer_id'];
            }
            $stmt->close();
        }
    }

    // Fallback to 1 if still not found (legacy behaviour)
    if (!$farmer_id) $farmer_id = 1;

    // Prepare INSERT query - now includes status
    $insertSql = "INSERT INTO agreements (rental_request_id, farmer_id, signature_type, signature_data, ip_address, signed_at, status) 
                  VALUES (?, ?, ?, ?, ?, NOW(), 'signature_captured')
                  ON DUPLICATE KEY UPDATE 
                  signature_type = VALUES(signature_type), 
                  signature_data = VALUES(signature_data),
                  signed_at = NOW(),
                  ip_address = VALUES(ip_address),
                  status = 'signature_captured'";

    $stmt = $conn->prepare($insertSql);
    if (!$stmt) {
         throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("iisss", $rental_request_id, $farmer_id, $signature_type, $signature_data, $ip_address);
    if (!$stmt->execute()) {
         throw new Exception("Execute failed: " . $stmt->error);
    }
    $stmt->close();

    // Update rental_requests table to mark as farmer_signed (only if column exists)
    // Check if agreement_status column exists first
    /* Premature update removed - now handled in process_rental_completion.php after payment
    $checkCol = $conn->query("SHOW COLUMNS FROM rental_requests LIKE 'agreement_status'");
    if ($checkCol && $checkCol->num_rows > 0) {
        $updateRequestSql = "UPDATE rental_requests SET agreement_status = 'farmer_signed' WHERE id = ?";
        ...
    }
    */

    // 5. Send notification to the owner
    $response['notification_sent'] = false;
    if ($owner_id) {
        $agreementIdForNotif = "AGR-" . $rental_request_id;
        $notifMessage = "A farmer has signed and paid for the rental agreement for '{$equipment_name}'. The agreement is waiting for your signature.";
        $actionUrl = "agreements.html?id=" . $agreementIdForNotif;
        
        $notifStmt = $conn->prepare(
            "INSERT INTO notifications (user_id, message, related_agreement_id, type, action_url, is_read) 
             VALUES (?, ?, ?, 'agreement_pending_owner_signature', ?, FALSE)"
        );
        if ($notifStmt) {
            $notifStmt->bind_param("isss", $owner_id, $notifMessage, $agreementIdForNotif, $actionUrl);
            if ($notifStmt->execute()) {
                $response['notification_sent'] = true;
            } else {
                error_log("Failed to create owner notification: " . $notifStmt->error);
            }
            $notifStmt->close();
        }
    }

    $response['success'] = true;
    $response['message'] = 'Signature saved. Agreement status updated to farmer_signed';

} catch (Exception $e) {
    if (isset($conn) && $conn->error) $response['db_error'] = $conn->error;
    $response['error'] = $e->getMessage();
    file_put_contents('debug_log.txt', date('[Y-m-d H:i:s] ') . $e->getMessage() . "\n", FILE_APPEND);
}

// Clean buffer and output JSON
// Ensure we don't clear the buffer if it's empty to avoid issues, but ob_end_clean is safe.
if (ob_get_length()) ob_end_clean();
echo json_encode($response);
?>
