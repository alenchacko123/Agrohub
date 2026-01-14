<?php
header('Content-Type: application/json');
require_once 'config.php';

// Get database connection
$conn = getDBConnection();

try {
    // Update all equipment records that have the Unsplash default image
    $defaultImageUrl = 'https://images.unsplash.com/photo-1595053826286-2e59efd9ff18?q=80&w=400&fit=crop';
    
    $sql = "UPDATE equipment SET image_url = NULL WHERE image_url = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $defaultImageUrl);
    $stmt->execute();
    
    $affectedRows = $stmt->affected_rows;
    
    echo json_encode([
        'success' => true,
        'message' => "Removed default images from $affectedRows equipment record(s)",
        'affected_rows' => $affectedRows
    ]);
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
