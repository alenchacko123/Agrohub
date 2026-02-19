<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';

try {
    // Get filter parameters
    $category = isset($_GET['category']) ? $_GET['category'] : 'all';
    $location = isset($_GET['location']) ? $_GET['location'] : '';
    $minWage = isset($_GET['min_wage']) ? (int)$_GET['min_wage'] : 0;
    $maxWage = isset($_GET['max_wage']) ? (int)$_GET['max_wage'] : 999999;
    $status = isset($_GET['status']) ? $_GET['status'] : 'Open';
    $farmer_id = isset($_GET['farmer_id']) ? (int)$_GET['farmer_id'] : null;
    
    // Build query
    $sql = "SELECT * FROM job_postings WHERE (status = ? OR status = 'active' OR status = 'open')";
    $params = [$status];
    $types = "s";
    
    if ($category !== 'all') {
        $sql .= " AND LOWER(job_category) = LOWER(?)";
        $params[] = $category;
        $types .= "s";
    }
    
    if (!empty($location)) {
        $sql .= " AND location LIKE ?";
        $params[] = "%$location%";
        $types .= "s";
    }
    
    if ($minWage > 0) {
        $sql .= " AND payment_amount >= ?";
        $params[] = $minWage;
        $types .= "d";
    }
    
    if ($maxWage < 999999) {
        $sql .= " AND payment_amount <= ?";
        $params[] = $maxWage;
        $types .= "d";
    }
    
    if ($farmer_id) {
        $sql .= " AND farmer_id = ?";
        $params[] = $farmer_id;
        $types .= "i";
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $stmt = $conn->prepare($sql);
    
    if (count($params) > 0) {
        $bind_params = [];
        $bind_params[] = &$types;
        foreach ($params as $key => $value) {
            $bind_params[] = &$params[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $bind_params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $jobs = [];
    while ($row = $result->fetch_assoc()) {
        // Parse JSON fields
        $row['requirements'] = json_decode($row['requirements'], true);
        $row['responsibilities'] = json_decode($row['responsibilities'], true);
        
        // Backward compatibility
        $row['wage_per_day'] = $row['payment_amount'];
        $row['daily_wage'] = $row['payment_amount'];

        // Convert boolean fields
        $row['accommodation_provided'] = (bool)$row['accommodation_provided'];
        $row['food_provided'] = (bool)$row['food_provided'];
        $row['transportation_provided'] = (bool)$row['transportation_provided'];
        $row['tools_provided'] = (bool)$row['tools_provided'];
        
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
        
        $jobs[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'jobs' => $jobs,
        'count' => count($jobs)
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
