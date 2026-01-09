<?php
require_once 'config.php';
$conn = getDBConnection();
$sql = "ALTER TABLE users MODIFY profile_picture MEDIUMTEXT";
if ($conn->query($sql) === TRUE) {
    echo "Table users modified successfully (MEDIUMTEXT)";
} else {
    echo "Error modifying table: " . $conn->error;
}
$conn->close();
?>
