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
    
    if (!isset($input['application_id']) || !isset($input['status'])) {
        throw new Exception('Application ID and status are required');
    }
    
    $application_id = intval($input['application_id']);
    $status = $input['status']; // 'accepted' or 'declined'
    
    // Validate status
    if (!in_array($status, ['accepted', 'declined', 'pending'])) {
        throw new Exception('Invalid status');
    }
    
    // Update application status
    $query = "UPDATE job_applications SET status = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('si', $status, $application_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Application ' . $status . ' successfully'
        ]);
    } else {
        throw new Exception('Failed to update application');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
