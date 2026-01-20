<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config.php';

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

try {
    $conn = getDBConnection();
    
    // Verify admin user (optional - you can add authentication here)
    // For now, we'll trust the request
    
    if ($method === 'POST') {
        // Add new video
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        if (!isset($data['title']) || !isset($data['video_url'])) {
            throw new Exception('Title and video URL are required');
        }
        
        $title = $data['title'];
        $description = $data['description'] ?? '';
        $video_url = $data['video_url'];
        $category = $data['category'] ?? 'equipment';
        $level = $data['level'] ?? 'beginner';
        $duration = $data['duration'] ?? '';
        $instructor = $data['instructor'] ?? '';
        $topics = isset($data['topics']) ? json_encode($data['topics']) : '[]';
        $thumbnail_url = $data['thumbnail_url'] ?? '';
        
        $stmt = $conn->prepare("INSERT INTO videos (title, description, video_url, category, level, duration, instructor, topics, thumbnail_url, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        
        $stmt->bind_param('sssssssss', $title, $description, $video_url, $category, $level, $duration, $instructor, $topics, $thumbnail_url);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Video uploaded successfully',
                'video_id' => $conn->insert_id
            ]);
        } else {
            throw new Exception('Failed to insert video');
        }
        
    } elseif ($method === 'PUT') {
        // Update existing video
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['id'])) {
            throw new Exception('Video ID is required');
        }
        
        $id = $data['id'];
        $title = $data['title'];
        $description = $data['description'] ?? '';
        $video_url = $data['video_url'];
        $category = $data['category'] ?? 'equipment';
        $level = $data['level'] ?? 'beginner';
        $duration = $data['duration'] ?? '';
        $instructor = $data['instructor'] ?? '';
        $topics = isset($data['topics']) ? json_encode($data['topics']) : '[]';
        $thumbnail_url = $data['thumbnail_url'] ?? '';
        
        $stmt = $conn->prepare("UPDATE videos SET title=?, description=?, video_url=?, category=?, level=?, duration=?, instructor=?, topics=?, thumbnail_url=? WHERE id=?");
        
        $stmt->bind_param('sssssssssi', $title, $description, $video_url, $category, $level, $duration, $instructor, $topics, $thumbnail_url, $id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Video updated successfully'
            ]);
        } else {
            throw new Exception('Failed to update video');
        }
        
    } elseif ($method === 'DELETE') {
        // Delete video (soft delete by setting status to inactive)
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['id'])) {
            throw new Exception('Video ID is required');
        }
        
        $id = $data['id'];
        
        $stmt = $conn->prepare("UPDATE videos SET status='inactive' WHERE id=?");
        $stmt->bind_param('i', $id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Video deleted successfully'
            ]);
        } else {
            throw new Exception('Failed to delete video');
        }
        
    } elseif ($method === 'GET') {
        // Get all videos (including inactive for admin view)
        $query = "SELECT * FROM videos ORDER BY created_at DESC";
        $result = $conn->query($query);
        
        $videos = [];
        while ($row = $result->fetch_assoc()) {
            if ($row['topics']) {
                $row['topics'] = json_decode($row['topics'], true);
            } else {
                $row['topics'] = [];
            }
            $videos[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'videos' => $videos
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
