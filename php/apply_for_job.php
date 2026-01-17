<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

$job_id = isset($data['job_id']) ? (int)$data['job_id'] : 0;
$worker_id = isset($data['worker_id']) ? (int)$data['worker_id'] : 0;
$worker_name = isset($data['worker_name']) ? $conn->real_escape_string($data['worker_name']) : '';
$worker_email = isset($data['worker_email']) ? $conn->real_escape_string($data['worker_email']) : '';

if (!$job_id || !$worker_id) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Check if already applied
$check_sql = "SELECT id FROM job_applications WHERE job_id = ? AND worker_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $job_id, $worker_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'You have already applied for this job']);
    exit;
}

// Insert application
$sql = "INSERT INTO job_applications (job_id, worker_id, worker_name, worker_email, status, applied_at) VALUES (?, ?, ?, ?, 'pending', NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiss", $job_id, $worker_id, $worker_name, $worker_email);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Application submitted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error submitting application: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
