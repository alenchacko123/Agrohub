<?php
// Test the new submit_booking.php endpoint
$data = json_encode([
    'equipment_id' => 8,
    'farmer_id' => 8,
    'farmer_name' => 'Test Farmer',
    'start_date' => '2026-01-22',
    'end_date' => '2026-01-26',
    'total_amount' => 5000
]);

echo "Testing submit_booking.php\n";
echo "Data: " . $data . "\n\n";

$ch = curl_init('http://localhost/Agrohub/php/submit_booking.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n\n";

$result = json_decode($response, true);
if ($result) {
    if (isset($result['success']) && $result['success']) {
        echo "✅ SUCCESS! Booking ID: " . $result['booking_id'] . "\n";
    } else {
        echo "❌ ERROR: " . ($result['error'] ?? 'Unknown') . "\n";
    }
} else {
    echo "❌ Failed to parse JSON\n";
}
?>
