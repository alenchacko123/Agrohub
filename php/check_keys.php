<?php
require_once 'config.php';
$conn = getDBConnection();
// fetch one row
$res = $conn->query("SELECT * FROM rental_requests LIMIT 1");
$row = $res->fetch_assoc();
echo json_encode(array_keys($row));
?>
