<?php
require 'config.php';
$conn = getDBConnection();
$res = $conn->query("SELECT id, name, email FROM users WHERE id = 1");
print_r($res->fetch_assoc());
?>
