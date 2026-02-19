<?php
require_once 'config.php';

$conn = getDBConnection();

$sql = "CREATE TABLE IF NOT EXISTS agreements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rental_request_id INT NOT NULL,
    farmer_id INT NOT NULL,
    owner_id INT NOT NULL,
    equipment_id INT NOT NULL,
    agreement_content TEXT,
    farmer_signature VARCHAR(255),
    signed_at DATETIME,
    status ENUM('pending', 'signed', 'expired') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rental_request_id) REFERENCES rental_requests(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'agreements' created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>
