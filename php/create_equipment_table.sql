-- Create equipment table for storing machinery listings
CREATE TABLE IF NOT EXISTS equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    owner_name VARCHAR(255) NOT NULL,
    equipment_name VARCHAR(255) NOT NULL,
    category VARCHAR(50) NOT NULL,
    price_per_day DECIMAL(10, 2) NOT NULL,
    equipment_condition VARCHAR(50) NOT NULL,
    availability_status VARCHAR(50) NOT NULL DEFAULT 'available',
    description TEXT,
    image_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_availability (availability_status),
    INDEX idx_owner (owner_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
