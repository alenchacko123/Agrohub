# Agreement Signing & Payment Workflow - Implementation Plan

## Workflow Overview

### Phase 1: Farmer Signs & Pays
1. Farmer digitally signs agreement (typed or drawn)
2. Signature saved with timestamp & IP address
3. Farmer completes payment via Razorpay
4. Payment status → "Completed"
5. Rental status → "Active"
6. Agreement status → "Farmer Signed"

### Phase 2: Owner Signs
1. Owner views agreement in read-only mode
2. Owner can digitally sign (no approve/reject option)
3. Owner signature saved with timestamp & IP address
4. Agreement status → "Fully Signed"
5. Agreement permanently locked

## Database Schema Updates

### `agreements` table needs:
```sql
-- Farmer signature fields (already exist)
signature_data LONGTEXT
signature_type ENUM('text', 'image')
signed_at DATETIME
ip_address VARCHAR(45)

-- NEW: Owner signature fields
owner_signature_data LONGTEXT
owner_signature_type ENUM('text', 'image')
owner_signed_at DATETIME
owner_ip_address VARCHAR(45)

-- Status fields
status VARCHAR(50)  -- 'pending', 'farmer_signed', 'fully_signed'
```

### `bookings` / `rental_requests` tables need:
```sql
payment_status ENUM('pending', 'completed')
rental_status ENUM('pending', 'active', 'completed', 'cancelled')
agreement_status VARCHAR(50)
```

## Status Transitions

### Agreement Status Flow:
```
1. Initial: 'pending'
2. After farmer signs & pays: 'farmer_signed'
3. After owner signs: 'fully_signed'
```

### Payment Status Flow:
```
1. Initial: 'pending'
2. After Razorpay payment: 'completed'
```

### Rental Status Flow:
```
1. Initial: 'pending_payment'
2. After payment: 'active'
3. After rental period ends: 'completed'
```

## Implementation Tasks

### 1. Database Migration Script ✅
- Create `update_agreements_schema.php`
- Add owner signature columns
- Add status columns if missing

### 2. Update `save_signature.php` ✅
- Handle farmer signature
- Update agreement status to 'farmer_signed'
- Store farmer IP and timestamp

### 3. Create `save_owner_signature.php` ✅
- Handle owner signature
- Update agreement status to 'fully_signed'
- Lock agreement from edits
- Store owner IP and timestamp

### 4. Update `process_rental_completion.php` ✅
- After payment success:
  - Set payment_status = 'completed'
  - Set rental_status = 'active'
  - Set agreement_status = 'farmer_signed'

### 5. Update `get_bookings.php` ✅
- JOIN with agreements table
- Return both farmer and owner signatures
- Return all status fields

### 6. Update `agreements.html` Frontend ✅
- Show farmer signature when present
- Show owner signature when present
- Read-only mode for owner before signing
- Owner signing interface (no approve/reject)
- Lock agreement when fully signed
- Display both signatures and timestamps

## Security Requirements

### All database operations must use:
```php
$stmt = $conn->prepare("UPDATE agreements SET ... WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
```

### IP Address capture:
```php
$ip_address = $_SERVER['REMOTE_ADDR'];
```

### Timestamp:
```php
signed_at = NOW()
```

## UI/UX Requirements

### Farmer View:
- Can sign before payment
- Can pay after signing
- Sees "Farmer Signed" status
- Sees owner signature when available
- Cannot edit after owner signs

### Owner View:
- Sees agreements with "Farmer Signed" status
- Can view full agreement details
- Can digitally sign (same UI as farmer)
- No approve/reject buttons
- Cannot edit agreement terms
- Sees "Fully Signed" status after signing

### Locked Agreement (Fully Signed):
- Read-only for both parties
- Shows both signatures with timestamps
- Shows both IP addresses
- Download/Print enabled
- No edit buttons visible

## File Structure

```
php/
  ├── update_agreements_schema.php (NEW)
  ├── save_signature.php (UPDATE)
  ├── save_owner_signature.php (NEW)
  ├── process_rental_completion.php (UPDATE)
  └── get_bookings.php (UPDATE)

agreements.html (MAJOR UPDATE)
  ├── Show/hide based on user type
  ├── Owner signing interface
  ├── Lock mechanism
  └── Dual signature display
```

## Testing Checklist

- [ ] Farmer can sign agreement
- [ ] Farmer can complete payment
- [ ] Status updates to "Farmer Signed"
- [ ] Rental status becomes "Active"
- [ ] Owner sees agreement in dashboard
- [ ] Owner can sign (read-only mode)
- [ ] Owner signature saves properly
- [ ] Status updates to "Fully Signed"
- [ ] Agreement locks after full signing
- [ ] Both signatures display correctly
- [ ] IP addresses and timestamps captured
- [ ] Download PDF includes both signatures
- [ ] Database uses prepared statements
- [ ] No SQL injection vulnerabilities
