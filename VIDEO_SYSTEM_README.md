# Video Management System Setup Guide

## Overview
This system allows admin users to upload and manage tutorial videos that will be displayed in the farmer's skill center.

## Files Created

### Database
- `sql/create_videos_table.sql` - SQL script to create videos table with sample data

### PHP Backend
- `php/get_videos.php` - API to fetch videos (used by farmer dashboard)
- `php/upload_video.php` - API for admin CRUD operations on videos
- `php/setup_videos_table.php` - Setup script to initialize database

### HTML Pages
- `admin-videos.html` - Admin interface for managing videos
- Updated `admin-dashboard.html` - Added link to video management
- Updated `tutorials.html` - Now fetches videos from database instead of hardcoded data

## Setup Instructions

### Step 1: Create the Database Table

**Option A - Using Browser:**
1. Open your browser and navigate to: `http://localhost/Agrohub/php/setup_videos_table.php`
2. You should see "Videos table created successfully!" and "Sample video data inserted!"

**Option B - Using phpMyAdmin:**
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Select your AgroHub database
3. Click on "SQL" tab
4. Open the file `sql/create_videos_table.sql`
5. Copy all the content and paste it into the SQL query box
6. Click "Go" to execute

**Option C - Using MySQL Command Line:**
```bash
mysql -u root -p agrohub < sql/create_videos_table.sql
```

### Step 2: Access Admin Video Management

1. Login to admin dashboard: `http://localhost/Agrohub/admin-dashboard.html`
2. Click on "Tutorial Videos" in the sidebar under "Management" section
3. You'll be redirected to `admin-videos.html`

### Step 3: Upload Videos

#### How to Add a New Video:
1. Click the "Add New Video" button
2. Fill in the form:
   - **Title**: e.g., "Tractor Operation for Beginners"
   - **Description**: Detailed description of what the video teaches
   - **Video URL**: Use YouTube embed URL format:
     - Regular YouTube URL: `https://www.youtube.com/watch?v=VIDEO_ID`
     - Embed URL (required): `https://www.youtube.com/embed/VIDEO_ID`
   - **Category**: Equipment Operation, Maintenance, Safety Guidelines, or Farming Techniques
   - **Difficulty Level**: Beginner, Intermediate, or Advanced
   - **Duration**: e.g., "25 min"
   - **Instructor Name**: Name of the person teaching
   - **Topics**: Comma-separated, e.g., "Controls, Starting, Driving, Safety"
   - **Thumbnail URL**: (Optional) Direct link to an image
3. Click "Save Video"

#### How to Get YouTube Embed URL:
1. Go to the YouTube video
2. Click "Share" button
3. Click "Embed"
4. Copy the URL from the iframe src (it will look like: `https://www.youtube.com/embed/VIDEO_ID`)

### Step 4: Verify Videos Appear in Farmer Dashboard

1. Login as a farmer or navigate to: `http://localhost/Agrohub/farmer-dashboard.html`
2. Click on "Skill Center" in the sidebar
3. Click "Watch Videos" button 
4. You'll be redirected to `tutorials.html`
5. All videos uploaded by admin should appear here!

## Features

### Admin Features:
- ✅ Upload new tutorial videos
- ✅ Edit existing videos
- ✅ Delete videos (soft delete - sets status to inactive)
- ✅ View all videos with metadata
- ✅ Real-time preview of video library

### Farmer Features:
- ✅ Browse all active videos
- ✅ Filter by category (Equipment, Maintenance, Safety, Farming)
- ✅ Search videos by title, description, or topics
- ✅ Watch videos in modal player
- ✅ View video metadata (duration, instructor, rating, views)

## Database Schema

```sql
CREATE TABLE videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    video_url VARCHAR(500) NOT NULL,
    category VARCHAR(50) DEFAULT 'equipment',
    level VARCHAR(20) DEFAULT 'beginner',
    duration VARCHAR(20),
    instructor VARCHAR(100),
    topics JSON,
    thumbnail_url VARCHAR(500),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'active',
    views INT DEFAULT 0,
    rating DECIMAL(2,1) DEFAULT 0.0
)
```

## API Endpoints

### GET /php/get_videos.php
Fetch all active videos (used by farmer dashboard)

**Query Parameters:**
- `category` - Filter by category
- `level` - Filter by difficulty level
- `search` - Search term

**Response:**
```json
{
  "success": true,
  "videos": [...],
  "count": 10
}
```

### POST /php/upload_video.php
Upload a new video (admin only)

**Request Body:**
```json
{
  "title": "Video Title",
  "description": "Description",
  "video_url": "https://youtube.com/embed/...",
  "category": "equipment",
  "level": "beginner",
  "duration": "25 min",
  "instructor": "Instructor Name",
  "topics": ["Topic1", "Topic2"],
  "thumbnail_url": "https://..."
}
```

### PUT /php/upload_video.php
Update an existing video

**Request Body:** Same as POST with additional `id` field

### DELETE /php/upload_video.php
Delete a video (soft delete)

**Request Body:**
```json
{
  "id": 1
}
```

### GET /php/upload_video.php
Get all videos including inactive (admin only)

## Troubleshooting

### Videos not appearing in farmer dashboard:
1. Check if database table was created: Run setup script
2. Check browser console for JavaScript errors
3. Verify API endpoint is accessible: `http://localhost/Agrohub/php/get_videos.php`

### Cannot upload videos:
1. Check if you're logged in as admin
2. Verify database connection in `php/config.php`
3. Check PHP error logs

### Videos not playing:
1. Ensure you're using YouTube embed URL format
2. Check if video is not region-restricted
3. Try different video URL

## Sample Video Data

The setup script includes 4 sample videos to get you started:
1. Tractor Operation for Beginners (Equipment, Beginner)
2. Harvester Operation & Safety (Equipment, Intermediate)
3. Rotavator Usage & Maintenance (Equipment, Beginner)
4. Equipment Safety Fundamentals (Safety, Beginner)

## Next Steps

1. **Set up the database** - Run the setup script
2. **Test the admin interface** - Upload a test video
3. **Verify farmer view** - Check that videos appear in tutorials page
4. **Add real videos** - Replace sample videos with actual educational content

## Support

For issues or questions, check:
- Database connection settings in `php/config.php`
- Browser developer console for JavaScript errors
- Server error logs for PHP issues

---

**Created:** 2026-01-20
**Version:** 1.0
