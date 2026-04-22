-- Add address column to visitors table
ALTER TABLE visitors ADD COLUMN address VARCHAR(255) AFTER phone;
