<?php
require_once 'config.php';

try {
    $conn = getDBConnection();
    
    // Check if column exists
    $check = $conn->query("SHOW COLUMNS FROM equipment LIKE 'approval_status'");
    
    if ($check->num_rows == 0) {
        // Add column
        $sql = "ALTER TABLE equipment ADD COLUMN approval_status VARCHAR(20) DEFAULT 'pending' AFTER availability_status";
        if ($conn->query($sql)) {
            echo "Column 'approval_status' added successfully.\n";
            
            // Update existing records to approved so we don't break existing data
            $conn->query("UPDATE equipment SET approval_status = 'approved'");
            echo "Existing records updated to 'approved'.\n";
        } else {
            echo "Error adding column: " . $conn->error . "\n";
        }
    } else {
        echo "Column 'approval_status' already exists.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} finally {
    if (isset($conn)) $conn->close();
}
?>
