<?php
require_once 'php/config.php';

try {
    $conn = getDBConnection();
    
    // Delete the "indofarm harvesters" booking (ID: 10)
    $booking_id = 10;
    
    $sql = "DELETE FROM bookings WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $booking_id);
    
    if ($stmt->execute()) {
        echo "✅ Successfully deleted booking AGR-{$booking_id} (indofarm harvesters)\n";
        echo "\nBooking removed from database.";
    } else {
        echo "❌ Error deleting booking: " . $stmt->error;
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
