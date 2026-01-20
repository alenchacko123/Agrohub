<?php
/**
 * Submit Booking (Equipment Rental)
 * Simplified endpoint that submits directly to bookings table
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($input === null) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate required fields
    $required = ['equipment_id', 'farmer_id', 'farmer_name', 'start_date', 'end_date', 'total_amount'];
    
    foreach ($required as $field) {
        if (!isset($input[$field]) || $input[$field] === '' || $input[$field] === null) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Get database connection
    $conn = getDBConnection();
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    // Insert booking
    $stmt = $conn->prepare("
        INSERT INTO bookings 
        (equipment_id, farmer_id, farmer_name, start_date, end_date, total_amount, status)
        VALUES (?, ?, ?, ?, ?, ?, 'pending')
    ");
    
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }
    
    $stmt->bind_param(
        "iisssd",
        $input['equipment_id'],
        $input['farmer_id'],
        $input['farmer_name'],
        $input['start_date'],
        $input['end_date'],
        $input['total_amount']
    );
    
    if ($stmt->execute()) {
        $bookingId = $stmt->insert_id;
        
        echo json_encode([
            'success' => true,
            'message' => 'Booking request submitted successfully!',
            'booking_id' => $bookingId
        ]);
    } else {
        throw new Exception('Failed to execute statement: ' . $stmt->error);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
