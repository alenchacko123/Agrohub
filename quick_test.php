<?php
// Quick test of rental submission
$data = json_encode([
    'equipment_id' => 8,
    'equipment_name' => 'Test Equipment',
    'farmer_id' => 8,
    'farmer_name' => 'Test Farmer',
    'farmer_email' => 'testfarmer@agrohub.com',
    'owner_id' => 5,  // Valid owner ID
    'start_date' => '2026-01-21',
    'end_date' => '2026-01-25',
    'num_days' => 5,
    'total_amount' => 2500,
    'delivery_address' => 'Test Farm',
    'need_operator' => 0,
    'need_insurance' => 0,
    'special_requirements' => ''
]);

$ch = curl_init('http://localhost/Agrohub/php/submit_rental_request.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";

$result = json_decode($response, true);
if ($result && isset($result['success'])) {
    if ($result['success']) {
        echo "✅ SUCCESS!\n";
    } else {
        echo "❌ ERROR: " . ($result['error'] ?? 'Unknown') . "\n";
    }
}
?>
