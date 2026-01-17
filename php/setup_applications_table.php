<?php
require_once 'config.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL to create table
$sql = "CREATE TABLE IF NOT EXISTS job_applications (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    job_id INT(11) NOT NULL,
    worker_id INT(11) NOT NULL,
    worker_name VARCHAR(255) NOT NULL,
    worker_email VARCHAR(255),
    status VARCHAR(50) DEFAULT 'pending',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES job_postings(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table job_applications created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>
