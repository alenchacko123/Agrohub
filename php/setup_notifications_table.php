<?php
/**
 * Setup Notifications Table
 * Creates a notifications table for user notifications
 */

require_once 'config.php';

try {
    $conn = getDBConnection();
    
    // Create notifications table
    $sql = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        message TEXT NOT NULL,
        related_agreement_id VARCHAR(50) DEFAULT NULL,
        related_booking_id INT DEFAULT NULL,
        notification_type VARCHAR(50) DEFAULT 'info',
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        read_at TIMESTAMP NULL DEFAULT NULL,
        INDEX idx_user_id (user_id),
        INDEX idx_is_read (is_read),
        INDEX idx_created_at (created_at),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql) === TRUE) {
        echo "Notifications table created successfully or already exists.\n";
        
        // Check if table has any data
        $result = $conn->query("SELECT COUNT(*) as count FROM notifications");
        $row = $result->fetch_assoc();
        echo "Current notifications count: " . $row['count'] . "\n";
        
    } else {
        throw new Exception("Error creating table: " . $conn->error);
    }
    
    $conn->close();
    echo "\nNotifications table setup completed successfully!\n";
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}
?>
