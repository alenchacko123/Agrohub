<?php
// Get All Rentals (Bookings + Requests)
require_once dirname(__DIR__) . '/config.php';

header('Content-Type: application/json');

try {
    $conn = getDBConnection();
    
    // 1. Bookings (Active + Completed)
    $sqlBookings = "SELECT b.id, b.equipment_name, b.farmer_name, b.start_date, b.end_date, b.total_amount, b.status 
                    FROM bookings b";
    
    // 2. Rental Requests (Pending)
    $sqlRequests = "SELECT r.id, r.equipment_name, r.farmer_name, r.start_date, r.end_date, r.total_amount, r.status 
                    FROM rental_requests r";
                    
    $bookings = $conn->query($sqlBookings)->fetch_all(MYSQLI_ASSOC);
    $requests = $conn->query($sqlRequests)->fetch_all(MYSQLI_ASSOC);
    
    // Map status for unified view
    foreach ($bookings as &$booking) {
        $booking['type'] = 'booking';
        $today = date('Y-m-d');
        if ($booking['end_date'] < $today) {
            $booking['status_label'] = 'Completed';
            $booking['status_color'] = '#10b981'; // Green
        } else {
            $booking['status_label'] = 'Active';
            $booking['status_color'] = '#3b82f6'; // Blue
        }
    }
    
    foreach ($requests as &$request) {
        $request['type'] = 'request';
        if ($request['status'] == 'pending' || $request['status'] == 'pending_payment') {
            $request['status_label'] = 'Pending';
            $request['status_color'] = '#f59e0b'; // Amber
        } elseif ($request['status'] == 'rejected') {
            $request['status_label'] = 'Rejected';
            $request['status_color'] = '#ef4444'; // Red
        } else {
            $request['status_label'] = ucfirst($request['status']);
            $request['status_color'] = '#6b7280'; // Gray
        }
    }
    
    echo json_encode([
        'success' => true,
        'rentals' => array_merge($bookings, $requests)
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
