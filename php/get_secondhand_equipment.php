<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once 'config.php';

try {
    $conn = getDBConnection();

    // Check if secondhand_equipment table exists, if not create it
    $createTable = "CREATE TABLE IF NOT EXISTS secondhand_equipment (
        id INT AUTO_INCREMENT PRIMARY KEY,
        owner_id INT NOT NULL,
        owner_name VARCHAR(255) NOT NULL,
        owner_phone VARCHAR(30),
        owner_email VARCHAR(255),
        equipment_name VARCHAR(255) NOT NULL,
        category VARCHAR(50) NOT NULL,
        condition_status VARCHAR(50) NOT NULL,
        selling_price DECIMAL(12, 2) NOT NULL,
        original_price DECIMAL(12, 2),
        year_of_manufacture VARCHAR(10),
        hours_used INT DEFAULT 0,
        location VARCHAR(255),
        description TEXT,
        image_url VARCHAR(500),
        warranty VARCHAR(100),
        is_available TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $conn->query($createTable);

    // Fetch all available secondhand equipment
    $categoryFilter = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';
    $conditionFilter = isset($_GET['condition']) ? $conn->real_escape_string($_GET['condition']) : '';
    $ownerId = isset($_GET['owner_id']) ? intval($_GET['owner_id']) : 0;

    $query = "SELECT * FROM secondhand_equipment WHERE is_available = 1";
    if ($ownerId > 0) {
        $query .= " AND owner_id = $ownerId";
    }
    if (!empty($categoryFilter)) {
        $query .= " AND category = '$categoryFilter'";
    }
    if (!empty($conditionFilter)) {
        $query .= " AND condition_status = '$conditionFilter'";
    }
    $query .= " ORDER BY created_at DESC";

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
