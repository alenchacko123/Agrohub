<?php
/**
 * Worker Signup API
 * Handles worker registration for the AgroHub platform
 */

require_once 'config.php';
require_once 'logger.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$requiredFields = ['fullName', 'email', 'phone', 'password', 'dateOfBirth', 'gender', 'location', 'workerType', 'experienceYears', 'dailyWage'];
foreach ($requiredFields as $field) {
    if (empty($input[$field])) {
        jsonResponse(false, "Missing required field: $field");
    }
}

// Sanitize inputs
$fullName = sanitize($input['fullName']);
$email = filter_var(sanitize($input['email']), FILTER_VALIDATE_EMAIL);
$phone = sanitize($input['phone']);
$password = $input['password'];
$dateOfBirth = sanitize($input['dateOfBirth']);
$gender = sanitize($input['gender']);
$location = sanitize($input['location']);
$workerType = sanitize($input['workerType']);
$experienceYears = intval($input['experienceYears']);
$dailyWage = floatval($input['dailyWage']);
$bio = isset($input['bio']) ? sanitize($input['bio']) : '';

// Validate email
if (!$email) {
    jsonResponse(false, 'Invalid email address');
}

// Validate password
if (strlen($password) < 8) {
    jsonResponse(false, 'Password must be at least 8 characters');
}

// Validate worker type
$validWorkerTypes = ['laborer', 'operator', 'specialist'];
if (!in_array($workerType, $validWorkerTypes)) {
    jsonResponse(false, 'Invalid worker type');
}

// Validate gender
$validGenders = ['male', 'female', 'other'];
if (!in_array($gender, $validGenders)) {
    jsonResponse(false, 'Invalid gender');
}

// Create database connection
$conn = getDBConnection();

try {
    // Start transaction
    $conn->begin_transaction();

    // Check if email already exists
    $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $result = $checkEmail->get_result();
    
    if ($result->num_rows > 0) {
        $conn->rollback();
        jsonResponse(false, 'Email already registered');
    }
    $checkEmail->close();

    // Hash password
    $hashedPassword = hashPassword($password);

    // Insert into users table first (user_type = 'worker')
    $insertUser = $conn->prepare("INSERT INTO users (name, email, password, user_type, created_at) VALUES (?, ?, ?, 'worker', NOW())");
    $insertUser->bind_param("sss", $fullName, $email, $hashedPassword);
    
    if (!$insertUser->execute()) {
        throw new Exception("Failed to create user account: " . $insertUser->error);
    }
    
    $userId = $conn->insert_id;
    $insertUser->close();

    // Insert into worker_profiles table
    $insertProfile = $conn->prepare("
        INSERT INTO worker_profiles 
        (user_id, full_name, phone, location, worker_type, bio, experience_years, daily_wage, date_of_birth, gender, availability_status, is_verified, is_approved, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'available', FALSE, FALSE, NOW())
    ");
    
    $insertProfile->bind_param(
        "isssssiiss",
        $userId,
        $fullName,
        $phone,
        $location,
        $workerType,
        $bio,
        $experienceYears,
        $dailyWage,
        $dateOfBirth,
        $gender
    );
    
    if (!$insertProfile->execute()) {
        throw new Exception("Failed to create worker profile: " . $insertProfile->error);
    }
    
    $workerProfileId = $conn->insert_id;
    $insertProfile->close();

    // Commit transaction
    $conn->commit();

    // Log the successful registration
    logMessage("New worker registered: $email (ID: $userId, Profile ID: $workerProfileId)");

    // Return success response
    jsonResponse(true, 'Registration successful! Please login to continue.', [
        'userId' => $userId,
        'workerProfileId' => $workerProfileId,
        'message' => 'Your profile is pending admin approval. You will be notified once approved.'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    logMessage("Worker registration error: " . $e->getMessage());
    jsonResponse(false, 'Registration failed. Please try again.', ['debug' => $e->getMessage()]);
}

$conn->close();
?>
