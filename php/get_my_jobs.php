<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';

// Enable error logging
error_log("=== GET MY JOBS API CALLED ===");

try {
    if (!isset($_GET['farmer_id'])) {
        error_log("ERROR: No farmer_id provided");
        echo json_encode(['success' => false, 'message' => 'Farmer ID is required', 'debug' => 'No farmer_id in request']);
        exit;
    }
    
    $farmer_id = (int)$_GET['farmer_id'];
    error_log("Farmer ID: " . $farmer_id);
    
    // Get all jobs posted by this farmer
    $sql = "SELECT * FROM job_postings WHERE farmer_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $farmer_id);
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        throw new Exception("Execute failed: " . $stmt->error);
    }
    $result = $stmt->get_result();
    
    error_log("Query executed. Rows found: " . $result->num_rows);
    
    $jobs = [];
    while ($row = $result->fetch_assoc()) {
        // Parse JSON fields if they exist
        if (isset($row['requirements']) && is_string($row['requirements'])) {
            $row['requirements'] = json_decode($row['requirements'], true);
        }
        if (isset($row['responsibilities']) && is_string($row['responsibilities'])) {
            $row['responsibilities'] = json_decode($row['responsibilities'], true);
        }
        
        // Calculate days posted
        $created = new DateTime($row['created_at']);
        $now = new DateTime();
        $diff = $now->diff($created);
        
        if ($diff->days == 0) {
            $row['posted_ago'] = 'today';
        } elseif ($diff->days == 1) {
            $row['posted_ago'] = '1 day ago';
        } else {
            $row['posted_ago'] = $diff->days . ' days ago';
        }
        
        // Get application count (if table exists)
        $row['application_count'] = 0;
        // Skip application count for now - table may not exist or have different schema
        // This allows jobs to load even without application tracking
        
        $jobs[] = $row;
    }
    
    error_log("Total jobs to return: " . count($jobs));
    
    echo json_encode([
        'success' => true,
        'jobs' => $jobs,
        'count' => count($jobs),
        'debug' => [
            'farmer_id' => $farmer_id,
            'query' => $sql,
            'rows_found' => count($jobs)
        ]
    ]);
    
    $stmt->close();
} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'debug' => [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}

$conn->close();
?>
