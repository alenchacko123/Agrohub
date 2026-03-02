<?php
require_once 'php/config.php';
$res = $conn->query("DESCRIBE bookings");
$out = "Table: bookings\n";
while ($row = $res->fetch_assoc()) {
    $out .= "  {$row['Field']} - {$row['Type']}\n";
}
file_put_contents('bookings_schema.txt', $out);
?>
