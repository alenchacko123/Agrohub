<?php
/**
 * AgroHub - Profile Update Handler
 * 
 * This script handles updating user profile information
 */

// Prevent any output before JSON headers
ob_start();

// Disable display errors but log them
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';
require_once 'logger.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get Authorization Header
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

    if (empty($authHeader) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    }

    $token = '';
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];
    }

    if (empty($token) && isset($_GET['token'])) {
        $token = sanitize($_GET['token']);
    }

    if (empty($token)) {
        throw new Exception('Unauthorized: No token provided');
    }

    $conn = getDBConnection();

    // Attempt to migrate DB (silently, catch errors so it doesn't break flow)
    try {
        $conn->query("ALTER TABLE users MODIFY profile_picture MEDIUMTEXT");
    } catch (Throwable $e) {
        logDebug("Migration failed (non-critical): " . $e->getMessage());
    }

    // Check and add columns if missing (Migration)
    $columns = [
        'location' => 'VARCHAR(255)', 
        'farm_size' => 'VARCHAR(50)', 
        'phone' => 'VARCHAR(20)',
        'business_name' => 'VARCHAR(255)',
        'equipment_count' => 'VARCHAR(50)'
    ];
    
    foreach ($columns as $col => $type) {
        $check = $conn->query("SHOW COLUMNS FROM users LIKE '$col'");
        if ($check->num_rows == 0) {
            $conn->query("ALTER TABLE users ADD COLUMN $col $type NULL");
        }
    }

    // Validate Token and get User ID
    $stmt = $conn->prepare("SELECT u.id, u.email, u.password FROM user_sessions s JOIN users u ON s.user_id = u.id WHERE s.session_token = ? AND s.expires_at > NOW()");
    if (!$stmt) {
        throw new Exception("Database prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Invalid or expired session');
    }

    $userData = $result->fetch_assoc();
    $userId = $userData['id'];

    // Get input data
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    if (!$input) {
        throw new Exception('Invalid data provided');
    }

    $name = isset($input['name']) ? sanitize($input['name']) : '';
    $email = isset($input['email']) ? sanitize($input['email']) : '';
    $phone = isset($input['phone']) ? sanitize($input['phone']) : null;
    $location = isset($input['location']) ? sanitize($input['location']) : null;
    $farm_size = isset($input['farm_size']) ? sanitize($input['farm_size']) : null;
    $business_name = isset($input['business_name']) ? sanitize($input['business_name']) : null;
    $equipment_count = isset($input['equipment_count']) ? sanitize($input['equipment_count']) : null;
    $picture = isset($input['picture']) ? $input['picture'] : null;
    $password = isset($input['password']) ? $input['password'] : '';

    if (empty($name) || empty($email)) {
        throw new Exception('Name and email are required');
    }

    // Check if email is being changed and if new email already exists
    if ($email !== $userData['email']) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $userId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception('Email already in use by another account');
        }
    }

    // Build Update Query
    $updateFields = ["name = ?", "email = ?"];
    $types = "ss";
    $params = [$name, $email];

    if ($phone !== null) {
        $updateFields[] = "phone = ?";
        $types .= "s";
        $params[] = $phone;
    }

    if ($location !== null) {
        $updateFields[] = "location = ?";
        $types .= "s";
        $params[] = $location;
    }

    if ($farm_size !== null) {
        $updateFields[] = "farm_size = ?";
        $types .= "s";
        $params[] = $farm_size;
    }

    if ($business_name !== null) {
        $updateFields[] = "business_name = ?";
        $types .= "s";
        $params[] = $business_name;
    }
    
    if ($equipment_count !== null) {
        $updateFields[] = "equipment_count = ?";
        $types .= "s";
        $params[] = $equipment_count;
    }

    // Get new fields
    $gender = isset($input['gender']) ? sanitize($input['gender']) : null;
    $date_of_birth = isset($input['date_of_birth']) ? sanitize($input['date_of_birth']) : null;
    // Location already exists
    $worker_type = isset($input['worker_type']) ? sanitize($input['worker_type']) : null;
    $experience_years = isset($input['experience_years']) ? sanitize($input['experience_years']) : null;
    $daily_wage = isset($input['daily_wage']) ? sanitize($input['daily_wage']) : null;
    $bio = isset($input['bio']) ? sanitize($input['bio']) : null;

    // Check and add columns if missing (Migration for Worker fields)
    $worker_columns = [
        'gender' => 'VARCHAR(20)',
        'date_of_birth' => 'DATE',
        'worker_type' => 'VARCHAR(50)',
        'experience_years' => 'INT',
        'daily_wage' => 'DECIMAL(10,2)',
        'bio' => 'TEXT'
    ];
    
    foreach ($worker_columns as $col => $type) {
        $check = $conn->query("SHOW COLUMNS FROM users LIKE '$col'");
        if ($check->num_rows == 0) {
            $conn->query("ALTER TABLE users ADD COLUMN $col $type NULL");
        }
    }

    if ($picture) {
        $updateFields[] = "profile_picture = ?";
        $types .= "s";
        $params[] = $picture;
    }

    if ($gender !== null) {
        $updateFields[] = "gender = ?";
        $types .= "s";
        $params[] = $gender;
    }

    if ($date_of_birth !== null) {
        $updateFields[] = "date_of_birth = ?";
        $types .= "s";
        $params[] = $date_of_birth;
    }

    if ($worker_type !== null) {
        $updateFields[] = "worker_type = ?";
        $types .= "s";
        $params[] = $worker_type;
    }

    if ($experience_years !== null) {
        $updateFields[] = "experience_years = ?";
        $types .= "i";
        $params[] = $experience_years;
    }

    if ($daily_wage !== null) {
        $updateFields[] = "daily_wage = ?";
        $types .= "d";
        $params[] = $daily_wage;
    }

    if ($bio !== null) {
        $updateFields[] = "bio = ?";
        $types .= "s";
        $params[] = $bio;
    }

    if (!empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $updateFields[] = "password = ?";
        $types .= "s";
        $params[] = $hashedPassword;
    }

    $sql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = ?";
    $types .= "i";
    $params[] = $userId;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if (!$stmt->execute()) {
        throw new Exception('Failed to update profile: ' . $stmt->error);
    }

    // Fetch updated user data to return
    $stmt = $conn->prepare("SELECT id, name, email, phone, location, farm_size, profile_picture, user_type, gender, date_of_birth, experience_years, daily_wage, worker_type, bio FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $updatedUser = $stmt->get_result()->fetch_assoc();

    // Prepare response object
    $responseUser = [
        'id' => $updatedUser['id'],
        'name' => $updatedUser['name'],
        'email' => $updatedUser['email'],
        'phone' => $updatedUser['phone'],
        'location' => $updatedUser['location'],
        'farm_size' => $updatedUser['farm_size'],
        'user_type' => $updatedUser['user_type'],
        'picture' => $updatedUser['profile_picture'],
        'gender' => $updatedUser['gender'],
        'date_of_birth' => $updatedUser['date_of_birth'],
        'experience_years' => $updatedUser['experience_years'],
        'daily_wage' => $updatedUser['daily_wage'],
        'worker_type' => $updatedUser['worker_type'],
        'bio' => $updatedUser['bio']
    ];

    // Clear buffer
    ob_end_clean();
    jsonResponse(true, 'Profile updated successfully', ['user' => $responseUser]);

} catch (Exception $e) {
    ob_end_clean();
    logDebug("Update Profile Error: " . $e->getMessage());
    jsonResponse(false, $e->getMessage());
}
?>
