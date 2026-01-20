-- Create videos table for admin-managed tutorial videos
CREATE TABLE IF NOT EXISTS videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    video_url VARCHAR(500) NOT NULL,
    category VARCHAR(50) DEFAULT 'equipment',
    level VARCHAR(20) DEFAULT 'beginner',
    duration VARCHAR(20),
    instructor VARCHAR(100),
    topics JSON,
    thumbnail_url VARCHAR(500),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'active',
    views INT DEFAULT 0,
    rating DECIMAL(2,1) DEFAULT 0.0,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_category (category),
    INDEX idx_level (level),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample video data
INSERT INTO videos (title, description, video_url, category, level, duration, instructor, topics, thumbnail_url, rating, views) VALUES
('Tractor Operation for Beginners', 'Learn the basics of tractor operation, controls, and safe driving practices.', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'equipment', 'beginner', '25 min', 'Ramesh Kumar', '["Controls", "Starting", "Driving", "Safety"]', '', 4.8, 1250),
('Harvester Operation & Safety', 'Master combined harvester operation with safety protocols and efficiency tips.', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'equipment', 'intermediate', '40 min', 'Prakash Reddy', '["Operation", "Adjustment", "Safety", "Maintenance"]', '', 4.9, 890),
('Rotavator Usage & Maintenance', 'Complete guide to rotavator operation, blade maintenance, and soil preparation.', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'equipment', 'beginner', '18 min', 'Suresh Patil', '["Setup", "Operation", "Blade Care", "Depth Control"]', '', 4.7, 1450),
('Equipment Safety Fundamentals', 'Essential safety guidelines for all agricultural equipment operators.', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'safety', 'beginner', '22 min', 'Raghu N', '["PPE", "Emergency", "Fire Safety", "First Aid"]', '', 4.9, 2100);
