<?php
require_once 'c:/xampp/htdocs/Agrohub/php/config.php';
$conn = getDBConnection();
$res = $conn->query("SELECT email, raw_password FROM users WHERE id = (SELECT farmer_id FROM rental_requests WHERE equipment_id = 17 LIMIT 1)");
print_r($res->fetch_assoc());
?>
