<?php
/**
 * Mark Notification as Read
 * Updates notification read status
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

try {
    $conn = getDBConnection();
    
    // Get input
    $json = json_decode(file_get_contents('php://input'), true);
    $notificationId = isset($json['notification_id']) ? intval($json['notification_id']) : 0;
    $userId = isset($json['user_id']) ? intval($json['user_id']) : 0;
    
    if (empty($notificationId) || empty($userId)) {
        throw new Exception('Notification ID and User ID are required');
    }
    
    // Update notification (with user verification for security)
    $sql = "UPDATE notifications SET is_read = TRUE, read_at = NOW() WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param("ii", $notificationId, $userId);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Notification marked as read'
            ]);
        } else {
            throw new Exception('Notification not found or already read');
        }
    } else {
        throw new Exception('Failed to update notification: ' . $stmt->error);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
