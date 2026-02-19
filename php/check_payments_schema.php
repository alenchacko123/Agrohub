<?php
require_once 'config.php';
$conn = getDBConnection();
$result = $conn->query("DESCRIBE payments");
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
?>
