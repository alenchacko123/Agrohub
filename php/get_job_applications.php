<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';

$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;

if (!$job_id) {
    echo json_encode(['success' => false, 'message' => 'Job ID is required']);
    exit;
}

try {
    // Check if table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'job_applications'");
    if ($table_check->num_rows == 0) {
        throw new Exception("Applications table not found");
    }

    $sql = "SELECT id, worker_id, worker_name, worker_email, status, applied_at FROM job_applications WHERE job_id = ? ORDER BY applied_at DESC";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
    
    $stmt->bind_param("i", $job_id);
    
    if (!$stmt->execute()) throw new Exception("Execute failed: " . $stmt->error);
    
    $result = $stmt->get_result();
    $applications = [];
    
    while ($row = $result->fetch_assoc()) {
         // Format date
         $date = new DateTime($row['applied_at']);
         $row['applied_date'] = $date->format('M d, Y');
         $applications[] = $row;
    }
    
    echo json_encode(['success' => true, 'applications' => $applications]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
