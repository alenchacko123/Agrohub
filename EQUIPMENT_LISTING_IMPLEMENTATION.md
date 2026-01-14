# Equipment Listing System - Implementation Summary

## Overview
Implemented a complete equipment listing system where owners can add equipment through the owner dashboard, and it automatically appears on the farmer dashboard's rent equipment page.

## Files Created/Modified

### 1. PHP Backend Files (Created)

#### `php/add_equipment.php`
- Handles equipment listing submissions from owners
- Supports both form upload and base64 image uploads
- Validates input data and file types
- Stores equipment information in the database
- Returns JSON response with success/error status

#### `php/get_equipment.php`
- Fetches equipment listings from database
- Supports filtering by category and availability
- Returns equipment data in JSON format
- Used by farmer dashboard to display available equipment

#### `php/create_equipment_table.sql`
- SQL schema for creating the equipment table
- Includes fields: id, owner_id, owner_name, equipment_name, category, price_per_day, equipment_condition, availability_status, description, image_url, timestamps
- Has indexes for efficient querying

### 2. Owner Dashboard (`owner-dashboard.html`) - Modified

#### Changes Made:
1. **Added Form Input Names and IDs:**
   - equipment_name
   - category (added Rotavator and Plough options)
   - price_per_day
   - condition
   - availability
   - description
   - equipment_image

2. **Image Upload with Preview:**
   - Added `previewEquipmentImage(input)` function
   - Shows image preview before upload
   - Added `removeImage(event)` function to clear selection
   - Preview container with thumbnail and remove button

3. **Form Submission:**
   - Completely rewrote `submitNewListing()` function
   - Collects all form data using FormData API
   - Gets owner information from localStorage
   - Handles image upload
   - Sends POST request to PHP backend
   - Shows loading state during submission
   - Displays success/error notifications
   - Reloads page after successful submission

4. **Notification System:**
   - Added `showNotification(message, type)` function
   - Displays success/error messages with animations
   - Auto-dismisses after 4 seconds

### 3. Farmer Dashboard - Rent Equipment (`rent-equipment.html`) - Modified

#### Changes Made:
1. **Dynamic Data Loading:**
   - Replaced hardcoded `equipmentData` array
   - Added `loadEquipment()` async function
   - Fetches equipment from `php/get_equipment.php`
   - Shows loading spinner while fetching
   - Displays empty state if no equipment found
   - Shows error state if API call fails

2. **Data Transformation:**
   - Transforms database format to match card display format
   - Maps availability status correctly
   - Handles image URLs from database

3. **Updated Initialization:**
   - Changed from `renderEquipment()` to `loadEquipment ()`

### 4. Directory Structure Created
```
c:\xampp\htdocs\Agrohub\
├── uploads/
│   └── equipment/        (Created for storing uploaded images)
```

## Database Setup Instructions

1. **Create Equipment Table:**
   ```bash
   # Navigate to phpMyAdmin or MySQL CLI
   # Execute the SQL file:
   ```
   Run the SQL in `php/create_equipment_table.sql`:
   ```sql
   CREATE TABLE IF NOT EXISTS equipment (
       id INT AUTO_INCREMENT PRIMARY KEY,
       owner_id INT NOT NULL,
       owner_name VARCHAR(255) NOT NULL,
       equipment_name VARCHAR(255) NOT NULL,
       category VARCHAR(50) NOT NULL,
       price_per_day DECIMAL(10, 2) NOT NULL,
       equipment_condition VARCHAR(50) NOT NULL,
       availability_status VARCHAR(50) NOT NULL DEFAULT 'available',
       description TEXT,
       image_url VARCHAR(500),
       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
       updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
       INDEX idx_category (category),
       INDEX idx_availability (availability_status),
       INDEX idx_owner (owner_id)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
   ```

## Usage Flow

### For Equipment Owners:
1. Login to owner dashboard
2. Click "Add Equipment" from Quick Actions or sidebar
3. Fill in equipment details:
   - Equipment Name (e.g., Mahindra 575)
   - Category (Tractor, Harvester, etc.)
   - Price per day
   - Condition (New, Excellent, Good, Fair)
   - Availability Status
   - Upload equipment image
   - Add description
4. Click "Publish Listing"
5. Equipment is saved to database
6. Success notification appears
7. Page reloads showing the new listing

### For Farmers:
1. Login to farmer dashboard
2. Navigate to "Rent Equipment" or "View Machinery"
3. Equipment listings are automatically fetched from database
4. See all available equipment with:
   - Images
   - Names and categories
   - Prices
   - Availability status
   - Owner information
5. Click on any equipment card to open rental modal
6. Fill in rental details and submit

## Features Implemented

✅ **Image Upload Functionality:**
- Supports file upload through form
- Image preview before submission
- Ability to remove selected image
- Stores images in `uploads/equipment/` directory
- Validates file type and size (max 5MB)

✅ **Form Validation:**
- All required fields validated
- Browser native validation
- Server-side validation in PHP

✅ **Database Integration:**
- Equipment data persisted in MySQL
- Proper indexing for performance
- Timestamps for tracking

✅ **Real-time Updates:**
- New listings appear immediately after submission
- Farmer dashboard fetches latest data on page load

✅ **Error Handling:**
- Network errors handled gracefully
- Empty states for no equipment
- User-friendly error messages

✅ **Responsive Design:**
- Form works on all devices
- Image preview responsive
- Loading states and notifications

## Testing the Implementation

1. **Add Equipment as Owner:**
   - Go to `owner-dashboard.html`
   - Click "Add Equipment"
   - Fill form with test data:
     * Name: "Test Tractor"
     * Category: "Tractor"
     * Price: 2000
     * Condition: "Good"
     * Upload an image
     * Description: "Test description with HP, Year, etc."
   - Click "Publish Listing"
   - Should see success message

2. **View on Farmer Dashboard:**
   - Go to `farmer-dashboard.html`
   - Click "Rent Equipment" or "View Machinery"
   - Should see the newly added equipment
   - Click on it to open rental modal

## API Endpoints

### POST `/php/add_equipment.php`
**Request:** FormData with:
- equipment_name
- category
- price_per_day
- condition
- availability
- description
- equipment_image (file)
- owner_name
- owner_id

**Response:**
```json
{
    "success": true,
    "message": "Equipment listed successfully!",
    "equipment_id": 1,
    "image_url": "uploads/equipment/equipment_xxx.png"
}
```

### GET `/php/get_equipment.php`
**Query Parameters:**
- category (optional): filter by category
- availability (optional): filter by availability

**Response:**
```json
{
    "success": true,
    "equipment": [
        {
            "id": 1,
            "owner_id": 1,
            "owner_name": "John Doe",
            "equipment_name": "Mahindra 575",
            "category": "tractor",
            "price_per_day": "2000.00",
            "equipment_condition": "good",
            "availability_status": "available",
            "description": "50 HP, 2020 model, Diesel",
            "image_url": "uploads/equipment/xxx.png",
            "created_at": "2026-01-12 14:30:00"
        }
    ],
    "count": 1
}
```

## Notes
- Images are stored in `uploads/equipment/` with unique filenames
- Default image is used if no image is uploaded
- Owner information is retrieved from localStorage
- Equipment availability syncs between owner and farmer dashboards
