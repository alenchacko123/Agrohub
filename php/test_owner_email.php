<?php
/**
 * test_owner_email.php  —  diagnostic script
 * Visit: http://localhost/Agrohub/php/test_owner_email.php
 * Traces exactly where the owner-signed email fails.
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/config.php';

echo "=== SMTP CONFIG ===\n";
echo "SMTP_USER : " . (defined('SMTP_USER') ? SMTP_USER : 'NOT DEFINED') . "\n";
echo "SMTP_PASS : " . (defined('SMTP_PASS') ? (strlen(SMTP_PASS) . ' chars') : 'NOT DEFINED') . "\n";
echo "SMTP_FROM : " . (defined('SMTP_FROM') ? SMTP_FROM : 'NOT DEFINED') . "\n\n";

echo "=== DB CONNECTION ===\n";
$conn = getDBConnection();
echo "Connected: OK\n\n";

echo "=== FINDING A SIGNED AGREEMENT ===\n";
// Find a booking with a farmer who has email
$sql = "SELECT b.id as booking_id, b.farmer_id, b.request_id,
               uf.name as farmer_name, uf.email as farmer_email,
               uo.name as owner_name, e.name as eq_name
        FROM bookings b
        JOIN users uf ON uf.id = b.farmer_id
        JOIN equipment e ON e.id = b.equipment_id
        JOIN users uo ON uo.id = e.owner_id
        WHERE uf.email IS NOT NULL AND uf.email != ''
        LIMIT 1";
$res = $conn->query($sql);
if (!$res || $res->num_rows === 0) {
    echo "ERROR: No bookings with farmer email found.\n";
    exit;
}
$row = $res->fetch_assoc();
echo "booking_id   : " . $row['booking_id'] . "\n";
echo "farmer_name  : " . $row['farmer_name'] . "\n";
echo "farmer_email : " . $row['farmer_email'] . "\n";
echo "owner_name   : " . $row['owner_name'] . "\n";
echo "eq_name      : " . $row['eq_name'] . "\n\n";

echo "=== LOADING EMAIL HELPER ===\n";
require_once __DIR__ . '/send_agreement_email.php';
echo "Loaded OK\n\n";

echo "=== SENDING TEST EMAIL ===\n";
$result = sendOwnerSignedEmail(
    $row['farmer_email'],
    $row['farmer_name'],
    $row['owner_name'],
    $row['eq_name'],
    'AGR-' . $row['booking_id'],
    date('Y-m-d H:i:s')
);

echo "Result: " . ($result ? "SUCCESS ✓" : "FAILED ✗") . "\n\n";

echo "=== SMTP LOG (smtp_error_log.txt) ===\n";
$logFile = __DIR__ . '/../smtp_error_log.txt';
if (file_exists($logFile)) {
    // Show last 3000 chars of log
    $log = file_get_contents($logFile);
    echo substr($log, -3000) . "\n";
} else {
    echo "(no smtp_error_log.txt found)\n";
}
?>
