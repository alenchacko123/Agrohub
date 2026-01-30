<?php
require_once 'php/config.php';

$conn = getDBConnection();
$result = $conn->query("SHOW COLUMNS FROM equipment");

$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = $row;
}
echo json_encode($columns, JSON_PRETTY_PRINT);
?>
