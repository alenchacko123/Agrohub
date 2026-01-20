<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing Rental Request Submission\n";
echo "==================================\n\n";

// Test data
$rentalData = [
    'equipment_id' => 1,
    'equipment_name' => 'Test Equipment',
    'farmer_id' => 8,
    'farmer_name' => 'Test Farmer',
    'farmer_email' => 'testfarmer@agrohub.com',
    'owner_id' => 1,
    'start_date' => '2026-01-21',
    'end_date' => '2026-01-25',
    'num_days' => 5,
    'total_amount' => 2500,
    'delivery_address' => 'Test Farm, Kerala',
    'need_operator' => 0,
    'need_insurance' => 0,
    'special_requirements' => 'Test rental request'
];

echo "Request Data:\n";
print_r($rentalData);
echo "\n";

// Initialize cURL
$url = 'http://localhost/Agrohub/php/submit_rental_request.php';
echo "Sending request to: $url\n\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($rentalData));
curl_setopt($ch, CURLOPT_VERBOSE, true);

// Execute request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Display results
echo "HTTP Status Code: $httpCode\n";
if ($curlError) {
    echo "cURL Error: $curlError\n";
}
echo "\nRaw Response:\n";
echo "Length: " . strlen($response) . " bytes\n";
echo "Content:\n" . $response . "\n";
echo "---\n\n";

// Try to parse JSON
$result = json_decode($response, true);
if ($result) {
    echo "✓ JSON Parsed Successfully\n";
    echo "Result:\n";
    print_r($result);
    
    if (isset($result['success']) && $result['success']) {
        echo "\n✅ SUCCESS! Rental request submitted with ID: " . ($result['request_id'] ?? 'unknown') . "\n";
    } else {
        echo "\n❌ ERROR: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "❌ Could not parse JSON response\n";
    echo "JSON Error: " . json_last_error_msg() . "\n";
}
?>
