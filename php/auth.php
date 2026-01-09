<?php
/**
 * AgroHub - Authentication Handler
 * 
 * This script handles Login, Signup, and Google Sign-In
 */

// Prevent any output before JSON headers
ob_start();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Disable display errors but log them
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';
require_once 'logger.php';

try {
    $action = isset($_GET['action']) ? sanitize($_GET['action']) : '';
    
    // Get raw input
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (json_last_error() !== JSON_ERROR_NONE && !empty($rawInput)) {
        throw new Exception('Invalid JSON input');
    }

    switch ($action) {
        case 'signup':
            handleSignup($input);
            break;
        case 'login':
            handleLogin($input);
            break;
        case 'google-auth':
            handleGoogleAuth($input);
            break;
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    // Clear any buffered output including warnings
    ob_end_clean();
    logDebug("Exception: " . $e->getMessage());
    jsonResponse(false, 'Server Error: ' . $e->getMessage());
}

/**
 * Handle Traditional Signup
 */
function handleSignup($data) {
    $name = isset($data['name']) ? sanitize($data['name']) : '';
    $email = isset($data['email']) ? sanitize($data['email']) : '';
    $password = isset($data['password']) ? $data['password'] : '';
    $userType = isset($data['userType']) ? sanitize($data['userType']) : 'farmer';

    if (empty($name) || empty($email) || empty($password)) {
        jsonResponse(false, 'All fields are required');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(false, 'Invalid email format');
    }

    $conn = getDBConnection();
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        jsonResponse(false, 'Email already registered');
    }
    $stmt->close();

    // Create user
    $hashedPassword = hashPassword($password);
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, user_type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $hashedPassword, $userType);
    
    if ($stmt->execute()) {
        $userId = $stmt->insert_id;
        $session = createSession($userId);
        jsonResponse(true, 'Account created successfully', [
            'user' => [
                'id' => $userId,
                'name' => $name,
                'email' => $email,
                'userType' => $userType
            ],
            'token' => $session['token']
        ]);
    } else {
        jsonResponse(false, 'Error creating account: ' . $stmt->error);
    }
}

/**
 * Handle Traditional Login
 */
function handleLogin($data) {
    $email = isset($data['email']) ? sanitize($data['email']) : '';
    $password = isset($data['password']) ? $data['password'] : '';
    $userType = isset($data['userType']) ? sanitize($data['userType']) : 'farmer';

    if (empty($email) || empty($password)) {
        jsonResponse(false, 'Email and password are required');
    }

    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND user_type = ?");
    $stmt->bind_param("ss", $email, $userType);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (verifyPassword($password, $user['password'])) {
            $session = createSession($user['id']);
            jsonResponse(true, 'Login successful', [
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'userType' => $user['user_type'],
                    'picture' => $user['profile_picture']
                ],
                'token' => $session['token']
            ]);
        }
    }
    
    jsonResponse(false, 'Invalid email or password');
}

