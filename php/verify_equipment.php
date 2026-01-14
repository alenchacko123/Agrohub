<?php
// Verify Equipment - Shows what's currently in the database
require_once 'config.php';

// Get database connection
$conn = getDBConnection();

$result = $conn->query("SELECT id, equipment_name, owner_name, category, price_per_day, availability_status, created_at FROM equipment ORDER BY id");

echo "=== Current Equipment in Database ===\n\n";

if ($result->num_rows > 0) {
    $count = 0;
    while ($row = $result->fetch_assoc()) {
        $count++;
        echo "Equipment #{$count}:\n";
        echo "  ID: {$row['id']}\n";
        echo "  Name: {$row['equipment_name']}\n";
        echo "  Owner: {$row['owner_name']}\n";
        echo "  Category: {$row['category']}\n";
        echo "  Price: ₹{$row['price_per_day']}/day\n";
        echo "  Status: {$row['availability_status']}\n";
        echo "  Added: {$row['created_at']}\n";
        echo "  --------------------------------\n";
    }
    echo "\nTotal Equipment: {$count}\n";
} else {
    echo "No equipment found in database.\n";
}

$conn->close();
?>
