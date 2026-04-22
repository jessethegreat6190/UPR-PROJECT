-- UPDMS Database Setup Script
-- Run this to create the database and all tables

CREATE DATABASE IF NOT EXISTS updms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE updms_db;

-- 1. Facilities (Prisons)
CREATE TABLE IF NOT EXISTS facilities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    facility_code VARCHAR(10) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    region VARCHAR(50),
    type ENUM('maximum', 'medium', 'minimum', 'rehabilitation') DEFAULT 'medium',
    capacity INT DEFAULT 0,
    address VARCHAR(255),
    contact_phone VARCHAR(20),
    contact_email VARCHAR(100),
    is_active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Users (Staff)
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    badge_number VARCHAR(20) UNIQUE,
    role ENUM('admin', 'hq_command', 'supervisor', 'gate_officer') NOT NULL,
    facility_id INT,
    phone VARCHAR(20),
    email VARCHAR(100),
    is_active TINYINT DEFAULT 1,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE SET NULL
);

-- 3. Prisoners
CREATE TABLE IF NOT EXISTS prisoners (
    id INT PRIMARY KEY AUTO_INCREMENT,
    prisoner_number VARCHAR(20) UNIQUE NOT NULL,
    national_id VARCHAR(20),
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    gender ENUM('male', 'female') NOT NULL,
    date_of_birth DATE,
    photo_path VARCHAR(255),
    nationality VARCHAR(50) DEFAULT 'Ugandan',
    marital_status VARCHAR(20),
    education_level VARCHAR(50),
    occupation VARCHAR(50),
    address VARCHAR(255),
    next_of_kin_name VARCHAR(100),
    next_of_kin_phone VARCHAR(20),
    next_of_kin_relation VARCHAR(30),
    admission_date DATE NOT NULL,
    facility_id INT NOT NULL,
    cell_block VARCHAR(20),
    cell_number VARCHAR(10),
    status ENUM('remand', 'convicted', 'released', 'transferred', 'deceased', 'escaped') DEFAULT 'remand',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (facility_id) REFERENCES facilities(id)
);

-- 4. Warrants
CREATE TABLE IF NOT EXISTS warrants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    prisoner_id INT NOT NULL,
    warrant_number VARCHAR(30) UNIQUE NOT NULL,
    warrant_type ENUM('remand', 'conviction', 'transfer', 'parole', 'release') NOT NULL,
    issuing_court VARCHAR(100),
    issuing_magistrate VARCHAR(100),
    case_number VARCHAR(30),
    offense_description TEXT,
    sentence_years INT DEFAULT 0,
    sentence_months INT DEFAULT 0,
    sentence_days INT DEFAULT 0,
    issue_date DATE NOT NULL,
    effective_date DATE,
    expiry_date DATE,
    status ENUM('active', 'expired', 'revoked', 'executed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prisoner_id) REFERENCES prisoners(id) ON DELETE CASCADE
);

-- 5. Sentence Records
CREATE TABLE IF NOT EXISTS sentences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    prisoner_id INT NOT NULL,
    warrant_id INT,
    sentence_start_date DATE NOT NULL,
    sentence_end_date DATE,
    total_sentence_days INT,
    served_days INT DEFAULT 0,
    remission_days INT DEFAULT 0,
    parole_eligible_date DATE,
    release_date DATE,
    release_type ENUM('full', 'parole', 'remission', 'compassionate', 'expiry'),
    status ENUM('serving', 'released', 'parole', 'completed') DEFAULT 'serving',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (prisoner_id) REFERENCES prisoners(id) ON DELETE CASCADE,
    FOREIGN KEY (warrant_id) REFERENCES warrants(id) ON DELETE SET NULL
);

