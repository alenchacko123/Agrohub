<?php
require_once 'php/config.php';
$conn = getDBConnection();
$res = $conn->query("SHOW COLUMNS FROM users LIKE 'user_type'");
$row = $res->fetch_assoc();
echo "ENUM_DEFINITION: " . $row['Type'];
?>
