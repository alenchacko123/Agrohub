<?php
/**
 * Update Rental Request Status
 * Allows owners to accept/decline rental requests
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['request_id']) || empty($input['status'])) {
        throw new Exception('Request ID and status are required');
    }
    
    $requestId = intval($input['request_id']);
    $status = sanitize($input['status']);
    
    // Validate status
    $validStatuses = ['accepted', 'declined', 'completed', 'cancelled'];
    if (!in_array($status, $validStatuses)) {
        throw new Exception('Invalid status');
    }
    
    $conn = getDBConnection();
    
    // Update the request status
    $stmt = $conn->prepare("UPDATE rental_requests SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $status, $requestId);
    
    if ($stmt->execute()) {
        // If accepted, optionally update equipment availability
        if ($status === 'accepted') {
            $equipmentId = isset($input['equipment_id']) ? intval($input['equipment_id']) : 0;
            if ($equipmentId > 0) {
                $updateEquip = $conn->prepare("UPDATE equipment SET availability_status = 'booked' WHERE id = ?");
                $updateEquip->bind_param("i", $equipmentId);
                $updateEquip->execute();
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Request status updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update request status');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