-- 6. Court Appearances
CREATE TABLE IF NOT EXISTS court_appearances (
    id INT PRIMARY KEY AUTO_INCREMENT,
    prisoner_id INT NOT NULL,
    court_name VARCHAR(100) NOT NULL,
    case_number VARCHAR(30),
    hearing_date DATE NOT NULL,
    hearing_time TIME,
    purpose VARCHAR(200),
    outcome VARCHAR(200),
    next_hearing_date DATE,
    status ENUM('scheduled', 'completed', 'adjourned', 'failed') DEFAULT 'scheduled',
    escort_officer_id INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prisoner_id) REFERENCES prisoners(id) ON DELETE CASCADE,
    FOREIGN KEY (escort_officer_id) REFERENCES users(id) ON DELETE SET NULL
);

-- 7. Prisoner Transfers
CREATE TABLE IF NOT EXISTS transfers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    prisoner_id INT NOT NULL,
    from_facility_id INT NOT NULL,
    to_facility_id INT NOT NULL,
    transfer_date DATE NOT NULL,
    reason TEXT,
    authorized_by INT,
    status ENUM('pending', 'in_transit', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prisoner_id) REFERENCES prisoners(id) ON DELETE CASCADE,
    FOREIGN KEY (from_facility_id) REFERENCES facilities(id),
    FOREIGN KEY (to_facility_id) REFERENCES facilities(id),
    FOREIGN KEY (authorized_by) REFERENCES users(id) ON DELETE SET NULL
);

-- 8. Daily Counts
CREATE TABLE IF NOT EXISTS daily_counts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    facility_id INT NOT NULL,
    count_date DATE NOT NULL,
    opening_count INT DEFAULT 0,
    admissions INT DEFAULT 0,
    releases INT DEFAULT 0,
    transfers_in INT DEFAULT 0,
    transfers_out INT DEFAULT 0,
    deaths INT DEFAULT 0,
    escapes INT DEFAULT 0,
    closing_count INT DEFAULT 0,
    remand_count INT DEFAULT 0,
    convicted_count INT DEFAULT 0,
    verified_by INT,
    verification_time TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (facility_id) REFERENCES facilities(id),
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_daily_count (facility_id, count_date)
);

-- 9. Visitors
CREATE TABLE IF NOT EXISTS visitors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    visitor_type ENUM('inmate', 'hospital', 'staff', 'official', 'delivery') NOT NULL,
    national_id VARCHAR(20),
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    company_name VARCHAR(100),
    vehicle_plate VARCHAR(20),
    vehicle_photo_path VARCHAR(255),
    driver_name VARCHAR(100),
    is_known_driver TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 10. Visitor Bookings
CREATE TABLE IF NOT EXISTS visitor_bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    visitor_id INT NOT NULL,
    prisoner_id INT,
    facility_id INT,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    visit_purpose VARCHAR(200),
    status ENUM('pending', 'approved', 'rejected', 'completed', 'cancelled') DEFAULT 'pending',
    approved_by INT,
    approved_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (visitor_id) REFERENCES visitors(id) ON DELETE CASCADE,
    FOREIGN KEY (prisoner_id) REFERENCES prisoners(id) ON DELETE SET NULL,
    FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- 11. Visitor Logs (Entry/Exit Records)
CREATE TABLE IF NOT EXISTS visitor_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    visitor_id INT NOT NULL,
    prisoner_id INT,
    booking_id INT,
    facility_id INT NOT NULL,
    visitor_type ENUM('inmate', 'hospital', 'staff', 'official', 'delivery') NOT NULL,
    entry_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    exit_time TIMESTAMP NULL,
    duration_minutes INT,
    cargo_description TEXT,
    cargo_checked TINYINT DEFAULT 0,
    contraband_seized TINYINT DEFAULT 0,
    seized_items TEXT,
    status ENUM('inside', 'exited', 'blocked') DEFAULT 'inside',
    gate_officer_entry_id INT NOT NULL,
    gate_officer_exit_id INT,
    entry_photo_path VARCHAR(255),
    exit_photo_path VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (visitor_id) REFERENCES visitors(id),
    FOREIGN KEY (prisoner_id) REFERENCES prisoners(id) ON DELETE SET NULL,
    FOREIGN KEY (booking_id) REFERENCES visitor_bookings(id) ON DELETE SET NULL,
    FOREIGN KEY (facility_id) REFERENCES facilities(id),
    FOREIGN KEY (gate_officer_entry_id) REFERENCES users(id),
    FOREIGN KEY (gate_officer_exit_id) REFERENCES users(id) ON DELETE SET NULL
);

