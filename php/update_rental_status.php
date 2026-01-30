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
    
    // First, fetch the request details
    $reqStmt = $conn->prepare("SELECT * FROM rental_requests WHERE id = ?");
    $reqStmt->bind_param("i", $requestId);
    $reqStmt->execute();
    $requestResult = $reqStmt->get_result();
    
    if ($requestResult->num_rows === 0) {
        throw new Exception('Rental request not found');
    }
    
    $requestData = $requestResult->fetch_assoc();
    $reqStmt->close();
    
    // Update the request status
    $stmt = $conn->prepare("UPDATE rental_requests SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $status, $requestId);
    
    if ($stmt->execute()) {
        // If accepted, add to bookings table and update equipment availability
        if ($status === 'accepted') {
            $equipmentId = $requestData['equipment_id'];
            
            // 1. Update equipment availability
            if ($equipmentId > 0) {
                $updateEquip = $conn->prepare("UPDATE equipment SET availability_status = 'booked' WHERE id = ?");
                $updateEquip->bind_param("i", $equipmentId);
                $updateEquip->execute();
                $updateEquip->close();
            }

            // 2. Create a booking record
            // We set status to 'approved' for bookings to match get_bookings.php expectation
            $bookingStatus = 'approved'; 
            $paymentStatus = 'pending';
            $paidAmount = 0.00;
            
            $insertBooking = $conn->prepare("INSERT INTO bookings (
                equipment_id, 
                farmer_id, 
                farmer_name, 
                start_date, 
                end_date, 
                total_amount, 
                status, 
                payment_status, 
                paid_amount,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            
            $insertBooking->bind_param(
                "iisssdssd", 
                $requestData['equipment_id'],
                $requestData['farmer_id'],
                $requestData['farmer_name'],
                $requestData['start_date'],
                $requestData['end_date'],
                $requestData['total_amount'],
                $bookingStatus,
                $paymentStatus,
                $paidAmount
            );
            
            if (!$insertBooking->execute()) {
                // Log error but don't fail the whole request
                error_log("Failed to insert booking: " . $insertBooking->error);
            }
            $insertBooking->close();
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
