<?php
// Auto-expire rentals logic
// This script checks for expired rentals and updates their status
// It is intended to be included in other scripts where $conn is available

if (isset($conn) && $conn instanceof mysqli) {
    if (!isset($updatedCount)) {
        $updatedCount = 0;
    }

    try {
        // 1. Identify expired bookings (end_date < CURRENT_DATE) that are still active
        // We catch everything that is NOT clearly finished to be safe (including empty strings)
        $expiredSql = "SELECT * FROM bookings 
                      WHERE end_date < CURRENT_DATE() 
                      AND (status IS NULL OR status NOT IN ('completed', 'cancelled', 'rejected', 'declined'))";
        
        $stmtExp = $conn->prepare($expiredSql);
        if ($stmtExp) {
            $stmtExp->execute();
            $resultExp = $stmtExp->get_result();
            
            while ($row = $resultExp->fetch_assoc()) {
                $bookingId = $row['id'];
                $equipmentId = $row['equipment_id'];
                
                // 1. Update Booking Status to 'completed' and agreement_status to 'expired'
                $updateBooking = $conn->prepare("UPDATE bookings SET status = 'completed', agreement_status = 'expired' WHERE id = ?");
                if ($updateBooking) {
                    $updateBooking->bind_param("i", $bookingId);
                    $updateBooking->execute();
                    $updateBooking->close();
                }
                
                // 2. Update Equipment Status to 'available'
                // This makes it show as "Available" in rent-equipment.html
                $updateEquip = $conn->prepare("UPDATE equipment SET availability_status = 'available' WHERE id = ?");
                if ($updateEquip) {
                    $updateEquip->bind_param("i", $equipmentId);
                    $updateEquip->execute();
                    $updateEquip->close();
                }

                // 3. Also update rental_requests status if applicable
                if (isset($row['request_id']) && !empty($row['request_id'])) {
                     $reqId = $row['request_id'];
                     
                     // Update rental request
                     $updateReq = $conn->prepare("UPDATE rental_requests SET status = 'completed', agreement_status = 'expired' WHERE id = ?");
                     if ($updateReq) {
                         $updateReq->bind_param("i", $reqId);
                         $updateReq->execute();
                         $updateReq->close();
                     }
                     
                     // Update agreement status in agreements table
                     // agreements table links via rental_request_id
                     $updateAgr = $conn->prepare("UPDATE agreements SET status = 'expired' WHERE rental_request_id = ?");
                     if ($updateAgr) {
                         $updateAgr->bind_param("i", $reqId);
                         $updateAgr->execute();
                         $updateAgr->close();
                     }
                }
                
                $updatedCount++;
            }
            $stmtExp->close();
        }
    } catch (Exception $e) {
        // Silently fail or log error, don't break the parent script
        error_log("Error in expire_rentals.php: " . $e->getMessage());
    }
}
?>
