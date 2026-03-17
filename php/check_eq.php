<?php
require 'config.php';
$conn = getDBConnection();
$res = $conn->query("SHOW COLUMNS FROM equipment");
while($r = $res->fetch_assoc()) echo $r['Field'] . "\n";
?>
