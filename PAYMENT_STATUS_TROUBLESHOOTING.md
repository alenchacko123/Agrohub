# Payment Status Not Updating - Troubleshooting Guide

## Issue
After completing payment via Razorpay, the agreement still shows "Payment Pending" status.

## What Should Happen

1. **Payment completes** → Razorpay success handler called
2. **`process_rental_completion.php` called** → Creates booking with:
   - `payment_status = 'completed'`
   - `rental_status = 'active'`
   - `status = 'active'`
3. **Success modal shows** → "Payment Successful!"
4. **`loadAgreementsFromDatabase()` called** → Refreshes agreement list
5. **Agreement card updated** → Shows "Paid" or "Active" status

## Current Flow Check

### File: `php/process_rental_completion.php`
✅ Creates booking with correct statuses (if columns exist)
✅ Updates rental_request to status='paid'

### File: `php/get_bookings.php`  
✅ Now returns payment_status, transaction_id, signature data

### File: `agreements.html`
✅ Calls `loadAgreementsFromDatabase()` after payment
❓ Need to verify the data mapping

## Debugging Steps

### 1. Check if Booking Was Created
Open your database and run:
```sql
SELECT id, farmer_id, equipment_id, status, payment_status, rental_status, 
       paid_amount, paid_at, transaction_id, created_at 
FROM bookings 
ORDER BY created_at DESC 
LIMIT 5;
```

**Expected Result:**
- Latest booking should have:
  - `status = 'active'`
  - `payment_status = 'completed'` (if column exists)
  - `paid_amount = [your payment amount]`
  - `transaction_id = [razorpay payment id]`

### 2. Check Rental Request Status
```sql
SELECT id, status, agreement_status, total_amount, created_at
FROM rental_requests
WHERE status = 'paid'
ORDER BY created_at DESC
LIMIT 5;
```

**Expected Result:**
- Should show `status = 'paid'`

### 3. Check Frontend Data Loading
Open browser console and check:
1. After payment, does `loadAgreementsFromDatabase()` get called?
2. What data does `get_bookings.php` return?

```javascript
// Check in console after payment
console.log('Agreements loaded:', agreements);
```

## Likely Issues & Fixes

### Issue #1: Frontend Not Mapping Payment Status Correctly

**Check in `agreements.html` around line 1364:**
```javascript
paymentStatus: booking.payment_status || 'paid',
```

This might default to 'paid' but the UI might be checking for 'completed'.

**Fix Required:**
Update the agreement rendering to check BOTH the booking's payment_status AND if it's from the bookings table (which means it's already paid).

### Issue #2: Agreement is Loading from `rental_requests` Instead of `bookings`

After payment:
- Booking is created in `bookings` table
- But `rental_requests` still exists with status='paid'
- If the frontend loads from `rental_requests` first, it shows no payment details

**Check the code around line 1373-1400** in `agreements.html`:
- It loads BOTH bookings AND rental_requests
- Need to verify that paid bookings are being loaded, not the old requests

### Issue #3: Page Not Refreshing

The `loadAgreementsFromDatabase()` is called, but:
- Maybe it's not re-rendering the cards
- Maybe caching is preventing update

## Quick Fix to Test

### Add Debug Logging

In `agreements.html`, find `loadAgreementsFromDatabase()` and add:
```javascript
async function loadAgreementsFromDatabase() {
    try {
        // ... existing code ...
        
        console.log('=== DEBUG: Bookings Data ===');
        console.log('Bookings response:', bookingsData);
        console.log('Requests response:', requestsData);
        console.log('All agreements:', allAgreements);
        
        // ... rest of code ...
    }
}
```

Then after payment, open console and check what data is being loaded.

## Most Likely Solution

The issue is probably in how the agreement card displays the payment status. Let me check the actual status display logic...

### In `agreements.html`, find where status badges are rendered:

Look for the status badge rendering (around line 1450-1550):
```javascript
${agreement.paymentStatus === 'paid' ? ... }
```

**Problem:** It might be checking for `'paid'` but the actual value is `'completed'`

**Fix:** Update to check for BOTH:
```javascript
${(agreement.paymentStatus === 'paid' || agreement.paymentStatus === 'completed') ? ... }
```

OR update the backend to always return 'paid' in the status field.

## Immediate Action Items

1. **Check database** - Are bookings being created with correct status?
2. **Check console** - What data is `get_bookings.php` returning?
3. **Update status check** - Change status comparison to handle both 'paid' and 'completed'

## Files to Check/Update

1. `agreements.html` (lines 1364, 1450-1550, 1670-1750)
2. Database tables: `bookings`, `rental_requests`
3. Browser console after payment

Would you like me to update the frontend status checking logic to handle both 'paid' and 'completed' statuses?
