<?php
require_once 'php/config.php';
$conn = getDBConnection();
$res = $conn->query("DESCRIBE agreements");
$out = "";
while($row = $res->fetch_assoc()) {
    $out .= $row['Field'] . " - " . $row['Type'] . "\n";
}
file_put_contents('agreements_schema.txt', $out);
?>
