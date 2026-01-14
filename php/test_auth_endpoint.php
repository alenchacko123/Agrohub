<?php
/**
 * Direct test of auth.php with owner userType
 */

// Simulate a POST request to auth.php?action=google-auth
$testData = [
    'credential' => 'fake.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IlJlYWwgT3duZXIgVGVzdCIsImVtYWlsIjoid  nJlYWxvd25lckBnbWFpbC5jb20iLCJwaWN0dXJlIjoiaHR0cHM6Ly92aWEucGxhY2Vob2xkZXIuY29tLzE1MCJ9.signature',
    'userType' => 'owner'
];

echo "Testing auth.php endpoint with owner userType...\n\n";
echo "Request data:\n";
echo json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";

$url = 'http://localhost/Agrohub/php/auth.php?action=google-auth';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status Code: " . $httpCode . "\n";
echo "Response:\n";
echo $response . "\n";

// Check if owner was created
require_once 'config.php';
$conn = getDBConnection();
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'owner'");
$row = $result->fetch_assoc();
echo "\nTotal owners in database: " . $row['count'] . "\n";
?>
