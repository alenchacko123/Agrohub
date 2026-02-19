<?php
require_once 'config.php';

try {
    $conn = getDBConnection();

    // Create withdrawals table
    $sql = "CREATE TABLE IF NOT EXISTS withdrawals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        owner_id INT NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        status ENUM('requested', 'processed', 'completed', 'failed') NOT NULL DEFAULT 'requested',
        razorpay_payout_id VARCHAR(100),
        bank_account_number VARCHAR(50),
        ifsc_code VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (owner_id) REFERENCES users(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if ($conn->query($sql) === TRUE) {
        echo "Table withdrawals created successfully or already exists.<br>";
    } else {
        echo "Error creating table: " . $conn->error . "<br>";
    }

    // Add bank_account_number to users table
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'bank_account_number'");
    if ($result->num_rows == 0) {
        $sql = "ALTER TABLE users ADD COLUMN bank_account_number VARCHAR(50) AFTER address";
        if ($conn->query($sql) === TRUE) {
            echo "Column bank_account_number added successfully.<br>";
        } else {
            echo "Error adding column bank_account_number: " . $conn->error . "<br>";
        }
    } else {
        echo "Column bank_account_number already exists.<br>";
    }

    // Add ifsc_code to users table
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'ifsc_code'");
    if ($result->num_rows == 0) {
        $sql = "ALTER TABLE users ADD COLUMN ifsc_code VARCHAR(20) AFTER bank_account_number";
        if ($conn->query($sql) === TRUE) {
            echo "Column ifsc_code added successfully.<br>";
        } else {
            echo "Error adding column ifsc_code: " . $conn->error . "<br>";
        }
    } else {
        echo "Column ifsc_code already exists.<br>";
    }

    $conn->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
