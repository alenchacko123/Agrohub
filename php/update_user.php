<?php
header('Content-Type: application/json');
require_once 'config.php';

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User ID is required']);
    exit;
}

$userId = intval($input['user_id']);
$updates = [];
$types = "";
$params = [];

// Check for status update
if (isset($input['status'])) {
    $updates[] = "status = ?";
    $types .= "s";
    $params[] = $input['status'];
}

// Check for role update
if (isset($input['role'])) {
    $updates[] = "user_type = ?";
    $types .= "s";
    $params[] = $input['role'];
}

// Check for password reset (optional, basic implementation)
if (isset($input['password'])) {
    $updates[] = "password = ?";
    $types .= "s";
    $params[] = password_hash($input['password'], PASSWORD_DEFAULT);
}

if (empty($updates)) {
    echo json_encode(['success' => false, 'error' => 'No updates provided']);
    exit;
}

try {
    $conn = getDBConnection();
    
    $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
    $types .= "i";
    $params[] = $userId;
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    } else {
        throw new Exception("Update failed: " . $conn->error);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} finally {
    if (isset($conn)) $conn->close();
}
?>
