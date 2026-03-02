<?php
require_once 'config.php';

$sql = "
CREATE TABLE IF NOT EXISTS job_messages (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    job_id      INT NOT NULL,
    sender_id   INT NOT NULL,
    sender_role ENUM('worker','farmer') NOT NULL,
    message     TEXT NOT NULL,
    is_read     TINYINT(1) DEFAULT 0,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_job (job_id),
    INDEX idx_sender (sender_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql)) {
    echo json_encode(['success' => true, 'message' => 'job_messages table ready.']);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}
$conn->close();
