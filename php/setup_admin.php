<?php
require_once 'config.php';

echo "Setting up admin user...\n";

$email = 'admin@gmail.com';
$password = 'admin123';
$name = 'System Admin';
$userType = 'admin';

$hashedPassword = hashPassword($password);

$conn = getDBConnection();

// Check if user exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "User with email $email already exists. Updating password and role...\n";
    $row = $result->fetch_assoc();
    $userId = $row['id'];
    
    $updateDate = $conn->prepare("UPDATE users SET password = ?, user_type = ?, name = ? WHERE id = ?");
    $updateDate->bind_param("sssi", $hashedPassword, $userType, $name, $userId);
    
    if ($updateDate->execute()) {
        echo "Admin user updated successfully.\n";
    } else {
        echo "Error updating admin user: " . $conn->error . "\n";
    }
} else {
    echo "Creating new admin user...\n";
    $insert = $conn->prepare("INSERT INTO users (name, email, password, user_type, is_verified) VALUES (?, ?, ?, ?, 1)");
    $insert->bind_param("ssss", $name, $email, $hashedPassword, $userType);
    
    if ($insert->execute()) {
        echo "Admin user created successfully.\n";
    } else {
        echo "Error creating admin user: " . $conn->error . "\n";
    }
}

$conn->close();
?>
