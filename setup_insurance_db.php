<?php
require_once 'php/config.php';

try {
    // 1. Create insurance_plans table
    $sql = "CREATE TABLE IF NOT EXISTS insurance_plans (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plan_name VARCHAR(100) NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        coverage_amount DECIMAL(15, 2) NOT NULL,
        features TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    if (!$conn->query($sql)) throw new Exception("Error creating insurance_plans: " . $conn->error);

    // 2. Insert predefined plans if table is empty
    $check_plans = $conn->query("SELECT COUNT(*) as count FROM insurance_plans");
    $count = $check_plans->fetch_assoc()['count'];
    if ($count == 0) {
        $plans = [
            ['Basic Protection', 499.00, 50000.00, json_encode(['Theft Protection', 'Accidental Damage Cover', '24/7 Customer Support', 'Up to ₹50,000 coverage', 'Instant claim processing'])],
            ['Premium Shield', 999.00, 200000.00, json_encode(['All Basic features', 'Fire & Natural Disasters', 'Third-party Liability', 'Up to ₹2,00,000 coverage', 'Zero deductible', 'Free annual maintenance'])],
            ['Enterprise Plus', 1999.00, 1000000.00, json_encode(['All Premium features', 'Equipment Breakdown', 'Business Interruption', 'Unlimited coverage', 'Legal assistance', 'Replacement equipment', 'Premium support'])]
        ];
        $stmt = $conn->prepare("INSERT INTO insurance_plans (plan_name, price, coverage_amount, features) VALUES (?, ?, ?, ?)");
        foreach ($plans as $plan) {
            $stmt->bind_param("sdds", $plan[0], $plan[1], $plan[2], $plan[3]);
            $stmt->execute();
        }
        $stmt->close();
        echo "Predefined insurance plans inserted.\n";
    }

    // 3. Update rental_requests table
    $cols_to_add = [
        'insurance_plan_id' => "INT NULL DEFAULT NULL",
        'insurance_fee' => "DECIMAL(10, 2) NULL DEFAULT 0.00"
    ];
    foreach ($cols_to_add as $col => $definition) {
        $check = $conn->query("SHOW COLUMNS FROM rental_requests LIKE '$col'");
        if ($check->num_rows == 0) {
            $conn->query("ALTER TABLE rental_requests ADD COLUMN $col $definition");
            echo "Added $col to rental_requests.\n";
        }
    }

    // 4. Update bookings table
    $cols_to_add_bookings = [
        'insurance_plan_id' => "INT NULL DEFAULT NULL",
        'insurance_fee' => "DECIMAL(10, 2) NULL DEFAULT 0.00",
        'insurance_status' => "ENUM('Inactive', 'Active') DEFAULT 'Inactive'",
        'insurance_start_date' => "DATE NULL",
        'insurance_end_date' => "DATE NULL"
    ];
    foreach ($cols_to_add_bookings as $col => $definition) {
        $check = $conn->query("SHOW COLUMNS FROM bookings LIKE '$col'");
        if ($check->num_rows == 0) {
            $conn->query("ALTER TABLE bookings ADD COLUMN $col $definition");
            echo "Added $col to bookings.\n";
        }
    }

    echo "Database schema updated successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
