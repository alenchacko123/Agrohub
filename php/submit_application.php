<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config.php';

try {
    $conn = getDBConnection();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (!isset($input['job_id']) || !isset($input['applicant_id'])) {
        throw new Exception('Job ID and Applicant ID are required');
    }
    
    $job_id = intval($input['job_id']);
    $applicant_id = intval($input['applicant_id']);
    $message = isset($input['message']) ? trim($input['message']) : '';
    $experience = isset($input['experience']) ? trim($input['experience']) : '';
    
    // Check if already applied
    $check_query = "SELECT id FROM job_applications WHERE job_id = ? AND applicant_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param('ii', $job_id, $applicant_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        throw new Exception('You have already applied for this job');
    }
    
    // Insert new application
    $query = "INSERT INTO job_applications (job_id, applicant_id, message, experience, status, created_at) 
              VALUES (?, ?, ?, ?, 'pending', NOW())";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('iiss', $job_id, $applicant_id, $message, $experience);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Application submitted successfully!',
            'application_id' => $conn->insert_id
        ]);
    } else {
        throw new Exception('Failed to submit application');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
