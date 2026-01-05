<?php
require_once 'config.php';

// Prevent output before JSON
ob_start();

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

$conn = getDBConnection();

// Initial Token Validation (GET Request)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = isset($_GET['token']) ? sanitize($_GET['token']) : '';
    $email = isset($_GET['email']) ? sanitize($_GET['email']) : '';

    if (empty($token) || empty($email)) {
        jsonResponse(false, 'Invalid link parameters.');
    }

    $stmt = $conn->prepare("SELECT user_id, expires_at FROM password_reset_tokens WHERE token = ? AND email = ? AND used = 0");
    $stmt->bind_param("ss", $token, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        jsonResponse(false, 'Invalid or expired password reset link.');
    }

    $row = $result->fetch_assoc();
    if (strtotime($row['expires_at']) < time()) {
        jsonResponse(false, 'This link has expired.');
    }

    // Token is valid
    // Calculate minutes remaining
    $minutesLeft = round((strtotime($row['expires_at']) - time()) / 60);

    jsonResponse(true, 'Valid token', [
        'email' => $email,
        'expires_in' => $minutesLeft . ' minutes'
    ]);
}

// Password Reset Action (POST Request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $token = isset($input['token']) ? sanitize($input['token']) : '';
    $email = isset($input['email']) ? sanitize($input['email']) : '';
    $password = isset($input['password']) ? $input['password'] : '';
    $confirm = isset($input['confirm_password']) ? $input['confirm_password'] : '';

    if (empty($password) || strlen($password) < 8) {
        jsonResponse(false, 'Password must be at least 8 characters long.');
    }

    if ($password !== $confirm) {
        jsonResponse(false, 'Passwords do not match.');
    }

    // Double check token validity
    $stmt = $conn->prepare("SELECT user_id, user_type, expires_at FROM password_reset_tokens WHERE token = ? AND email = ? AND used = 0");
    $stmt->bind_param("ss", $token, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        jsonResponse(false, 'Invalid or expired token.');
    }

    $tokenData = $result->fetch_assoc();
    if (strtotime($tokenData['expires_at']) < time()) {
        jsonResponse(false, 'Token expired.');
    }

    $userId = $tokenData['user_id'];
    $userType = $tokenData['user_type'];

    // Update password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $updateStmt->bind_param("si", $hashedPassword, $userId);

    if ($updateStmt->execute()) {
        // Mark token as used
        $markStmt = $conn->prepare("UPDATE password_reset_tokens SET used = 1 WHERE token = ?");
        $markStmt->bind_param("s", $token);
        $markStmt->execute();
        
        // Determine login page redirect
        $redirect = 'login-farmer.html';
        if ($userType === 'owner') $redirect = 'login-owner.html';
        if ($userType === 'admin') $redirect = 'login-admin.html';

        jsonResponse(true, 'Password updated successfully', ['redirect' => $redirect]);
    } else {
        jsonResponse(false, 'Database error updating password.');
    }
}

ob_end_clean();
?>
