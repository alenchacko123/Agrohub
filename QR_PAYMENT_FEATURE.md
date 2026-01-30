# QR Code Payment Feature for Agreements

## Feature Overview
Added QR code payment option for **farmers only** (not for owners) to pay for rental agreements using UPI apps.

## How It Works

### For Farmers:
1. Go to "Agreements & Insurance" page
2. Each agreement card now shows a green **"Pay with QR"** button
3. Click the button to see a UPI QR code
4. Scan with any UPI app (Google Pay, PhonePe, Paytm, etc.)
5. Complete payment in your UPI app
6. Click "Mark as Paid" button
7. ✅ Payment confirmed!

### For Owners:
- **No "Pay with QR" button** shown
- Owners only see: View Details, Download, Delete buttons
- Owners receive payment notifications (future feature)

## User Interface

### Farmer sees:
```
┌─────────────────────────────────────┐
│ indofarm harvesters          Active │
│ AGR-10                              │
│ Amount: ₹4,000                      │
│                                     │
│ [🟢 Pay with QR] [👁️ View] [🗑️ Delete]│
└─────────────────────────────────────┘
```

### Owner sees:
```
┌─────────────────────────────────────┐
│ indofarm harvesters          Active │
│ AGR-10                              │
│ Amount: ₹4,000                      │
│                                     │
│ [👁️ View Details] [📥 Download] [🗑️ Delete]│
└─────────────────────────────────────┘
```

## QR Code Payment Modal

When farmer clicks "Pay with QR":

```
┌──────────────────────────────────────┐
│        Pay with QR Code          [X] │
├──────────────────────────────────────┤
│ Scan the QR code to complete payment│
│                                      │
│ 📋 Payment Details:                  │
│ Equipment: indof arm harvesters      │
│ Amount: ₹4,000                       │
│                                      │
│  ┌────────────┐                      │
│  │  QR CODE   │  ← Scannable UPI QR  │
│  │    HERE    │                      │
│  └────────────┘                      │
│                                      │
│ Scan with Google Pay, PhonePe, etc. │
│                                      │
│ ✅ Safe & Secure Payment             │
│    via UPI                           │
│                                      │
│ [Close] [✓ Mark as Paid]             │
└──────────────────────────────────────┘
```

## Technical Details

### QR Code Content
Generated UPI deep link format:
```
upi://pay?pa=agrohub@upi&pn=AgroHub&am=4000&cu=INR&tn=Payment for indofarm harvesters
```

### Libraries Used
- **QR Code Generator**: `qrcodejs` (CDN)
- **Size**: 256x256 pixels
- **Error Correction**: High level

### User Detection
```javascript
getCurrentUserType() // Returns 'farmer' or 'owner'
```

Only farmers see the "Pay with QR" button.

## Files Modified

### 1. `agreements.html`
**Added:**
- QR code library CDN (line 20-21)
- QR Payment Modal (lines 684-709)
- Conditional "Pay with QR" button for farmers (lines 920-925)
- `getCurrentUserType()` function
- `openQRPayment()` function
- `markPaymentComplete()` function

## Testing

### Test as Farmer:
1. Login as farmer: `http://localhost/Agrohub/login-farmer.html`
2. Go to "Agreements & Insurance"
3. ✅ **You should see green "Pay with QR" button**
4. Click it to see QR code modal
5. Scan with UPI app or click "Mark as Paid"

### Test as Owner:
1. Login as owner: `http://localhost/Agrohub/login-owner.html`
2. Go to "Agreements & Insurance"  
3. ✅ **No "Pay with QR" button** (correct!)
4. Only see: View Details, Download, Delete

## UPI Payment Flow

1. **Farmer clicks "Pay with QR"**
2. **QR code modal opens**
3. **QR code contains UPI payment link**
4. **Farmer scans with phone**
5. **UPI app opens** (Google Pay/PhonePe/etc.)
6. **Payment details pre-filled**
7. **Farmer confirms payment in UPI app**
8. **Returns to browser**
9. **Clicks "Mark as Paid"**
10. **✅ Success message shown**

## Future Enhancements

### Payment Verification (Coming Soon):
- **Automatic Payment Verification**: Check UPI transaction status
- **Payment History**: Track all payments
- **Receipt Generation**: Auto-generate payment receipts
- **Owner Notifications**: Alert owner when payment received
- **Payment Status**: Show "Paid" / "Pending" badges

### Database Update (Optional):
```php
// php/update_payment_status.php
UPDATE bookings 
SET payment_status = 'paid', 
    paid_at = NOW() 
WHERE id = ?
```

## Security Notes

- ✅ QR code contains only payment information
- ✅ Actual payment happens in secure UPI apps
- ✅ No sensitive data stored in browser
- ✅ Owner cannot see/use QR payment option

## Customization

### Change UPI ID:
Edit line 1146 in `agreements.html`:
```javascript
const upiId = 'yourUpiId@bankname'; // Change this
```

### Change QR Code Size:
Edit lines 1151-1152:
```javascript
width: 300,  // Change size
height: 300, // Change size
```

---
**Status**: ✅ Implemented
**Date**: 2026-01-29
**User**: Farmers only
