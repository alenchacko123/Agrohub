<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config.php';
$conn = getDBConnection();

$sql = "SELECT id, farmer_id, equipment_name, status, agreement_status FROM rental_requests WHERE equipment_name LIKE '%mahindra%'";
$result = $conn->query($sql);
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
echo json_encode($data, JSON_PRETTY_PRINT);
?>
