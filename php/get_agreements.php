<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

// Get user type and user ID
$user_type = isset($_GET['user_type']) ? $_GET['user_type'] : '';
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$status = isset($_GET['status']) ? $_GET['status'] : 'all';

if (empty($user_type) || $user_id === 0) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

try {
    // Base query - joins rental_requests with equipment and users
    $query = "SELECT 
        rr.id as request_id,
        rr.farmer_id,
        rr.equipment_id,
        rr.start_date,
        rr.end_date,
        rr.total_amount,
        rr.booking_status,
        rr.created_at,
        e.equipment_name,
        e.category,
        e.price_per_day,
        e.owner_id,
        fu.username as farmer_name,
        fu.email as farmer_email,
        fu.phone as farmer_phone,
        ou.username as owner_name,
        ou.email as owner_email,
        ou.phone as owner_phone,
        a.id as agreement_id,
        a.agreement_status,
        a.agreement_number,
        a.farmer_signed,
        a.owner_signed,
        a.signed_at,
        a.payment_status,
        a.insurance_included,
        a.generated_at
    FROM rental_requests rr
    LEFT JOIN equipment e ON rr.equipment_id = e.id
    LEFT JOIN users fu ON rr.farmer_id = fu.id
    LEFT JOIN users ou ON e.owner_id = ou.id
    LEFT JOIN agreements a ON rr.id = a.request_id
    WHERE ";
    
    // Filter by user type
    if ($user_type === 'farmer') {
        $query .= "rr.farmer_id = ? ";
    } else if ($user_type === 'owner') {
        $query .= "e.owner_id = ? ";
    }
    
    // Filter by agreement status if specified
    if ($status !== 'all') {
        $query .= "AND a.agreement_status = ? ";
    }
    
    // Only show approved bookings (which should have agreements)
    $query .= "AND rr.booking_status = 'approved' ";
    $query .= "ORDER BY rr.created_at DESC";
    
    $stmt = $conn->prepare($query);
    
    if ($status !== 'all') {
        $stmt->bind_param("is", $user_id, $status);
    } else {
        $stmt->bind_param("i", $user_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $agreements = [];
    while ($row = $result->fetch_assoc()) {
        $agreements[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'agreements' => $agreements,
        'count' => count($agreements)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>
