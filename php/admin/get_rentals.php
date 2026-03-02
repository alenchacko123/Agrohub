<?php
// Get All Rental Transactions for Admin Dashboard
require_once dirname(__DIR__) . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $conn = getDBConnection();

    $today = date('Y-m-d');

    // Auto-update expired bookings status
    $conn->query("UPDATE bookings SET status = 'completed' WHERE end_date < '$today' AND status IN ('active', 'confirmed', 'paid')");

    // ─────────────────────────────────────────────────────
    // 1. BOOKINGS (confirmed / paid / completed rentals)
    //    Join with equipment and users to get names
    // ─────────────────────────────────────────────────────
    $sqlBookings = "
        SELECT
            b.id,
            'booking'               AS source,
            e.equipment_name,
            u_farmer.name           AS farmer_name,
            u_farmer.email          AS farmer_email,
            u_owner.name            AS owner_name,
            b.start_date,
            b.end_date,
            COALESCE(b.total_amount, 0) AS total_amount,
            b.status,
            b.payment_status,
            b.created_at
        FROM bookings b
        LEFT JOIN equipment  e        ON b.equipment_id = e.id
        LEFT JOIN users      u_farmer ON b.farmer_id   = u_farmer.id
        LEFT JOIN users      u_owner  ON e.owner_id    = u_owner.id
        ORDER BY b.created_at DESC
    ";

    // ─────────────────────────────────────────────────────
    // 2. RENTAL REQUESTS (pending / rejected requests)
    //    Only include those NOT already converted to a booking
    // ─────────────────────────────────────────────────────
    $sqlRequests = "
        SELECT
            rr.id,
            'request'               AS source,
            e.equipment_name,
            u_farmer.name           AS farmer_name,
            u_farmer.email          AS farmer_email,
            u_owner.name            AS owner_name,
            rr.start_date,
            rr.end_date,
            COALESCE(rr.total_amount, 0) AS total_amount,
            rr.status,
            NULL                    AS payment_status,
            rr.created_at
        FROM rental_requests rr
        LEFT JOIN equipment  e        ON rr.equipment_id = e.id
        LEFT JOIN users      u_farmer ON rr.farmer_id   = u_farmer.id
        LEFT JOIN users      u_owner  ON rr.owner_id    = u_owner.id
        WHERE rr.status NOT IN ('approved', 'completed')
        ORDER BY rr.created_at DESC
    ";

    $bookings_result = $conn->query($sqlBookings);
    $requests_result = $conn->query($sqlRequests);

    if (!$bookings_result) {
        throw new Exception("Bookings query failed: " . $conn->error);
    }
    if (!$requests_result) {
        throw new Exception("Requests query failed: " . $conn->error);
    }

    $bookings = $bookings_result->fetch_all(MYSQLI_ASSOC);
    $requests = $requests_result->fetch_all(MYSQLI_ASSOC);

    // ─── Normalise status labels & colors ────────────────
    foreach ($bookings as &$b) {
        $b['total_amount'] = floatval($b['total_amount']);
        $status = strtolower($b['status'] ?? 'unknown');
        if ($status === 'completed' || ($b['end_date'] && $b['end_date'] < $today)) {
            $b['status_label'] = 'Completed';
            $b['status_color'] = '#10b981';
        } elseif ($status === 'active' || $status === 'confirmed' || $status === 'paid') {
            $b['status_label'] = 'Active';
            $b['status_color'] = '#3b82f6';
        } elseif ($status === 'pending') {
            $b['status_label'] = 'Pending';
            $b['status_color'] = '#f59e0b';
        } elseif ($status === 'cancelled' || $status === 'canceled') {
            $b['status_label'] = 'Cancelled';
            $b['status_color'] = '#ef4444';
        } else {
            $b['status_label'] = ucfirst($status);
            $b['status_color'] = '#6b7280';
        }
    }
    unset($b);

    foreach ($requests as &$r) {
        $r['total_amount'] = floatval($r['total_amount']);
        $status = strtolower($r['status'] ?? 'pending');
        if ($status === 'pending' || $status === 'pending_payment') {
            $r['status_label'] = 'Pending';
            $r['status_color'] = '#f59e0b';
        } elseif ($status === 'rejected') {
            $r['status_label'] = 'Rejected';
            $r['status_color'] = '#ef4444';
        } else {
            $r['status_label'] = ucfirst($status);
            $r['status_color'] = '#6b7280';
        }
    }
    unset($r);

    $all = array_merge($bookings, $requests);

    // Sort all by created_at DESC
    usort($all, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

    echo json_encode([
        'success'          => true,
        'rentals'          => $all,
        'total'            => count($all),
        'booking_count'    => count($bookings),
        'request_count'    => count($requests),
    ]);

    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
