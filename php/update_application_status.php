<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once 'config.php';

$data = json_decode(file_get_contents("php://input"), true);

$application_id = isset($data['application_id']) ? (int)$data['application_id'] : 0;
$status = isset($data['status']) ? $conn->real_escape_string($data['status']) : '';

if (!$application_id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $sql = "UPDATE job_applications SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
    
    $stmt->bind_param("si", $status, $application_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Application status updated']);
    } else {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
