<?php
// check_signature_data.php
// Check signature data for bookings

// Simulate farmer login
$_GET['farmer_id'] = 12; // Use a known farmer ID

ob_start();
include 'php/get_bookings.php';
$output = ob_get_clean();

$json = json_decode($output, true);

if ($json && isset($json['bookings'])) {
    echo "Found " . count($json['bookings']) . " bookings.\n";
    foreach ($json['bookings'] as $b) {
        $sigData = isset($b['signature_data']) ? substr($b['signature_data'], 0, 50) . '...' : 'NULL';
        $sigType = $b['signature_type'] ?? 'NULL';
        $status = $b['payment_status'];
        echo "ID: {$b['id']}, PayStatus: $status, SigType: $sigType, SigData: $sigData\n";
    }
} else {
    echo "Failed to fetch bookings or no bookings found.\n";
    echo "Output: " . substr($output, 0, 100) . "\n";
}
?>
