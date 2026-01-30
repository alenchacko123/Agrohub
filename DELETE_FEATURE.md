# Equipment Deletion Logic

## Implemented Features
1. **Backend Script**: `delete_equipment.php` handles secure deletion with ownership verification.
2. **Dashboard UI**:
   - Added a "Delete" (Trash icon) button to every equipment card.
   - Added a main "Delete Listing" button inside the View Details modal.
3. **Safety**: Includes a confirmation prompt ("Are you sure...") before deletion occurs.

## How to Use
- **Quick Delete**: Click the trash icon on the dashboard card.
- **Review & Delete**: Open "View Details" and use the large "Delete Listing" button at the bottom.
- **Logic**: Deletes the record permanently from the database. Note: It does not check for active rentals (assuming owner manages this), but basic deletion logic works.

## Files Modified
- `owner-dashboard.html`
- `php/delete_equipment.php`
