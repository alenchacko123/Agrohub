<?php
function logDebug($message) {
    $logFile = __DIR__ . '/debug_auth.log';
    $timestamp = date('[Y-m-d H:i:s] ');
    file_put_contents($logFile, $timestamp . $message . PHP_EOL, FILE_APPEND);
}
?>
