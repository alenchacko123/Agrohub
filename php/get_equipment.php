<?php
header('Content-Type: application/json');
require_once 'config.php';

// Get database connection
$conn = getDBConnection();

try {
    $category = isset($_GET['category']) ? $_GET['category'] : '';
    $availability = isset($_GET['availability']) ? $_GET['availability'] : '';
    $owner_id = isset($_GET['owner_id']) ? $_GET['owner_id'] : '';
    
    // Build SQL query
    $sql = "SELECT * FROM equipment WHERE 1=1";
    $params = [];
    $types = "";

    if (!empty($owner_id)) {
        $sql .= " AND owner_id = ?";
        $params[] = intval($owner_id);
        $types .= "i";
    }

    if (!empty($category) && $category !== 'all') {
        $sql .= " AND category = ?";
        $params[] = $category;
        $types .= "s";
    }

    if (!empty($availability) && $availability !== 'all') {
        $sql .= " AND availability_status = ?";
        $params[] = $availability;
        $types .= "s";
    }

    $sql .= " ORDER BY created_at DESC";

    $stmt = $conn->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $equipment_list = [];
    while ($row = $result->fetch_assoc()) {
        $equipment_list[] = $row;
    }

    echo json_encode([
        'success' => true,
        'equipment' => $equipment_list,
        'count' => count($equipment_list)
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
