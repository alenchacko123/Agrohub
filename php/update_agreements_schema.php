<?php
// update_agreements_schema.php
// Adds owner signature columns and status fields to agreements table

header('Content-Type: application/json');
require_once 'config.php';

$response = ['success' => false, 'messages' => []];

try {
    $conn = getDBConnection();
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Define columns to add
    $columns_to_add = [
        'owner_signature_data' => "LONGTEXT NULL COMMENT 'Owner digital signature'",
        'owner_signature_type' => "ENUM('text', 'image') DEFAULT 'text' COMMENT 'Type of owner signature'",
        'owner_signed_at' => "DATETIME NULL COMMENT 'When owner signed'",
        'owner_ip_address' => "VARCHAR(45) NULL COMMENT 'IP address of owner when signing'",
        'status' => "VARCHAR(50) DEFAULT 'pending' COMMENT 'Agreement status: pending, farmer_signed, fully_signed'"
    ];

    foreach ($columns_to_add as $column => $definition) {
        // Check if column exists
        $check_sql = "SHOW COLUMNS FROM agreements LIKE ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("s", $column);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            // Column doesn't exist, add it
            $alter_sql = "ALTER TABLE agreements ADD COLUMN $column $definition";
            if ($conn->query($alter_sql)) {
                $response['messages'][] = "Added column: $column";
            } else {
                // Check if error is "duplicate column" (which is OK)
                if ($conn->errno != 1060) {
                    throw new Exception("Error adding $column: " . $conn->error);
                }
            }
        } else {
            $response['messages'][] = "Column already exists: $column";
        }
        $stmt->close();
    }

    // Update bookings table if needed
    $booking_columns = [
        'payment_status' => "VARCHAR(20) DEFAULT 'pending'",
        'rental_status' => "VARCHAR(20) DEFAULT 'pending'",
        'agreement_status' => "VARCHAR(50) DEFAULT 'pending'"
    ];

    foreach ($booking_columns as $column => $definition) {
        $check_sql = "SHOW COLUMNS FROM bookings LIKE ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("s", $column);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            $alter_sql = "ALTER TABLE bookings ADD COLUMN $column $definition";
            if ($conn->query($alter_sql)) {
                $response['messages'][] = "Added to bookings: $column";
            } else {
                if ($conn->errno != 1060) {
                    $response['messages'][] = "Note: Could not add $column to bookings (may not exist): " . $conn->error;
                }
            }
        }
        $stmt->close();
    }

    $response['success'] = true;
    $response['message'] = 'Schema updated successfully';

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

if (isset($conn)) {
    $conn->close();
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>
