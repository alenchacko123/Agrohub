<?php
require_once 'php/config.php';

try {
    $conn = getDBConnection();
    
    echo "Adding specification columns to equipment table...\n\n";
    
    // Add model column
    $sql1 = "ALTER TABLE equipment ADD COLUMN IF NOT EXISTS model VARCHAR(100) AFTER description";
    if ($conn->query($sql1)) echo "✅ Added 'model' column\n";
    else echo "⚠️ 'model' column issue: " . $conn->error . "\n";
    
    // Add year_of_manufacture column
    $sql2 = "ALTER TABLE equipment ADD COLUMN IF NOT EXISTS year_of_manufacture INT AFTER model";
    if ($conn->query($sql2)) echo "✅ Added 'year_of_manufacture' column\n";
    else echo "⚠️ 'year_of_manufacture' column issue: " . $conn->error . "\n";
    
    // Add fuel_type column
    $sql3 = "ALTER TABLE equipment ADD COLUMN IF NOT EXISTS fuel_type VARCHAR(50) AFTER year_of_manufacture";
    if ($conn->query($sql3)) echo "✅ Added 'fuel_type' column\n";
    else echo "⚠️ 'fuel_type' column issue: " . $conn->error . "\n";
    
    // Add capacity column
    $sql4 = "ALTER TABLE equipment ADD COLUMN IF NOT EXISTS capacity VARCHAR(100) AFTER fuel_type";
    if ($conn->query($sql4)) echo "✅ Added 'capacity' column\n";
    else echo "⚠️ 'capacity' column issue: " . $conn->error . "\n";
    
    echo "\n✅ Migration completed successfully!";
    $conn->close();
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage();
}
?>
