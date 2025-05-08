-- First, add the new columns
ALTER TABLE users 
ADD COLUMN first_name VARCHAR(50) AFTER email,
ADD COLUMN middle_name VARCHAR(50) AFTER first_name,
ADD COLUMN last_name VARCHAR(50) AFTER middle_name;

-- Update existing records by splitting full_name into the new columns
UPDATE users 
SET first_name = SUBSTRING_INDEX(full_name, ' ', 1),
    last_name = SUBSTRING_INDEX(full_name, ' ', -1),
    middle_name = CASE 
        WHEN LENGTH(full_name) - LENGTH(REPLACE(full_name, ' ', '')) > 1 
        THEN SUBSTRING_INDEX(SUBSTRING_INDEX(full_name, ' ', 2), ' ', -1)
        ELSE NULL 
    END;

-- Remove the old full_name column
ALTER TABLE users DROP COLUMN full_name; 