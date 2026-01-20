<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';

// Get worker ID from query parameter
$worker_id = isset($_GET['worker_id']) ? (int)$_GET['worker_id'] : 0;

if (!$worker_id) {
    echo json_encode(['success' => false, 'message' => 'Worker ID is required']);
    exit;
}

try {
    // Join job_applications with job_postings to get job details
    $sql = "SELECT 
                ja.id as application_id,
                ja.status as application_status,
                ja.applied_at,
                jp.id as job_id,
                jp.job_title,
                jp.location,
                jp.wage_per_day,
                jp.duration_days,
                jp.start_date,
                jp.end_date,
                jp.farmer_name,
                jp.farmer_id
            FROM job_applications ja
            JOIN job_postings jp ON ja.job_id = jp.id
            WHERE ja.worker_id = ?
            ORDER BY ja.applied_at DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $worker_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $jobs = [];

    while ($row = $result->fetch_assoc()) {
        // Format dates
        $applied_date = new DateTime($row['applied_at']);
        $row['applied_date_formatted'] = $applied_date->format('M d, Y');
        
        // Add duration formatted
        if (!empty($row['start_date'])) {
            $start = new DateTime($row['start_date']);
            $row['start_date_formatted'] = $start->format('M d');
            
            if (!empty($row['end_date'])) {
                $end = new DateTime($row['end_date']);
                $row['date_range'] = $start->format('M d') . ' - ' . $end->format('M d');
            } else {
                $row['date_range'] = 'Starts ' . $start->format('M d');
            }
        } else {
            $row['date_range'] = 'Dates TBD';
        }

        $jobs[] = $row;
    }

    echo json_encode(['success' => true, 'jobs' => $jobs]);

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
