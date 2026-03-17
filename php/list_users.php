<?php
require 'config.php';
$conn = getDBConnection();
$res = $conn->query("SELECT id, name, email FROM users");
while($r = $res->fetch_assoc()) {
    echo "ID: " . $r['id'] . " | Name: " . $r['name'] . " | Email: " . $r['email'] . "\n";
}
?>
