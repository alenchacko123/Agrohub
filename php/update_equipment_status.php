<?php
header('Content-Type: application/json');
require_once 'config.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['equipment_id']) || !isset($input['status'])) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

$equipmentId = intval($input['equipment_id']);
$status = $input['status']; // 'approved', 'rejected'

if (!in_array($status, ['approved', 'rejected', 'pending'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid status']);
    exit;
}

try {
    $conn = getDBConnection();
    
    // Check if column exists first (fallback if migration didn't run)
    $check = $conn->query("SHOW COLUMNS FROM equipment LIKE 'approval_status'");
    if ($check->num_rows == 0) {
        // Just return success if column doesn't exist, to avoid breaking UI? 
        // Or better, error out.
        // Actually, let's try to adapt. If 'approval_status' missing, maybe we can't do anything.
        // But assumed migration passed.
        throw new Exception("Database schema not updated (missing approval_status)");
    }

    $stmt = $conn->prepare("UPDATE equipment SET approval_status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $equipmentId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Equipment status updated']);
    } else {
        throw new Exception("Update failed: " . $conn->error);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} finally {
    if (isset($conn)) $conn->close();
}
?>
