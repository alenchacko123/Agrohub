-- Delete Specific Jobs from Database
-- This will remove jobs with IDs 1-5

-- First, let's see what we're about to delete
SELECT id, job_title, farmer_name, location 
FROM job_postings 
WHERE id IN (1, 2, 3, 4, 5);

-- Delete the specific jobs
DELETE FROM job_postings 
WHERE id IN (1, 2, 3, 4, 5);

-- Verify deletion
SELECT id, job_title, farmer_name, location, created_at 
FROM job_postings 
ORDER BY created_at DESC;

-- Check total remaining jobs
SELECT COUNT(*) as total_jobs_remaining 
FROM job_postings;
