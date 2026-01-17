<?php
// Diagnostic script to test job posting
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

// Test 1: Database connection
$diagnostics = [
    'database_connected' => false,
    'table_exists' => false,
    'received_data' => null,
    'validation_errors' => [],
    'error_details' => null
];

try {
    // Check database connection
    if ($conn->ping()) {
        $diagnostics['database_connected'] = true;
    }
    
    // Check if job_postings table exists
    $result = $conn->query("SHOW TABLES LIKE 'job_postings'");
    if ($result && $result->num_rows > 0) {
        $diagnostics['table_exists'] = true;
    }
    
    // Get posted data
    $data = json_decode(file_get_contents('php://input'), true);
    $diagnostics['received_data'] = $data ? 'Data received successfully' : 'No data received';
    
    if ($data) {
        // Validate required fields
        $required = ['farmer_id', 'farmer_name', 'farmer_email', 'job_title', 'job_type', 
                     'job_category', 'job_description', 'workers_needed', 'wage_per_day', 
                     'duration_days', 'start_date', 'location'];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                $diagnostics['validation_errors'][] = "Missing or empty: $field";
            }
        }
        
        // Show actual values for debugging
        $diagnostics['farmer_id'] = $data['farmer_id'] ?? 'NOT SET';
        $diagnostics['farmer_name'] = $data['farmer_name'] ?? 'NOT SET';
        $diagnostics['farmer_email'] = $data['farmer_email'] ?? 'NOT SET';
    }
    
    echo json_encode([
        'success' => true,
        'diagnostics' => $diagnostics
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    $diagnostics['error_details'] = $e->getMessage();
    echo json_encode([
        'success' => false,
        'diagnostics' => $diagnostics,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}

$conn->close();
?>
