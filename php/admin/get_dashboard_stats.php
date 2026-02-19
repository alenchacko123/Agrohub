<?php
// Get Admin Dashboard Statistics
require_once dirname(__DIR__) . '/config.php';

header('Content-Type: application/json');

try {
    $conn = getDBConnection();
    
    $stats = [
        'users' => ['total' => 0, 'farmers' => 0, 'owners' => 0, 'workers' => 0],
        'equipment' => ['total' => 0, 'active' => 0], 
        'rentals' => ['active' => 0, 'completed' => 0, 'pending' => 0],
        'jobs' => ['total' => 0],
        'payments' => ['total_revenue' => 0]
    ];
    
    // 1. Users
    $res = $conn->query("SELECT user_type, COUNT(*) as count FROM users GROUP BY user_type");
    while ($row = $res->fetch_assoc()) {
        $type = strtolower($row['user_type']);
        if (isset($stats['users'][$type])) {
            $stats['users'][$type] = (int)$row['count'];
        }
        $stats['users']['total'] += (int)$row['count'];
    }
    
    // 2. Equipment
    $res = $conn->query("SELECT COUNT(*) as total FROM equipment");
    $stats['equipment']['total'] = (int)$res->fetch_assoc()['total'];
    
    $res = $conn->query("SELECT COUNT(*) as active FROM equipment WHERE availability_status = 'available'");
    $stats['equipment']['active'] = (int)$res->fetch_assoc()['active'];
    
    // 3. Rentals (Bookings + Requests)
    // Active: Bookings with end_date >= TODAY
    // Completed: Bookings with end_date < TODAY
    // Pending: Rental Requests with status 'pending' or 'pending_payment'
    
    $today = date('Y-m-d');
    
    $res = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE end_date >= '$today'");
    $stats['rentals']['active'] = (int)$res->fetch_assoc()['count'];
    
    $res = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE end_date < '$today'");
    $stats['rentals']['completed'] = (int)$res->fetch_assoc()['count'];
    
    $res = $conn->query("SELECT COUNT(*) as count FROM rental_requests WHERE status IN ('pending', 'pending_payment')");
    $stats['rentals']['pending'] = (int)$res->fetch_assoc()['count'];
    
    // 4. Jobs
    if ($conn->query("SHOW TABLES LIKE 'job_postings'")->num_rows > 0) {
        $res = $conn->query("SELECT COUNT(*) as count FROM job_postings");
        $stats['jobs']['total'] = (int)$res->fetch_assoc()['count'];
    }
    
    // 5. Payments
    if ($conn->query("SHOW TABLES LIKE 'payments'")->num_rows > 0) {
        $res = $conn->query("SELECT SUM(amount) as total FROM payments WHERE status = 'success' OR status = 'captured'"); // Adjust based on payment status usage
        $row = $res->fetch_assoc();
        $stats['payments']['total_revenue'] = $row['total'] ? (float)$row['total'] : 0;
    }
    
    echo json_encode(['success' => true, 'stats' => $stats]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
