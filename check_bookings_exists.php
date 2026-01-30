<?php
require_once 'php/config.php';
$conn = getDBConnection();
$res = $conn->query("SHOW TABLES LIKE 'bookings'");
if($res->num_rows > 0) {
    echo "bookings table exists.\n";
    $desc = $conn->query("DESCRIBE bookings");
    while($row = $desc->fetch_assoc()) {
        echo $row['Field'] . "\n";
    }
} else {
    echo "bookings table DOES NOT EXIST.\n";
}

echo "\nChecking rental_requests cols:\n";
$desc = $conn->query("DESCRIBE rental_requests");
while($row = $desc->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
