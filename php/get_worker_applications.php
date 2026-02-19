<?php
header('Content-Type: application/json');
require_once 'config.php';

// Get worker_id
$worker_id = isset($_GET['worker_id']) ? intval($_GET['worker_id']) : 0;

if ($worker_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid Worker ID']);
    exit;
}

try {
    $conn = getDBConnection();
    
    $sql = "SELECT 
                a.id as application_id,
                a.status as application_status,
                a.created_at as applied_date,
                j.id as job_id,
                j.job_title,
                j.location,
                j.payment_amount as wage_per_day,
                j.duration_days,
                j.start_date,
                j.end_date,
                u.id as farmer_id,
                u.name as farmer_name,
                u.phone as farmer_phone,
                u.email as farmer_email
            FROM job_applications a
            JOIN job_postings j ON a.job_id = j.id
            JOIN users u ON j.farmer_id = u.id
            WHERE a.worker_id = ?
            ORDER BY a.created_at DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $worker_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $jobs = [];
    while ($row = $result->fetch_assoc()) {
        // Calculate date range string safely
        try {
            if (!empty($row['start_date']) && !empty($row['end_date'])) {
                $start = new DateTime($row['start_date']);
                $end = new DateTime($row['end_date']);
                $row['date_range'] = $start->format('M d') . ' - ' . $end->format('M d, Y');
            } else {
                $row['date_range'] = 'Dates not specified';
            }
        } catch (Exception $dateEx) {
            $row['date_range'] = 'Dates not specified';
        }
        
        try {
            $row['applied_date_formatted'] = (new DateTime($row['applied_date']))->format('M d, Y');
        } catch (Exception $dateEx) {
            $row['applied_date_formatted'] = 'Recently';
        }
        
        // Hide farmer contact if not hired
        if ($row['application_status'] !== 'Hired') {
            unset($row['farmer_phone']);
            unset($row['farmer_email']);
        }
        
        $jobs[] = $row;
    }
    
    echo json_encode(['success' => true, 'jobs' => $jobs]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
