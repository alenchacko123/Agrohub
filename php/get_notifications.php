<?php
/**
 * Get Notifications for User
 * Fetches notifications for the logged-in user
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';

try {
    $conn = getDBConnection();
    
    // Get user ID from query parameters
    $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
    
    if (empty($userId)) {
        throw new Exception('User ID is required');
    }
    
    // Build query
    $sql = "SELECT * FROM notifications WHERE user_id = ? AND (is_deleted = 0 OR is_deleted IS NULL)";
    if ($unreadOnly) {
        $sql .= " AND is_read = FALSE";
    }
    $sql .= " ORDER BY created_at DESC LIMIT 50";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    
    // Get unread count
    $countSql = "SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = FALSE";
    $countStmt = $conn->prepare($countSql);
    $countStmt->bind_param("i", $userId);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $countData = $countResult->fetch_assoc();
    $unreadCount = $countData['unread_count'];
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $unreadCount,
        'total_count' => count($notifications)
    ]);
    
    $stmt->close();
    $countStmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
