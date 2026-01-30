<?php
require_once 'php/config.php';

function describeTable($conn, $tableName) {
    echo "\nTable: $tableName\n";
    $result = $conn->query("DESCRIBE $tableName");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } else {
        echo "Table not found or error: " . $conn->error . "\n";
    }
}

$conn = getDBConnection();
describeTable($conn, 'rental_requests');
describeTable($conn, 'bookings');
$conn->close();
?>
