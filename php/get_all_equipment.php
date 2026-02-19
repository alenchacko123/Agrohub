<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    $conn = getDBConnection();
    
    // Query to get all equipment with owner details
    $query = "SELECT e.*, u.name as owner_name, u.email as owner_email 
              FROM equipment e 
              LEFT JOIN users u ON e.owner_id = u.id 
              ORDER BY e.created_at DESC";
    
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Database query failed: " . $conn->error);
    }
    
    $equipment = [];
    
    while ($row = $result->fetch_assoc()) {
        $equipment[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'equipment' => $equipment,
        'count' => count($equipment)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
