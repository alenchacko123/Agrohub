<?php
require_once 'config.php';

$conn = getDBConnection();

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Updating Database Schema...\n";

// 1. Create Agreements Table
$sql = "CREATE TABLE IF NOT EXISTS agreements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rental_request_id INT NOT NULL,
    farmer_id INT NOT NULL,
    owner_id INT NOT NULL,
    equipment_id INT NOT NULL,
    agreement_content TEXT,
    farmer_signature VARCHAR(255),
    signed_at DATETIME,
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "✓ Table 'agreements' check/create successful\n";
} else {
    echo "❌ Error creating 'agreements': " . $conn->error . "\n";
}

// 2. Create Payments Table
$sql = "CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rental_request_id INT,
    amount DECIMAL(10,2),
    payment_method VARCHAR(50),
    transaction_id VARCHAR(100),
    status VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "✓ Table 'payments' check/create successful\n";
} else {
    echo "❌ Error creating 'payments': " . $conn->error . "\n";
}

// 3. Update rental_requests status column (to allow 'pending_payment')
// First check if it's ENUM
$result = $conn->query("SHOW COLUMNS FROM rental_requests LIKE 'status'");
$row = $result->fetch_assoc();
$type = $row['Type'];

if (strpos($type, 'enum') !== false) {
    echo "⚠ 'status' column is ENUM ($type). Converting to VARCHAR(50)...\n";
    if ($conn->query("ALTER TABLE rental_requests MODIFY status VARCHAR(50)")) {
        echo "✓ Converted 'status' to VARCHAR(50)\n";
    } else {
        echo "❌ Error altering 'rental_requests': " . $conn->error . "\n";
    }
} else {
    echo "✓ 'status' column is already compatible ($type)\n";
}

// 4. Create Bookings Table if not exists (simplified version if missing)
$sql = "CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT,
    farmer_id INT,
    farmer_name VARCHAR(255),
    start_date DATE,
    end_date DATE,
    total_amount DECIMAL(10,2),
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === TRUE) {
    echo "✓ Table 'bookings' check/create successful\n";
    
    // Add missing columns if table existed but columns didn't
    $cols = $conn->query("SHOW COLUMNS FROM bookings");
    $existingCols = [];
    while($row = $cols->fetch_assoc()) {
        $existingCols[] = $row['Field'];
    }

    if (!in_array('payment_status', $existingCols)) {
        $conn->query("ALTER TABLE bookings ADD COLUMN payment_status VARCHAR(50) DEFAULT 'paid'");
        echo "✓ Added column 'payment_status' to bookings\n";
    }
    if (!in_array('paid_amount', $existingCols)) {
        $conn->query("ALTER TABLE bookings ADD COLUMN paid_amount DECIMAL(10,2) DEFAULT 0.00");
         echo "✓ Added column 'paid_amount' to bookings\n";
    }
    if (!in_array('paid_at', $existingCols)) {
        $conn->query("ALTER TABLE bookings ADD COLUMN paid_at DATETIME");
         echo "✓ Added column 'paid_at' to bookings\n";
    }
}

$conn->close();
?>
