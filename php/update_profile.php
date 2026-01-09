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

    // Validate Token and get User ID
    $stmt = $conn->prepare("SELECT u.id, u.email FROM user_sessions s JOIN users u ON s.user_id = u.id WHERE s.session_token = ? AND s.expires_at > NOW()");
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

    // Update profile details
    if ($picture) {
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, profile_picture = ? WHERE id = ?");
        $stmt->bind_param("sssi", $name, $email, $picture, $userId);
    } else {
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $email, $userId);
    }

    if (!$stmt->execute()) {
        throw new Exception('Failed to update profile: ' . $stmt->error);
    }

    // Update password if provided
    if (!empty($password)) {
        $hashedPassword = hashPassword($password);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $userId);
        $stmt->execute();
    }

    // Clear buffer
    ob_end_clean();
    jsonResponse(true, 'Profile updated successfully');

} catch (Exception $e) {
    ob_end_clean();
    logDebug("Update Profile Error: " . $e->getMessage());
    jsonResponse(false, $e->getMessage());
}
?>
