<?php
// Cleanup Script - Remove Sample Equipment
// This script keeps only the first equipment and removes all others

header('Content-Type: application/json');
require_once 'config.php';

// Get database connection
$conn = getDBConnection();

try {
    // First, let's see what we have
    $result = $conn->query("SELECT id, equipment_name, owner_name, price_per_day FROM equipment ORDER BY id");
    
    $all_equipment = [];
    while ($row = $result->fetch_assoc()) {
        $all_equipment[] = $row;
    }
    
    if (count($all_equipment) <= 1) {
        echo json_encode([
            'success' => true,
            'message' => 'No sample equipment to delete. Only one or zero equipment found.',
            'equipment_found' => $all_equipment
        ]);
        exit;
    }
    
    // Get the first equipment ID (the real one to keep)
    $keep_id = $all_equipment[0]['id'];
    
    // Delete all equipment except the first one
    $stmt = $conn->prepare("DELETE FROM equipment WHERE id > ?");
    $stmt->bind_param("i", $keep_id);
    
    if ($stmt->execute()) {
        $deleted_count = $stmt->affected_rows;
        
        // Get remaining equipment
        $result_after = $conn->query("SELECT id, equipment_name, owner_name, price_per_day FROM equipment ORDER BY id");
        $remaining_equipment = [];
        while ($row = $result_after->fetch_assoc()) {
            $remaining_equipment[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'message' => "Successfully deleted {$deleted_count} sample equipment listing(s)",
            'deleted_count' => $deleted_count,
            'kept_equipment' => $all_equipment[0],
            'remaining_equipment' => $remaining_equipment
        ], JSON_PRETTY_PRINT);
    } else {
        throw new Exception("Failed to delete sample equipment: " . $conn->error);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
