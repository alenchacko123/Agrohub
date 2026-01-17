<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';

try {
    if (!isset($_GET['job_id'])) {
        echo json_encode(['success' => false, 'message' => 'Job ID is required']);
        exit;
    }
    
    $job_id = (int)$_GET['job_id'];
    
    $sql = "SELECT * FROM job_postings WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Job not found']);
        exit;
    }
    
    $job = $result->fetch_assoc();
    
    // Parse JSON fields
    $job['requirements'] = json_decode($job['requirements'], true);
    $job['responsibilities'] = json_decode($job['responsibilities'], true);
    
    // Convert boolean fields
    $job['accommodation_provided'] = (bool)$job['accommodation_provided'];
    $job['food_provided'] = (bool)$job['food_provided'];
    $job['transportation_provided'] = (bool)$job['transportation_provided'];
    $job['tools_provided'] = (bool)$job['tools_provided'];
    
    // Calculate days posted
    $created = new DateTime($job['created_at']);
    $now = new DateTime();
    $diff = $now->diff($created);
    
    if ($diff->days == 0) {
        $job['posted_ago'] = 'Posted today';
    } elseif ($diff->days == 1) {
        $job['posted_ago'] = 'Posted yesterday';
    } else {
        $job['posted_ago'] = "Posted {$diff->days} days ago";
    }
    
    
    // Get application count (optional - table might not exist yet)
    $job['application_count'] = 0;
    try {
        $app_sql = "SELECT COUNT(*) as count FROM job_applications WHERE job_id = ?";
        $app_stmt = $conn->prepare($app_sql);
        if ($app_stmt) {
            $app_stmt->bind_param("i", $job_id);
            $app_stmt->execute();
            $app_result = $app_stmt->get_result();
            $app_data = $app_result->fetch_assoc();
            $job['application_count'] = $app_data['count'];
            $app_stmt->close();
        }
    } catch (Exception $e) {
        // Table doesn't exist yet, set count to 0
        $job['application_count'] = 0;
    }
    
    echo json_encode([
        'success' => true,
        'job' => $job
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
