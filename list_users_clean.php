<?php
require_once 'php/config.php';
$conn = getDBConnection();
$result = $conn->query('SELECT id, name, user_type FROM users');
while($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Name: " . $row['name'] . " | Type: " . $row['user_type'] . "\n";
}
?>
