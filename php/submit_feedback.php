<?php
/**
 * submit_feedback.php
 * Saves 5-question star rating feedback from farmer and sends email to owner.
 * Supports both confirmed bookings (bookings table) and rental requests (rental_requests table).
 */
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once __DIR__ . '/config.php';

$response = ['success' => false, 'error' => 'Unknown error'];

try {
    $conn = getDBConnection();

    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!$data) throw new Exception('Invalid JSON input');

    // Determine record type: 'booking' (confirmed booking) or 'request' (rental request)
    $record_type = $data['record_type'] ?? 'booking';
    $booking_id  = intval($data['booking_id'] ?? 0);
    $request_id  = intval($data['request_id'] ?? 0);
    $farmer_id   = intval($data['farmer_id']  ?? 0);

    if ($booking_id === 0 && $request_id === 0) {
        throw new Exception('Missing booking_id or request_id');
    }
    if (!$farmer_id) throw new Exception('Missing farmer_id');

    // Star ratings (1–5 each)
    $q1 = max(1, min(5, intval($data['q1_condition']     ?? 0)));
    $q2 = max(1, min(5, intval($data['q2_performance']   ?? 0)));
    $q3 = max(1, min(5, intval($data['q3_value']         ?? 0)));
    $q4 = max(1, min(5, intval($data['q4_communication'] ?? 0)));
    $q5 = max(1, min(5, intval($data['q5_recommend']     ?? 0)));

    if (!$q1 || !$q2 || !$q3 || !$q4 || !$q5) {
        throw new Exception('Please answer all 5 questions');
    }

    $comments = trim($data['additional_comments'] ?? '');
    $overall  = round(($q1 + $q2 + $q3 + $q4 + $q5) / 5, 2);

    // ── Fetch rental info ────────────────────────────────────────
    if ($record_type === 'request' && $request_id > 0) {
        // Rental request path
        $stmt = $conn->prepare("
            SELECT rr.farmer_id, e.owner_id, e.equipment_name,
                   uf.name AS farmer_name, uf.email AS farmer_email,
                   uo.name AS owner_name,  uo.email AS owner_email
            FROM rental_requests rr
            JOIN equipment e ON rr.equipment_id = e.id
            JOIN users uf    ON rr.farmer_id    = uf.id
            JOIN users uo    ON e.owner_id      = uo.id
            WHERE rr.id = ?
        ");
        $stmt->bind_param('i', $request_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) throw new Exception('Rental request not found (id=' . $request_id . ')');
        $record = $res->fetch_assoc();
        $stmt->close();
        // Offset request IDs to avoid collision with booking IDs in unique key
        $booking_id_for_db = $request_id + 1000000;
        $display_id        = $request_id;

    } else {
        // Confirmed booking path (default)
        $stmt = $conn->prepare("
            SELECT b.farmer_id, e.owner_id, e.equipment_name,
                   uf.name AS farmer_name, uf.email AS farmer_email,
                   uo.name AS owner_name,  uo.email AS owner_email
            FROM bookings b
            JOIN equipment e ON b.equipment_id = e.id
            JOIN users uf    ON b.farmer_id    = uf.id
            JOIN users uo    ON e.owner_id     = uo.id
            WHERE b.id = ?
        ");
        $stmt->bind_param('i', $booking_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) throw new Exception('Booking not found (id=' . $booking_id . ')');
        $record = $res->fetch_assoc();
        $stmt->close();
        $booking_id_for_db = $booking_id;
        $display_id        = $booking_id;
    }

    // Verify farmer matches
    if ((int)$record['farmer_id'] !== $farmer_id) {
        throw new Exception('Unauthorized: you are not the farmer for this rental');
    }

    $owner_id       = (int)$record['owner_id'];
    $equipment_name = $record['equipment_name'];
    $farmer_name    = $record['farmer_name'];
    $owner_name     = $record['owner_name'];
    $owner_email    = $record['owner_email'];

    // ── Save feedback to DB ──────────────────────────────────────
    $ins = $conn->prepare("
        INSERT INTO rental_feedback
            (booking_id, farmer_id, owner_id, equipment_name,
             q1_condition, q2_performance, q3_value, q4_communication, q5_recommend,
             overall_rating, additional_comments)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            q1_condition=VALUES(q1_condition),
            q2_performance=VALUES(q2_performance),
            q3_value=VALUES(q3_value),
            q4_communication=VALUES(q4_communication),
            q5_recommend=VALUES(q5_recommend),
            overall_rating=VALUES(overall_rating),
            additional_comments=VALUES(additional_comments),
            created_at=CURRENT_TIMESTAMP
    ");
    $ins->bind_param('iiisiiiiids',
        $booking_id_for_db, $farmer_id, $owner_id, $equipment_name,
        $q1, $q2, $q3, $q4, $q5,
        $overall, $comments
    );
    if (!$ins->execute()) throw new Exception('DB insert error: ' . $ins->error);
    $ins->close();

    // ── Send feedback email to owner ─────────────────────────────
    if (!empty($owner_email)) {
        require_once __DIR__ . '/send_agreement_email.php';
        sendFeedbackEmail(
            $owner_email, $owner_name,
            $farmer_name, $equipment_name, $display_id,
            $q1, $q2, $q3, $q4, $q5,
            $overall, $comments
        );
    }

    $response = [
        'success'        => true,
        'message'        => 'Feedback submitted! Thank you.',
        'overall_rating' => $overall
    ];

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
    error_log('[submit_feedback] ' . $e->getMessage());
}

if (ob_get_length()) ob_end_clean();
echo json_encode($response);
?>
