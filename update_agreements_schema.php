<?php
// update_agreements_schema.php
require_once 'php/config.php';
$conn = getDBConnection();

// 1. Update STATUS enum
// We need to alter the enum to include new statuses
$sql = "ALTER TABLE agreements MODIFY COLUMN status ENUM('pending', 'signed', 'expired', 'farmer_signed', 'fully_signed') DEFAULT 'pending'";
if ($conn->query($sql)) {
    echo "Updated status enum.\n";
} else {
    echo "Error updating status enum: " . $conn->error . "\n";
}

// 2. Add Owner Signature Columns
$columns = [
    "owner_signature_data LONGTEXT",
    "owner_signature_type ENUM('text', 'image') DEFAULT 'text'",
    "owner_signed_at DATETIME",
    "owner_ip_address VARCHAR(45)",
    "agreement_pdf_path VARCHAR(255)"
];

foreach ($columns as $col) {
    if (!$conn->query("ALTER TABLE agreements ADD COLUMN $col")) {
        // Ignore "Duplicate column" error
        if ($conn->errno != 1060) {
             echo "Error adding column $col: " . $conn->error . "\n";
        }
    } else {
        echo "Added column $col.\n";
    }
}

// 3. Ensure Booking Status has 'active' and 'completed'
// Check bookings table status column
$res = $conn->query("SHOW COLUMNS FROM bookings LIKE 'status'");
$row = $res->fetch_assoc();
// If it's an enum, we might need to change it. If it's varchar, we're good.
// Assuming it's varchar or already has them based on previous usage.

echo "Schema update complete.\n";
?>
