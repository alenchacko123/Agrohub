<?php
// Get All Payments
require_once dirname(__DIR__) . '/config.php';

header('Content-Type: application/json');

try {
    $conn = getDBConnection();
    
    // Check if table exists
    if ($conn->query("SHOW TABLES LIKE 'payments'")->num_rows == 0) {
        throw new Exception("Table 'payments' does not exist.");
    }
    
    // Select payments
    // Join with booking/request details if possible, or just raw
    $sql = "SELECT p.id, p.amount, p.payment_method, p.status, p.created_at, p.booking_id 
            FROM payments p 
            ORDER BY p.created_at DESC";
            
    $res = $conn->query($sql);
    $payments = [];
    while ($row = $res->fetch_assoc()) {
        $payments[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'payments' => $payments
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
