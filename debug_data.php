<?php
require_once 'php/config.php';
$conn = getDBConnection();

$tables = ['bookings', 'rental_requests', 'equipment'];
foreach ($tables as $table) {
    echo "--- Table: $table ---\n";
    $res = $conn->query("SELECT * FROM $table");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            print_r($row);
        }
    } else {
        echo "Error: " . $conn->error . "\n";
    }
    echo "\n";
}
