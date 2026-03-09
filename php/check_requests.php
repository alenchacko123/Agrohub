<?php
require_once 'c:/xampp/htdocs/Agrohub/php/config.php';
$conn = getDBConnection();
$res = $conn->query("SELECT id, start_date, end_date, status FROM rental_requests WHERE equipment_name LIKE '%john degree%'");
$out = [];
while($row = $res->fetch_assoc()) {
    $out[] = $row;
}
file_put_contents('c:/xampp/htdocs/Agrohub/php/check_out.txt', json_encode($out, JSON_PRETTY_PRINT));
echo "Done";
?>
