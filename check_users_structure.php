<?php
require_once 'php/config.php';
$conn = getDBConnection();
$result = $conn->query('DESCRIBE users');
if ($result) {
    while($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
} else {
    echo "Error: " . $conn->error;
}
?>
