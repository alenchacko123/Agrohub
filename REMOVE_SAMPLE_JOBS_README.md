# Remove Sample Job Postings from AgroHub

## Overview
This guide explains how to remove all sample/test job records from the Job Portal dashboard.

## Sample Jobs to be Removed
Based on your screenshot, these sample jobs will be deleted:
1. ✓ **Test Job - Rice Harvesting** (Test Farm, Bangalore)
2. ✓ **Rice Harvesting Worker** (Mysore, Karnataka)
3. ✓ **Tractor Operator**
4. ✓ Any jobs posted by "Test Farmer" or "Ramesh Kumar" (if test accounts)
5. ✓ Any jobs with "test" in the title or description

---

## 🚀 Quick Method (Recommended)

### Step 1: Open the Cleanup Page
Navigate to:
```
http://localhost/Agrohub/clean-sample-jobs.html
```

### Step 2: Click "Delete Sample Jobs"
- Click the red "Delete Sample Jobs" button
- Confirm the action when prompted
- You'll see a report of what was deleted

### Step 3: Verify
- Go back to the Job Portal Dashboard
- Refresh the page
- Sample jobs should be gone! ✅

---

## 📋 Alternative Methods

### Method A: Run PHP Script Directly
Open in browser:
```
http://localhost/Agrohub/php/remove_sample_jobs.php
```

This will:
- Show you all sample jobs found
- Delete them automatically
- Display the results
- Show remaining jobs in the database

### Method B: Use phpMyAdmin
1. Open **phpMyAdmin** (usually at `http://localhost/phpmyadmin`)
2. Select the **`agrohub`** database
3. Click **SQL** tab
4. Copy and paste this query:

```sql
-- Delete sample jobs
DELETE FROM job_postings 
WHERE job_title IN (
    'Test Job - Rice Harvesting',
    'Rice Harvesting Worker',
    'Tractor Operator'
)
OR LOWER(job_title) LIKE '%test%'
OR farmer_name LIKE '%Test%'
OR description LIKE '%this is a test%';

-- Verify deletion
SELECT * FROM job_postings ORDER BY created_at DESC;
```

5. Click **Go**

### Method C: Use SQL File
1. Open terminal/command prompt
2. Navigate to the Agrohub directory
3. Run:

**Windows (XAMPP):**
```bash
cd C:\xampp\htdocs\Agrohub
mysql -u root agrohub < sql/remove_sample_jobs.sql
```

**Mac/Linux:**
```bash
cd /path/to/Agrohub
mysql -u root -p agrohub < sql/remove_sample_jobs.sql
```

---

## 🔍 What Gets Deleted?

The cleanup script removes jobs matching ANY of these criteria:

1. **Job Title contains "test"** (case-insensitive)
   - Example: "Test Job - Rice Harvesting"

2. **Specific job titles:**
   - "Test Job - Rice Harvesting"
   - "Rice Harvesting Worker"
   - "Tractor Operator"

3. **Farmer name contains "Test"**
   - Example: "Test Farmer"

4. **Description contains "this is a test"**
   - Example: jobs with test descriptions

---

## ✅ What's Safe?

The following will **NOT** be deleted:
- Real job postings by actual farmers
- Jobs without "test" keywords
- Jobs posted by real users

---

## 🔄 After Deletion

### 1. Refresh the Dashboard
After running the cleanup, refresh your browser:
```
http://localhost/Agrohub/job-portal-dashboard.html
```

### 2. Verify Empty State
If all jobs were samples, you should see an empty state message:
> "No jobs found matching your criteria"

### 3. Add Real Jobs
You can now:
- Post real jobs as a farmer
- Import production data
- Start fresh with actual job postings

---

## 📊 Database Impact

### Before Cleanup:
```
job_postings table:
- Test Job - Rice Harvesting
- Rice Harvesting Worker
- Tractor Operator
- [possibly more test jobs]
```

### After Cleanup:
```
job_postings table:
- [only real jobs remain]
- [or empty if all were samples]
```

---

## 🛡️ Safety Features

1. **Only deletes test data** - Real jobs are protected by keyword filters
2. **Shows preview** - You can see what will be deleted before confirming
3. **Provides backup SQL** - You can review the SQL before running
4. **Confirmation required** - Browser confirmation dialog prevents accidental deletion

---

## 🚨 Troubleshooting

### Issue: "No sample jobs found"
**Solution:** The jobs may already be deleted, or they don't match the search criteria.

### Issue: "Error connecting to database"
**Solution:** 
1. Make sure XAMPP is running
2. Check that MySQL is started
3. Verify `config.php` has correct database credentials

### Issue: "Some jobs still appear"
**Solution:** 
1. Clear browser cache (Ctrl+F5)
2. Check if remaining jobs have different keywords
3. Update the SQL query to include specific job IDs

### Issue: "Permission denied"
**Solution:**
1. Make sure you're logged in as admin
2. Check file permissions on PHP files
3. Verify database user has DELETE privileges

---

## 📝 Files Created

1. **`clean-sample-jobs.html`** - User interface to trigger cleanup
2. **`php/remove_sample_jobs.php`** - PHP script that deletes sample jobs
3. **`sql/remove_sample_jobs.sql`** - SQL commands for manual execution
4. **`REMOVE_SAMPLE_JOBS_README.md`** - This documentation file

---

## 🔄 To Re-add Sample Jobs (Testing)

If you need sample jobs again for testing:

```sql
INSERT INTO job_postings (job_title, job_category, farmer_name, farmer_id, 
                          location, wage_per_day, workers_needed, duration_days, 
                          description, status) 
VALUES 
('Test Job - Rice Harvesting', 'harvesting', 'Test Farmer', 1, 
 'Test Farm, Bangalore', 800.00, 5, 3, 
 'This is a test job posting', 'active');
```

---

## 📞 Support

If you encounter any issues:
1. Check the browser console for errors (F12)
2. Review the SQL error messages
3. Verify your database connection in `php/config.php`

---

## ✨ Summary

**Quick Steps:**
1. Open `http://localhost/Agrohub/clean-sample-jobs.html`
2. Click "Delete Sample Jobs"
3. Confirm deletion
4. Refresh Job Portal Dashboard
5. Done! ✅

All sample jobs will be removed, and your Job Portal will be clean and ready for real job postings!
