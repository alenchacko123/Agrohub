<?php
/**
 * Get Rental Requests
 * Fetches rental requests for owners and farmers with user names
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';

try {
    $conn = getDBConnection();
    
    // Get user type and ID from query parameters
    $userType = isset($_GET['user_type']) ? sanitize($_GET['user_type']) : '';
    $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    $status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
    
    if (empty($userType) || empty($userId)) {
        throw new Exception('User type and ID are required');
    }
    
    // Check if agreements table exists
    $checkAgreements = $conn->query("SHOW TABLES LIKE 'agreements'");
    $hasAgreements = ($checkAgreements && $checkAgreements->num_rows > 0);

    // Build query based on user type
    $sql = "SELECT rr.*, 
                   f.name as farmer_name, 
                   o.name as owner_name";
    
    if ($hasAgreements) {
        $sql .= ", a.signature_data, a.signature_type, a.signed_at,
                   a.owner_signature_data, a.owner_signature_type, a.owner_signed_at,
                   a.status as agreement_full_status";
    }

    $sql .= " FROM rental_requests rr
            LEFT JOIN users f ON rr.farmer_id = f.id
            LEFT JOIN users o ON rr.owner_id = o.id";
    
    if ($hasAgreements) {
        $sql .= " LEFT JOIN agreements a ON a.rental_request_id = rr.id";
    }

    $sql .= " WHERE ";

    if ($userType === 'owner') {
        $sql .= "rr.owner_id = ?";
        if ($status) {
            $sql .= " AND rr.status = ?";
        }
    } elseif ($userType === 'farmer') {
        $sql .= "rr.farmer_id = ? AND (rr.is_dismissed = 0 OR rr.is_dismissed IS NULL)";
        if ($status) {
            $sql .= " AND rr.status = ?";
        }
    } else {
        throw new Exception('Invalid user type');
    }
    
    $sql .= " ORDER BY rr.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    if ($status) {
        $stmt->bind_param("is", $userId, $status);
    } else {
        $stmt->bind_param("i", $userId);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $requests = [];
    while ($row = $result->fetch_assoc()) {
        // Ensure numeric types
        $row['total_amount'] = floatval($row['total_amount']);
        $row['id'] = intval($row['id']);
        $row['farmer_id'] = intval($row['farmer_id']);
        $row['owner_id'] = intval($row['owner_id']);
        $requests[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'requests' => $requests,
        'count' => count($requests)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
