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
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['application_id']) || !isset($input['status'])) {
        throw new Exception('Application ID and status are required');
    }
    
    $application_id = intval($input['application_id']);
    // $farmer_id is derived from session
    $status = $input['status'];
    
    // Validate status
    if (!in_array($status, ['accepted', 'declined', 'pending', 'Hired', 'Rejected', 'Applied'])) {
        throw new Exception('Invalid status');
    }
    
    // Update application status with ownership check
    $query = "UPDATE job_applications ja 
              INNER JOIN job_postings jp ON ja.job_id = jp.id 
              SET ja.status = ?, ja.updated_at = NOW() 
              WHERE ja.id = ? AND jp.farmer_id = ?";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param('sii', $status, $application_id, $farmer_id);
    
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Application ' . $status . ' successfully'
        ]);
    } else {
        // If no rows affected, it could be: ID not found, owner mismatch, or status same.
        // We can't easily distinguish without a separate SELECT, but for security simply saying "Failed" is okay.
        // However, if status was already 'accepted', affected_rows might be 0.
        // To be safe, we could do a SELECT first to check ownership, but UPDATE constraint is safer.
        // But if 0 rows updated, it might be confusing. 
        // Let's assume validation failure or no-op.
         echo json_encode([
            'success' => false, // Return false if nothing changed or verification failed
            'message' => 'Failed to update application. Access denied or no changes made.'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
