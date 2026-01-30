# Agreement Management Features - Delete & Auto-Expire

## New Features Added

### 1. ✅ Delete Agreement Button
Users can now manually delete any agreement with a single click.

**How it works:**
- Red "Delete" button on each agreement card
- Confirmation dialog before deletion
- Removes the booking from the database
- Automatically refreshes the list

### 2. ✅ Auto-Expire Agreements
Agreements automatically show as "Expired" after their end date passes.

**How it works:**
- Compares end date with current date
- Automatically marks as "Expired" (gray badge)
- Still shows in the list but clearly marked
- Can be deleted manually if needed

## Files Created/Modified

### 1. Created: `php/delete_agreement.php`
- **Purpose**: Backend API to delete agreements
- **Method**: POST
- **Input**: `{ booking_id: <id> }`
- **Output**: Success/error message

### 2. Modified: `agreements.html`
**Changes:**
- Added auto-expiry detection (lines 761-769)
- Added `bookingId` property to agreements
- Added red Delete button to each card (lines 899-902)
- Added `deleteAgreement()` function (lines 1029-1061)

## How to Use

### Delete an Agreement:

1. Go to "Agreements & Insurance" page
2. Find the agreement you want to delete
3. Click the red **"Delete"** button
4. Confirm the deletion
5. ✅ Agreement removed and list refreshes automatically

### View Expired Agreements:

- **Active**: Green badge with checkmark ✅
- **Expired**: Gray badge with X ❌

Expired agreements remain in the list until you manually delete them.

## Status Indicators

| Status | Badge Color | Icon | Meaning |
|--------|-------------|------|---------|
| Active | Green | ✓ | Rental period is ongoing |
| Expired | Gray | ✗ | End date has passed |

## Button Layout

Each agreement card now has 3 buttons:

1. **View Details** (Blue) - Opens full agreement document
2. **Download** (Gray) - Downloads PDF (coming soon)
3. **Delete** (Red) - Removes agreement from database

## Example

**Before end date:**
```
indofarm harvesters
AGR-10
Status: Active ✅
End Date: 1/31/2026
[View Details] [Download] [Delete]
```

**After end date (2/1/2026):**
```
indofarm harvesters
AGR-10
Status: Expired ❌
End Date: 1/31/2026
[View Details] [Download] [Delete]
```

## Safety Features

1. **Confirmation Dialog**: Prevents accidental deletion
2. **Cannot be undone**: Clear warning message
3. **Reload on success**: List automatically updates
4. **Error handling**: Shows friendly error messages

## Testing

### Test Delete Feature:
1. Login to your dashboard
2. Go to "Agreements & Insurance"
3. Click "Delete" on any agreement
4. Confirm deletion
5. ✅ It should disappear from the list

### Test Auto-Expire:
1. Find an agreement with an old end date
2. Refresh the page
3. ✅ It should show as "Expired" with gray badge

## Future Enhancements (Optional)

- **Auto-delete expired**: Automatically remove agreements 30 days after expiry
- **Archive instead of delete**: Move to an "Archived" section
- **Filter by status**: Show only Active or only Expired
- **Bulk delete**: Select multiple agreements to delete at once

---
**Status**: ✅ Implemented
**Date**: 2026-01-29
