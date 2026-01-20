<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config.php';

try {
    $conn = getDBConnection();
    
    // Get optional filters from query parameters
    $category = isset($_GET['category']) && $_GET['category'] !== 'all' ? $_GET['category'] : null;
    $level = isset($_GET['level']) ? $_GET['level'] : null;
    $search = isset($_GET['search']) ? $_GET['search'] : null;
    
    // Build query with filters
    $query = "SELECT * FROM videos WHERE status = 'active'";
    $params = [];
    $types = '';
    
    if ($category) {
        $query .= " AND category = ?";
        $params[] = $category;
        $types .= 's';
    }
    
    if ($level) {
        $query .= " AND level = ?";
        $params[] = $level;
        $types .= 's';
    }
    
    if ($search) {
        $query .= " AND (title LIKE ? OR description LIKE ? OR instructor LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= 'sss';
    }
    
    $query .= " ORDER BY created_at DESC";
    
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $videos = [];
    while ($row = $result->fetch_assoc()) {
        // Parse JSON topics if exists
        if ($row['topics']) {
            $row['topics'] = json_decode($row['topics'], true);
        } else {
            $row['topics'] = [];
        }
        $videos[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'videos' => $videos,
        'count' => count($videos)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch videos: ' . $e->getMessage()
    ]);
}
?>
