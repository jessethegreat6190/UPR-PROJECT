-- Hospital Pharmacy Management System Database
-- Run this SQL to set up the database

CREATE DATABASE IF NOT EXISTS hospital_management;
USE hospital_management;

-- Medicines table
CREATE TABLE IF NOT EXISTS medicines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100) DEFAULT 'tablet',
    unit_price DECIMAL(12,2) NOT NULL,
    cost_price DECIMAL(12,2) NOT NULL,
    total_stock INT DEFAULT 0,
    low_stock_threshold INT DEFAULT 10,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Batches table for FIFO tracking
CREATE TABLE IF NOT EXISTS batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medicine_id INT NOT NULL,
    batch_number VARCHAR(100) NOT NULL,
    expiry_date DATE NOT NULL,
    quantity INT NOT NULL,
    remaining_qty INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE
);

-- Sales ledger
CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medicine_id INT NOT NULL,
    qty_sold INT NOT NULL,
    total_price DECIMAL(12,2) NOT NULL,
    cost_amount DECIMAL(12,2) NOT NULL,
    profit DECIMAL(12,2) NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'Cash',
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE
);

-- Sample data
INSERT INTO medicines (name, category, unit_price, cost_price, total_stock, low_stock_threshold) VALUES
('Amoxicillin 500mg', 'tablet', 500, 350, 100, 20),
('Paracetamol 500mg', 'tablet', 200, 150, 50, 15),
('Ciprofloxacin 250mg', 'tablet', 800, 600, 30, 10),
('ORS Sachets', 'other', 1500, 1000, 25, 8),
('Iron Syrup', 'syrup', 3500, 2500, 20, 5);

INSERT INTO batches (medicine_id, batch_number, expiry_date, quantity, remaining_qty) VALUES
(1, 'BN-2024-001', '2026-12-31', 100, 100),
(2, 'BN-2024-002', '2027-06-30', 50, 50),
(3, 'BN-2024-003', '2025-09-30', 30, 30),
(4, 'BN-2024-004', '2026-03-30', 25, 25),
(5, 'BN-2024-005', '2026-08-31', 20, 20);
