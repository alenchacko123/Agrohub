<?php
// Get All Job Postings for Admin Dashboard
require_once dirname(__DIR__) . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $conn = getDBConnection();

    // Check if table exists
    if ($conn->query("SHOW TABLES LIKE 'job_postings'")->num_rows == 0) {
        echo json_encode(['success' => true, 'jobs' => [], 'total' => 0, 'note' => 'job_postings table does not exist yet']);
        exit;
    }

    // Get filter params (for future extensibility)
    $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
    $search        = isset($_GET['search']) ? trim($_GET['search']) : '';

    // Build query using correct column names (job_title, payment_amount, farmer_id)
    $sql = "SELECT
                j.id,
                j.job_title        AS title,
                j.job_category,
                j.job_type,
                j.status,
                j.payment_amount   AS wage,
                j.payment_type,
                j.location,
                j.workers_needed,
                j.duration_days,
                j.start_date,
                j.end_date,
                j.created_at,
                u.name             AS posted_by,
                u.email            AS poster_email,
                u.user_type        AS poster_role,
                (SELECT COUNT(*) FROM job_applications ja WHERE ja.job_id = j.id) AS application_count
            FROM job_postings j
            LEFT JOIN users u ON j.farmer_id = u.id";

    $where = [];
    $params = [];
    $types = '';

    if ($status_filter && $status_filter !== 'all') {
        $where[] = "LOWER(j.status) = LOWER(?)";
        $params[] = $status_filter;
        $types .= 's';
    }

    if ($search) {
        $like = "%$search%";
        $where[] = "(j.job_title LIKE ? OR j.location LIKE ? OR u.name LIKE ?)";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types .= 'sss';
    }

    if ($where) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY j.created_at DESC";

    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $jobs = [];
    while ($row = $result->fetch_assoc()) {
        $row['wage']             = floatval($row['wage'] ?? 0);
        $row['application_count']= intval($row['application_count'] ?? 0);
        $jobs[] = $row;
    }

    // Summary counts
    $total    = count($jobs);
    $open     = count(array_filter($jobs, fn($j) => strtolower($j['status']) === 'open'));
    $closed   = count(array_filter($jobs, fn($j) => in_array(strtolower($j['status']), ['closed', 'filled', 'completed'])));
    $pending  = count(array_filter($jobs, fn($j) => strtolower($j['status']) === 'pending'));

    echo json_encode([
        'success'  => true,
        'jobs'     => $jobs,
        'total'    => $total,
        'open'     => $open,
        'closed'   => $closed,
        'pending'  => $pending,
    ]);

    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
