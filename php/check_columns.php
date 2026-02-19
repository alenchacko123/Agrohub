<?php
header('Content-Type: application/json');
require_once 'config.php';
$conn = getDBConnection();
$tables = ['rental_requests'];
$result = [];
foreach ($tables as $table) {
    try {
        $cols = $conn->query("SHOW COLUMNS FROM $table");
        $c = [];
        if ($cols) {
            while ($row = $cols->fetch_assoc()) {
                $c[] = $row['Field'];
            }
        }
        $result[$table] = $c;
    } catch (Exception $e) {
        $result[$table] = $e->getMessage();
    }
}
echo json_encode($result);
?>