-- 12. Vehicles
CREATE TABLE IF NOT EXISTS vehicles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    plate_number VARCHAR(20) NOT NULL,
    vehicle_type VARCHAR(30),
    color VARCHAR(30),
    make_model VARCHAR(50),
    owner_name VARCHAR(100),
    owner_phone VARCHAR(20),
    company VARCHAR(100),
    last_driver_name VARCHAR(100),
    last_visit TIMESTAMP NULL,
    total_visits INT DEFAULT 0,
    is_blacklisted TINYINT DEFAULT 0,
    blacklisted_reason TEXT,
    blacklisted_by INT,
    blacklisted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (blacklisted_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_plate (plate_number)
);

-- 13. Vehicle Logs (Entry/Exit)
CREATE TABLE IF NOT EXISTS vehicle_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vehicle_id INT NOT NULL,
    visitor_log_id INT,
    facility_id INT NOT NULL,
    visitor_type ENUM('inmate', 'hospital', 'staff', 'official', 'delivery') NOT NULL,
    driver_name VARCHAR(100),
    driver_id VARCHAR(20),
    entry_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    exit_time TIMESTAMP NULL,
    duration_minutes INT,
    cargo_description TEXT,
    cargo_checked TINYINT DEFAULT 0,
    status ENUM('inside', 'exited') DEFAULT 'inside',
    gate_officer_entry_id INT NOT NULL,
    gate_officer_exit_id INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (visitor_log_id) REFERENCES visitor_logs(id) ON DELETE SET NULL,
    FOREIGN KEY (facility_id) REFERENCES facilities(id),
    FOREIGN KEY (gate_officer_entry_id) REFERENCES users(id),
    FOREIGN KEY (gate_officer_exit_id) REFERENCES users(id) ON DELETE SET NULL
);

-- 14. Contraband Seizures
CREATE TABLE IF NOT EXISTS contraband_seizures (
    id INT PRIMARY KEY AUTO_INCREMENT,
    visitor_log_id INT,
    vehicle_log_id INT,
    item_description TEXT NOT NULL,
    quantity VARCHAR(50),
    unit VARCHAR(20),
    location_found VARCHAR(100),
    prisoner_name VARCHAR(100),
    seized_by INT NOT NULL,
    seized_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    disposition ENUM('retained', 'destroyed', 'returned', 'forwarded_police') DEFAULT 'retained',
    case_reference VARCHAR(50),
    notes TEXT,
    FOREIGN KEY (visitor_log_id) REFERENCES visitor_logs(id) ON DELETE SET NULL,
    FOREIGN KEY (vehicle_log_id) REFERENCES vehicle_logs(id) ON DELETE SET NULL,
    FOREIGN KEY (seized_by) REFERENCES users(id)
);

-- 15. Incidents
CREATE TABLE IF NOT EXISTS incidents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    facility_id INT NOT NULL,
    incident_type ENUM('security', 'health', 'fire', 'escape_attempt', 'assault', 'contraband', 'death', 'riot', 'other') NOT NULL,
    incident_date DATE NOT NULL,
    incident_time TIME NOT NULL,
    location VARCHAR(100),
    description TEXT NOT NULL,
    persons_involved TEXT,
    action_taken TEXT,
    outcome VARCHAR(200),
    reported_by INT NOT NULL,
    status ENUM('reported', 'investigating', 'resolved', 'closed') DEFAULT 'reported',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (facility_id) REFERENCES facilities(id),
    FOREIGN KEY (reported_by) REFERENCES users(id)
);

