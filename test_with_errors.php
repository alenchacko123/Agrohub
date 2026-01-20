<?php
$data = json_encode([
    'equipment_id' => 8,
    'equipment_name' => 'Test Equipment',
    'farmer_id' => 8,
    'farmer_name' => 'Test Farmer',
    'farmer_email' => 'testfarmer@agrohub.com',
    'owner_id' => 5,
    'start_date' => '2026-01-21',
    'end_date' => '2026-01-25',
    'num_days' => 5,
    'total_amount' => 2500,
    'delivery_address' => 'Test Farm',
    'need_operator' => 0,
    'need_insurance' => 0,
    'special_requirements' => ''
]);

echo "Testing with data:\n" . print_r(json_decode($data, true), true) . "\n\n";

$ch = curl_init('http://localhost/Agrohub/php/test_submit_rental.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response:\n$response\n\n";

$result = json_decode($response, true);
if ($result) {
    print_r($result);
    if (isset($result['success']) && $result['success']) {
        echo "\n✅ SUCCESS! Request ID: " . $result['request_id'] . "\n";
    } else {
        echo "\n❌ ERROR: " . ($result['error'] ?? 'Unknown') . "\n";
        if (isset($result['trace'])) {
            echo "Trace:\n" . $result['trace'] . "\n";
        }
    }
}
?>
