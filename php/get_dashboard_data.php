<?php
require_once 'config.php';

header('Content-Type: application/json');

// Get Authorization Header
$headers = getallheaders();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

// Helper for Apache/Nginx where getallheaders might be missing or capitalized differently
if (empty($authHeader) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
}

$token = '';
if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $token = $matches[1];
}

// Fallback: Check if token is passed as query parameter
if (empty($token) && isset($_GET['token'])) {
    $token = sanitize($_GET['token']);
}

if (empty($token)) {
    jsonResponse(false, 'No token provided');
}

$conn = getDBConnection();

// Validate Token
$stmt = $conn->prepare("SELECT u.id, u.name, u.email, u.user_type, u.profile_picture AS picture 
                        FROM user_sessions s 
                        JOIN users u ON s.user_id = u.id 
                        WHERE s.session_token = ? AND s.expires_at > NOW()");

if (!$stmt) {
    // If table doesn't exist, we can't validate, so fail
    jsonResponse(false, 'System error: Validation failed');
}

$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    jsonResponse(false, 'Invalid or expired token');
}

$user = $result->fetch_assoc();

// Get Dashboard Services
$services = [];
$stmt = $conn->prepare("SELECT title, description, icon, link, badge_text, badge_type 
                        FROM dashboard_services 
                        WHERE user_type = ? AND is_active = TRUE 
                        ORDER BY sort_order ASC");

if ($stmt) {
    $stmt->bind_param("s", $user['user_type']);
    $stmt->execute();
    $servicesResult = $stmt->get_result();
    
    while ($row = $servicesResult->fetch_assoc()) {
        $services[] = $row;
    }
}

jsonResponse(true, 'Data retrieved', [
    'user' => $user,
    'services' => $services
]);
?>
