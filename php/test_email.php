<?php
require_once 'config.php';
require_once 'send_agreement_email.php';

$to = SMTP_USER; // Send to self
$subject = "AgroHub SMTP Test";
$body = "<h1>SMTP Test</h1><p>If you see this, your SMTP settings in config.php are working correctly!</p>";

echo "Testing SMTP to $to...\n";
$result = sendAgreementEmail($to, "Test User", $subject, $body);

if ($result) {
    echo "SUCCESS: Email sent!\n";
} else {
    echo "FAILED: Email could not be sent. Check smtp_error_log.txt for details.\n";
}
?>