-- 16. Action Logs (Audit Trail - APPEND ONLY)
CREATE TABLE IF NOT EXISTS action_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    user_name VARCHAR(100),
    action_type VARCHAR(50) NOT NULL,
    table_name VARCHAR(50) NOT NULL,
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    device_info VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 17. Remand Alerts
CREATE TABLE IF NOT EXISTS remand_alerts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    prisoner_id INT NOT NULL,
    days_on_remand INT NOT NULL,
    legal_limit_days INT DEFAULT 365,
    alert_date DATE NOT NULL,
    status ENUM('pending', 'acknowledged', 'resolved') DEFAULT 'pending',
    action_taken TEXT,
    acknowledged_by INT,
    acknowledged_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prisoner_id) REFERENCES prisoners(id) ON DELETE CASCADE,
    FOREIGN KEY (acknowledged_by) REFERENCES users(id) ON DELETE SET NULL
);

-- 18. Search Records
CREATE TABLE IF NOT EXISTS search_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    search_type ENUM('visitor', 'vehicle', 'cell', 'staff', 'other') NOT NULL,
    visitor_log_id INT,
    vehicle_log_id INT,
    facility_id INT NOT NULL,
    searched_by INT NOT NULL,
    search_location VARCHAR(100),
    items_found TEXT,
    contraband_found TINYINT DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (visitor_log_id) REFERENCES visitor_logs(id) ON DELETE SET NULL,
    FOREIGN KEY (vehicle_log_id) REFERENCES vehicle_logs(id) ON DELETE SET NULL,
    FOREIGN KEY (facility_id) REFERENCES facilities(id),
    FOREIGN KEY (searched_by) REFERENCES users(id)
);

-- 19. System Settings
CREATE TABLE IF NOT EXISTS system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT,
    description VARCHAR(200),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create indexes for better performance
CREATE INDEX idx_prisoners_status ON prisoners(status);
CREATE INDEX idx_prisoners_facility ON prisoners(facility_id);
CREATE INDEX idx_visitor_logs_status ON visitor_logs(status);
CREATE INDEX idx_visitor_logs_entry ON visitor_logs(entry_time);
CREATE INDEX idx_vehicle_logs_status ON vehicle_logs(status);
CREATE INDEX idx_vehicle_logs_entry ON vehicle_logs(entry_time);
CREATE INDEX idx_incidents_date ON incidents(incident_date);
CREATE INDEX idx_action_logs_user ON action_logs(user_id);
CREATE INDEX idx_action_logs_created ON action_logs(created_at);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password_hash, full_name, badge_number, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'ADMIN001', 'admin');

-- Insert sample facility
INSERT INTO facilities (facility_code, name, region, type, capacity) VALUES
('LUZ', 'Luzira Upper Prison', 'Central', 'maximum', 3000),
('KLA', 'Kampala Remand Home', 'Central', 'medium', 1500),
('MUB', 'Mubende Prison', 'Western', 'medium', 800);

-- Insert sample HQ Command user
INSERT INTO users (username, password_hash, full_name, badge_number, role) VALUES
('hqadmin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'HQ Administrator', 'HQ001', 'hq_command');

-- Insert sample facility users
INSERT INTO users (username, password_hash, full_name, badge_number, role, facility_id) VALUES
('oc_luzira', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'OC Luzira', 'LUZ001', 'supervisor', 1),
('gate1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Gate Officer One', 'LUZ101', 'gate_officer', 1);

-- Insert system settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('overstay_hours', '72', 'Vehicle overstay alert threshold in hours'),
('remand_limit_days', '365', 'Maximum remand period in days'),
('remand_alert_days', '330', 'Days before remand limit to trigger alert'),
('system_version', '1.0.0', 'Current system version');
