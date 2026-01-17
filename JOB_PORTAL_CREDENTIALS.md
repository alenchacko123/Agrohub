# 🔐 Job Portal Login - Sample Credentials

## ✅ How to Create a Test Account

Since the Job Portal login works by checking if you have submitted a job application, you need to follow these steps:

### **Method 1: Apply for a Job (Recommended)**

1. **Open Job Portal**:
   ```
   http://localhost/Agrohub/job-portal.html
   ```

2. **Click "Apply Now"** on any position

3. **Fill the Application Form**:
   ```
   📝 Sample Data:
   
   Full Name: John Doe
   Email: john@test.com
   Phone: 9876543210
   Location: Bangalore, Karnataka
   Experience: 3 years
   Cover Letter: I am passionate about agricultural technology
   Skills: PHP, JavaScript, MySQL
   Education: Bachelor in Computer Science
   ```

4. **Submit Application**
   - You'll be automatically redirected to the dashboard
   - Your login credentials are stored

### **Method 2: Login with Your Email**

After applying, you can login anytime:

1. **Open Job Portal Login**:
   ```
   http://localhost/Agrohub/login-job-portal.html
   ```

2. **Enter Your Credentials**:
   ```
   📧 Email: john@test.com
   🔒 Password: any password (not validated currently)
   ```

3. **Click "Sign In"**
   - The system checks if your email has applications
   - If found, you're logged in
   - Redirects to dashboard

---

## 🎯 Test Credentials

### **Sample Account 1**
```
Email: john@test.com
Password: password123
Status: Need to apply first via job-portal.html
```

### **Sample Account 2**
```
Email: jane@example.com
Password: test123
Status: Need to apply first via job-portal.html
```

---

## 🔄 Complete Flow

### **First Time User:**
```
1. Visit: job-portal.html
2. Browse positions
3. Click "Apply Now"
4. Fill form with your email
5. Submit
6. Auto-redirected to dashboard ✅
```

### **Returning User:**
```
1. Visit: login-job-portal.html
2. Enter email you used to apply
3. Enter any password
4. Click "Sign In"
5. Redirected to dashboard ✅
```

---

## ⚡ Quick Test

Want to test immediately? Run this in MySQL:

```sql
-- Open phpMyAdmin or MySQL command line
USE agrohub;

-- Insert sample applicant
INSERT INTO job_portal_applications 
(full_name, email, phone, location, position_applied, experience_years, 
cover_letter, skills, education, application_status, applied_date)
VALUES 
('John Doe', 'john@test.com', '9876543210', 'Bangalore', 
'IT Developer', 3, 'Passionate about agri-tech', 
'PHP, JavaScript, MySQL', 'B.Tech in CS', 'submitted', NOW());
```

Then login with:
- **Email**: `john@test.com`
- **Password**: anything (e.g., `test123`)

---

## 🎉 Ready to Test!

**Quick Links:**
- 📝 Apply: http://localhost/Agrohub/job-portal.html
- 🔐 Login: http://localhost/Agrohub/login-job-portal.html
- 📊 Dashboard: http://localhost/Agrohub/job-portal-dashboard.html

---

## 📌 Important Notes

1. **Password is NOT checked** - The current implementation only checks if email exists in applications
2. **First user must apply** - You can't login without first submitting an application
3. **Google Sign-In works** - You can also use "Continue with Google"
4. **Data stored in localStorage** - Your session is saved in browser

---

## 🔧 For Production

In production, you should:
- ✅ Add password hashing
- ✅ Implement proper authentication
- ✅ Create separate user accounts table
- ✅ Add email verification
- ✅ Implement password reset

Currently, this is simplified for demo purposes.

---

**Created**: January 14, 2026  
**Status**: ✅ Working  
**Database**: agrohub  
**Table**: job_portal_applications
