<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';

try {
    if (!isset($_GET['farmer_id'])) {
        echo json_encode(['success' => false, 'message' => 'Farmer ID is required']);
        exit;
    }
    
    $farmer_id = (int)$_GET['farmer_id'];
    
    // Get all jobs posted by this farmer
    $sql = "SELECT * FROM job_postings WHERE farmer_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $farmer_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    $result = $stmt->get_result();
    
    $jobs = [];
    while ($row = $result->fetch_assoc()) {
        // Calculate days posted
        $created = new DateTime($row['created_at']);
        $now = new DateTime();
        $diff = $now->diff($created);
        
        if ($diff->days == 0) {
            $row['posted_ago'] = 'Posted today';
        } elseif ($diff->days == 1) {
            $row['posted_ago'] = 'Posted yesterday';
        } else {
            $row['posted_ago'] = "Posted {$diff->days} days ago";
        }
        
        // Get application count (if table exists)
        $row['application_count'] = 0;
        try {
            $app_sql = "SELECT COUNT(*) as count FROM job_applications WHERE job_id = ?";
            $app_stmt = $conn->prepare($app_sql);
            if ($app_stmt) {
                $app_stmt->bind_param("i", $row['id']);
                $app_stmt->execute();
                $app_result = $app_stmt->get_result();
                $app_data = $app_result->fetch_assoc();
                $row['application_count'] = $app_data['count'];
                $app_stmt->close();
            }
        } catch (Exception $e) {
            $row['application_count'] = 0;
        }
        
        $jobs[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'jobs' => $jobs
    ]);
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
