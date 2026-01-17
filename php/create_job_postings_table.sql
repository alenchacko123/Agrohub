-- Create job_postings table for farmers to post job requirements
CREATE TABLE IF NOT EXISTS job_postings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    farmer_name VARCHAR(255) NOT NULL,
    farmer_email VARCHAR(255) NOT NULL,
    farmer_phone VARCHAR(20),
    farmer_location VARCHAR(255),
    
    job_title VARCHAR(255) NOT NULL,
    job_type VARCHAR(100) NOT NULL,
    job_category VARCHAR(100) NOT NULL,
    job_description TEXT NOT NULL,
    
    workers_needed INT NOT NULL DEFAULT 1,
    wage_per_day DECIMAL(10, 2) NOT NULL,
    duration_days INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    
    location VARCHAR(255) NOT NULL,
    work_hours_per_day INT DEFAULT 8,
    
    -- Requirements (stored as JSON or TEXT)
    requirements TEXT,
    
    -- Responsibilities (stored as JSON or TEXT)
    responsibilities TEXT,
    
    -- Benefits and Facilities
    accommodation_provided BOOLEAN DEFAULT FALSE,
    food_provided BOOLEAN DEFAULT FALSE,
    transportation_provided BOOLEAN DEFAULT FALSE,
    tools_provided BOOLEAN DEFAULT FALSE,
    other_benefits TEXT,
    
    -- Status
    status ENUM('active', 'filled', 'expired', 'closed') DEFAULT 'active',
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create job_applications table for workers to apply
CREATE TABLE IF NOT EXISTS job_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    worker_id INT NOT NULL,
    worker_name VARCHAR(255) NOT NULL,
    worker_email VARCHAR(255) NOT NULL,
    worker_phone VARCHAR(20),
    
    cover_message TEXT,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (job_id) REFERENCES job_postings(id) ON DELETE CASCADE,
    FOREIGN KEY (worker_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_application (job_id, worker_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
