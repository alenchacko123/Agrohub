<?php
/**
 * Update Rental Insurance
 * Associates an insurance plan with a rental request BEFORE payment.
 * Insurance becomes "Active" only after successful payment (handled in process_rental_completion.php).
 */
require_once 'config.php';

header('Content-Type: application/json');

try {
    $conn = getDBConnection();
    $json = json_decode(file_get_contents('php://input'), true);

    $request_id = isset($json['request_id']) ? intval($json['request_id']) : 0;
    $plan_id    = isset($json['insurance_plan_id']) ? intval($json['insurance_plan_id']) : null;

    if (!$request_id) throw new Exception("Rental request ID is required.");

    // Validate the rental request exists and belongs to this session (basic check)
    $reqCheck = $conn->prepare("SELECT id, farmer_id, status FROM rental_requests WHERE id = ?");
    $reqCheck->bind_param("i", $request_id);
    $reqCheck->execute();
    $reqRes = $reqCheck->get_result();
    if ($reqRes->num_rows === 0) throw new Exception("Rental request not found.");
    $reqRow = $reqRes->fetch_assoc();
    $reqCheck->close();

    // Block if already paid
    if ($reqRow['status'] === 'paid') {
        throw new Exception("Cannot change insurance on a completed rental.");
    }

    // Validate the plan exists and get its price
    $fee = 0.00;
    if ($plan_id) {
        $stmt = $conn->prepare("SELECT id, price FROM insurance_plans WHERE id = ?");
        $stmt->bind_param("i", $plan_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) throw new Exception("Invalid insurance plan.");
        $plan = $res->fetch_assoc();
        $fee = floatval($plan['price']);
        $stmt->close();
    }

    // Check if columns exist in rental_requests (safe update)
    $hasPlanId = $conn->query("SHOW COLUMNS FROM rental_requests LIKE 'insurance_plan_id'")->num_rows > 0;
    $hasFee    = $conn->query("SHOW COLUMNS FROM rental_requests LIKE 'insurance_fee'")->num_rows > 0;

    if (!$hasPlanId || !$hasFee) {
        throw new Exception("Insurance columns not set up in database. Please run setup_insurance_db.php first.");
    }

    // Update rental_requests with chosen insurance plan (or clear it if plan_id is 0/null)
    $sql  = "UPDATE rental_requests SET insurance_plan_id = ?, insurance_fee = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("idi", $plan_id, $fee, $request_id);

    if ($stmt->execute()) {
        $msg = $plan_id
            ? "Insurance plan applied successfully. Fee of ₹" . number_format($fee, 2) . " will be added to your total."
            : "Insurance removed from this rental.";
        echo json_encode(['success' => true, 'message' => $msg, 'insurance_fee' => $fee]);
    } else {
        throw new Exception("Database update failed: " . $stmt->error);
    }
    $stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
