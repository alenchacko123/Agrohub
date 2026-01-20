<?php
// Diagnostic script to check the system
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>AgroHub Rental System Diagnostic</h1>";

// Test 1: Check if config.php exists
echo "<h2>Test 1: Config File</h2>";
if (file_exists('php/config.php')) {
    echo "✅ config.php exists<br>";
    require_once 'php/config.php';
    echo "✅ config.php loaded successfully<br>";
} else {
    echo "❌ config.php NOT FOUND<br>";
}

// Test 2: Database connection
echo "<h2>Test 2: Database Connection</h2>";
try {
    $conn = getDBConnection();
    if ($conn) {
        echo "✅ Database connection successful<br>";
        echo "Connected to: agrohub database<br>";
    } else {
        echo "❌ Database connection failed<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 3: Check if rental_requests table exists
echo "<h2>Test 3: Rental Requests Table</h2>";
try {
    $result = $conn->query("SHOW TABLES LIKE 'rental_requests'");
    if ($result->num_rows > 0) {
        echo "✅ rental_requests table exists<br>";
        
        // Get table structure
        $structure = $conn->query("DESCRIBE rental_requests");
        echo "<br><strong>Table columns:</strong><br>";
        while ($row = $structure->fetch_assoc()) {
            echo "- " . $row['Field'] . " (" . $row['Type'] . ")<br>";
        }
    } else {
        echo "❌ rental_requests table NOT FOUND<br>";
        echo "Run this SQL to create it: php/create_rental_requests_table.sql<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 4: Check equipment table
echo "<h2>Test 4: Equipment Table</h2>";
try {
    $result = $conn->query("SELECT COUNT(*) as count FROM equipment");
    $row = $result->fetch_assoc();
    echo "✅ Equipment table exists<br>";
    echo "Total equipment: " . $row['count'] . "<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 5: Test JSON encoding
echo "<h2>Test 5: JSON Output Test</h2>";
$testData = [
    'success' => true,
    'message' => 'Test successful',
    'data' => ['test' => 'value']
];
$json = json_encode($testData);
if ($json) {
    echo "✅ JSON encoding works<br>";
    echo "Sample output: " . $json . "<br>";
} else {
    echo "❌ JSON encoding failed<br>";
}

echo "<h2>Summary</h2>";
echo "<p>If all tests pass, the rental system should work correctly.</p>";
echo "<p>Next step: Try submitting a rental from rent-equipment.html</p>";
?>
