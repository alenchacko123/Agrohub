<?php
require_once 'php/config.php';

try {
    $conn = getDBConnection();
    
    echo "Adding payment tracking columns to bookings table...\n\n";
    
    // Add payment_status column
    $sql1 = "ALTER TABLE bookings 
             ADD COLUMN IF NOT EXISTS payment_status VARCHAR(20) DEFAULT 'pending' 
             AFTER status";
    
    if ($conn->query($sql1)) {
        echo "✅ Added payment_status column\n";
    } else {
        echo "⚠️ payment_status column may already exist or error: " . $conn->error . "\n";
    }
    
    // Add paid_amount column
    $sql2 = "ALTER TABLE bookings 
             ADD COLUMN IF NOT EXISTS paid_amount DECIMAL(10,2) DEFAULT 0.00 
             AFTER payment_status";
    
    if ($conn->query($sql2)) {
        echo "✅ Added paid_amount column\n";
    } else {
        echo "⚠️ paid_amount column may already exist or error: " . $conn->error . "\n";
    }
    
    // Add paid_at column
    $sql3 = "ALTER TABLE bookings 
             ADD COLUMN IF NOT EXISTS paid_at TIMESTAMP NULL 
             AFTER paid_amount";
    
    if ($conn->query($sql3)) {
        echo "✅ Added paid_at column\n";
    } else {
        echo "⚠️ paid_at column may already exist or error: " . $conn->error . "\n";
    }
    
    echo "\n✅ Database migration completed successfully!\n";
    echo "\nYou can now track payment status for all bookings.\n";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
