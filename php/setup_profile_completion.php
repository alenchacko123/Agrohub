<?php
/**
 * Setup Profile Completion Fields
 * Adds phone and profile_completed columns to users table if they don't exist
 */

require_once 'config.php';

try {
    $conn = getDBConnection();
    
    // Check if phone column exists
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'phone'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL AFTER email");
        echo "Added 'phone' column to users table.\n";
    } else {
        echo "'phone' column already exists.\n";
    }
    
    // Check if profile_completed column exists
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_completed'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN profile_completed BOOLEAN DEFAULT FALSE AFTER phone");
        echo "Added 'profile_completed' column to users table.\n";
    } else {
        echo "'profile_completed' column already exists.\n";
    }
    
    // Set profile_completed to TRUE for existing users who have phone numbers
    $conn->query("UPDATE users SET profile_completed = TRUE WHERE phone IS NOT NULL AND phone != ''");
    echo "Updated existing users with phone numbers to profile_completed = TRUE.\n";
    
    echo "\nProfile completion setup completed successfully!\n";
    
    $conn->close();
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}
?>
