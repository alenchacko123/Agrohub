# Profile Completion System

## Overview
This system ensures that all users (especially those signing in with Google) complete their profile with required information before accessing the platform's features.

## Database Schema

### Users Table Updates
Added two new columns:
```sql
ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL AFTER email;
ALTER TABLE users ADD COLUMN profile_completed BOOLEAN DEFAULT FALSE AFTER phone;
```

## Files Created/Modified

### 1. Database Setup
**File:** `php/setup_profile_completion.php`
- Adds `phone` and `profile_completed` columns to users table
- Sets existing users with phone numbers to profile_completed = TRUE
- Run once to update database schema

### 2. Complete Profile Page
**File:** `complete-profile.html`
- Beautiful, modern UI for profile completion
- Collects phone number (required)
- Confirms user role (Farmer, Owner, Worker)
- Pre-fills name and email (read-only)
- Validates phone number format
- Shows success/error messages
- Auto-redirects to appropriate dashboard after completion

### 3. Profile Completion API
**File:** `php/complete_profile.php`
- Validates user ID, phone number, and role
- Checks for duplicate phone numbers
- Updates users table with phone and role
- Sets profile_completed = TRUE
- Uses prepared statements for security
- Returns updated user data

### 4. Authentication Updates
**File:** `php/auth.php` (Modified)
- All login/signup responses now include:
  - `phone` field
  - `profile_completed` field
- Applies to:
  - Traditional signup
  - Traditional login
  - Google Sign-In

### 5. Profile Check Script
**File:** `js/profile-check.js`
- Reusable JavaScript module
- Checks if user is logged in
- Checks if profile is complete
- Redirects to complete-profile.html if incomplete
- Include in all protected pages

### 6.  **Dashboard Integration:**
    *   The `js/profile-check.js` script was added to the `<head>` section of:
        *   `farmer-dashboard.html`
        *   `owner-dashboard.html`
    *   This ensures that users must complete their profile before accessing these dashboards.

7.  **Farmer Profile Enhancements:**
    *   Added **Farm Location / District** and **Farm Size** fields to `farmer-dashboard.html` profile settings.
    *   These fields are editable and saved via `php/update_profile.php`.
    *   `php/update_profile.php` automatically adds `location` and `farm_size` columns to the `users` table if missing.
    *   `php/auth.php` returns these fields on login/Google auth for frontend use.
    *   Enhanced profile settings UI with premium CSS styling (`css/profile-settings.css`).

## User Flow

### New User (Google Sign-In)
```
1. User clicks "Sign in with Google" on signup page
2. Google authentication completes
3. Account created with:
   - name, email, picture from Google
   - phone = NULL
   - profile_completed = FALSE
4. User redirected to dashboard
5. profile-check.js detects incomplete profile
6. User redirected to complete-profile.html
7. User fills phone number and confirms role
8. Profile updated, profile_completed = TRUE
9. User redirected to appropriate dashboard
10. Can now access all features
```

### Existing User (With Phone)
```
1. User logs in (any method)
2. profile_completed = TRUE (or phone exists)
3. Directly access dashboard
4. No interruption
```

### New User (Traditional Signup)
```
1. User creates account via signup form
2. Account created with:
   - phone = NULL
   - profile_completed = FALSE
3. Same flow as Google Sign-In from step 4
```

## Security Features

1. **Prepared Statements:** All database queries use prepared statements
2. **Phone Validation:** Regex pattern `/^[0-9+\-\s()]{10,20}$/`
3. **Duplicate Check:** Prevents same phone number for multiple accounts
4. **User Verification:** Validates user_id before updates
5. **Input Sanitization:** All inputs sanitized via config.php

## Implementation Checklist

### ✅ Phase 1: Database (COMPLETE)
- [x] Create setup script
- [x] Add phone column
- [x] Add profile_completed column
- [x] Migrate existing users

### ✅ Phase 2: Backend (COMPLETE)
- [x] Create complete_profile.php API
- [x] Update auth.php responses
- [x] Add validation logic

### ✅ Phase 3: Frontend (COMPLETE)
- [x] Create complete-profile.html
- [x] Create profile-check.js
- [x] Add to farmer-dashboard.html

### 🔄 Phase 4: Rollout (TODO)
- [ ] Add profile-check.js to owner-dashboard.html
- [ ] Add profile-check.js to worker-dashboard.html
- [ ] Add profile-check.js to rent-equipment.html
- [ ] Add profile-check.js to hire-workers.html
- [ ] Add profile-check.js to agreements.html
- [ ] Add profile-check.js to post-job.html
- [ ] Test complete flow with Google Sign-In
- [ ] Test complete flow with traditional signup

## Adding Profile Check to New Pages

To protect any page and enforce profile completion:

```html
<head>
    ...
    <!-- Profile Completion Check -->
    <script src="js/profile-check.js"></script>
    ...
</head>
```

That's it! The script automatically:
1. Checks if user is logged in
2. Checks if profile is complete
3. Redirects to complete-profile.html if needed

## Testing

### Test Case 1: Google Sign-In (New User)
1. Sign in with a new Google account on signup page
2. Should be redirected to complete-profile.html
3. Fill phone number and select role
4. Submit form
5. Should be redirected to appropriate dashboard
6. Refresh page - should stay on dashboard

### Test Case 2: Traditional Signup (New User)
1. Create account via signup form
2. Should be redirected to complete-profile.html
3. Complete profile
4. Should access dashboard normally

### Test Case 3: Existing User with Phone
1. Log in with existing account that has phone number
2. Should directly access dashboard
3. No redirection to complete-profile.html

### Test Case 4: Direct URL Access
1. Not logged in, try to access farmer-dashboard.html
2. Should redirect to login.html
3. Log in with incomplete profile
4. Should redirect to complete-profile.html

## API Endpoints

### POST /php/complete_profile.php
**Request:**
```json
{
    "user_id": 123,
    "phone": "+91 98765 43210",
    "role": "farmer"
}
```

**Success Response:**
```json
{
    "success": true,
    "message": "Profile completed successfully",
    "user": {
        "id": 123,
        "name": "John Doe",
        "email": "john@example.com",
        "phone": "+91 98765 43210",
        "user_type": "farmer",
        "profile_completed": true
    }
}
```

**Error Response:**
```json
{
    "success": false,
    "error": "Phone number is required"
}
```

## Features Restricted Until Profile Complete

The following features should check for profile completion:
- ✅ Dashboard access
- ✅ Renting equipment
- ✅ Listing equipment
- ✅ Posting jobs
- ✅ Applying for jobs
- ✅ Viewing agreements
- ✅ Making payments
- ✅ Contacting other users

## Notes

- Phone number is now **required** for all users
- Role can be changed during profile completion
- Profile picture from Google is preserved
- Existing users with phones are auto-marked as completed
- System is backward compatible with existing accounts
