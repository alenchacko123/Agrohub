-- Sample Job Portal Login Credentials
-- Run this SQL to add a test applicant to the database

USE agrohub;

-- Insert sample applicant
INSERT INTO job_portal_applications 
    (full_name, email, phone, location, position_applied, experience_years, cover_letter, skills, education, status, applied_date)
VALUES 
    ('John Doe', 
     'john@test.com', 
     '9876543210', 
     'Bangalore, Karnataka', 
     'IT Developer', 
     3, 
     'I am passionate about agricultural technology and would love to contribute to AgroHub.', 
     'PHP, JavaScript, MySQL, HTML, CSS, React', 
     'Bachelor of Technology in Computer Science', 
     'submitted', 
     NOW());

-- Verify the record was inserted
SELECT * FROM job_portal_applications WHERE email = 'john@test.com';
