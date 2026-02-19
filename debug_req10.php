<?php
require_once 'php/config.php';
$conn = getDBConnection();
echo "--- Request 10 ---\n";
$res = $conn->query("SELECT * FROM rental_requests WHERE id = 10");
print_r($res->fetch_assoc());

echo "\n--- Agreement for Request 10 ---\n";
$res = $conn->query("SELECT * FROM agreements WHERE rental_request_id = 10");
print_r($res->fetch_assoc());
?>
