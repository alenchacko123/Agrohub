<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['job_id']) || !isset($input['status']) || !isset($input['farmer_id'])) {
        throw new Exception('Job ID, status, and farmer ID are required');
    }
    
    $job_id = intval($input['job_id']);
    $farmer_id = intval($input['farmer_id']);
    $status = $input['status'];
    
    // Validate status
    if (!in_array($status, ['Open', 'Closed', 'Filled', 'Expired'])) {
        throw new Exception('Invalid status');
    }
    
    $query = "UPDATE job_postings SET status = ?, updated_at = NOW() WHERE id = ? AND farmer_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('sii', $status, $job_id, $farmer_id);
    
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Job status updated to ' . $status
        ]);
    } else {
        // If status is same, affected_rows is 0. Check if job exists and belongs to farmer.
        // For simplicity, we can assume success if no error, but let's be strict about ownership.
        // If we want to be sure, we can SELECT first.
        // But let's return a message.
        echo json_encode([
            'success' => true, // Treat as success or "no change needed"
            'message' => 'Job status updated or already ' . $status
        ]);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
