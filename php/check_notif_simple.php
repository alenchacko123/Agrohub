<?php
header('Content-Type: text/plain');
require_once 'config.php';
$conn = getDBConnection();

echo "Rental Requests:\n";
$res = $conn->query("SHOW COLUMNS FROM rental_requests");
while ($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}

echo "\nNotifications:\n";
$res2 = $conn->query("SHOW COLUMNS FROM notifications");
if ($res2) {
    while ($row = $res2->fetch_assoc()) {
        echo $row['Field'] . "\n";
    }
} else {
    echo "Table notifications not found\n";
}
?>
