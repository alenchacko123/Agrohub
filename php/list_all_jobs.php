<?php
require_once 'config.php';

echo "<h2>Debug: List of All Jobs in Database</h2>";
echo "<style>table { border-collapse: collapse; width: 100%; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background-color: #f2f2f2; }</style>";

// Check if table exists
$table_check = $conn->query("SHOW TABLES LIKE 'job_postings'");
if ($table_check->num_rows == 0) {
    echo "<h3 style='color:red;'>ERROR: table 'job_postings' does not exist!</h3>";
    exit;
}

$sql = "SELECT id, job_title, farmer_id, created_at FROM job_postings ORDER BY id DESC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>Job ID</th><th>Job Title</th><th>Farmer ID (Owner)</th><th>Posted Date</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row["id"] . "</td>";
        echo "<td>" . $row["job_title"] . "</td>";
        echo "<td><strong>" . $row["farmer_id"] . "</strong></td>";
        echo "<td>" . $row["created_at"] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<h3>No jobs found in the database table 'job_postings'.</h3>";
}
$conn->close();
?>
