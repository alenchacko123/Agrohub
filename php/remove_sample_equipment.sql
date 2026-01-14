-- Remove Sample Equipment Listings
-- This SQL script will help you identify and remove sample equipment

-- First, let's see all equipment to identify which ones to delete
SELECT id, equipment_name, owner_name, price_per_day, created_at 
FROM equipment 
ORDER BY id;

-- Based on your description, you want to keep only the first equipment (Mahindra tractor)
-- and remove the sample ones (Rotavator, John Deere, Mahindra 575)

-- OPTION 1: Delete by ID (Recommended - Replace with actual IDs)
-- After running the SELECT query above, note the IDs of the sample equipment
-- Then uncomment and run these lines (replace X, Y, Z with actual IDs):

-- DELETE FROM equipment WHERE id = 2;  -- Replace 2 with Rotavator ID
-- DELETE FROM equipment WHERE id = 3;  -- Replace 3 with John Deere ID
-- DELETE FROM equipment WHERE id = 4;  -- Replace 4 with Mahindra 575 ID

-- OPTION 2: Delete by Name (If you know the exact names)
-- Uncomment the lines below if you want to delete by equipment name:

-- DELETE FROM equipment WHERE equipment_name = 'Rotavator 6ft Heavy Duty';
-- DELETE FROM equipment WHERE equipment_name = 'John Deere 5050D';
-- DELETE FROM equipment WHERE equipment_name = 'Mahindra 575 DI Tractor';

-- OPTION 3: Keep only the first equipment and delete all others
-- WARNING: This will delete ALL equipment except the one with the lowest ID
-- Only use this if you're absolutely sure you only want to keep the first equipment
-- Uncomment the line below to use this option:

-- DELETE FROM equipment WHERE id > (SELECT MIN(id) FROM (SELECT id FROM equipment) AS temp);

-- After deletion, verify the remaining equipment:
SELECT id, equipment_name, owner_name, price_per_day, created_at 
FROM equipment 
ORDER BY id;
