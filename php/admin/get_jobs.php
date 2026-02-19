<?php
// Get All Job Postings
require_once dirname(__DIR__) . '/config.php';

header('Content-Type: application/json');

try {
    $conn = getDBConnection();
    
    // Check if table exists
    if ($conn->query("SHOW TABLES LIKE 'job_postings'")->num_rows == 0) {
        throw new Exception("Table 'job_postings' does not exist.");
    }
    
    // Select jobs
    $sql = "SELECT j.id, j.title, j.job_type, j.status, j.wage, j.location, u.name as posted_by, j.created_at
            FROM job_postings j 
            JOIN users u ON j.farmer_id = u.id 
            ORDER BY j.created_at DESC";
            
    $res = $conn->query($sql);
    $jobs = [];
    while ($row = $res->fetch_assoc()) {
        $jobs[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'jobs' => $jobs
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
