# Job Posting System Implementation

## Overview
This document describes the complete job posting system implementation for AgroHub, where **farmers post jobs** and **workers apply for them**.

## System Flow

### 1. Farmer Posts a Job
**File:** `post-job.html`

Farmers can create job postings with the following details:
- **Job Information**: Title, Category, Type, Description
- **Requirements**: Number of workers, wages, duration, dates, location
- **Skills Required**: Dynamic list of requirements
- **Responsibilities**: Tasks workers will perform
- **Benefits**: Accommodation, food, transportation, tools, and other benefits

**Process:**
1. Farmer navigates to `post-job.html` from farmer dashboard
2. Fills out the comprehensive job posting form
3. System validates and submits data to `php/post_job.php`
4. Job is stored in `job_postings` database table
5. Job appears in job portal for workers to view

### 2. Workers Browse Jobs
**File:** `job-portal-dashboard.html`

Workers can:
- View all active job postings
- Filter by:
  - Category (Harvesting, Planting, Machine Operation, etc.)
  - Location
  - Minimum wage
  - Duration
- Click "View Details" to see complete job information

### 3. View Job Details
**File:** `job-details.html`

Displays comprehensive job information:
- Job description and requirements
- Responsibilities
- Benefits and facilities provided
- Farmer contact details
- Apply button and contact options

## Database Structure

### Table: `job_postings`
Stores all job postings created by farmers with fields:
- Job details (title, category, type, description)
- Worker requirements (count, wage, duration)  
- Dates and location
- Requirements and responsibilities (JSON arrays)
- Benefits (accommodation, food, transportation, tools)
- Farmer information
- Status (active, filled, expired, closed)

### Table: `job_applications`
Stores worker applications to jobs:
- Job ID and Worker ID references
- Application status (pending, accepted, rejected)
- Cover message
- Timestamps

## Backend API Endpoints

### 1. `php/post_job.php`
- **Method:** POST
- **Purpose:** Create new job posting
- **Input:** JSON job data from post-job.html form
- **Output:** Success/failure response with job ID

### 2. `php/get_jobs.php`
- **Method:** GET
- **Purpose:** Retrieve job listings with filters
- **Parameters:** 
  - `category` - Job category filter
  - `location` - Location filter
  - `min_wage`, `max_wage` - Wage range
  - `status` - Job status (default: active)
  - `farmer_id` - Filter by specific farmer
- **Output:** Array of job objects

### 3. `php/get_job_details.php`
- **Method:** GET
- **Purpose:** Get detailed information for a specific job
- **Parameters:** `job_id`
- **Output:** Complete job object with all details

## Features Implemented

### Farmer Features
✅ Post new jobs with comprehensive details
✅ Specify number of workers needed
✅ Set wages per day
✅ List requirements and responsibilities
✅ Offer benefits and facilities
✅ View own job postings (via farmer dashboard)

### Worker Features
✅ Browse all available jobs
✅ Filter by category, location, wage, duration
✅ View complete job details
✅ See farmer contact information
✅ Apply for jobs (basic functionality)

### Job Details Display
✅ Job title and description
✅ Location and duration
✅ Wage information
✅ Number of workers needed
✅ Requirements list with checkmarks
✅ Responsibilities list
✅ Benefits grid showing:
  - Accommodation
  - Food  
  - Transportation
  - Tools
  - Other benefits
✅ Farmer profile with:
  - Name and role
  - Contact details (phone, email)
  - Location
  - Rating (placeholder)

## Integration Points

### Add to Farmer Dashboard
Add a "Post Job" button/card in the farmer dashboard:

```html
<div class="service-card" onclick="window.location.href='post-job.html'">
    <div class="service-icon">📋</div>
    <h3>Post Job</h3>
    <p>Hire workers for your farm</p>
</div>
```

### Link Worker Dashboard
Update worker/job portal navigation to use `job-portal-dashboard.html` instead of `hire-workers.html`.

## Next Steps

### Recommended Enhancements
1. **Application System**: Complete the job application submission and tracking
2. **Farmer Job Management**: View, edit, and close posted jobs
3. **Notifications**: Alert farmers when workers apply
4. **Search Functionality**: Add text search for job titles
5. **Saved Jobs**: Allow workers to bookmark jobs
6. **Job History**: Track completed jobs for both farmers and workers
7. **Reviews & Ratings**: Real rating system for farmers and workers
8. **Payment Integration**: Secure payment processing
9. **Image Upload**: Allow farmers to add farm/job photos
10. **Chat System**: Direct messaging between farmers and workers

## Files Created

1. `php/create_job_postings_table.sql` - Database schema
2. `php/post_job.php` - Job posting API
3. `php/get_jobs.php` - Job listing API with filters
4. `php/get_job_details.php` - Individual job details API
5. `post-job.html` - Job posting form for farmers
6. `job-portal-dashboard.html` - Job browsing interface for workers
7. `job-details.html` - Detailed job information page

## Usage Instructions

### For Farmers:
1. Login to farmer dashboard
2. Click "Post Job" (add this to dashboard)
3. Fill out job details form
4. Submit to post the job
5. Job appears in job portal for workers

### For Workers:
1. Navigate to `job-portal-dashboard.html`
2. Browse available jobs
3. Use filters to find suitable jobs
4. Click "View Details" on any job
5. Review complete job information
6. Contact farmer or apply directly

## Testing

To test the complete system:
1. Create a test job posting as a farmer
2. View it in the job portal
3. Click "View Details" to see all information
4. Verify all fields display correctly
5. Test filters and search functionality

---

**Status:** ✅ Core functionality complete and ready for use
**Database:** ✅ Tables created and ready
**APIs:** ✅ All endpoints implemented
**UI:** ✅ All pages designed and functional
