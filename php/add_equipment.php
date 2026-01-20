<?php
header('Content-Type: application/json');
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session to get owner info
session_start();

// Get database connection
$conn = getDBConnection();

try {
    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get the JSON data from request body if it exists
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // If data is in form format, use $_POST instead
    if (empty($data)) {
        $data = $_POST;
    }

    // Validate required fields
    $required_fields = ['equipment_name', 'category', 'price_per_day', 'condition', 'availability', 'description', 'location'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Get owner information from session or request
    $owner_id = $data['owner_id'] ?? 1; // Default to 1 for testing
    $owner_name = $data['owner_name'] ?? 'Equipment Owner';

    // Handle image upload
    $image_url = null;
    if (isset($_FILES['equipment_image']) && $_FILES['equipment_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/equipment/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Generate unique filename
        $file_extension = pathinfo($_FILES['equipment_image']['name'], PATHINFO_EXTENSION);
        $unique_filename = uniqid('equipment_') . '_' . time() . '.' . $file_extension;
        $target_file = $upload_dir . $unique_filename;

        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($_FILES['equipment_image']['type'], $allowed_types)) {
            throw new Exception('Invalid file type. Only JPG, PNG, GIF, and WEBP images are allowed.');
        }

        // Validate file size (max 5MB)
        if ($_FILES['equipment_image']['size'] > 5 * 1024 * 1024) {
            throw new Exception('File size too large. Maximum 5MB allowed.');
        }

        if (move_uploaded_file($_FILES['equipment_image']['tmp_name'], $target_file)) {
            $image_url = 'uploads/equipment/' . $unique_filename;
        } else {
            throw new Exception('Failed to upload image');
        }
    } else if (!empty($data['image_data'])) {
        // Handle base64 image data
        $image_data = $data['image_data'];
        if (strpos($image_data, 'data:image') === 0) {
            // Extract base64 data
            list($type, $image_data) = explode(';', $image_data);
            list(, $image_data) = explode(',', $image_data);
            $image_data = base64_decode($image_data);

            // Get extension from mime type
            $extension = str_replace('data:image/', '', $type);
            if (!in_array($extension, ['jpeg', 'jpg', 'png', 'gif', 'webp'])) {
                $extension = 'png';
            }

            $upload_dir = '../uploads/equipment/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $unique_filename = uniqid('equipment_') . '_' . time() . '.' . $extension;
            $target_file = $upload_dir . $unique_filename;

            if (file_put_contents($target_file, $image_data)) {
                $image_url = 'uploads/equipment/' . $unique_filename;
            }
        }
    }

    // If no image uploaded, leave as null (frontend will show placeholder icon)
    // $image_url remains null if no image was uploaded

    // Prepare SQL statement
    $sql = "INSERT INTO equipment (
        owner_id,
        owner_name,
        equipment_name,
        category,
        price_per_day,
        equipment_condition,
        availability_status,
        description,
        location,
        image_url,
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }

    $stmt->bind_param(
        "isssdsssss",
        $owner_id,
        $owner_name,
        $data['equipment_name'],
        $data['category'],
        $data['price_per_day'],
        $data['condition'],
        $data['availability'],
        $data['description'],
        $data['location'],
        $image_url
    );

    if ($stmt->execute()) {
        $equipment_id = $stmt->insert_id;
        
        echo json_encode([
            'success' => true,
            'message' => 'Equipment listed successfully!',
            'equipment_id' => $equipment_id,
            'image_url' => $image_url
        ]);
    } else {
        throw new Exception('Failed to insert equipment: ' . $stmt->error);
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
