<?php
/**
 * setup_feedback_table.php
 * Visit once: http://localhost/Agrohub/php/setup_feedback_table.php
 * Creates the rental_feedback table with 5 question columns.
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/config.php';
$conn = getDBConnection();

$sql = "CREATE TABLE IF NOT EXISTS `rental_feedback` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `booking_id`        INT UNSIGNED NOT NULL,
    `farmer_id`         INT UNSIGNED NOT NULL,
    `owner_id`          INT UNSIGNED NOT NULL,
    `equipment_name`    VARCHAR(255) NOT NULL DEFAULT '',

    -- 5 question ratings (each 1-5 stars)
    `q1_condition`      TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Equipment Condition (1-5)',
    `q2_performance`    TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Performance & Reliability (1-5)',
    `q3_value`          TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Value for Money (1-5)',
    `q4_communication`  TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Owner Communication (1-5)',
    `q5_recommend`      TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Would Recommend (1-5)',

    `overall_rating`    DECIMAL(3,2) NOT NULL DEFAULT 0.00 COMMENT 'Auto-calculated average',
    `additional_comments` TEXT DEFAULT NULL,

    `created_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY `unique_booking_feedback` (`booking_id`),
    KEY `idx_owner_id` (`owner_id`),
    KEY `idx_farmer_id` (`farmer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql)) {
    echo "SUCCESS: rental_feedback table created (or already exists).\n";
} else {
    echo "ERROR: " . $conn->error . "\n";
}
$conn->close();
?>
