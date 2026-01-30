<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config.php';

try {
    $conn = getDBConnection();
    
    // Get payment data from request
    $input = json_decode(file_get_contents('php://input'), true);
    $booking_id = isset($input['booking_id']) ? intval($input['booking_id']) : 0;
    $amount = isset($input['amount']) ? floatval($input['amount']) : 0;
    
    if ($booking_id === 0) {
        throw new Exception('Booking ID is required');
    }
    
    if ($amount === 0) {
        throw new Exception('Payment amount is required');
    }
    
    // Update payment status in bookings table
    $sql = "UPDATE bookings 
            SET payment_status = 'paid', 
                paid_amount = ?,
                paid_at = NOW() 
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("di", $amount, $booking_id);
    
    if ($stmt->execute()) {
        // Get updated booking details
        $selectSql = "SELECT payment_status, paid_amount, paid_at FROM bookings WHERE id = ?";
        $selectStmt = $conn->prepare($selectSql);
        $selectStmt->bind_param("i", $booking_id);
        $selectStmt->execute();
        $result = $selectStmt->get_result();
        $booking = $result->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment recorded successfully',
            'payment_data' => $booking
        ]);
        
        $selectStmt->close();
    } else {
        throw new Exception('Failed to update payment status');
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
