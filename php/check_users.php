<?php
require 'config.php';
$conn = getDBConnection();
$res = $conn->query("SHOW COLUMNS FROM users");
while($r = $res->fetch_assoc()) echo $r['Field'] . "\n";
?>
