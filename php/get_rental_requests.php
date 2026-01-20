<?php
/**
 * Get Rental Requests
 * Fetches rental requests for owners and farmers
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
    
    // Build query based on user type
    if ($userType === 'owner') {
        $sql = "SELECT * FROM rental_requests WHERE owner_id = ?";
        if ($status) {
            $sql .= " AND status = ?";
        }
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $conn->prepare($sql);
        if ($status) {
            $stmt->bind_param("is", $userId, $status);
        } else {
            $stmt->bind_param("i", $userId);
        }
        
    } elseif ($userType === 'farmer') {
        $sql = "SELECT * FROM rental_requests WHERE farmer_id = ?";
        if ($status) {
            $sql .= " AND status = ?";
        }
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $conn->prepare($sql);
        if ($status) {
            $stmt->bind_param("is", $userId, $status);
        } else {
            $stmt->bind_param("i", $userId);
        }
    } else {
        throw new Exception('Invalid user type');
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $requests = [];
    while ($row = $result->fetch_assoc()) {
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
