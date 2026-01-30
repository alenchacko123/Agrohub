<?php
require_once 'php/config.php';

$conn = getDBConnection();

// First, let's see what users exist
echo "<h1>All Users in Database</h1>\n";
$result = $conn->query('SELECT id, name, email, user_type, created_at FROM users ORDER BY created_at DESC');

if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>User Type</th><th>Created At</th></tr>\n";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['name'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "<td>" . $row['user_type'] . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    echo "<p>Total users: " . $result->num_rows . "</p>";
} else {
    echo "<p>No users found in database.</p>";
}

$conn->close();
?>
