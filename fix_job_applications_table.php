<?php
require_once 'config.php';

try {
    $conn = getDBConnection();
    
    echo "Updating job_applications table structure...\n\n";
    
    // Add message column if it doesn't exist
    $sql1 = "ALTER TABLE job_applications 
             ADD COLUMN IF NOT EXISTS message TEXT NULL AFTER worker_email";
    
    if ($conn->query($sql1)) {
        echo "✅ Added 'message' column\n";
    } else {
        echo "ℹ️  Message column: " . $conn->error . "\n";
    }
    
    // Add experience column if it doesn't exist
    $sql2 = "ALTER TABLE job_applications 
             ADD COLUMN IF NOT EXISTS experience VARCHAR(255) NULL AFTER message";
    
    if ($conn->query($sql2)) {
        echo "✅ Added 'experience' column\n";
    } else {
        echo "ℹ️  Experience column: " . $conn->error . "\n";
    }
    
    echo "\n✅ Table structure updated successfully!\n";
    echo "\nCurrent table structure:\n";
    
    $result = $conn->query("DESCRIBE job_applications");
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['Field']} ({$row['Type']})\n";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
