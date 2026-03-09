<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

try {
    if (!isset($_GET['owner_id'])) {
        throw new Exception("Owner ID is missing.");
    }

    $owner_id = intval($_GET['owner_id']);
    $conn = getDBConnection();

    $stmt = $conn->prepare("
        SELECT rf.*, 
               u.name AS farmer_name, 
               u.profile_picture AS farmer_image 
        FROM rental_feedback rf
        LEFT JOIN users u ON rf.farmer_id = u.id
        WHERE rf.owner_id = ?
        ORDER BY rf.created_at DESC
    ");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $owner_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }

    $stmt->close();
    $conn->close();

    echo json_encode([
        'success' => true,
        'reviews' => $reviews
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
