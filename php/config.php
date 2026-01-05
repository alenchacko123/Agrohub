<?php
/**
 * AgroHub - Database Configuration
 * 
 * This file contains database connection settings and common configurations
 */

// Database Configuration
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');           // Change to your MySQL username
define('DB_PASS', '');               // Change to your MySQL password
define('DB_NAME', 'agrohub');        // Your database name

// Email Configuration (using PHPMailer or native mail)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'alenchacko2028@mca.ajce.in');     // Your email address
define('SMTP_PASS', 'sfrxoljhnaqzybpi');           // Gmail App Password
define('SMTP_FROM', 'alenchacko2028@mca.ajce.in');
define('SMTP_FROM_NAME', 'AgroHub');

// Site Configuration
define('SITE_URL', 'http://localhost/Agrohub');  // Update with your actual URL
define('SITE_NAME', 'AgroHub');
define('DEVELOPMENT_MODE', false); // SET TO false IN PRODUCTION. If true, shows reset link in UI if email fails.

// Security Configuration
define('TOKEN_EXPIRY_MINUTES', 15);  // Reset token expires in 15 minutes
define('PASSWORD_MIN_LENGTH', 8);

// Create database connection
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        // Return JSON error if connection fails, so frontend can handle it forcefully
        if (function_exists('jsonResponse')) {
            jsonResponse(false, "Database connection failed: " . $conn->connect_error);
        } else {
            die(json_encode(['success' => false, 'message' => "Database connection failed: " . $conn->connect_error]));
        }
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

// Generate secure random token
function generateSecureToken($length = 64) {
    return bin2hex(random_bytes($length / 2));
}

// Hash password securely
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Sanitize input
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Send JSON response
function jsonResponse($success, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}
?>