function handleGoogleAuth($data) {
    $email = '';
    $name = '';
    $picture = '';
    $googleId = '';

    // Case 1: ID Token (JWT) provided aka "One Tap" or "Sign In Button"
    if (isset($data['credential'])) {
        $tokenParts = explode(".", $data['credential']);
        if (count($tokenParts) < 2) {
            jsonResponse(false, 'Invalid Google token format');
        }
        $payloadJson = base64UrlDecode($tokenParts[1]);
        $payload = json_decode($payloadJson, true);
        if (!$payload) {
            jsonResponse(false, 'Failed to decode Google token payload');
        }
        $googleId = isset($payload['sub']) ? $payload['sub'] : '';
        $email = isset($payload['email']) ? $payload['email'] : '';
        $name = isset($payload['name']) ? $payload['name'] : '';
        $picture = isset($payload['picture']) ? $payload['picture'] : '';
    } 
    // Case 2: Access Token provided (via oauth2 client)
    elseif (isset($data['accessToken'])) {
        logDebug("Google Auth: Access Token provided");
        $accessToken = $data['accessToken'];
        $url = "https://www.googleapis.com/oauth2/v3/userinfo";
        
        $response = false;
        
        // Try cURL first
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); 
            
            $response = curl_exec($ch);
            
            if (curl_errno($ch)) {
                logDebug('Google Auth cURL Error: ' . curl_error($ch));
                $response = false; 
            }
            curl_close($ch);
        }
        
        // Fallback to file_get_contents
        if ($response === false) {
            logDebug("Google Auth: Falling back to file_get_contents");
            $opts = [
                "http" => [
                    "method" => "GET",
                    "header" => "Authorization: Bearer " . $accessToken . "\r\n" .
                                "User-Agent: AgroHub/1.0\r\n"
                ],
                "ssl" => [
                    "verify_peer" => false,
                    "verify_peer_name" => false
                ]
            ];
            $context = stream_context_create($opts);
            $response = @file_get_contents($url, false, $context);
        }

        if ($response === false) {
            logDebug("Google Auth: All methods failed to contact Google API");
            jsonResponse(false, 'Failed to connect to Google API. Check server internet connection.');
        }

        $googleUser = json_decode($response, true);
        if (!$googleUser) {
            logDebug("Google Auth: Failed to decode response: " . substr($response, 0, 100));
            jsonResponse(false, 'Invalid response from Google API');
        }

        $googleId = isset($googleUser['sub']) ? $googleUser['sub'] : '';
        $email = isset($googleUser['email']) ? $googleUser['email'] : '';
        $name = isset($googleUser['name']) ? $googleUser['name'] : '';
        $picture = isset($googleUser['picture']) ? $googleUser['picture'] : '';
        
        logDebug("Google Auth: Got user data - " . $email);
        
    } else {
        logDebug("Google Auth: No credentials provided in request");
        jsonResponse(false, 'No Google credential or access token provided');
    }

    $userType = isset($data['userType']) ? sanitize($data['userType']) : 'farmer';

    if (empty($email)) {
        logDebug("Google Auth: Email empty in retrieved data");
        jsonResponse(false, 'Google account missing email address');
    }

    // Truncate picture URL if too long
    if (strlen($picture) > 255) {
        $picture = substr($picture, 0, 255);
    }

    $conn = getDBConnection();
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // User exists, log them in
        logDebug("Google Auth: User exists, logging in");
        $user = $result->fetch_assoc();
        
        // Update their picture if it changed
        if ($user['profile_picture'] !== $picture) {
            $update = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
            $update->bind_param("si", $picture, $user['id']);
            $update->execute();
        }
    } else {
        // Create new user
        logDebug("Google Auth: Creating new user");
        // Generate a random secure password
        try {
            $randomBytes = random_bytes(16);
        } catch (Exception $e) {
            $randomBytes = openssl_random_pseudo_bytes(16);
        }
        $placeholderPass = password_hash(bin2hex($randomBytes), PASSWORD_BCRYPT);
        
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, user_type, profile_picture, is_verified) VALUES (?, ?, ?, ?, ?, TRUE)");
        $stmt->bind_param("sssss", $name, $email, $placeholderPass, $userType, $picture);
        
        if (!$stmt->execute()) {
            logDebug("Google Auth: Database Error - " . $stmt->error);
            throw new Exception("Database error creating user: " . $stmt->error);
        }
        
        $userId = $stmt->insert_id;
        $user = [
            'id' => $userId,
            'name' => $name,
            'email' => $email,
            'user_type' => $userType,
            'profile_picture' => $picture
        ];
    }

    $session = createSession($user['id']);
    logDebug("Google Auth: Session created, success");

    
    // Flush buffer before outputting JSON
    if (ob_get_length()) ob_clean();
    
    jsonResponse(true, 'Google Sign-In successful', [
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'userType' => $user['user_type'],
            'picture' => $picture
        ],
        'token' => $session['token']
    ]);
}

/**
 * Create a session for the user
 */
function createSession($userId) {
    logDebug("createSession called for user " . $userId);
    if (!$userId) {
        throw new Exception("Cannot create session for invalid user ID");
    }

    $conn = getDBConnection();
    
    try {
        $randomBytes = random_bytes(32);
    } catch (Exception $e) {
        $randomBytes = openssl_random_pseudo_bytes(32);
    }
    $token = bin2hex($randomBytes);
    
    $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
    $ip = $_SERVER['REMOTE_ADDR'];
    $ua = $_SERVER['HTTP_USER_AGENT'];

    $stmt = $conn->prepare("INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        logDebug("createSession prepare failed: " . $conn->error);
        throw new Exception("Database error: " . $conn->error);
    }
    $stmt->bind_param("issss", $userId, $token, $ip, $ua, $expires);
    
    if (!$stmt->execute()) {
        logDebug("createSession execute failed: " . $stmt->error);
        throw new Exception("Failed to create session: " . $stmt->error);
    }

    return ['token' => $token];
}

/**
 * URL-safe Base64 Decode
 */
function base64UrlDecode($data) {
    $data = str_replace(array('-', '_'), array('+', '/'), $data);
    $mod4 = strlen($data) % 4;
    if ($mod4) {
        $data .= substr('====', $mod4);
    }
    return base64_decode($data);
}
?>
