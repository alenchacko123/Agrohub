<?php
header('Content-Type: application/json');
require_once 'config.php';
$conn = getDBConnection();

// Fetch requests with equipment name like 'mahindra' to see their status
$sql = "SELECT r.id, r.equipment_name, r.status, r.agreement_status, 
               b.status as booking_tbl_status, b.payment_status, b.agreement_status as booking_agreement_status,
               a.status as agreement_tbl_status, a.farmer_signature, a.owner_signature_data
        FROM rental_requests r
        LEFT JOIN bookings b ON r.id = b.request_id
        LEFT JOIN agreements a ON r.id = a.rental_request_id
        WHERE r.equipment_name LIKE '%mahindra%'";

$result = $conn->query($sql);
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
echo json_encode($data, JSON_PRETTY_PRINT);
?>
