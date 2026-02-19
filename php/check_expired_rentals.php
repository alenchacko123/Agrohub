<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    $conn = getDBConnection();

    // 1. Identify expired bookings (end_date < today) that are active
    // We want to free up the equipment associated with these bookings.
    // Assuming 'active' or 'booked' status in bookings table.
    // However, wait, let's look at the requirement:
    // Use centralized logic
    $updatedCount = 0;
    require 'expire_rentals.php';
    
    echo json_encode([
        'success' => true,
        'message' => 'Expired rentals checks completed',
        'updated_count' => $updatedCount
    ]);
    
    $conn->close();

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
