-- Add captured_plates table for storing OCR captures
CREATE TABLE IF NOT EXISTS captured_plates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    plate_number VARCHAR(20),
    image_path VARCHAR(255) NOT NULL,
    confidence INT,
    driver_name VARCHAR(100),
    captured_by INT NOT NULL,
    captured_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (captured_by) REFERENCES users(id)
);

-- Insert existing images from uploads/plates/ folder
-- This will be populated by the ocr_process.php when capturing
