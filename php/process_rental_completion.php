<?php
// Process Rental Payment Completion
// Triggered after successful payment
// 1. Check if request exists
// 2. Update Request Status -> "Paid" / "Active"
// 3. Mark Equipment as "Rented" (implied by active booking)
// 4. Create Booking Entry (if not using request as booking)

header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$response = ['success' => false, 'error' => 'Unknown error'];

try {
    $conn = getDBConnection();
    
    $json = json_decode(file_get_contents('php://input'), true);
    
    // Debug Logging
    file_put_contents('debug_payment.txt', date('[Y-m-d H:i:s] ') . "Input: " . print_r($json, true) . "\n", FILE_APPEND);
    
    $request_id = isset($json['request_id']) ? $json['request_id'] : null;
    
    // Handle different payment response keys (Razorpay uses transaction_id)
    if (isset($json['payment_response'])) {
        $payment_gateway_response = $json['payment_response'];
    } elseif (isset($json['transaction_id'])) {
        $payment_gateway_response = $json['transaction_id'];
    } else {
        $payment_gateway_response = 'Simulated Payment';
    }
    
    if (!$request_id) {
        throw new Exception("Request ID is required. Received payload: " . json_encode($json));
    }
    
    // Clean ID
    $clean_id = str_replace('REQ-', '', $request_id);
    
    // Begin Transaction
    $conn->begin_transaction();
    
    // 1. Fetch Request Details
    $sql = "SELECT * FROM rental_requests WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $clean_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) throw new Exception("Rental request not found.");
    $request = $result->fetch_assoc();
    $stmt->close();
    
    // 2. Check Availability One Last Time (Race Condition Prevention)
    $checkSql = "SELECT id FROM bookings 
                 WHERE equipment_id = ? 
                 AND status IN ('confirmed', 'active') 
                 AND (start_date <= ? AND end_date >= ?)";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("iss", $request['equipment_id'], $request['end_date'], $request['start_date']);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        throw new Exception("Equipment has just been rented by another user. Payment cancelled.");
    }
    $checkStmt->close();
    
    // 2.5 Ensure request_id column exists in bookings and rental_requests has agreement_status
    // This ensures we can link bookings to agreements later
    $checkReqCol = $conn->query("SHOW COLUMNS FROM bookings LIKE 'request_id'");
    if ($checkReqCol && $checkReqCol->num_rows == 0) {
        $conn->query("ALTER TABLE bookings ADD COLUMN request_id INT(11) DEFAULT NULL AFTER id");
    }
    
    // Ensure agreement_status exists in bookings too (optional but good for caching status)
    $checkAgStatus = $conn->query("SHOW COLUMNS FROM bookings LIKE 'agreement_status'");
    if ($checkAgStatus && $checkAgStatus->num_rows == 0) {
         $conn->query("ALTER TABLE bookings ADD COLUMN agreement_status VARCHAR(50) DEFAULT 'pending'");
    }
    
    // 3. Create Booking Record with proper statuses (if columns exist)
    // Check which columns exist in bookings table
    $hasPaymentStatus = $conn->query("SHOW COLUMNS FROM bookings LIKE 'payment_status'")->num_rows > 0;
    $hasRentalStatus = $conn->query("SHOW COLUMNS FROM bookings LIKE 'rental_status'")->num_rows > 0;
    $hasAgreementStatus = $conn->query("SHOW COLUMNS FROM bookings LIKE 'agreement_status'")->num_rows > 0;
    $hasPaidAmount = $conn->query("SHOW COLUMNS FROM bookings LIKE 'paid_amount'")->num_rows > 0;
    $hasPaidAt = $conn->query("SHOW COLUMNS FROM bookings LIKE 'paid_at'")->num_rows > 0;

    $hasTransactionId = $conn->query("SHOW COLUMNS FROM bookings LIKE 'transaction_id'")->num_rows > 0;
    $hasRequestId = $conn->query("SHOW COLUMNS FROM bookings LIKE 'request_id'")->num_rows > 0;
    
    // Build SQL based on available columns
    $columns = ['farmer_id', 'equipment_id', 'start_date', 'end_date', 'total_amount', 'status', 'created_at'];
    $values = ['?', '?', '?', '?', '?', "'active'", 'NOW()'];
    $bindTypes = 'iissd';
    $bindParams = [
        $request['farmer_id'],
        $request['equipment_id'],
        $request['start_date'],
        $request['end_date'],
        $request['total_amount']
    ];
    
    if ($hasPaymentStatus) {
        $columns[] = 'payment_status';
        $values[] = "'completed'";
    }
    if ($hasRentalStatus) {
        $columns[] = 'rental_status';
        $values[] = "'active'";
    }
    if ($hasAgreementStatus) {
        $columns[] = 'agreement_status';
        $values[] = "'farmer_signed'";
    }
    if ($hasPaidAmount) {
        $columns[] = 'paid_amount';
        $values[] = '?';
        $bindTypes .= 'd';
        $bindParams[] = $request['total_amount'];
    }
    if ($hasPaidAt) {
        $columns[] = 'paid_at';
        $values[] = 'NOW()';
    }
    if ($hasTransactionId) {
        $columns[] = 'transaction_id';
        $values[] = '?';
        $bindTypes .= 's';
        $bindParams[] = $json['transaction_id'] ?? $payment_gateway_response;
    }
    if ($hasRequestId) {
        $columns[] = 'request_id';
        $values[] = '?';
        $bindTypes .= 'i';
        $bindParams[] = $clean_id;
    }
    
    $bookingSql = "INSERT INTO bookings (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ")";
    $bookingStmt = $conn->prepare($bookingSql);
    if (!$bookingStmt) throw new Exception("Booking preparation failed: " . $conn->error);
    
    // Bind parameters dynamically
    $bookingStmt->bind_param($bindTypes, ...$bindParams);
    
    if (!$bookingStmt->execute()) throw new Exception("Failed to confirm booking: " . $bookingStmt->error);
    $booking_id = $conn->insert_id;
    $bookingStmt->close();
    
    // 4. Update Rental Request Status to 'paid' (and agreement_status if column exists)
    // First try with agreement_status, if it fails, update without it
    $checkCol = $conn->query("SHOW COLUMNS FROM rental_requests LIKE 'agreement_status'");
    if ($checkCol && $checkCol->num_rows > 0) {
        $updateSql = "UPDATE rental_requests SET status = 'paid', agreement_status = 'farmer_signed' WHERE id = ?";
    } else {
        $updateSql = "UPDATE rental_requests SET status = 'paid' WHERE id = ?";
    }
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("i", $clean_id);
    $updateStmt->execute();
    $updateStmt->close();

    // 5. Update Equipment Status to 'Rented'
    // This blocks the equipment from being rented by others instantly
    $eqSql = "UPDATE equipment SET availability_status = 'rented' WHERE id = ?";
    $eqStmt = $conn->prepare($eqSql);
    $eqStmt->bind_param("i", $request['equipment_id']);
    $eqStmt->execute();
    $eqStmt->close();
    
    // 6. Record Payment (Log)
    // Make this fault-tolerant so payment logging failure doesn't rollback a valid booking
    try {
        // Check columns
        $hasReqId = $conn->query("SHOW COLUMNS FROM payments LIKE 'request_id'")->num_rows > 0;
        $hasBookId = $conn->query("SHOW COLUMNS FROM payments LIKE 'booking_id'")->num_rows > 0;
        $hasUserId = $conn->query("SHOW COLUMNS FROM payments LIKE 'user_id'")->num_rows > 0;
        $hasTxnId = $conn->query("SHOW COLUMNS FROM payments LIKE 'transaction_id'")->num_rows > 0;
        $hasMethod = $conn->query("SHOW COLUMNS FROM payments LIKE 'payment_method'")->num_rows > 0;
        
        $pCols = ['amount', 'status', 'created_at'];
        $pVals = ['?', "'success'", 'NOW()'];
        $pTypes = 'd';
        $pParams = [$request['total_amount']];
        
        if ($hasReqId) {
            $pCols[] = 'request_id';
            $pVals[] = '?';
            $pTypes .= 'i';
            $pParams[] = $clean_id;
        }
        if ($hasBookId) {
            $pCols[] = 'booking_id';
            $pVals[] = '?';
            $pTypes .= 'i';
            $pParams[] = $booking_id;
        }
        if ($hasUserId) {
            $pCols[] = 'user_id';
            $pVals[] = '?';
            $pTypes .= 'i';
            $pParams[] = $request['farmer_id'];
        }
        if ($hasTxnId) {
            $pCols[] = 'transaction_id';
            $pVals[] = '?';
            $pTypes .= 's';
            $pParams[] = $payment_gateway_response;
        }
        if ($hasMethod) {
            $pCols[] = 'payment_method';
            $pVals[] = '?';
            $pTypes .= 's';
            $pParams[] = $json['payment_method'] ?? 'razorpay';
        }
        
        $paymentSql = "INSERT INTO payments (" . implode(', ', $pCols) . ") VALUES (" . implode(', ', $pVals) . ")";
        $payStmt = $conn->prepare($paymentSql);
        if ($payStmt) {
            $payStmt->bind_param($pTypes, ...$pParams);
            $payStmt->execute();
            $payStmt->close();
        }

    } catch (Exception $e) {
        // Log error but DO NOT fail the transaction
        file_put_contents('payment_error_log.txt', date('[Y-m-d H:i:s] ') . "Payment Log Error: " . $e->getMessage() . "\n", FILE_APPEND);
    }

    // 7. Notify Owner to Sign Agreement
    try {
        // Get Owner ID
        $ownSql = "SELECT owner_id, name as equipment_name FROM equipment WHERE id = ?";
        $ownStmt = $conn->prepare($ownSql);
        $ownStmt->bind_param("i", $request['equipment_id']);
        $ownStmt->execute();
        $ownRes = $ownStmt->get_result();
        
        if ($ownRes->num_rows > 0) {
            $equipmentData = $ownRes->fetch_assoc();
            $owner_id = $equipmentData['owner_id'];
            $eq_name = $equipmentData['equipment_name'];
            
            // Insert Notification
            $notifMsg = "Action Required: Please sign the rental agreement for " . $eq_name . " (Booking #" . $booking_id . ")";
            $notifAction = "agreements.html?id=AGR-" . $booking_id;
            
            // Check if columns exist (safe insert)
            $nCols = ['user_id', 'message', 'type', 'created_at', 'is_read'];
            $nVals = ['?', '?', "'action_required'", 'NOW()', '0'];
            $nTypes = 'is';
            $nParams = [$owner_id, $notifMsg];
            
            // Add related_id/action_url if they exist (we know they do from schema check, but allow fallback)
            $checkRel = $conn->query("SHOW COLUMNS FROM notifications LIKE 'related_id'");
            if ($checkRel && $checkRel->num_rows > 0) {
                $nCols[] = 'related_id';
                $nVals[] = '?';
                $nTypes .= 'i';
                $nParams[] = $booking_id;
            }
            $checkUrl = $conn->query("SHOW COLUMNS FROM notifications LIKE 'action_url'");
            if ($checkUrl && $checkUrl->num_rows > 0) {
                $nCols[] = 'action_url';
                $nVals[] = '?';
                $nTypes .= 's';
                $nParams[] = $notifAction;
            }
            
            $notifSql = "INSERT INTO notifications (" . implode(', ', $nCols) . ") VALUES (" . implode(', ', $nVals) . ")";
            $notifStmt = $conn->prepare($notifSql);
            if ($notifStmt) {
                $notifStmt->bind_param($nTypes, ...$nParams);
                $notifStmt->execute();
                $notifStmt->close();
            }
        }
        $ownStmt->close();
    } catch (Exception $e) {
         // Log notification error but don't fail booking
         file_put_contents('debug_log.txt', "Notification Error: " . $e->getMessage() . "\n", FILE_APPEND);
    }
    
    // Commit Transaction
    $conn->commit();
    
    $response = [
        'success' => true,
        'booking_id' => $booking_id,
        'message' => 'Payment successful and booking confirmed.'
    ];
    
} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
?>
