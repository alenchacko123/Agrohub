<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';

error_log("=== GET MY JOBS API CALLED ===");

try {
    if (!isset($_GET['farmer_id'])) {
        error_log("ERROR: No farmer_id provided");
        echo json_encode(['success' => false, 'message' => 'Farmer ID is required']);
        exit;
    }

    $farmer_id = (int)$_GET['farmer_id'];
    error_log("Farmer ID: " . $farmer_id);

    // ── STEP 1: Mark expired jobs (status update only — do NOT remove them) ──
    // A job is expired if:
    //   • end_date IS set AND end_date < today, OR
    //   • end_date IS NULL and (start_date + duration_days) < today
    // We mark them Expired so the UI can show them distinctly, but we keep them visible.
    $expireSQL = "UPDATE job_postings
                  SET status = 'Expired', updated_at = NOW()
                  WHERE farmer_id = ?
                    AND status NOT IN ('Expired', 'Closed')
                    AND (
                          (end_date IS NOT NULL AND end_date < CURDATE())
                          OR
                          (end_date IS NULL AND DATE_ADD(start_date, INTERVAL duration_days DAY) < CURDATE())
                        )";
    $expireStmt = $conn->prepare($expireSQL);
    $expired_count = 0;
    if ($expireStmt) {
        $expireStmt->bind_param("i", $farmer_id);
        $expireStmt->execute();
        $expired_count = $expireStmt->affected_rows;
        $expireStmt->close();
        error_log("Marked $expired_count jobs as Expired for farmer_id=$farmer_id");
    }

    // ── STEP 2: Fetch ALL jobs for this farmer (active + expired) ───────────
    $sql = "SELECT * FROM job_postings
            WHERE farmer_id = ?
            ORDER BY
                CASE WHEN status = 'Expired' THEN 1 ELSE 0 END ASC,
                created_at DESC";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $farmer_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    $result = $stmt->get_result();
    error_log("Query executed. Active rows found: " . $result->num_rows);

    $jobs  = [];
    $today = new DateTime('today');

    while ($row = $result->fetch_assoc()) {
        // Parse JSON fields
        if (isset($row['requirements']) && is_string($row['requirements'])) {
            $row['requirements'] = json_decode($row['requirements'], true);
        }
        if (isset($row['responsibilities']) && is_string($row['responsibilities'])) {
            $row['responsibilities'] = json_decode($row['responsibilities'], true);
        }

        // Backward compatibility for wage column name
        if (isset($row['payment_amount'])) {
            $row['wage_per_day'] = $row['payment_amount'];
        } else {
            $row['wage_per_day'] = $row['wage_per_day'] ?? 0;
        }

        // Calculate "posted X days ago"
        $created = new DateTime($row['created_at']);
        $diff    = $today->diff($created);
        if ($diff->days == 0) {
            $row['posted_ago'] = 'today';
        } elseif ($diff->days == 1) {
            $row['posted_ago'] = '1 day ago';
        } else {
            $row['posted_ago'] = $diff->days . ' days ago';
        }

        // Calculate expiry date for display
        if (!empty($row['end_date'])) {
            $expiry = new DateTime($row['end_date']);
        } elseif (!empty($row['start_date']) && !empty($row['duration_days'])) {
            $expiry = new DateTime($row['start_date']);
            $expiry->modify('+' . (int)$row['duration_days'] . ' days');
        } else {
            $expiry = null;
        }
        $row['expiry_date'] = $expiry ? $expiry->format('Y-m-d') : null;

        // Days remaining until expiry
        if ($expiry) {
            $daysLeft             = (int)$today->diff($expiry)->days;
            $row['days_remaining'] = ($expiry >= $today) ? $daysLeft : 0;
        } else {
            $row['days_remaining'] = null;
        }

        $row['application_count'] = 0;
        $jobs[] = $row;
    }

    error_log("Total active jobs to return: " . count($jobs));

    echo json_encode([
        'success'      => true,
        'jobs'         => $jobs,
        'count'        => count($jobs),
        'auto_expired' => $expired_count,
    ]);

    $stmt->close();
} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
    ]);
}

$conn->close();
?>
