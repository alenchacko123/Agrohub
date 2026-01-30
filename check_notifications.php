<?php
require_once 'php/config.php';
$conn = getDBConnection();
$res = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($res->num_rows > 0) {
    echo "notifications table exists\n";
    $desc = $conn->query("DESCRIBE notifications");
    while($row = $desc->fetch_assoc()) {
        echo $row['Field'] . "\n";
    }
} else {
    echo "notifications table does NOT exist\n";
}
$conn->close();
?>
