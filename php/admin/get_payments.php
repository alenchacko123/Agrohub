<?php
// Get All Payment Transactions for Admin – Financials View
// Sources: 1) payments table (if exists)  2) bookings table (paid records)
require_once dirname(__DIR__) . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $conn = getDBConnection();

    $payments = [];

    // ─────────────────────────────────────────────────────────────
    // SOURCE 1: dedicated `payments` table (may or may not exist)
    // ─────────────────────────────────────────────────────────────
    $paymentsTableExists = $conn->query("SHOW TABLES LIKE 'payments'")->num_rows > 0;

    if ($paymentsTableExists) {
        // Check which columns exist
        $hasBookingId     = $conn->query("SHOW COLUMNS FROM payments LIKE 'booking_id'")->num_rows    > 0;
        $hasRequestId     = $conn->query("SHOW COLUMNS FROM payments LIKE 'request_id'")->num_rows    > 0;
        $hasUserId        = $conn->query("SHOW COLUMNS FROM payments LIKE 'user_id'")->num_rows        > 0;
        $hasTxnId         = $conn->query("SHOW COLUMNS FROM payments LIKE 'transaction_id'")->num_rows > 0;
        $hasMethod        = $conn->query("SHOW COLUMNS FROM payments LIKE 'payment_method'")->num_rows > 0;

        $sel = "SELECT p.id, p.amount, p.status, p.created_at";
        $sel .= $hasMethod    ? ", p.payment_method"   : ", 'razorpay' AS payment_method";
        $sel .= $hasTxnId     ? ", p.transaction_id"   : ", NULL AS transaction_id";
        $sel .= $hasBookingId ? ", p.booking_id"       : ", NULL AS booking_id";
        $sel .= $hasUserId    ? ", u.name AS payer_name, u.email AS payer_email"
                              : ", NULL AS payer_name, NULL AS payer_email";

        $from = " FROM payments p";
        if ($hasUserId) {
            $from .= " LEFT JOIN users u ON p.user_id = u.id";
        }

        // Join bookings to get equipment name
        if ($hasBookingId) {
            $sel  .= ", e.equipment_name";
            $from .= " LEFT JOIN bookings b ON p.booking_id = b.id"
                   . " LEFT JOIN equipment e ON b.equipment_id = e.id";
        } else {
            $sel .= ", NULL AS equipment_name";
        }

        $sql = $sel . $from . " ORDER BY p.created_at DESC";
        $res = $conn->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $row['source'] = 'payments_table';
                $payments[] = $row;
            }
        }
    }

    // ─────────────────────────────────────────────────────────────
    // SOURCE 2: bookings table – rows where payment_status = 'paid'
    //           and paid_amount > 0 (not already captured above)
    // ─────────────────────────────────────────────────────────────
    $bookingsExists = $conn->query("SHOW TABLES LIKE 'bookings'")->num_rows > 0;

    if ($bookingsExists) {
        $hasPaidAmount  = $conn->query("SHOW COLUMNS FROM bookings LIKE 'paid_amount'")->num_rows  > 0;
        $hasPaidAt      = $conn->query("SHOW COLUMNS FROM bookings LIKE 'paid_at'")->num_rows      > 0;
        $hasPayStatus   = $conn->query("SHOW COLUMNS FROM bookings LIKE 'payment_status'")->num_rows > 0;
        $hasTxnIdB      = $conn->query("SHOW COLUMNS FROM bookings LIKE 'transaction_id'")->num_rows > 0;

        if ($hasPaidAmount && $hasPayStatus) {
            $bSel  = "SELECT b.id AS booking_id,";
            $bSel .= $hasPaidAmount ? " b.paid_amount AS amount,"    : " b.total_amount AS amount,";
            $bSel .= $hasPayStatus  ? " b.payment_status AS status," : " 'paid' AS status,";
            $bSel .= $hasPaidAt     ? " b.paid_at AS created_at,"    : " b.created_at AS created_at,";
            $bSel .= $hasTxnIdB     ? " b.transaction_id,"           : " NULL AS transaction_id,";
            $bSel .= " 'Razorpay / GPay' AS payment_method,";
            $bSel .= " e.equipment_name,";
            $bSel .= " u.name AS payer_name, u.email AS payer_email,";
            $bSel .= " NULL AS id"; // no payments.id for these rows

            $bFrom = " FROM bookings b"
                   . " LEFT JOIN equipment e ON b.equipment_id = e.id"
                   . " LEFT JOIN users u     ON b.farmer_id    = u.id";

            // Avoid duplicates: if payments table exists, exclude booking_ids already captured
            $excludeIds = array_filter(array_column($payments, 'booking_id'));
            $bWhere = " WHERE " . ($hasPayStatus ? "b.payment_status IN ('paid','completed')" : "1=1");
            if ($hasPaidAmount) $bWhere .= " AND b.paid_amount > 0";
            if ($excludeIds) {
                $inList = implode(',', array_map('intval', $excludeIds));
                $bWhere .= " AND b.id NOT IN ($inList)";
            }

            $bSql = $bSel . $bFrom . $bWhere . " ORDER BY created_at DESC";
            $bRes = $conn->query($bSql);
            if ($bRes) {
                while ($row = $bRes->fetch_assoc()) {
                    $row['source'] = 'bookings_table';
                    $payments[] = $row;
                }
            }
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Sort combined results by date DESC
    // ─────────────────────────────────────────────────────────────
    usort($payments, function($a, $b) {
        return strtotime($b['created_at'] ?? '0') - strtotime($a['created_at'] ?? '0');
    });

    // ─── Summary totals ───────────────────────────────────────────
    $totalRevenue = array_sum(array_column($payments, 'amount'));
    $paidCount    = count(array_filter($payments, fn($p) => in_array(strtolower($p['status'] ?? ''), ['paid', 'success', 'captured', 'completed'])));
    $pendingCount = count($payments) - $paidCount;

    echo json_encode([
        'success'       => true,
        'payments'      => $payments,
        'total'         => count($payments),
        'total_revenue' => floatval($totalRevenue),
        'paid_count'    => $paidCount,
        'pending_count' => $pendingCount,
        'sources'       => [
            'payments_table_exists' => $paymentsTableExists,
            'bookings_table_exists' => $bookingsExists,
        ],
    ]);

    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
