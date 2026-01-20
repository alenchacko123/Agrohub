-- Remove Sample/Test Job Postings from AgroHub Database
-- This script removes all test job postings from the job_postings table

-- First, let's see what sample jobs we have
SELECT id, job_title, farmer_name, location, created_at 
FROM job_postings 
WHERE job_title LIKE '%test%' 
   OR job_title LIKE '%Test%'
   OR job_title LIKE '%Rice Harvesting%'
   OR job_title LIKE '%Tractor Operator%'
   OR farmer_name LIKE '%Test%'
   OR description LIKE '%test%';

-- Delete sample job records
-- Option 1: Delete by specific job titles
DELETE FROM job_postings 
WHERE job_title IN (
    'Test Job - Rice Harvesting',
    'Rice Harvesting Worker',
    'Tractor Operator'
);

-- Option 2: Delete all jobs with "test" in the title (case-insensitive)
DELETE FROM job_postings 
WHERE LOWER(job_title) LIKE '%test%'
   OR LOWER(description) LIKE '%this is a test%';

-- Option 3: Delete all jobs posted by "Test Farmer" or test farmers
DELETE FROM job_postings 
WHERE farmer_name LIKE '%Test%';

-- Verify deletion - should return 0 rows if all sample jobs are removed
SELECT COUNT(*) as remaining_sample_jobs 
FROM job_postings 
WHERE job_title LIKE '%test%' 
   OR job_title LIKE '%Test%'
   OR farmer_name LIKE '%Test%';

-- Show remaining jobs
SELECT id, job_title, farmer_name, location, status, created_at 
FROM job_postings 
ORDER BY created_at DESC;
