<?php
// Admin Recent Activity Feed
// Aggregates latest events from: users, bookings, rental_requests, job_postings, payments
require_once dirname(__DIR__) . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $conn = getDBConnection();
    $activities = [];

    // ── Helper: check table exists ──────────────────────────────
    function tableExists($conn, $name) {
        return $conn->query("SHOW TABLES LIKE '$name'")->num_rows > 0;
    }

    // ── 1. New User Registrations ──────────────────────────────
    if (tableExists($conn, 'users')) {
        $res = $conn->query("SELECT id, name, email, user_type, created_at
                             FROM users ORDER BY created_at DESC LIMIT 8");
        while ($row = $res->fetch_assoc()) {
            $activities[] = [
                'type'    => 'user',
                'icon'    => 'person_add',
                'color'   => '#7c3aed',
                'title'   => 'New user registered',
                'detail'  => $row['name'] . ' (' . ucfirst($row['user_type']) . ')',
                'sub'     => $row['email'],
                'time'    => $row['created_at'],
            ];
        }
    }

    // ── 2. New Bookings (Rental Confirmations) ─────────────────
    if (tableExists($conn, 'bookings') && tableExists($conn, 'equipment')) {
        $hasPaidAt = $conn->query("SHOW COLUMNS FROM bookings LIKE 'paid_at'")->num_rows > 0;
        $timeCol   = $hasPaidAt ? "COALESCE(b.paid_at, b.created_at)" : "b.created_at";

        $res = $conn->query("SELECT b.id, e.equipment_name, u.name AS farmer_name,
                                    b.total_amount, b.status,
                                    $timeCol AS event_time
                             FROM bookings b
                             LEFT JOIN equipment e ON b.equipment_id = e.id
                             LEFT JOIN users u     ON b.farmer_id    = u.id
                             ORDER BY event_time DESC LIMIT 8");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $activities[] = [
                    'type'   => 'booking',
                    'icon'   => 'handshake',
                    'color'  => '#10b981',
                    'title'  => 'Rental booked',
                    'detail' => ($row['equipment_name'] ?? 'Equipment') . ' by ' . ($row['farmer_name'] ?? 'Farmer'),
                    'sub'    => '₹' . number_format(floatval($row['total_amount']), 0) . '  •  Status: ' . ucfirst($row['status'] ?? 'active'),
                    'time'   => $row['event_time'],
                ];
            }
        }
    }

    // ── 3. Rental Requests (Pending) ───────────────────────────
    if (tableExists($conn, 'rental_requests') && tableExists($conn, 'equipment')) {
        $res = $conn->query("SELECT rr.id, e.equipment_name, u.name AS farmer_name,
                                    rr.status, rr.created_at
                             FROM rental_requests rr
                             LEFT JOIN equipment e ON rr.equipment_id = e.id
                             LEFT JOIN users u     ON rr.farmer_id    = u.id
                             WHERE rr.status IN ('pending','pending_payment')
                             ORDER BY rr.created_at DESC LIMIT 5");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $activities[] = [
                    'type'   => 'request',
                    'icon'   => 'pending_actions',
                    'color'  => '#f59e0b',
                    'title'  => 'Rental request pending',
                    'detail' => ($row['equipment_name'] ?? 'Equipment') . ' — ' . ($row['farmer_name'] ?? 'Farmer'),
                    'sub'    => 'Awaiting payment / approval',
                    'time'   => $row['created_at'],
                ];
            }
        }
    }

    // ── 4. New Job Postings ────────────────────────────────────
    if (tableExists($conn, 'job_postings')) {
        $res = $conn->query("SELECT j.id, j.job_title, j.location, j.status,
                                    u.name AS posted_by, j.created_at
                             FROM job_postings j
                             LEFT JOIN users u ON j.farmer_id = u.id
                             ORDER BY j.created_at DESC LIMIT 5");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $activities[] = [
                    'type'   => 'job',
                    'icon'   => 'work',
                    'color'  => '#3b82f6',
                    'title'  => 'Job posted',
                    'detail' => ($row['job_title'] ?? 'Job') . ' in ' . ($row['location'] ?? ''),
                    'sub'    => 'By ' . ($row['posted_by'] ?? 'Farmer') . '  •  ' . ucfirst($row['status'] ?? 'open'),
                    'time'   => $row['created_at'],
                ];
            }
        }
    }

    // ── 5. Recent Payments ─────────────────────────────────────
    if (tableExists($conn, 'payments')) {
        $hasUserId = $conn->query("SHOW COLUMNS FROM payments LIKE 'user_id'")->num_rows > 0;
        $join      = $hasUserId ? "LEFT JOIN users u ON p.user_id = u.id" : "";
        $nameCol   = $hasUserId ? "u.name AS payer_name" : "NULL AS payer_name";

        $res = $conn->query("SELECT p.id, p.amount, p.status, p.created_at, $nameCol
                             FROM payments p $join
                             ORDER BY p.created_at DESC LIMIT 5");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $sc = in_array(strtolower($row['status']), ['success','paid','captured','completed']) ? '#10b981' : '#f59e0b';
                $activities[] = [
                    'type'   => 'payment',
                    'icon'   => 'payments',
                    'color'  => $sc,
                    'title'  => 'Payment ' . ucfirst($row['status'] ?? 'received'),
                    'detail' => '₹' . number_format(floatval($row['amount']), 0) . ' transaction',
                    'sub'    => $row['payer_name'] ? 'From ' . $row['payer_name'] : '',
                    'time'   => $row['created_at'],
                ];
            }
        }
    }

    // ── Sort all activities by time DESC, return latest 20 ─────
    usort($activities, fn($a, $b) => strtotime($b['time'] ?? '0') - strtotime($a['time'] ?? '0'));
    $activities = array_slice($activities, 0, 20);

    echo json_encode(['success' => true, 'activities' => $activities, 'count' => count($activities)]);
    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
