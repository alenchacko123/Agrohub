<?php
/**
 * Submit Rental Request
 * This endpoint handles rental requests from farmers
 */

// Prevent any output before JSON
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

// Clear any output that may have occurred
ob_clean();

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($input === null) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate required fields
    $required = ['equipment_id', 'equipment_name', 'start_date', 'end_date', 'num_days', 
                 'total_amount', 'delivery_address', 'farmer_id', 'farmer_name', 
                 'farmer_email', 'owner_id'];
    
    foreach ($required as $field) {
        if (!isset($input[$field]) || $input[$field] === '' || $input[$field] === null) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    $conn = getDBConnection();
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    // Insert rental request
    $stmt = $conn->prepare("
        INSERT INTO rental_requests 
        (equipment_id, equipment_name, farmer_id, farmer_name, farmer_email, owner_id,
         start_date, end_date, num_days, total_amount, delivery_address, 
         need_operator, need_insurance, special_requirements, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }
    
    // Set defaults for optional fields
    $needOperator = isset($input['need_operator']) ? (int)$input['need_operator'] : 0;
    $needInsurance = isset($input['need_insurance']) ? (int)$input['need_insurance'] : 0;
    $specialReq = isset($input['special_requirements']) ? $input['special_requirements'] : '';
    
    $stmt->bind_param(
        "isississdiiis",
        $input['equipment_id'],
        $input['equipment_name'],
        $input['farmer_id'],
        $input['farmer_name'],
        $input['farmer_email'],
        $input['owner_id'],
        $input['start_date'],
        $input['end_date'],
        $input['num_days'],
        $input['total_amount'],
        $input['delivery_address'],
        $needOperator,
        $needInsurance,
        $specialReq
    );
    
    if ($stmt->execute()) {
        $requestId = $stmt->insert_id;
        
        ob_clean(); // Clear any output
        echo json_encode([
            'success' => true,
            'message' => 'Rental request submitted successfully',
            'request_id' => $requestId
        ]);
    } else {
        throw new Exception('Failed to execute statement: ' . $stmt->error);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    ob_clean(); // Clear any output
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

ob_end_flush();
?>
