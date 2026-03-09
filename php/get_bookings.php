<?php
header('Content-Type: application/json');
require_once 'config.php';

// Get database connection
$conn = getDBConnection();

try {
    $owner_id = isset($_GET['owner_id']) ? intval($_GET['owner_id']) : 0;
    $farmer_id = isset($_GET['farmer_id']) ? intval($_GET['farmer_id']) : 0;
    $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
    
    if ($owner_id === 0 && $farmer_id === 0) {
        throw new Exception('Owner ID or Farmer ID is required');
    }


    // Auto-update expired bookings
    $updateSql = "UPDATE bookings SET status = 'completed' WHERE end_date < CURDATE() AND status IN ('active', 'confirmed', 'paid')";
    $conn->query($updateSql);

    // Build the SQL query - now includes signature data and more status fields
    $sql = "SELECT 
                b.id,
                b.equipment_id,
                b.farmer_id,
                u.name as farmer_name,
                u.address as farmer_address,
                u.location as farmer_location,
                u.phone as farmer_phone,
                u_owner.name as owner_name,
                u_owner.address as owner_address,
                u_owner.location as owner_location,
                u_owner.phone as owner_phone,
                b.start_date,
                b.end_date,
                b.total_amount,
                b.status,
                b.payment_status,
                b.paid_amount,
                b.paid_at,
                b.created_at,
                e.equipment_name,
                e.equipment_condition,
                DATEDIFF(b.end_date, b.start_date) as duration,
                rr_link.need_operator,
                b.insurance_plan_id,
                b.insurance_fee,
                b.insurance_status,
                b.insurance_start_date,
                b.insurance_end_date,
                ip.plan_name as insurance_plan_name,
                ip.coverage_amount as insurance_coverage_amount";
    
    // Add conditional columns (only if they exist)
    $checkTransactionId = $conn->query("SHOW COLUMNS FROM bookings LIKE 'transaction_id'");
    if ($checkTransactionId && $checkTransactionId->num_rows > 0) {
        $sql .= ", b.transaction_id";
    }
    
    $checkRentalStatus = $conn->query("SHOW COLUMNS FROM bookings LIKE 'rental_status'");
    if ($checkRentalStatus && $checkRentalStatus->num_rows > 0) {
        $sql .= ", b.rental_status";
    }
    
    $checkAgreementStatus = $conn->query("SHOW COLUMNS FROM bookings LIKE 'agreement_status'");
    if ($checkAgreementStatus && $checkAgreementStatus->num_rows > 0) {
        $sql .= ", b.agreement_status";
    }
    
    // Check if bookings has request_id column to link with agreements
    $checkRequestId = $conn->query("SHOW COLUMNS FROM bookings LIKE 'request_id'");
    $hasRequestId = ($checkRequestId && $checkRequestId->num_rows > 0);

    if ($hasRequestId) {
        $sql .= ", b.request_id";
    }

    // Check if agreements table exists
    $checkAgreements = $conn->query("SHOW TABLES LIKE 'agreements'");
    $hasAgreements = ($checkAgreements && $checkAgreements->num_rows > 0);
    
    // Only select agreement columns if we can link them
    if ($hasAgreements) {
        $sql .= ", a.signature_data, a.signature_type, a.signed_at,
                   a.owner_signature_data, a.owner_signature_type, a.owner_signed_at,
                   a.status as agreement_full_status";
    }
    
    // Check if feedback table exists
    $checkFeedback = $conn->query("SHOW TABLES LIKE 'rental_feedback'");
    $hasFeedbackTable = ($checkFeedback && $checkFeedback->num_rows > 0);

    if ($hasFeedbackTable) {
        $sql .= ", rf.id as feedback_id, rf.rating as feedback_rating, rf.comment as feedback_comment, 
                   CASE WHEN rf.id IS NOT NULL THEN 1 ELSE 0 END as has_feedback";
    }

    $sql .= " FROM bookings b
            INNER JOIN equipment e ON b.equipment_id = e.id
            LEFT JOIN users u ON b.farmer_id = u.id
            LEFT JOIN users u_owner ON e.owner_id = u_owner.id
            LEFT JOIN insurance_plans ip ON b.insurance_plan_id = ip.id";
    
    // Join Feedback table
    if ($hasFeedbackTable) {
        $sql .= " LEFT JOIN rental_feedback rf ON b.id = rf.booking_id";
    }
    
    // Smart Joining for Agreements
    // We try to link via request_id first, then fallback to matching fields
    // Smart Joining for Agreements and Rental Requests (for operator info)
    // We try to link via request_id first, then fallback to matching fields
    $sql .= " LEFT JOIN rental_requests rr_link ON ";
    
    if ($hasRequestId) {
        // If request_id column exists, use it OR fallback to matching fields if null
        $sql .= "(rr_link.id = b.request_id OR (b.request_id IS NULL AND rr_link.equipment_id = b.equipment_id AND rr_link.farmer_id = b.farmer_id AND rr_link.start_date = b.start_date))";
    } else {
        // Fallback purely to matching fields if column doesn't exist
        $sql .= "(rr_link.equipment_id = b.equipment_id AND rr_link.farmer_id = b.farmer_id AND rr_link.start_date = b.start_date)";
    }
    
    // Add columns from rental requests if needed (e.g. need_operator)
    // We can't easily add to SELECT clause after constructing it, so we rely on the fact that we're joining here.
    // Wait, I need to add `rr_link.need_operator` to the SELECT clause.
    // Let me rewrite the SELECT part slightly or just assume I can add it?
    // The SELECT clause is already built. I need to restart or inject it?
    // Actually, I can just modify the query logic flow since I am editing the file.
    
    if ($hasAgreements) {
        // Then join agreements on the found request
        $sql .= " LEFT JOIN agreements a ON a.rental_request_id = rr_link.id";
    }
    
    $sql .= " WHERE ";
    
    // Add owner_id or farmer_id filter
    if ($owner_id > 0) {
        $sql .= "e.owner_id = ?";
    } else {
        $sql .= "b.farmer_id = ?";
    }
    
    // Add status filter if provided
    if (!empty($status_filter)) {
        $sql .= " AND b.status = ?";
    }

    
    // Validate uniqueness
    $sql .= " GROUP BY b.id";

    $sql .= " ORDER BY 
                CASE 
                    WHEN b.status = 'pending' THEN 1
                    WHEN b.status = 'approved' THEN 2
                    WHEN b.status = 'rejected' THEN 3
                    ELSE 4
                END,
                b.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    
    $id_param = $owner_id > 0 ? $owner_id : $farmer_id;
    
    if (!empty($status_filter)) {
        $stmt->bind_param("is", $id_param, $status_filter);
    } else {
        $stmt->bind_param("i", $id_param);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'bookings' => $bookings,
        'count' => count($bookings)
    ]);
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
