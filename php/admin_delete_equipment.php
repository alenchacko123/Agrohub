<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    $game_input = json_decode(file_get_contents('php://input'), true);

    if (!isset($game_input['equipment_id'])) {
        throw new Exception('Equipment ID is required');
    }

    $equipmentId = intval($game_input['equipment_id']);
    
    // In a real app, verify ADMIN session here.
    // For now, assuming this endpoint is protected or trusted.
    
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("DELETE FROM equipment WHERE id = ?");
    $stmt->bind_param("i", $equipmentId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Delete failed: " . $conn->error);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} finally {
    if (isset($conn)) $conn->close();
}
?>
