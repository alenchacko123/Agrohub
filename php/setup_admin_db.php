<?php
// Setup Admin Database Structures
require_once __DIR__ . '/config.php';

$conn = getDBConnection();
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Setting up Admin Database Structures...\n";

// 1. Update Users Table (Add Status)
$sql = "SHOW COLUMNS FROM users LIKE 'status'";
$result = $conn->query($sql);
if ($result->num_rows == 0) {
    if ($conn->query("ALTER TABLE users ADD COLUMN status ENUM('active', 'deactivated', 'pending') DEFAULT 'active'")) {
        echo "✓ Added 'status' column to 'users' table.\n";
    } else {
        echo "❌ Error adding 'status' column: " . $conn->error . "\n";
    }
} else {
    echo "✓ 'users.status' column already exists.\n";
}

// 2. Create System Settings Table
$sql = "CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "✓ Table 'system_settings' check/create successful.\n";
    
    // Insert default settings
    $settings = [
        'platform_name' => 'AgroHub',
        'platform_logo' => 'https://example.com/logo.png',
        'support_email' => 'support@agrohub.com',
        'registration_open' => 'true',
        'maintenance_mode' => 'false',
        'commission_percentage' => '5',
        'currency' => 'INR',
        'rental_policy' => "1. Equipment must be returned clean.\n2. Damages are the renter's responsibility.\n3. Late returns incur a fee.",
        'agreement_template' => "AGREEMENT BETWEEN OWNER AND RENTER\n\nThis agreement confirms the rental of [Equipment] for [Duration]...",
        'digital_signature_enabled' => 'true',
        'password_min_length' => '8',
        'email_verification_required' => 'true',
        'session_timeout_minutes' => '30',
        'max_equipment_listings' => '10',
        'max_job_applications' => '50',
        'terms_and_conditions' => 'Standard platform terms apply.'
    ];
    
    foreach ($settings as $key => $val) {
        $stmt = $conn->prepare("SELECT id FROM system_settings WHERE setting_key = ?");
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $check = $stmt->get_result();
        
        if ($check->num_rows == 0) {
            $insert = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)");
            $insert->bind_param("ss", $key, $val);
            if ($insert->execute()) {
                echo "  - Added default setting: $key\n";
            } else {
                echo "  ❌ Failed to add: $key - " . $conn->error . "\n";
            }
        }
    }
} else {
    echo "❌ Error creating 'system_settings': " . $conn->error . "\n";
}

// 3. Ensure other tables exist (Bookings, Payments, Jobs)
// Just quickly check existence, as they should be created by other scripts
$tables = ['bookings', 'rental_requests', 'agreements', 'payments', 'job_postings'];
foreach ($tables as $table) {
    if ($conn->query("SHOW TABLES LIKE '$table'")->num_rows > 0) {
        echo "✓ Table '$table' confirmed.\n";
    } else {
        echo "⚠ Table '$table' does not exist yet (will show empty data).\n";
    }
}

$conn->close();
?>
