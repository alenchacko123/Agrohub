<?php
require_once 'php/config.php';
$conn = getDBConnection();
$res = $conn->query("SHOW TABLES");
while($row = $res->fetch_array()) {
    echo $row[0] . "\n";
}
?>
