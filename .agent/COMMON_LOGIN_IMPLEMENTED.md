# Common Login Page Implementation

I have consolidated the separate login pages into a single **Common Login Page** (`login.html`) that supports all user types (Farmer, Owner, Admin, Worker).

## Changes Made:

### 1. Created `login.html`
- **Unified Interface**: A generic, premium-branded login page suitable for all users.
- **Dynamic Redirect**: The login script automatically identifies the user's role (Farmer, Owner, Worker, Admin) from the database and directs them to the correct dashboard:
  - **Farmers** -> `farmer-dashboard.html`
  - **Owners** -> `owner-dashboard.html`
  - **Workers** -> `worker-dashboard.html`
  - **Admins** -> `admin-dashboard.html`
- **Google Sign-In Support**: Integrated Google One-Tap/Sign-In that also respects the "registered email only" rule. It will not auto-create accounts without a specific role (users must sign up first via the specific signup pages).

### 2. Updated `php/auth.php`
- **Flexible Authentication**: Modified `handleLogin` to make `userType` optional.
  - If `userType` is provided (legacy pages), it verifies the role.
  - If `userType` is NOT provided (common login), it finds the user by email regardless of role.
- **Google Auth Update**: Updated `handleGoogleAuth` to prevent auto-creation of accounts when the role is unknown (common login flow), ensuring security and data integrity.

### 3. Updated `landingpage.html`
- Replaced links to `login-farmer.html` with the new `login.html`.
- Updated "Get Started" buttons to point to the central login hub.

## Next Steps
- You can now direct all users to `login.html`.
- The old login pages (`login-farmer.html`, `login-owner.html`, etc.) still function for backward compatibility but can be deprecated or redirected to `login.html` in the future.
- Users relying on specific role-based access will be seamlessly handled by the single entry point.
