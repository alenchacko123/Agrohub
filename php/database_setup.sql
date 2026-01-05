-- AgroHub Database Setup
-- Run this SQL in phpMyAdmin to create the required tables

-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS agrohub;
USE agrohub;

-- =====================================================
-- USERS TABLE - Stores all user information
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('farmer', 'owner', 'admin') NOT NULL DEFAULT 'farmer',
    phone VARCHAR(20),
    address TEXT,
    profile_picture TEXT,
    is_verified BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_user_type (user_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PASSWORD RESET TOKENS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    user_type ENUM('farmer', 'owner', 'admin') NOT NULL,
    expires_at DATETIME NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_email (email),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SERVICES TABLE - Stores dashboard features/services
-- =====================================================
CREATE TABLE IF NOT EXISTS dashboard_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_type ENUM('farmer', 'owner', 'admin') NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50) NOT NULL, -- Material Icon name
    link VARCHAR(255) DEFAULT '#',
    badge_text VARCHAR(20),
    badge_type VARCHAR(20),
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- INSERT DEFAULT SERVICES
-- =====================================================
INSERT INTO dashboard_services (user_type, title, description, icon, link, badge_text, badge_type) VALUES
-- Farmer Services
('farmer', 'Rent Equipment', 'Find and rent modern farming tools', 'agriculture', '#', 'New', 'info'),
('farmer', 'Purchase Equipment', 'Buy high-quality farming gear', 'shopping_cart', '#', NULL, NULL),
('farmer', 'Check Stock', 'Real-time tool availability', 'inventory', '#', 'Live', 'success'),
('farmer', 'Hire Workers', 'Professional labor for your farm', 'groups', '#', NULL, NULL),
('farmer', 'Tutorials', 'Learn modern farming techniques', 'school', '#', 'Premium', 'warning'),
('farmer', 'Agreements', 'Secure digital rental contracts', 'history_edu', '#', NULL, NULL),

-- Owner Services
('owner', 'My Equipment', 'Manage your listed machinery', 'inventory_2', '#', '12', 'info'),
('owner', 'Add Listing', 'Post new equipment for rent', 'add_circle', '#', NULL, NULL),
('owner', 'Booking Requests', 'New rental applications', 'event_note', '#', '5', 'warning'),
('owner', 'Active Rentals', 'Track your rented equipment', 'check_circle', '#', '8', 'success'),
('owner', 'Earnings', 'Financial reports and payouts', 'account_balance_wallet', '#', NULL, NULL),
('owner', 'Analytics', 'Business growth metrics', 'analytics', '#', NULL, NULL);

-- =====================================================
-- USER SESSIONS TABLE - For secure authentication
-- =====================================================
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
