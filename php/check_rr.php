<?php
header('Content-Type: application/json');
require_once 'config.php';
$conn = getDBConnection();
$res = $conn->query("SHOW COLUMNS FROM rental_requests");
$cols = [];
while ($row = $res->fetch_assoc()) {
    $cols[] = $row;
}
echo json_encode($cols);
?>
