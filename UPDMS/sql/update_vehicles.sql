-- Add missing columns for kiosk vehicle tracking
ALTER TABLE vehicles ADD COLUMN driver_name VARCHAR(100) AFTER plate_number;
ALTER TABLE vehicles ADD COLUMN company VARCHAR(100) AFTER driver_name;
