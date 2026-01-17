<?php
/**
 * Worker Dashboard API
 * Returns dashboard statistics and data for authenticated workers
 */

require_once 'config.php';
require_once 'logger.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Invalid request method');
}

// Get authorization token
$headers = getallheaders();
$token = null;

if (isset($headers['Authorization'])) {
    $token = str_replace('Bearer ', '', $headers['Authorization']);
}

if (!$token) {
    jsonResponse(false, 'Authentication required');
}

// Create database connection
$conn = getDBConnection();

try {
    // Verify token and get user ID
    $stmt = $conn->prepare("
        SELECT us.user_id, u.user_type 
        FROM user_sessions us 
        JOIN users u ON us.user_id = u.id 
        WHERE us.session_token = ? AND us.expires_at > NOW()
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        jsonResponse(false, 'Invalid or expired token');
    }
    
    $session = $result->fetch_assoc();
    $userId = $session['user_id'];
    
    if ($session['user_type'] !== 'worker') {
        jsonResponse(false, 'Access denied. Workers only.');
    }
    
    $stmt->close();

    // Get worker profile ID
    $stmt = $conn->prepare("SELECT id, rating, total_jobs_completed FROM worker_profiles WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $profileResult = $stmt->get_result();
    
    if ($profileResult->num_rows === 0) {
        jsonResponse(false, 'Worker profile not found');
    }
    
    $profile = $profileResult->fetch_assoc();
    $workerProfileId = $profile['id'];
    $stmt->close();

    // Get active contracts count
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM hiring_contracts 
        WHERE worker_profile_id = ? AND contract_status IN ('accepted', 'in_progress')
    ");
    $stmt->bind_param("i", $workerProfileId);
    $stmt->execute();
    $contractsResult = $stmt->get_result();
    $activeContracts = $contractsResult->fetch_assoc()['count'];
    $stmt->close();

    // Get total earnings (from completed contracts)
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(total_amount), 0) as total 
        FROM hiring_contracts 
        WHERE worker_profile_id = ? AND contract_status = 'completed'
    ");
    $stmt->bind_param("i", $workerProfileId);
    $stmt->execute();
    $earningsResult = $stmt->get_result();
    $totalEarnings = $earningsResult->fetch_assoc()['total'];
    $stmt->close();

    // Get pending applications count
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM job_applications 
        WHERE worker_profile_id = ? AND status = 'pending'
    ");
    $stmt->bind_param("i", $workerProfileId);
    $stmt->execute();
    $applicationsResult = $stmt->get_result();
    $pendingApplications = $applicationsResult->fetch_assoc()['count'];
    $stmt->close();

    // Return dashboard data
    jsonResponse(true, 'Dashboard data retrieved successfully', [
        'activeContracts' => intval($activeContracts),
        'totalEarnings' => floatval($totalEarnings),
        'pendingApplications' => intval($pendingApplications),
        'rating' => floatval($profile['rating']),
        'totalJobsCompleted' => intval($profile['total_jobs_completed'])
    ]);

} catch (Exception $e) {
    logMessage("Worker dashboard error: " . $e->getMessage());
    jsonResponse(false, 'Failed to load dashboard data');
}

$conn->close();
?>
