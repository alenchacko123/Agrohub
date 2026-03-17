<?php
require 'config.php';
$conn = getDBConnection();
$res = $conn->query("SELECT * FROM rental_feedback");
$results = [];
while($r = $res->fetch_assoc()) {
    $results[] = $r;
}
echo json_encode($results, JSON_PRETTY_PRINT);
?>
