# Equipment Specifications Feature

## Implemented Features
1. **Database Update**: Added `model`, `year_of_manufacture`, `fuel_type`, and `capacity` columns to the `equipment` table.
2. **Add Equipment Form**: Added input fields for these specifications in the "Add New Equipment" modal.
3. **View Details Modal**: Displays these specifications in a dedicated grid.

## How it Works
- **Adding Equipment**: When you list a new item, you can now provide technical details like Model, Year, Fuel Type, and Capacity.
- **Viewing Equipment**: Clicking "View Details" fetches this data and displays it.

## Note for Existing Listings
- Equipment listed *before* this update will show "--" for these fields because the data was not captured previously.
- You can delete and re-list the equipment to add these details if needed.

## Database Migration
The migration script `add_specs_columns.php` was automatically executed to update your database schema.
