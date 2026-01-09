<?php
require_once 'config.php';
$conn = getDBConnection();
$result = $conn->query("DESCRIBE users");
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
$conn->close();
?>
