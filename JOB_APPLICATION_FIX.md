# Job Application Error Fix

## Problem
When workers tried to apply for jobs, they received the error:
**"Unknown column 'job_id' in 'where clause'"**

## Root Cause
The database table `job_applications` was created with the column name `worker_id`, but the PHP code in `submit_application.php` was using `applicant_id` instead.

### Database Schema (from setup_applications_table.php):
```sql
CREATE TABLE job_applications (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    job_id INT(11) NOT NULL,
    worker_id INT(11) NOT NULL,  ← Uses worker_id
    ...
)
```

### Backend Code (submit_application.php) - BEFORE FIX:
```php
// Was using applicant_id (WRONG)
if (!isset($input['applicant_id'])) { ... }
$applicant_id = intval($input['applicant_id']);
WHERE job_id = ? AND applicant_id = ?
```

### Frontend Code (job-details.html) - BEFORE FIX:
```javascript
fetch('php/submit_application.php', {
    body: JSON.stringify({
        job_id: jobId,
        applicant_id: applicantId,  ← Was sending applicant_id
        ...
    })
})
```

## Solution Implemented

### 1. Fixed Backend (`php/submit_application.php`)
Changed all instances of `applicant_id` to `worker_id`:

**Line 20**: Validation check
```php
if (!isset($input['worker_id'])) {
    throw new Exception('Job ID and Worker ID are required');
}
```

**Line 25**: Variable assignment
```php
$worker_id = intval($input['worker_id']);
```

**Line 30**: Duplicate check query
```php
$check_query = "SELECT id FROM job_applications WHERE job_id = ? AND worker_id = ?";
$check_stmt->bind_param('ii', $job_id, $worker_id);
```

**Line 41**: Insert query
```php
$query = "INSERT INTO job_applications (job_id, worker_id, message, experience, status, created_at) 
          VALUES (?, ?, ?, ?, 'pending', NOW())";
$stmt->bind_param('iiss', $job_id, $worker_id, $message, $experience);
```

### 2. Fixed Frontend (`job-details.html`)
**Line 1127**: Changed the JSON body parameter
```javascript
body: JSON.stringify({
    job_id: jobId,
    worker_id: applicantId,  ← Changed from applicant_id
    message: message || '',
    experience: experience || ''
})
```

## Testing

To test the fix:
1. Login as a worker: `http://localhost/Agrohub/login-job-portal.html`
2. Browse available jobs: `http://localhost/Agrohub/available-jobs.html`
3. Click "View Details" on any job
4. Click "Apply for This Job"
5. You should see: ✅ "Application submitted successfully!"

Check the database:
```sql
SELECT * FROM job_applications ORDER BY created_at DESC LIMIT 5;
```

You should see the new application with:
- `job_id`: The job you applied to
- `worker_id`: Your user ID
- `status`: pending
- `created_at`: Current timestamp

## Files Modified
1. **c:\xampp\htdocs\Agrohub\php\submit_application.php** - Backend API
2. **c:\xampp\htdocs\Agrohub\job-details.html** - Frontend application form

---
**Status**: ✅ Fixed
**Date**: 2026-01-29
