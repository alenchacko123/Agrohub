-- Add location column to equipment table
ALTER TABLE equipment 
ADD COLUMN location VARCHAR(255) NOT NULL AFTER description;
