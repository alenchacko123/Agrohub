<?php
require_once 'config.php';

try {
    $conn = getDBConnection();

    // Add location column if not exists
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'location'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN location VARCHAR(255) NULL AFTER phone");
        echo "Added 'location' column.<br>";
    } else {
        echo "'location' column already exists.<br>";
    }

    // Add farm_size column if not exists
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'farm_size'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN farm_size VARCHAR(50) NULL AFTER location");
        echo "Added 'farm_size' column.<br>";
    } else {
        echo "'farm_size' column already exists.<br>";
    }

    echo "Farmer profile columns setup complete!";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
