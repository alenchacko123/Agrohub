<?php
// Setup script for creating videos table
require_once 'config.php';

try {
    $conn = getDBConnection();
    
    // Read and execute SQL file
    $sql = file_get_contents('../sql/create_videos_table.sql');
    
    if ($conn->multi_query($sql)) {
        echo "Videos table created successfully!\n";
        
        // Clear results
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());
        
        echo "Sample video data inserted!\n";
    } else {
        echo "Error: " . $conn->error . "\n";
    }
    
    $conn->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
