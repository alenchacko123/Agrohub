<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['job_id']) || !isset($data['new_start_date'])) {
    echo json_encode(['success' => false, 'message' => 'job_id and new_start_date are required']);
    exit;
}

$job_id        = (int) $data['job_id'];
$new_start     = $conn->real_escape_string($data['new_start_date']);
$new_end       = isset($data['new_end_date']) && $data['new_end_date'] ? $conn->real_escape_string($data['new_end_date']) : null;

try {
    // ── 1. Fetch the original job ────────────────────────────────────────────
    $fetchStmt = $conn->prepare(
        "SELECT * FROM job_postings WHERE id = ? LIMIT 1"
    );
    if (!$fetchStmt) throw new Exception("Prepare failed: " . $conn->error);
    $fetchStmt->bind_param("i", $job_id);
    $fetchStmt->execute();
    $result = $fetchStmt->get_result();
    $job    = $result->fetch_assoc();
    $fetchStmt->close();

    if (!$job) {
        echo json_encode(['success' => false, 'message' => 'Job not found']);
        exit;
    }

    // ── 2. Calculate new end_date ────────────────────────────────────────────
    // If caller didn't supply a new end date, derive it from duration_days
    if (!$new_end && !empty($job['duration_days'])) {
        $endObj  = new DateTime($new_start);
        $endObj->modify('+' . (int)$job['duration_days'] . ' days');
        $new_end = $endObj->format('Y-m-d');
    }

    // ── 3. Insert a new row (clone) with status Open ─────────────────────────
    $ins = $conn->prepare(
        "INSERT INTO job_postings (
            farmer_id, farmer_name, farmer_email, farmer_phone, farmer_location,
            job_title, job_type, job_category, job_description,
            workers_needed, payment_amount, payment_type, duration_days,
            start_date, end_date, location, work_hours_per_day,
            requirements, responsibilities,
            accommodation_provided, food_provided, transportation_provided,
            tools_provided, other_benefits, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    if (!$ins) throw new Exception("Prepare failed: " . $conn->error);

    // Scalar values from the original row
    $farmer_id     = (int)   $job['farmer_id'];
    $farmer_name   =         $job['farmer_name']   ?? '';
    $farmer_email  =         $job['farmer_email']  ?? '';
    $farmer_phone  =         $job['farmer_phone']  ?? '';
    $farmer_loc    =         $job['farmer_location'] ?? '';
    $job_title     =         $job['job_title'];
    $job_type      =         $job['job_type']      ?? 'Agricultural';
    $job_cat       =         $job['job_category'];
    $job_desc      =         $job['job_description'];
    $workers       = (int)   $job['workers_needed'];
    $pay_amt       = (float) ($job['payment_amount'] ?? $job['wage_per_day'] ?? 0);
    $pay_type      =         $job['payment_type']  ?? 'Daily Wage';
    $duration      = (int)   $job['duration_days'];
    $location      =         $job['location'];
    $work_hrs      = (int)  ($job['work_hours_per_day'] ?? 8);
    $requirements  =         $job['requirements']   ?? '[]';
    $responsib     =         $job['responsibilities'] ?? '[]';
    $accomm        = (int)   ($job['accommodation_provided']  ?? 0);
    $food          = (int)   ($job['food_provided']           ?? 0);
    $transport     = (int)   ($job['transportation_provided'] ?? 0);
    $tools         = (int)   ($job['tools_provided']          ?? 0);
    $other_ben     =         $job['other_benefits'] ?? null;
    $status        = 'Open';

    $ins->bind_param(
        "issssssssidsisssissiiisss",
        $farmer_id,    // 1  i
        $farmer_name,  // 2  s
        $farmer_email, // 3  s
        $farmer_phone, // 4  s
        $farmer_loc,   // 5  s
        $job_title,    // 6  s
        $job_type,     // 7  s
        $job_cat,      // 8  s
        $job_desc,     // 9  s
        $workers,      // 10 i
        $pay_amt,      // 11 d
        $pay_type,     // 12 s
        $duration,     // 13 i
        $new_start,    // 14 s  ← new date
        $new_end,      // 15 s  ← new end date
        $location,     // 16 s
        $work_hrs,     // 17 i
        $requirements, // 18 s
        $responsib,    // 19 s
        $accomm,       // 20 i
        $food,         // 21 i
        $transport,    // 22 i
        $tools,        // 23 i
        $other_ben,    // 24 s
        $status        // 25 s
    );

    if ($ins->execute()) {
        $new_id = $conn->insert_id;
        $ins->close();
        echo json_encode([
            'success' => true,
            'message' => 'Job re-posted successfully!',
            'new_job_id' => $new_id
        ]);
    } else {
        throw new Exception("Insert failed: " . $ins->error);
    }

} catch (Exception $e) {
    error_log("repost_job error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
