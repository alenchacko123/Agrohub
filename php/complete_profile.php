<?php
/**
 * Complete User Profile
 * Updates user's phone number and sets profile_completed to true
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid request data');
    }
    
    $userId = isset($input['user_id']) ? intval($input['user_id']) : 0;
    $phone = isset($input['phone']) ? sanitize($input['phone']) : '';
    $role = isset($input['role']) ? sanitize($input['role']) : '';
    
    // Validate inputs
    if (empty($userId)) {
        throw new Exception('User ID is required');
    }
    
    if (empty($phone)) {
        throw new Exception('Phone number is required');
    }
    
    if (empty($role) || !in_array($role, ['farmer', 'owner', 'worker'])) {
        throw new Exception('Valid role is required');
    }
    
    // Validate phone number format
    if (!preg_match('/^[0-9+\-\s()]{10,20}$/', $phone)) {
        throw new Exception('Invalid phone number format');
    }
    
    $conn = getDBConnection();
    
    // Check if phone number is already used by another user
    $checkSql = "SELECT id FROM users WHERE phone = ? AND id != ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("si", $phone, $userId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        throw new Exception('This phone number is already registered');
    }
    
    // Update user profile
    $updateSql = "UPDATE users SET 
                    phone = ?,
                    user_type = ?,
                    profile_completed = TRUE,
                    updated_at = NOW()
                  WHERE id = ?";
    
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("ssi", $phone, $role, $userId);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update profile');
    }
    
    if ($stmt->affected_rows === 0) {
        throw new Exception('User not found or no changes made');
    }
    
    // Get updated user data
    $getUserSql = "SELECT id, name, email, phone, user_type, profile_completed, created_at 
                   FROM users WHERE id = ?";
    $getUserStmt = $conn->prepare($getUserSql);
    $getUserStmt->bind_param("i", $userId);
    $getUserStmt->execute();
    $userResult = $getUserStmt->get_result();
    $updatedUser = $userResult->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'message' => 'Profile completed successfully',
        'user' => $updatedUser
    ]);
    
    $stmt->close();
    $getUserStmt->close();
    $conn->close();
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
