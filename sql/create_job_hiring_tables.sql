-- ===================================================================
-- AgroHub Job Application and Hiring Module - Database Schema
-- ===================================================================
-- This script creates all tables required for the Job Application
-- and Hiring module for Agricultural Workers and Machine Operators
-- ===================================================================

-- 1. Worker Profiles Table
-- Stores comprehensive worker information
CREATE TABLE IF NOT EXISTS worker_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    location VARCHAR(255),
    worker_type ENUM('laborer', 'operator', 'specialist') NOT NULL,
    bio TEXT,
    profile_image VARCHAR(500),
    experience_years INT DEFAULT 0,
    daily_wage DECIMAL(10,2),
    availability_status ENUM('available', 'busy', 'unavailable') DEFAULT 'available',
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    is_verified BOOLEAN DEFAULT FALSE,
    is_approved BOOLEAN DEFAULT FALSE,
    rating DECIMAL(3,2) DEFAULT 0.00,
    total_jobs_completed INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_worker_type (worker_type),
    INDEX idx_location (location),
    INDEX idx_availability (availability_status),
    INDEX idx_approved (is_approved)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Worker Skills Table
-- Stores multiple skills per worker with proficiency levels
CREATE TABLE IF NOT EXISTS worker_skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    worker_profile_id INT NOT NULL,
    skill_name VARCHAR(255) NOT NULL,
    proficiency_level ENUM('beginner', 'intermediate', 'expert') DEFAULT 'intermediate',
    years_of_experience INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (worker_profile_id) REFERENCES worker_profiles(id) ON DELETE CASCADE,
    INDEX idx_skill_name (skill_name),
    INDEX idx_worker (worker_profile_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Worker Availability Calendar
-- Tracks when workers are available for hire
CREATE TABLE IF NOT EXISTS worker_availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    worker_profile_id INT NOT NULL,
    available_from DATE NOT NULL,
    available_to DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (worker_profile_id) REFERENCES worker_profiles(id) ON DELETE CASCADE,
    INDEX idx_dates (available_from, available_to),
    INDEX idx_worker (worker_profile_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Job Posts Table
-- Farmers can post job openings
CREATE TABLE IF NOT EXISTS job_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    job_title VARCHAR(255) NOT NULL,
    job_description TEXT,
    job_type VARCHAR(100),
    required_skills TEXT,
    location VARCHAR(255),
    start_date DATE,
    end_date DATE,
    duration_days INT,
    daily_wage DECIMAL(10,2),
    required_workers INT DEFAULT 1,
    accommodation_provided BOOLEAN DEFAULT FALSE,
    food_provided BOOLEAN DEFAULT FALSE,
    working_hours_per_day INT DEFAULT 8,
    special_requirements TEXT,
    status ENUM('open', 'closed', 'filled', 'cancelled') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_location (location),
    INDEX idx_dates (start_date, end_date),
    INDEX idx_farmer (farmer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Job Applications Table
-- Workers apply for job posts
CREATE TABLE IF NOT EXISTS job_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_post_id INT NOT NULL,
    worker_profile_id INT NOT NULL,
    application_message TEXT,
    proposed_wage DECIMAL(10,2),
    status ENUM('pending', 'reviewed', 'accepted', 'rejected', 'withdrawn') DEFAULT 'pending',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_post_id) REFERENCES job_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (worker_profile_id) REFERENCES worker_profiles(id) ON DELETE CASCADE,
    UNIQUE KEY unique_application (job_post_id, worker_profile_id),
    INDEX idx_status (status),
    INDEX idx_worker (worker_profile_id),
    INDEX idx_job (job_post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Hiring Contracts Table
-- Tracks actual hiring agreements between farmers and workers
CREATE TABLE IF NOT EXISTS hiring_contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    worker_profile_id INT NOT NULL,
    job_post_id INT NULL,
    job_application_id INT NULL,
    job_title VARCHAR(255) NOT NULL,
    job_description TEXT,
    start_date DATE NOT NULL,
    end_date DATE,
    duration_days INT,
    daily_wage DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(10,2),
    working_hours_per_day INT DEFAULT 8,
    farm_location TEXT,
    accommodation_provided BOOLEAN DEFAULT FALSE,
    food_provided BOOLEAN DEFAULT FALSE,
    special_requirements TEXT,
    contract_status ENUM('requested', 'accepted', 'rejected', 'in_progress', 'completed', 'cancelled') DEFAULT 'requested',
    payment_status ENUM('pending', 'partial', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (worker_profile_id) REFERENCES worker_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (job_post_id) REFERENCES job_posts(id) ON DELETE SET NULL,
    FOREIGN KEY (job_application_id) REFERENCES job_applications(id) ON DELETE SET NULL,
    INDEX idx_farmer (farmer_id),
    INDEX idx_worker (worker_profile_id),
    INDEX idx_status (contract_status),
    INDEX idx_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Worker Reviews Table
-- Farmers can review and rate workers after job completion
CREATE TABLE IF NOT EXISTS worker_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hiring_contract_id INT NOT NULL,
    worker_profile_id INT NOT NULL,
    farmer_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    review_text TEXT,
    work_quality_rating INT CHECK (work_quality_rating >= 1 AND work_quality_rating <= 5),
    punctuality_rating INT CHECK (punctuality_rating >= 1 AND punctuality_rating <= 5),
    professionalism_rating INT CHECK (professionalism_rating >= 1 AND professionalism_rating <= 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hiring_contract_id) REFERENCES hiring_contracts(id) ON DELETE CASCADE,
    FOREIGN KEY (worker_profile_id) REFERENCES worker_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_worker (worker_profile_id),
    INDEX idx_rating (rating),
    INDEX idx_contract (hiring_contract_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Worker Documents Table
-- Stores uploaded verification documents
CREATE TABLE IF NOT EXISTS worker_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    worker_profile_id INT NOT NULL,
    document_type ENUM('id_proof', 'address_proof', 'certification', 'license', 'other') NOT NULL,
    document_name VARCHAR(255),
    document_path VARCHAR(500),
    verified BOOLEAN DEFAULT FALSE,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified_at TIMESTAMP NULL,
    verified_by INT NULL,
    FOREIGN KEY (worker_profile_id) REFERENCES worker_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_worker (worker_profile_id),
    INDEX idx_verified (verified)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Worker Certifications Table
-- Professional certifications and licenses
CREATE TABLE IF NOT EXISTS worker_certifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    worker_profile_id INT NOT NULL,
    certification_name VARCHAR(255) NOT NULL,
    issuing_organization VARCHAR(255),
    issue_date DATE,
    expiry_date DATE,
    certification_number VARCHAR(100),
    verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (worker_profile_id) REFERENCES worker_profiles(id) ON DELETE CASCADE,
    INDEX idx_worker (worker_profile_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Job Portal Applications (for general employees)
-- Separate from farmer-worker hiring, this is for regular job applications
CREATE TABLE IF NOT EXISTS job_portal_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    location VARCHAR(255),
    position_applied VARCHAR(255),
    experience_years INT DEFAULT 0,
    resume_path VARCHAR(500),
    cover_letter TEXT,
    skills TEXT,
    education VARCHAR(500),
    application_status ENUM('submitted', 'under_review', 'shortlisted', 'rejected', 'hired') DEFAULT 'submitted',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (application_status),
    INDEX idx_position (position_applied)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================================
-- Insert sample data for testing
-- ===================================================================

-- Sample worker profiles (assuming user IDs 1-3 exist as workers)
-- Note: Adjust user_id values based on your actual users table

-- Sample Skills Data
INSERT INTO worker_skills (worker_profile_id, skill_name, proficiency_level, years_of_experience) VALUES
(1, 'Tractor Operation', 'expert', 5),
(1, 'Harvesting', 'intermediate', 3),
(2, 'Irrigation Management', 'expert', 7),
(2, 'Plowing', 'expert', 6),
(3, 'Pesticide Spraying', 'intermediate', 4);

-- ===================================================================
-- Triggers for automatic calculations
-- ===================================================================

-- Update worker rating when new review is added
DELIMITER //
CREATE TRIGGER IF NOT EXISTS update_worker_rating_after_review
AFTER INSERT ON worker_reviews
FOR EACH ROW
BEGIN
    UPDATE worker_profiles
    SET rating = (
        SELECT AVG(rating)
        FROM worker_reviews
        WHERE worker_profile_id = NEW.worker_profile_id
    )
    WHERE id = NEW.worker_profile_id;
END//

-- Update total jobs completed when contract is marked as completed
CREATE TRIGGER IF NOT EXISTS update_jobs_completed_after_contract
AFTER UPDATE ON hiring_contracts
FOR EACH ROW
BEGIN
    IF NEW.contract_status = 'completed' AND OLD.contract_status != 'completed' THEN
        UPDATE worker_profiles
        SET total_jobs_completed = total_jobs_completed + 1
        WHERE id = NEW.worker_profile_id;
    END IF;
END//

-- Calculate total amount when contract is created/updated
CREATE TRIGGER IF NOT EXISTS calculate_contract_total_before_insert
BEFORE INSERT ON hiring_contracts
FOR EACH ROW
BEGIN
    IF NEW.duration_days IS NOT NULL AND NEW.daily_wage IS NOT NULL THEN
        SET NEW.total_amount = NEW.duration_days * NEW.daily_wage;
    END IF;
END//

CREATE TRIGGER IF NOT EXISTS calculate_contract_total_before_update
BEFORE UPDATE ON hiring_contracts
FOR EACH ROW
BEGIN
    IF NEW.duration_days IS NOT NULL AND NEW.daily_wage IS NOT NULL THEN
        SET NEW.total_amount = NEW.duration_days * NEW.daily_wage;
    END IF;
END//

DELIMITER ;

-- ===================================================================
-- Views for common queries
-- ===================================================================

-- Available workers with their skills
CREATE OR REPLACE VIEW available_workers_view AS
SELECT 
    wp.id,
    wp.full_name,
    wp.location,
    wp.worker_type,
    wp.experience_years,
    wp.daily_wage,
    wp.rating,
    wp.total_jobs_completed,
    wp.profile_image,
    GROUP_CONCAT(ws.skill_name) as skills
FROM worker_profiles wp
LEFT JOIN worker_skills ws ON wp.id = ws.worker_profile_id
WHERE wp.availability_status = 'available' AND wp.is_approved = TRUE
GROUP BY wp.id;

-- Active job posts with applicant count
CREATE OR REPLACE VIEW active_jobs_with_applicants AS
SELECT 
    jp.*,
    u.name as farmer_name,
    COUNT(ja.id) as total_applicants
FROM job_posts jp
LEFT JOIN users u ON jp.farmer_id = u.id
LEFT JOIN job_applications ja ON jp.id = ja.job_post_id
WHERE jp.status = 'open'
GROUP BY jp.id;

-- Worker dashboard summary
CREATE OR REPLACE VIEW worker_dashboard_summary AS
SELECT 
    wp.id as worker_id,
    wp.full_name,
    wp.rating,
    wp.total_jobs_completed,
    COUNT(DISTINCT ja.id) as total_applications,
    COUNT(DISTINCT CASE WHEN hc.contract_status = 'in_progress' THEN hc.id END) as active_contracts,
    SUM(CASE WHEN hc.contract_status = 'completed' THEN hc.total_amount ELSE 0 END) as total_earnings
FROM worker_profiles wp
LEFT JOIN job_applications ja ON wp.id = ja.worker_profile_id
LEFT JOIN hiring_contracts hc ON wp.id = hc.worker_profile_id
GROUP BY wp.id;

-- ===================================================================
-- Indexes for performance optimization
-- ===================================================================

-- Additional composite indexes for common queries
CREATE INDEX idx_worker_location_type ON worker_profiles(location, worker_type, availability_status);
CREATE INDEX idx_job_location_status ON job_posts(location, status, start_date);
CREATE INDEX idx_contract_farmer_status ON hiring_contracts(farmer_id, contract_status);
CREATE INDEX idx_contract_worker_status ON hiring_contracts(worker_profile_id, contract_status);

-- ===================================================================
-- Setup complete!
-- ===================================================================
