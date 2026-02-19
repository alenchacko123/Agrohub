# Admin Access Setup

I have configured the database with a system admin account and updated the authentication flow to support it correctly.

## Admin Credentials
**Email:** `admin@gmail.com`
**Password:** `admin123`

## Implementation Details
1. **Database Update**: The `users` table now contains a record for the admin with a hashed password and `user_type = 'admin'`.
2. **Login Flow**:
   - The common `login.html` page accepts these credentials.
   - The backend `auth.php` verifies the password against the database hash.
   - Upon success, it returns `userType: 'admin'`.
   - The frontend automatically redirects to `admin-dashboard.html` based on this role.
3. **Cleanup**: Removed the previous temporary hardcoded admin check (`admin@agrohub.com`) from the frontend code to ensure only DB-verified admins can login.

## Verification
You can now log in at `login.html` using the credentials above, and you will be redirected to the Admin Dashboard.
