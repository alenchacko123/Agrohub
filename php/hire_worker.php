<?php
header('Content-Type: application/json');
require_once 'config.php';

// Get Authorization Header or Token
$headers = getallheaders();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
$token = '';

if (empty($authHeader) && isset($_GET['token'])) {
    $token = sanitize($_GET['token']);
} elseif (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $token = $matches[1];
}

if (empty($token)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $conn = getDBConnection();
    
    // Validate Token and get User ID
    $stmt = $conn->prepare("SELECT u.id, u.user_type FROM user_sessions s JOIN users u ON s.user_id = u.id WHERE s.session_token = ? AND s.expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Invalid session');
    }
    
    $user = $result->fetch_assoc();
    $farmer_id = $user['id'];
    
    // Get parameters
    $input = json_decode(file_get_contents('php://input'), true);
    $application_id = isset($input['application_id']) ? intval($input['application_id']) : 0;
    
    if ($application_id <= 0) {
        throw new Exception('Invalid Application ID');
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    // Verify application and ownership
    $check_sql = "SELECT a.id, a.job_id, a.worker_id, j.status as job_status 
                  FROM job_applications a 
                  JOIN job_postings j ON a.job_id = j.id 
                  WHERE a.id = ? AND j.farmer_id = ?";
                  
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $application_id, $farmer_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        throw new Exception('Application not found or unauthorized access');
    }
    
    $app_data = $check_result->fetch_assoc();
    $job_id = $app_data['job_id'];
    
    // Check if job is already filled
    if ($app_data['job_status'] !== 'Open' && $app_data['job_status'] !== 'In Progress') { // Allow re-hiring if needed, or strictly check
         // If job is already 'In Progress', check if another worker is hired?
         // For now, let's assume we can hire if it's Open.
    }
    
    // Check if another worker is already hired for this job through application
    $hired_check = "SELECT id FROM job_applications WHERE job_id = ? AND status = 'Hired' AND id != ?";
    $hired_stmt = $conn->prepare($hired_check);
    $hired_stmt->bind_param("ii", $job_id, $application_id);
    $hired_stmt->execute();
    if ($hired_stmt->get_result()->num_rows > 0) {
        throw new Exception('Another worker is already hired for this job');
    }
    
    // Update Application Status to Hired
    $update_app = "UPDATE job_applications SET status = 'Hired' WHERE id = ?";
    $update_stmt = $conn->prepare($update_app);
    $update_stmt->bind_param("i", $application_id);
    if (!$update_stmt->execute()) {
        throw new Exception('Failed to update application status');
    }
    
    // Update Job Status to In Progress
    $update_job = "UPDATE job_postings SET status = 'In Progress' WHERE id = ?";
    $job_stmt = $conn->prepare($update_job);
    $job_stmt->bind_param("i", $job_id);
    if (!$job_stmt->execute()) {
        throw new Exception('Failed to update job status');
    }
    
    // Retrieve worker and farmer details to return
    $worker_sql = "SELECT name, email, phone FROM users WHERE id = ?";
    $worker_stmt = $conn->prepare($worker_sql);
    $worker_stmt->bind_param("i", $app_data['worker_id']);
    $worker_stmt->execute();
    $worker_details = $worker_stmt->get_result()->fetch_assoc();
    
    $farmer_sql = "SELECT name, email, phone FROM users WHERE id = ?";
    $farmer_stmt = $conn->prepare($farmer_sql);
    $farmer_stmt->bind_param("i", $farmer_id);
    $farmer_stmt->execute();
    $farmer_details = $farmer_stmt->get_result()->fetch_assoc();
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Worker successfully hired!',
        'worker_contact' => $worker_details,
        'farmer_contact' => $farmer_details
    ]);

} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
