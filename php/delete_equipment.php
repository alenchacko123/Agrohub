<?php
header('Content-Type: application/json');
require_once 'config.php';

// Get database connection
$conn = getDBConnection();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    $equipment_id = $data['equipment_id'] ?? null;
    $owner_id = $data['owner_id'] ?? null; // For security, verify owner

    if (!$equipment_id || !$owner_id) {
        throw new Exception('Missing equipment ID or owner ID');
    }

    // Verify ownership before deleting
    $check_sql = "SELECT id FROM equipment WHERE id = ? AND owner_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $equipment_id, $owner_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Equipment not found or you do not have permission to delete it');
    }
    $check_stmt->close();

    // Proceed to delete
    $delete_sql = "DELETE FROM equipment WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $equipment_id);

    if ($delete_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Equipment deleted successfully']);
    } else {
        throw new Exception('Failed to delete equipment: ' . $conn->error);
    }
    
    $delete_stmt->close();
    $conn->close();

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
