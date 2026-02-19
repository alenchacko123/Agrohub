<?php
header('Content-Type: application/json');
require_once 'config.php';
$conn = getDBConnection();

$response = [];

// 1. Add is_dismissed to rental_requests
try {
    $conn->query("ALTER TABLE rental_requests ADD COLUMN is_dismissed TINYINT(1) DEFAULT 0");
    $response[] = "Added is_dismissed to rental_requests";
} catch (Exception $e) {
    $response[] = "rental_requests: " . $e->getMessage();
}

// 2. Add is_deleted to notifications
try {
    $conn->query("ALTER TABLE notifications ADD COLUMN is_deleted TINYINT(1) DEFAULT 0");
    $response[] = "Added is_deleted to notifications";
} catch (Exception $e) {
    $response[] = "notifications: " . $e->getMessage();
}

echo json_encode($response);
?>
