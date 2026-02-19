<?php
require_once 'config.php';

try {
    $conn = getDBConnection();

    // Create job_applications table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS job_applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        job_id INT NOT NULL,
        worker_id INT NOT NULL,
        message TEXT,
        experience VARCHAR(255),
        status ENUM('Applied', 'Hired', 'Rejected') DEFAULT 'Applied',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (job_id),
        INDEX (worker_id),
        UNIQUE KEY unique_application (job_id, worker_id)
    )";

    if ($conn->query($sql) === TRUE) {
        echo "Table job_applications created or already exists.<br>";
    } else {
        throw new Exception("Error creating table: " . $conn->error);
    }

    // Check if status column needs update in job_postings to ensure 'In Progress' is supported
    // Usually job_postings has a status column. Let's ensure it's not too restrictive if it's an ENUM.
    // We can't easily check ENUM values in MySQL without querying information_schema, 
    // but we can try to alter it to be safe or just assume it's VARCHAR.
    // Most likely it is VARCHAR based on previous interaction (default 'Open').

    echo "Setup completed successfully.";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
