<?php
/**
 * Direct Owner Google Login Test
 * This simulates what should happen when an owner logs in with Google
 */

header('Content-Type: application/json');
require_once 'config.php';

try {
    $conn = getDBConnection();
    
    // Simulate a Google owner login
    $testOwner = [
        'name' => 'Test Owner (Google)',
        'email' => 'testowner@agrohub.com',
        'user_type' => 'owner',
        'profile_picture' => 'https://via.placeholder.com/150',
        'is_verified' => TRUE
    ];
    
    echo json_encode(['status' => 'Testing owner login...']) . "\n";
    
    // Check if this test user already exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $testOwner['email']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'message' => 'Test owner already exists in database',
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'user_type' => $user['user_type']
            ]
        ]) . "\n";
    } else {
        // Create new owner
        $placeholderPass = password_hash('test_password_12345', PASSWORD_BCRYPT);
        
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, user_type, profile_picture, is_verified) VALUES (?, ?, ?, ?, ?, TRUE)");
        $stmt->bind_param("sssss", 
            $testOwner['name'], 
            $testOwner['email'], 
            $placeholderPass, 
            $testOwner['user_type'], 
            $testOwner['profile_picture']
        );
        
        if ($stmt->execute()) {
            $userId = $stmt->insert_id;
            echo json_encode([
                'success' => true,
                'message' => 'Test owner created successfully!',
                'user' => [
                    'id' => $userId,
                    'name' => $testOwner['name'],
                    'email' => $testOwner['email'],
                    'user_type' => $testOwner['user_type']
                ]
            ]) . "\n";
        } else {
            throw new Exception('Failed to create test owner: ' . $stmt->error);
        }
    }
    
    $stmt->close();
    
    // Now verify we can query owners
    echo "\n" . json_encode(['status' => 'Querying all owners from database...']) . "\n";
    
    $query = "SELECT id, name, email, user_type, created_at FROM users WHERE user_type = 'owner' ORDER BY created_at DESC";
    $result = $conn->query($query);
    
    $owners = [];
    while ($row = $result->fetch_assoc()) {
        $owners[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Found ' . count($owners) . ' owner(s) in database',
        'owners' => $owners
    ]) . "\n";
    
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]) . "\n";
}
?>
