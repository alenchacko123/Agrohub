<?php
require_once 'php/config.php';

$conn = getDBConnection();
$result = $conn->query('DESCRIBE job_applications');

echo "job_applications table structure:\n\n";
while($row = $result->fetch_assoc()) {
    echo "Column: " . $row['Field'] . " | Type: " . $row['Type'] . "\n";
}

$conn->close();
?>
