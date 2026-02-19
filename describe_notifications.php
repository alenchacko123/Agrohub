<?php
require_once 'php/config.php';
$conn = getDBConnection();
$res = $conn->query("DESCRIBE notifications");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
