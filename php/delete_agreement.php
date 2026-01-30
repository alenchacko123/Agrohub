<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config.php';

try {
    $conn = getDBConnection();
    
    // Get booking ID from request
    $input = json_decode(file_get_contents('php://input'), true);
    $booking_id = isset($input['booking_id']) ? intval($input['booking_id']) : 0;
    
    if ($booking_id === 0) {
        throw new Exception('Booking ID is required');
    }
    
    // Delete the booking
    $sql = "DELETE FROM bookings WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $booking_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Agreement deleted successfully'
        ]);
    } else {
        throw new Exception('Failed to delete agreement');
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
