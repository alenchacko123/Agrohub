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
    
    // Get job_id
    $job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
    
    if ($job_id <= 0) {
        throw new Exception('Invalid Job ID');
    }
    
    // Verify job ownership and get full details
    $job_stmt = $conn->prepare("SELECT id, farmer_id, job_title, status, location, payment_amount as wage_per_day, workers_needed, payment_type, job_description as description, created_at, job_category, duration_days FROM job_postings WHERE id = ? AND farmer_id = ?");
    $job_stmt->bind_param("ii", $job_id, $farmer_id);
    $job_stmt->execute();
    $job_result = $job_stmt->get_result();
    
    if ($job_result->num_rows === 0) {
        throw new Exception('Job not found or unauthorized access');
    }
    
    $job = $job_result->fetch_assoc();
    
    // Fetch applications
    $sql = "SELECT 
                a.id as application_id, 
                a.status, 
                a.message, 
                a.experience as specific_experience, 
                a.created_at as applied_at,
                u.id as worker_id, 
                u.name, 
                u.email, 
                u.phone, 
                u.profile_picture, 
                u.bio, 
                u.experience_years as general_experience,
                u.worker_type as worker_role,
                u.location
            FROM job_applications a
            JOIN users u ON a.worker_id = u.id
            WHERE a.job_id = ?
            ORDER BY a.created_at DESC";
            
    $app_stmt = $conn->prepare($sql);
    $app_stmt->bind_param("i", $job_id);
    $app_stmt->execute();
    $app_result = $app_stmt->get_result();
    
    $applications = [];
    while ($row = $app_result->fetch_assoc()) {
        // Privacy logic
        if ($row['status'] !== 'Hired') {
            $row['phone'] = 'Hidden (Hire to reveal)';
            $row['email'] = 'Hidden (Hire to reveal)';
        }
        
        $applications[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'job' => $job,
        'applications' => $applications
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
