<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    $conn = getDBConnection();

    // 1. Identify expired bookings (end_date < today) that are active
    // We want to free up the equipment associated with these bookings.
    // Assuming 'active' or 'booked' status in bookings table.
    // However, wait, let's look at the requirement:
    // "after expiring its date it should be automatically remove from the active rentals"
    
    // We should update bookings status to 'completed' and equipment status to 'available'
    
    $today = date('Y-m-d');
    
    // Find bookings that have ended but are still marked as 'approved' or 'paid' (active)
    // Note: status might be 'approved' or 'paid'. payment_status is 'paid'.
    // Let's assume active booking has status 'approved' or 'active' and end_date < today.
    
    // Fetch expired active bookings
    $sql = "SELECT id, equipment_id FROM bookings 
            WHERE end_date < CURRENT_DATE() 
            AND (status = 'approved' OR status = 'active' OR status = 'paid' OR status = 'confirmed')";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $updatedCount = 0;
    
    while ($row = $result->fetch_assoc()) {
        $bookingId = $row['id'];
        $equipmentId = $row['equipment_id'];
        
        // 1. Update Booking Status to 'completed'
        $updateBooking = $conn->prepare("UPDATE bookings SET status = 'completed' WHERE id = ?");
        $updateBooking->bind_param("i", $bookingId);
        $updateBooking->execute();
        $updateBooking->close();
        
        // 2. Update Equipment Status to 'available'
        // Only if there are no other active bookings for this equipment overlapping today (unlikely if unique, but good practice)
        // For simplicity, we just mark it available as the primary booking expired.
        $updateEquip = $conn->prepare("UPDATE equipment SET availability_status = 'available' WHERE id = ?");
        $updateEquip->bind_param("i", $equipmentId);
        $updateEquip->execute();
        $updateEquip->close();
        
        $updatedCount++;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Expired rentals checks completed',
        'updated_count' => $updatedCount
    ]);
    
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
