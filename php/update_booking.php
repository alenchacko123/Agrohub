<?php
header('Content-Type: application/json');
require_once 'config.php';

// Get database connection
$conn = getDBConnection();

try {
    // Get the JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!isset($data['booking_id']) || !isset($data['status'])) {
        throw new Exception('Booking ID and status are required');
    }
    
    $booking_id = intval($data['booking_id']);
    $status = $data['status'];
    
    // Validate status
    $allowed_statuses = ['approved', 'rejected', 'pending', 'completed'];
    if (!in_array($status, $allowed_statuses)) {
        throw new Exception('Invalid status value');
    }
    
    // Update the booking status
    $sql = "UPDATE bookings SET status = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $booking_id);
    
    if ($stmt->execute()) {
        // If approved, update equipment availability
        if ($status === 'approved') {
            $updateEquipment = "UPDATE equipment e
                               INNER JOIN bookings b ON e.id = b.equipment_id
                               SET e.availability_status = 'booked'
                               WHERE b.id = ?";
            $stmt2 = $conn->prepare($updateEquipment);
            $stmt2->bind_param("i", $booking_id);
            $stmt2->execute();
            $stmt2->close();
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Booking status updated successfully',
            'booking_id' => $booking_id,
            'new_status' => $status
        ]);
    } else {
        throw new Exception('Failed to update booking status');
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
