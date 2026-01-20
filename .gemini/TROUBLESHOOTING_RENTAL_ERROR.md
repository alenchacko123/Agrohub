# 🔧 Troubleshooting: Network Error When Renting Equipment

## ❌ Problem
Getting "Network error. Please try again." alert when trying to rent equipment.

---

## ✅ Solution Steps

### **1. Make Sure XAMPP is Running**

#### Check Apache Status:
1. Open **XAMPP Control Panel**
2. Check if **Apache** has a green "Running" status
3. If not running, click **Start** next to Apache

#### Alternative Check:
- Open browser and go to: `http://localhost/`
- You should see the XAMPP dashboard
- If you get "Connection refused", Apache is not running

---

### **2. Verify MySQL is Running**

1. In XAMPP Control Panel, check **MySQL** status
2. Should also be green "Running"
3. If not, click **Start** next to MySQL

---

### **3. Test the API Directly**

I created a test file for you:

1. **Open in browser:** `http://localhost/Agrohub/test_rental.html`
2. Click the "Test Submit Rental" button
3. If it works → API is fine, problem is with the page
4. If it fails → Check error message for details

---

### **4. Check Browser Console**

1. Open the page where you're trying to rent equipment
2. Press **F12** to open Developer Tools
3. Go to **Console** tab
4. Try to submit rental again
5. Look for error messages (red text)
6. Send me a screenshot if you see errors

---

### **5. Verify File Paths**

Make sure these files exist:
- ✅ `C:\xampp\htdocs\Agrohub\php\submit_rental_request.php`
- ✅ `C:\xampp\htdocs\Agrohub\php\config.php`
- ✅ `C:\xampp\htdocs\Agrohub\rent-equipment.html`

---

### **6. Check You're Logged In**

The rental system requires you to be logged in:

1. Make sure you're logged in as a **farmer**
2. Check by opening browser console and typing:
   ```javascript
   localStorage.getItem('agrohub_user')
   ```
3. Should show user data, not `null`
4. If `null`, log in again

---

### **7. Common Issues & Fixes**

| Issue | Solution |
|-------|----------|
| **"Failed to fetch"** | XAMPP Apache not running |
| **HTTP 404** | PHP file path wrong or file missing |
| **HTTP 500** | PHP syntax error - check error logs |
| **No response** | Check if you're using `localhost` not `127.0.0.1` |
| **CORS error** | Already handled in PHP, shouldn't happen |

---

### **8. Check PHP Error Logs**

If nothing else works:

1. Go to: `C:\xampp\apache\logs\`
2. Open `error.log`
3. Look for recent errors (today's timestamp)
4. Send me the error if you find one

---

## 🚀 Quick Test

**Run this in Browser Console (F12):**

```javascript
fetch('php/submit_rental_request.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        equipment_id: 1,
        equipment_name: "Test",
        farmer_id: 1,
        farmer_name: "Test",
        farmer_email: "test@test.com",
        owner_id: 1,
        start_date: "2026-01-19",
        end_date: "2026-01-20",
        num_days: 1,
        total_amount: 1000,
        delivery_address: "Test",
        need_operator: 0,
        need_insurance: 0,
        special_requirements: ""
    })
})
.then(r => r.json())
.then(d => console.log(d))
.catch(e => console.error(e));
```

---

## ✅ What I Fixed

1. **Enhanced error messages** - Now shows specific issues
2. **Better validation** - Checks all fields before submitting
3. **Loading state** - Button shows "Submitting..." while processing
4. **Auto-redirect** - Goes to farmer dashboard after success
5. **Fixed data types** - Corrected PHP parameter binding

---

## 📞 Need More Help?

If still not working, send me:
1. Screenshot of the error
2. Browser console output (F12 → Console tab)
3. Confirmation that XAMPP Apache is running

**Status:** System should now work correctly! ✅
