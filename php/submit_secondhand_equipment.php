<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once 'config.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $conn = getDBConnection();

    // Ensure table exists
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

    // Get JSON or POST data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (empty($data)) {
        $data = $_POST;
    }

    // Validate required fields
    $required = ['equipment_name', 'category', 'condition_status', 'selling_price', 'owner_id', 'owner_name'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Handle image upload / base64
    $image_url = null;
    if (!empty($data['image_data'])) {
        $image_data = $data['image_data'];
        if (strpos($image_data, 'data:image') === 0) {
            list($type, $image_data) = explode(';', $image_data);
            list(, $image_data) = explode(',', $image_data);
            $image_data = base64_decode($image_data);
            $extension = str_replace('data:image/', '', $type);
            if (!in_array($extension, ['jpeg', 'jpg', 'png', 'gif', 'webp'])) {
                $extension = 'png';
            }
            $upload_dir = '../uploads/secondhand/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $unique_filename = uniqid('sh_') . '_' . time() . '.' . $extension;
            $target_file = $upload_dir . $unique_filename;
            if (file_put_contents($target_file, $image_data)) {
                $image_url = 'uploads/secondhand/' . $unique_filename;
            }
        }
    }

    $stmt = $conn->prepare("INSERT INTO secondhand_equipment 
        (owner_id, owner_name, owner_phone, owner_email, equipment_name, category, condition_status, 
         selling_price, original_price, year_of_manufacture, hours_used, location, description, image_url, warranty)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }

    $owner_id       = intval($data['owner_id']);
    $owner_name     = $data['owner_name'];
    $owner_phone    = $data['owner_phone'] ?? null;
    $owner_email    = $data['owner_email'] ?? null;
    $equip_name     = $data['equipment_name'];
    $category       = $data['category'];
    $condition      = $data['condition_status'];
    $sell_price     = floatval($data['selling_price']);
    $orig_price     = !empty($data['original_price']) ? floatval($data['original_price']) : null;
    $year           = $data['year_of_manufacture'] ?? null;
    $hours          = intval($data['hours_used'] ?? 0);
    $location       = $data['location'] ?? null;
    $description    = $data['description'] ?? null;
    $warranty       = $data['warranty'] ?? null;

    $stmt->bind_param(
        "issssssddsissss",
        $owner_id, $owner_name, $owner_phone, $owner_email,
        $equip_name, $category, $condition,
        $sell_price, $orig_price,
        $year, $hours, $location, $description, $image_url, $warranty
    );

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Equipment listed for sale successfully!',
            'id' => $stmt->insert_id
        ]);
    } else {
        throw new Exception('Failed to insert: ' . $stmt->error);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
