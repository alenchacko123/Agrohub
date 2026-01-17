# Job Application and Hiring Module - Implementation Plan

## Overview
This document outlines the implementation plan for a comprehensive Job Application and Hiring module for the AgroHub agricultural service platform. The module will support three main user types:
1. **Agricultural Workers/Machine Operators** - Create profiles and apply for jobs
2. **Farmers** - Search, hire, and manage workers
3. **Administrators** - Approve profiles and monitor activities

## Database Schema

### 1. Worker Profiles Table (`worker_profiles`)
```sql
CREATE TABLE worker_profiles (
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
    INDEX idx_availability (availability_status)
);
```

### 2. Worker Skills Table (`worker_skills`)
```sql
CREATE TABLE worker_skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    worker_profile_id INT NOT NULL,
    skill_name VARCHAR(255) NOT NULL,
    proficiency_level ENUM('beginner', 'intermediate', 'expert') DEFAULT 'intermediate',
    years_of_experience INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (worker_profile_id) REFERENCES worker_profiles(id) ON DELETE CASCADE,
    INDEX idx_skill_name (skill_name)
);
```

### 3. Worker Availability Calendar (`worker_availability`)
```sql
CREATE TABLE worker_availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    worker_profile_id INT NOT NULL,
    available_from DATE NOT NULL,
    available_to DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (worker_profile_id) REFERENCES worker_profiles(id) ON DELETE CASCADE,
    INDEX idx_dates (available_from, available_to)
);
```

### 4. Job Posts Table (`job_posts`)
```sql
CREATE TABLE job_posts (
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
    INDEX idx_dates (start_date, end_date)
);
```

### 5. Job Applications Table (`job_applications`)
```sql
CREATE TABLE job_applications (
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
    INDEX idx_worker (worker_profile_id)
);
```

### 6. Hiring Contracts Table (`hiring_contracts`)
```sql
CREATE TABLE hiring_contracts (
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
);
```

### 7. Worker Reviews Table (`worker_reviews`)
```sql
CREATE TABLE worker_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hiring_contract_id INT NOT NULL,
    worker_profile_id INT NOT NULL,
    farmer_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    review_text TEXT,
    work_quality_rating INT,
    punctuality_rating INT,
    professionalism_rating INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hiring_contract_id) REFERENCES hiring_contracts(id) ON DELETE CASCADE,
    FOREIGN KEY (worker_profile_id) REFERENCES worker_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_worker (worker_profile_id),
    INDEX idx_rating (rating)
);
```

### 8. Worker Documents Table (`worker_documents`)
```sql
CREATE TABLE worker_documents (
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
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
);
```

## Frontend Components

### 1. Worker Portal
- **worker-signup.html** - Worker registration page
- **worker-login.html** - Worker login page  
- **worker-dashboard.html** - Main dashboard for workers
- **worker-profile.html** - Create/edit worker profile
- **job-listings.html** - Browse and search available jobs
- **my-applications.html** - View and manage job applications
- **my-contracts.html** - View active and past contracts
- **worker-earnings.html** - View earnings and payment history

### 2. Farmer Portal (Enhancements)
- **post-job.html** - Create new job postings
- **manage-jobs.html** - Manage job posts
- **search-workers.html** - Search and filter workers (enhanced from hire-workers.html)
- **worker-applications.html** - Review applications for jobs
- **manage-contracts.html** - Manage hiring contracts
- **worker-payment-tracking.html** - Track payments to workers

### 3. Admin Portal (Enhancements)
- **admin-workers.html** - Approve and manage worker profiles
- **admin-verify-skills.html** - Verify worker skills and documents
- **admin-job-monitoring.html** - Monitor all job activities
- **admin-disputes.html** - Handle disputes between farmers and workers

### 4. Shared Components
- **job-portal.html** - Public job portal for employees to browse jobs
- **job-portal-dashboard.html** - Dashboard for job portal users

## Backend PHP Files

### API Endpoints

1. **php/worker_auth.php** - Worker authentication
2. **php/worker_profile.php** - CRUD operations for worker profiles
3. **php/worker_skills.php** - Manage worker skills
4. **php/worker_availability.php** - Manage availability calendar
5. **php/job_posts.php** - CRUD operations for job posts
6. **php/job_applications.php** - Manage job applications
7. **php/hiring_contracts.php** - Manage hiring contracts
8. **php/worker_reviews.php** - Worker reviews and ratings
9. **php/worker_search.php** - Advanced worker search
10. **php/job_search.php** - Advanced job search
11. **php/admin_worker_approval.php** - Admin approval workflows
12. **php/notifications.php** - Email/SMS notifications

## Key Features

### Worker Features
✅ Complete profile with skills, experience, and availability
✅ Upload profile photo and documents (ID, certifications)
✅ Browse and search job postings
✅ Apply for jobs with custom messages
✅ Receive direct hire requests from farmers
✅ Accept or reject job offers
✅ View active and completed contracts
✅ Track earnings and payment history
✅ Receive ratings and reviews
✅ Update availability calendar

### Farmer Features
✅ Post job openings with detailed requirements
✅ Search workers by skills, location, wage, availability
✅ View worker profiles with ratings and experience
✅ Send direct hire requests to workers
✅ Review job applications
✅ Accept or reject applications
✅ Create and manage contracts
✅ Track worker attendance and payments
✅ Leave reviews and ratings for workers
✅ View job history

### Admin Features
✅ Approve/reject worker profile registrations
✅ Verify worker skills and documents
✅ Monitor all job postings and applications
✅ Track contract activities
✅ Handle disputes and complaints
✅ Generate reports on hiring activities
✅ Manage worker and farmer ratings
✅ Ban/suspend problematic users

## Implementation Phases

### Phase 1: Database Setup (Day 1)
- Create all database tables
- Set up indexes and foreign keys
- Create sample data for testing

### Phase 2: Worker Portal (Days 2-3)
- Worker registration and login
- Worker profile creation and editing
- Skills and availability management
- Document upload functionality

### Phase 3: Job Posting & Applications (Days 4-5)
- Job posting interface for farmers
- Job listing and search for workers
- Application submission system
- Application review for farmers

### Phase 4: Hiring Contracts (Days 6-7)
- Contract creation and management
- Status tracking (requested, accepted, in-progress, completed)
- Direct hire requests
- Contract history

### Phase 5: Reviews & Ratings (Day 8)
- Review submission interface
- Rating calculation and display
- Review moderation

### Phase 6: Admin Module (Days 9-10)
- Worker approval workflow
- Skill verification
- Job monitoring dashboard
- Dispute resolution tools

### Phase 7: Job Portal (Days 11-12)
- Public job portal interface
- Employee registration
- Job application system
- Portal dashboard

### Phase 8: Testing & Polish (Days 13-14)
- End-to-end testing
- UI/UX refinements
- Performance optimization
- Bug fixes

## Success Metrics

- Number of registered workers
- Number of job posts created
- Application to hire conversion rate
- Average worker rating
- Contract completion rate
- User satisfaction scores

## Future Enhancements

- SMS notifications
- Mobile app
- Payment gateway integration
- Background verification services
- GPS-based attendance tracking
- Multi-language support
- Video profile introductions
- Skill assessment tests
