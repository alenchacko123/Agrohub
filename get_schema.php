<?php
require_once 'php/config.php';
$conn = getDBConnection();
$res = $conn->query("DESCRIBE notifications");
$out = "";
while($row = $res->fetch_assoc()) {
    $out .= $row['Field'] . " - " . $row['Type'] . "\n";
}
file_put_contents('notifications_schema.txt', $out);
?>
