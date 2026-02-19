<?php
// check_bookings_schema.php
// Quick script to check what columns exist in bookings table and show recent bookings

header('Content-Type: application/json');
require_once 'config.php';

$response = ['success' => false];

try {
    $conn = getDBConnection();
    
    // 1. Check what columns exist in bookings table
    $columnsResult = $conn->query("SHOW COLUMNS FROM bookings");
    $columns = [];
    while ($row = $columnsResult->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    $response['columns'] = $columns;
    
    // 2. Get recent bookings with all available data
    $bookingsResult = $conn->query("SELECT * FROM bookings ORDER BY created_at DESC LIMIT 3");
    $bookings = [];
    while ($row = $bookingsResult->fetch_assoc()) {
        $bookings[] = $row;
    }
    $response['recent_bookings'] = $bookings;
    
    // 3. Check rental_requests table
    $requestsResult = $conn->query("SELECT id, status, equipment_id, farmer_id, total_amount, created_at FROM rental_requests WHERE status = 'paid' ORDER BY created_at DESC LIMIT 3");
    $requests = [];
    while ($row = $requestsResult->fetch_assoc()) {
        $requests[] = $row;
    }
    $response['paid_requests'] = $requests;
    
    $response['success'] = true;
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>
