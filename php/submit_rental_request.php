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
    
    // 0. Cleanup Expired 'Initiated' Requests (Timeout Logic)
    // Cancel requests that have been 'initiated' for more than 10 minutes without payment
    $cleanup = $conn->query("UPDATE rental_requests SET status = 'cancelled' WHERE status = 'initiated' AND created_at < (NOW() - INTERVAL 10 MINUTE)");
    
    // 1. Check Date Overlaps in rental_requests
    // We check for any request that is NOT cancelled or rejected or expired
    // We treat 'initiated' and 'pending_payment' as locked slots.
    $checkOverlap = $conn->prepare("
        SELECT id FROM rental_requests 
        WHERE equipment_id = ? 
        AND status NOT IN ('cancelled', 'rejected', 'expired') 
        AND (
            (start_date <= ? AND end_date >= ?)
        )
    ");
    
    if (!$checkOverlap) {
        throw new Exception("Database error checking overlaps: " . $conn->error);
    }
    
    // Logic: If (RequestStart <= NewEnd) AND (RequestEnd >= NewStart) -> Overlap
    $checkOverlap->bind_param("iss", $input['equipment_id'], $input['end_date'], $input['start_date']);
    $checkOverlap->execute();
    $overlapResult = $checkOverlap->get_result();
    
    if ($overlapResult->num_rows > 0) {
        throw new Exception("Selected dates are not available (overlap with existing request)");
    }
    $checkOverlap->close();

    // 2. Check Overlaps in bookings table (confirmed bookings)
    // Assuming bookings table exists and is used for active rentals
    $checkBooking = $conn->prepare("
        SELECT id FROM bookings 
        WHERE equipment_id = ? 
        AND status IN ('active', 'confirmed', 'paid') 
        AND (
            (start_date <= ? AND end_date >= ?)
        )
    ");
    
    if ($checkBooking) {
        $checkBooking->bind_param("iss", $input['equipment_id'], $input['end_date'], $input['start_date']);
        $checkBooking->execute();
        $bookingResult = $checkBooking->get_result();
        
        if ($bookingResult->num_rows > 0) {
            throw new Exception("Selected dates are not available (already booked)");
        }
        $checkBooking->close();
    }

    // Insert rental request with status 'initiated'
    $stmt = $conn->prepare("
        INSERT INTO rental_requests 
        (equipment_id, equipment_name, farmer_id, farmer_name, farmer_email, owner_id,
         start_date, end_date, num_days, total_amount, delivery_address, 
         need_operator, need_insurance, special_requirements, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'initiated')
    ");
    
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }
    
    // Set defaults for optional fields
    $needOperator = isset($input['need_operator']) ? (int)$input['need_operator'] : 0;
    $needInsurance = isset($input['need_insurance']) ? (int)$input['need_insurance'] : 0;
    $specialReq = isset($input['special_requirements']) ? $input['special_requirements'] : '';
    
    $stmt->bind_param(
        "isississidsiis",
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
        
        // Success! Return the ID so frontend can redirect to agreements page
        ob_clean(); // Clear any output
        echo json_encode([
            'success' => true,
            'message' => 'Rental request initiated successfully',
            'request_id' => $requestId,
            'status' => 'initiated'
        ]);
    } else {
        throw new Exception('Failed to execute statement: ' . $stmt->error);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Throwable $e) {
    ob_clean(); // Clear any output
    http_response_code(500); // Set error code
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

ob_end_flush();
?>
