<?php
require_once 'php/config.php';
$conn = getDBConnection();

$sql = "ALTER TABLE users MODIFY COLUMN user_type ENUM('farmer', 'owner', 'admin', 'worker') NOT NULL";

if ($conn->query($sql) === TRUE) {
    echo "Table users modified successfully. Added 'worker' to user_type enum.";
} else {
    echo "Error modifying table: " . $conn->error;
}

$conn->close();
?>
