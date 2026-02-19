<?php
require_once 'c:/xampp/htdocs/Agrohub/php/config.php';
$conn = getDBConnection();
$res = $conn->query("SHOW COLUMNS FROM agreements");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
