<?php
// Script to clear sample videos from the database
require_once 'config.php';

try {
    $conn = getDBConnection();
    
    // Delete all sample videos (or set them to inactive)
    // Option 1: Permanently delete sample videos
    $query = "DELETE FROM videos WHERE video_url = 'https://www.youtube.com/embed/dQw4w9WgXcQ'";
    
    // Option 2: Soft delete (set status to inactive) - RECOMMENDED
    // $query = "UPDATE videos SET status='inactive' WHERE video_url = 'https://www.youtube.com/embed/dQw4w9WgXcQ'";
    
    if ($conn->query($query)) {
        $affectedRows = $conn->affected_rows;
        echo json_encode([
            'success' => true,
            'message' => "Successfully removed $affectedRows sample videos",
            'affected_rows' => $affectedRows
        ]);
    } else {
        throw new Exception('Failed to clear sample videos: ' . $conn->error);
    }
    
    $conn->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
