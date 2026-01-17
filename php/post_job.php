<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

// Get posted data
$data = json_decode(file_get_contents('php://input'), true);

// Log the received data for debugging (optional - comment out in production)
error_log("Received job posting data: " . print_r($data, true));

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data - no JSON received']);
    exit;
}

try {
    // Validate required fields - allow empty strings but not null/undefined
    $required = ['farmer_id', 'farmer_name', 'farmer_email', 'job_title', 'job_type', 
                 'job_category', 'job_description', 'workers_needed', 'wage_per_day', 
                 'duration_days', 'start_date', 'location'];
    
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing field: $field"]);
            exit;
        }
        // Check for empty values only on critical fields
        if (in_array($field, ['job_title', 'job_description', 'farmer_email']) && 
            ($data[$field] === '' || $data[$field] === null)) {
            echo json_encode(['success' => false, 'message' => "Field cannot be empty: $field"]);
            exit;
        }
    }
    
    // Convert arrays to JSON strings
    $requirements = isset($data['requirements']) ? json_encode($data['requirements']) : json_encode([]);
    $responsibilities = isset($data['responsibilities']) ? json_encode($data['responsibilities']) : json_encode([]);
    
    // Prepare SQL
    $sql = "INSERT INTO job_postings (
        farmer_id, farmer_name, farmer_email, farmer_phone, farmer_location,
        job_title, job_type, job_category, job_description,
        workers_needed, wage_per_day, duration_days, start_date, end_date,
        location, work_hours_per_day,
        requirements, responsibilities,
        accommodation_provided, food_provided, transportation_provided, 
        tools_provided, other_benefits, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    // Convert boolean/checkbox values to integers (0 or 1) for database
    $accommodation = ($data['accommodation_provided'] === true || $data['accommodation_provided'] === 1) ? 1 : 0;
    $food = ($data['food_provided'] === true || $data['food_provided'] === 1) ? 1 : 0;
    $transportation = ($data['transportation_provided'] === true || $data['transportation_provided'] === 1) ? 1 : 0;
    $tools = ($data['tools_provided'] === true || $data['tools_provided'] === 1) ? 1 : 0;
    
    // Handle optional fields with proper defaults
    $farmer_phone = isset($data['farmer_phone']) ? $data['farmer_phone'] : '';
    $farmer_location = isset($data['farmer_location']) ? $data['farmer_location'] : '';
    $end_date = isset($data['end_date']) && $data['end_date'] ? $data['end_date'] : NULL;
    $work_hours = isset($data['work_hours_per_day']) ? intval($data['work_hours_per_day']) : 8;
    $other_benefits = isset($data['other_benefits']) && $data['other_benefits'] ? $data['other_benefits'] : NULL;
    $status = 'active';
    
    // Bind parameters - CORRECTED: 24 parameters total
    // i = integer, s = string, d = double/decimal
    $stmt->bind_param(
        "issssssssiidisssissiiiss",  // 24 characters for 24 parameters
        $data['farmer_id'],           // 1:  i - farmer_id (integer)
        $data['farmer_name'],         // 2:  s - farmer_name
        $data['farmer_email'],        // 3:  s - farmer_email
        $farmer_phone,                // 4:  s - farmer_phone
        $farmer_location,             // 5:  s - farmer_location
        $data['job_title'],           // 6:  s - job_title
        $data['job_type'],            // 7:  s - job_type
        $data['job_category'],        // 8:  s - job_category
        $data['job_description'],     // 9:  s - job_description
        $data['workers_needed'],      // 10: i - workers_needed (integer)
        $data['wage_per_day'],        // 11: i - wage_per_day (should be decimal but using integer for now)
        $data['duration_days'],       // 12: d - duration_days (double/decimal)
        $data['start_date'],          // 13: i - start_date
        $end_date,                    // 14: s - end_date
        $data['location'],            // 15: s - location
        $work_hours,                  // 16: s - work_hours_per_day
        $requirements,                // 17: i - requirements (JSON string)
        $responsibilities,            // 18: s - responsibilities (JSON string)
        $accommodation,               // 19: s - accommodation_provided
        $food,                        // 20: i - food_provided
        $transportation,              // 21: i - transportation_provided
        $tools,                       // 22: i - tools_provided
        $other_benefits,              // 23: s - other_benefits
        $status                       // 24: s - status
    );
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Job posted successfully',
            'job_id' => $conn->insert_id
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to post job: ' . $stmt->error
        ]);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
