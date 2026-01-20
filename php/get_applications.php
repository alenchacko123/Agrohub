<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config.php';

try {
    $conn = getDBConnection();
    
    if (!isset($_GET['job_id'])) {
        throw new Exception('Job ID is required');
    }
    
    $job_id = intval($_GET['job_id']);
    
    // Get all applications for this job with applicant details
    $query = "SELECT a.*, u.name as applicant_name, u.email as applicant_email, u.phone as applicant_phone 
              FROM job_applications a 
              LEFT JOIN users u ON a.applicant_id = u.id 
              WHERE a.job_id = ? 
              ORDER BY a.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $job_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $applications = [];
    while ($row = $result->fetch_assoc()) {
        $applications[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'applications' => $applications,
        'count' => count($applications)
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'applications' => []
    ]);
}
?>
