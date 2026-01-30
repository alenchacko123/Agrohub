<?php
header('Content-Type: application/json');
require_once 'config.php';

// Get database connection
$conn = getDBConnection();

try {
    $owner_id = isset($_GET['owner_id']) ? intval($_GET['owner_id']) : 0;
    $farmer_id = isset($_GET['farmer_id']) ? intval($_GET['farmer_id']) : 0;
    $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
    
    if ($owner_id === 0 && $farmer_id === 0) {
        throw new Exception('Owner ID or Farmer ID is required');
    }

    // Build the SQL query
    $sql = "SELECT 
                b.id,
                b.equipment_id,
                b.farmer_id,
                b.farmer_name,
                b.start_date,
                b.end_date,
                b.total_amount,
                b.status,
                b.payment_status,
                b.paid_amount,
                b.paid_at,
                b.created_at,
                e.equipment_name,
                e.equipment_condition,
                DATEDIFF(b.end_date, b.start_date) as duration
            FROM bookings b
            INNER JOIN equipment e ON b.equipment_id = e.id
            WHERE ";
    
    // Add owner_id or farmer_id filter
    if ($owner_id > 0) {
        $sql .= "e.owner_id = ?";
    } else {
        $sql .= "b.farmer_id = ?";
    }
    
    // Add status filter if provided
    if (!empty($status_filter)) {
        $sql .= " AND b.status = ?";
    }
    
    $sql .= " ORDER BY 
                CASE 
                    WHEN b.status = 'pending' THEN 1
                    WHEN b.status = 'approved' THEN 2
                    WHEN b.status = 'rejected' THEN 3
                    ELSE 4
                END,
                b.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    
    $id_param = $owner_id > 0 ? $owner_id : $farmer_id;
    
    if (!empty($status_filter)) {
        $stmt->bind_param("is", $id_param, $status_filter);
    } else {
        $stmt->bind_param("i", $id_param);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'bookings' => $bookings,
        'count' => count($bookings)
    ]);
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
