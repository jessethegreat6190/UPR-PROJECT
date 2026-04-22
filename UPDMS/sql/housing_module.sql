-- UPDMS Housing Module — Quarters A B C D E F J M O
-- Run in phpMyAdmin: select database = updms_db, then Import this file
-- First run sql/setup.sql to create the database, then run this file
USE updms_db;

CREATE TABLE IF NOT EXISTS quarters (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(10) NOT NULL,
  category ENUM('senior_staff','officers','warders','support','other') DEFAULT 'warders',
  description TEXT,
  total_houses INT DEFAULT 0,
  active TINYINT(1) DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_name (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS houses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  quarter_id INT NOT NULL,
  house_number VARCHAR(20) NOT NULL,
  house_type ENUM('single','double','flat','bungalow','other') DEFAULT 'single',
  bedrooms INT DEFAULT 1,
  status ENUM('occupied','vacant','maintenance','reserved') DEFAULT 'vacant',
  notes TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (quarter_id) REFERENCES quarters(id),
  UNIQUE KEY uq_house (quarter_id, house_number)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS occupants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  house_id INT NOT NULL,
  staff_name VARCHAR(120) NOT NULL,
  service_no VARCHAR(40),
  rank VARCHAR(80),
  department VARCHAR(100),
  phone VARCHAR(20),
  national_id VARCHAR(40),
  move_in_date DATE NOT NULL,
  move_out_date DATE,
  status ENUM('active','transferred','departed','deceased') DEFAULT 'active',
  allocated_by VARCHAR(100),
  notes TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (house_id) REFERENCES houses(id),
  INDEX idx_staff (staff_name),
  INDEX idx_svc (service_no)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS housing_transfers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  occupant_id INT NOT NULL,
  from_house_id INT,
  to_house_id INT NOT NULL,
  transfer_date DATE NOT NULL,
  reason VARCHAR(255),
  authorised_by VARCHAR(100),
  notes TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (occupant_id) REFERENCES occupants(id),
  FOREIGN KEY (from_house_id) REFERENCES houses(id),
  FOREIGN KEY (to_house_id) REFERENCES houses(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS visitor_destinations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  entry_id INT,
  quarter_id INT NOT NULL,
  house_id INT NOT NULL,
  host_name VARCHAR(120),
  visit_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (quarter_id) REFERENCES quarters(id),
  FOREIGN KEY (house_id) REFERENCES houses(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS vehicle_entries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  plate VARCHAR(20) NOT NULL,
  make VARCHAR(60), colour VARCHAR(30), confidence DECIMAL(5,2),
  driver_name VARCHAR(120), driver_nid VARCHAR(30),
  purpose VARCHAR(100), person_visiting VARCHAR(120),
  destination_quarter VARCHAR(100), destination_house VARCHAR(40),
  phone VARCHAR(20), items_declared TEXT,
  gate VARCHAR(60) DEFAULT 'Main Gate', guard_id VARCHAR(60),
  entry_time DATETIME DEFAULT CURRENT_TIMESTAMP,
  exit_time DATETIME,
  flagged TINYINT(1) DEFAULT 0, flag_reason VARCHAR(255),
  status ENUM('active','exited','alert','pending') DEFAULT 'pending',
  guard_notes TEXT,
  INDEX idx_plate (plate), INDEX idx_entry_time (entry_time), INDEX idx_status (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS vehicle_blacklist (
  id INT AUTO_INCREMENT PRIMARY KEY,
  plate VARCHAR(20) NOT NULL UNIQUE,
  reason VARCHAR(255), added_by VARCHAR(100),
  added_at DATETIME DEFAULT CURRENT_TIMESTAMP, active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS vehicles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  plate VARCHAR(20) NOT NULL,
  make VARCHAR(60), model VARCHAR(60), colour VARCHAR(30),
  owner_name VARCHAR(120), owner_id VARCHAR(30),
  category ENUM('staff','supplier','official','visitor','unknown') DEFAULT 'visitor',
  INDEX idx_plate (plate)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS audit_log (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  event_time DATETIME DEFAULT CURRENT_TIMESTAMP,
  user_id VARCHAR(80), action VARCHAR(50), table_name VARCHAR(60),
  record_id INT, old_values JSON, new_values JSON, ip_address VARCHAR(45)
) ENGINE=InnoDB;

-- QUARTERS A B C D E F J M O
INSERT INTO quarters (name, category, description, total_houses) VALUES
('A','senior_staff','Quarter A — Senior Officers & above',20),
('B','senior_staff','Quarter B — Senior Officers & above',20),
('C','officers',    'Quarter C — Inspectors and above',   30),
('D','officers',    'Quarter D — Inspectors and above',   30),
('E','warders',     'Quarter E — Warders and Wardresses', 40),
('F','warders',     'Quarter F — Warders and Wardresses', 40),
('J','warders',     'Quarter J — Warders and Wardresses', 35),
('M','support',     'Quarter M — Support and Civilian Staff',25),
('O','support',     'Quarter O — Support and Civilian Staff',25)
ON DUPLICATE KEY UPDATE description=VALUES(description), total_houses=VALUES(total_houses);

-- Set quarter IDs for house inserts
SET @qa=(SELECT id FROM quarters WHERE name='A');
SET @qb=(SELECT id FROM quarters WHERE name='B');
SET @qc=(SELECT id FROM quarters WHERE name='C');
SET @qd=(SELECT id FROM quarters WHERE name='D');
SET @qe=(SELECT id FROM quarters WHERE name='E');
SET @qf=(SELECT id FROM quarters WHERE name='F');
SET @qj=(SELECT id FROM quarters WHERE name='J');
SET @qm=(SELECT id FROM quarters WHERE name='M');
SET @qo=(SELECT id FROM quarters WHERE name='O');

-- QUARTER A — 20 houses
INSERT IGNORE INTO houses (quarter_id,house_number,house_type,bedrooms,status) VALUES
(@qa,'A-01','bungalow',3,'occupied'),(@qa,'A-02','bungalow',3,'occupied'),(@qa,'A-03','bungalow',3,'vacant'),
(@qa,'A-04','bungalow',3,'occupied'),(@qa,'A-05','bungalow',3,'occupied'),(@qa,'A-06','bungalow',3,'maintenance'),
(@qa,'A-07','flat',2,'occupied'),    (@qa,'A-08','flat',2,'occupied'),    (@qa,'A-09','flat',2,'vacant'),
(@qa,'A-10','flat',2,'occupied'),    (@qa,'A-11','flat',2,'occupied'),    (@qa,'A-12','flat',2,'vacant'),
(@qa,'A-13','flat',2,'occupied'),    (@qa,'A-14','flat',2,'occupied'),    (@qa,'A-15','flat',2,'occupied'),
(@qa,'A-16','flat',2,'vacant'),      (@qa,'A-17','single',1,'occupied'),  (@qa,'A-18','single',1,'occupied'),
(@qa,'A-19','single',1,'vacant'),    (@qa,'A-20','single',1,'occupied');

-- QUARTER B — 20 houses
INSERT IGNORE INTO houses (quarter_id,house_number,house_type,bedrooms,status) VALUES
(@qb,'B-01','bungalow',3,'occupied'),(@qb,'B-02','bungalow',3,'occupied'),(@qb,'B-03','bungalow',3,'occupied'),
(@qb,'B-04','bungalow',3,'vacant'),  (@qb,'B-05','flat',2,'occupied'),    (@qb,'B-06','flat',2,'occupied'),
(@qb,'B-07','flat',2,'maintenance'), (@qb,'B-08','flat',2,'occupied'),    (@qb,'B-09','flat',2,'occupied'),
(@qb,'B-10','flat',2,'vacant'),      (@qb,'B-11','flat',2,'occupied'),    (@qb,'B-12','flat',2,'occupied'),
(@qb,'B-13','single',1,'vacant'),    (@qb,'B-14','single',1,'occupied'),  (@qb,'B-15','single',1,'occupied'),
(@qb,'B-16','single',1,'occupied'),  (@qb,'B-17','single',1,'vacant'),    (@qb,'B-18','single',1,'occupied'),
(@qb,'B-19','double',2,'occupied'),  (@qb,'B-20','double',2,'occupied');

-- QUARTER C — 30 houses
INSERT IGNORE INTO houses (quarter_id,house_number,house_type,bedrooms,status) VALUES
(@qc,'C-01','flat',2,'occupied'), (@qc,'C-02','flat',2,'occupied'), (@qc,'C-03','flat',2,'vacant'),
(@qc,'C-04','flat',2,'occupied'), (@qc,'C-05','flat',2,'occupied'), (@qc,'C-06','flat',2,'occupied'),
(@qc,'C-07','flat',2,'maintenance'),(@qc,'C-08','flat',2,'occupied'),(@qc,'C-09','flat',2,'vacant'),
(@qc,'C-10','flat',2,'occupied'), (@qc,'C-11','flat',2,'occupied'), (@qc,'C-12','flat',2,'occupied'),
(@qc,'C-13','single',1,'occupied'),(@qc,'C-14','single',1,'vacant'),(@qc,'C-15','single',1,'occupied'),
(@qc,'C-16','single',1,'occupied'),(@qc,'C-17','single',1,'occupied'),(@qc,'C-18','single',1,'vacant'),
(@qc,'C-19','single',1,'occupied'),(@qc,'C-20','single',1,'occupied'),(@qc,'C-21','single',1,'occupied'),
(@qc,'C-22','single',1,'occupied'),(@qc,'C-23','single',1,'vacant'), (@qc,'C-24','single',1,'occupied'),
(@qc,'C-25','double',2,'occupied'),(@qc,'C-26','double',2,'occupied'),(@qc,'C-27','double',2,'vacant'),
(@qc,'C-28','double',2,'occupied'),(@qc,'C-29','double',2,'occupied'),(@qc,'C-30','double',2,'occupied');

-- QUARTER D — 30 houses
INSERT IGNORE INTO houses (quarter_id,house_number,house_type,bedrooms,status) VALUES
(@qd,'D-01','flat',2,'occupied'), (@qd,'D-02','flat',2,'vacant'),   (@qd,'D-03','flat',2,'occupied'),
(@qd,'D-04','flat',2,'occupied'), (@qd,'D-05','flat',2,'occupied'), (@qd,'D-06','flat',2,'maintenance'),
(@qd,'D-07','flat',2,'occupied'), (@qd,'D-08','flat',2,'occupied'), (@qd,'D-09','flat',2,'vacant'),
(@qd,'D-10','flat',2,'occupied'), (@qd,'D-11','flat',2,'occupied'), (@qd,'D-12','flat',2,'occupied'),
(@qd,'D-13','single',1,'occupied'),(@qd,'D-14','single',1,'occupied'),(@qd,'D-15','single',1,'vacant'),
(@qd,'D-16','single',1,'occupied'),(@qd,'D-17','single',1,'occupied'),(@qd,'D-18','single',1,'occupied'),
(@qd,'D-19','single',1,'vacant'), (@qd,'D-20','single',1,'occupied'),(@qd,'D-21','single',1,'occupied'),
(@qd,'D-22','single',1,'occupied'),(@qd,'D-23','single',1,'occupied'),(@qd,'D-24','single',1,'vacant'),
(@qd,'D-25','double',2,'occupied'),(@qd,'D-26','double',2,'occupied'),(@qd,'D-27','double',2,'occupied'),
(@qd,'D-28','double',2,'vacant'), (@qd,'D-29','double',2,'occupied'),(@qd,'D-30','double',2,'occupied');

-- QUARTER E — 40 houses
INSERT IGNORE INTO houses (quarter_id,house_number,house_type,bedrooms,status) VALUES
(@qe,'E-01','single',1,'occupied'),(@qe,'E-02','single',1,'occupied'),(@qe,'E-03','single',1,'vacant'),
(@qe,'E-04','single',1,'occupied'),(@qe,'E-05','single',1,'occupied'),(@qe,'E-06','single',1,'occupied'),
(@qe,'E-07','single',1,'maintenance'),(@qe,'E-08','single',1,'occupied'),(@qe,'E-09','single',1,'vacant'),
(@qe,'E-10','single',1,'occupied'),(@qe,'E-11','single',1,'occupied'),(@qe,'E-12','single',1,'occupied'),
(@qe,'E-13','single',1,'occupied'),(@qe,'E-14','single',1,'vacant'), (@qe,'E-15','single',1,'occupied'),
(@qe,'E-16','single',1,'occupied'),(@qe,'E-17','single',1,'occupied'),(@qe,'E-18','single',1,'occupied'),
(@qe,'E-19','single',1,'vacant'), (@qe,'E-20','single',1,'occupied'),(@qe,'E-21','double',2,'occupied'),
(@qe,'E-22','double',2,'occupied'),(@qe,'E-23','double',2,'occupied'),(@qe,'E-24','double',2,'vacant'),
(@qe,'E-25','double',2,'occupied'),(@qe,'E-26','double',2,'occupied'),(@qe,'E-27','double',2,'occupied'),
(@qe,'E-28','double',2,'occupied'),(@qe,'E-29','double',2,'vacant'), (@qe,'E-30','double',2,'occupied'),
(@qe,'E-31','flat',2,'occupied'),  (@qe,'E-32','flat',2,'occupied'),  (@qe,'E-33','flat',2,'vacant'),
(@qe,'E-34','flat',2,'occupied'),  (@qe,'E-35','flat',2,'occupied'),  (@qe,'E-36','flat',2,'occupied'),
(@qe,'E-37','flat',2,'occupied'),  (@qe,'E-38','flat',2,'vacant'),    (@qe,'E-39','flat',2,'occupied'),
(@qe,'E-40','flat',2,'occupied');

-- QUARTER F — 40 houses
INSERT IGNORE INTO houses (quarter_id,house_number,house_type,bedrooms,status) VALUES
(@qf,'F-01','single',1,'occupied'),(@qf,'F-02','single',1,'occupied'),(@qf,'F-03','single',1,'vacant'),
(@qf,'F-04','single',1,'occupied'),(@qf,'F-05','single',1,'occupied'),(@qf,'F-06','single',1,'maintenance'),
(@qf,'F-07','single',1,'occupied'),(@qf,'F-08','single',1,'occupied'),(@qf,'F-09','single',1,'vacant'),
(@qf,'F-10','single',1,'occupied'),(@qf,'F-11','single',1,'occupied'),(@qf,'F-12','single',1,'occupied'),
(@qf,'F-13','single',1,'occupied'),(@qf,'F-14','single',1,'vacant'), (@qf,'F-15','single',1,'occupied'),
(@qf,'F-16','single',1,'occupied'),(@qf,'F-17','single',1,'occupied'),(@qf,'F-18','single',1,'occupied'),
(@qf,'F-19','single',1,'occupied'),(@qf,'F-20','single',1,'vacant'), (@qf,'F-21','double',2,'occupied'),
(@qf,'F-22','double',2,'occupied'),(@qf,'F-23','double',2,'occupied'),(@qf,'F-24','double',2,'occupied'),
(@qf,'F-25','double',2,'vacant'),  (@qf,'F-26','double',2,'occupied'),(@qf,'F-27','double',2,'occupied'),
(@qf,'F-28','double',2,'occupied'),(@qf,'F-29','double',2,'occupied'),(@qf,'F-30','double',2,'vacant'),
(@qf,'F-31','flat',2,'occupied'),  (@qf,'F-32','flat',2,'occupied'),  (@qf,'F-33','flat',2,'occupied'),
(@qf,'F-34','flat',2,'vacant'),    (@qf,'F-35','flat',2,'occupied'),  (@qf,'F-36','flat',2,'occupied'),
(@qf,'F-37','flat',2,'occupied'),  (@qf,'F-38','flat',2,'occupied'),  (@qf,'F-39','flat',2,'vacant'),
(@qf,'F-40','flat',2,'occupied');

-- QUARTER J — 35 houses
INSERT IGNORE INTO houses (quarter_id,house_number,house_type,bedrooms,status) VALUES
(@qj,'J-01','single',1,'occupied'),(@qj,'J-02','single',1,'occupied'),(@qj,'J-03','single',1,'vacant'),
(@qj,'J-04','single',1,'occupied'),(@qj,'J-05','single',1,'occupied'),(@qj,'J-06','single',1,'occupied'),
(@qj,'J-07','single',1,'occupied'),(@qj,'J-08','single',1,'maintenance'),(@qj,'J-09','single',1,'vacant'),
(@qj,'J-10','single',1,'occupied'),(@qj,'J-11','single',1,'occupied'),(@qj,'J-12','single',1,'occupied'),
(@qj,'J-13','single',1,'occupied'),(@qj,'J-14','single',1,'occupied'),(@qj,'J-15','single',1,'vacant'),
(@qj,'J-16','double',2,'occupied'),(@qj,'J-17','double',2,'occupied'),(@qj,'J-18','double',2,'occupied'),
(@qj,'J-19','double',2,'vacant'),  (@qj,'J-20','double',2,'occupied'),(@qj,'J-21','double',2,'occupied'),
(@qj,'J-22','double',2,'occupied'),(@qj,'J-23','double',2,'occupied'),(@qj,'J-24','double',2,'vacant'),
(@qj,'J-25','double',2,'occupied'),(@qj,'J-26','flat',2,'occupied'),  (@qj,'J-27','flat',2,'occupied'),
(@qj,'J-28','flat',2,'occupied'),  (@qj,'J-29','flat',2,'vacant'),    (@qj,'J-30','flat',2,'occupied'),
(@qj,'J-31','flat',2,'occupied'),  (@qj,'J-32','flat',2,'occupied'),  (@qj,'J-33','flat',2,'occupied'),
(@qj,'J-34','flat',2,'vacant'),    (@qj,'J-35','flat',2,'occupied');

-- QUARTER M — 25 houses
INSERT IGNORE INTO houses (quarter_id,house_number,house_type,bedrooms,status) VALUES
(@qm,'M-01','single',1,'occupied'),(@qm,'M-02','single',1,'occupied'),(@qm,'M-03','single',1,'vacant'),
(@qm,'M-04','single',1,'occupied'),(@qm,'M-05','single',1,'occupied'),(@qm,'M-06','single',1,'occupied'),
(@qm,'M-07','single',1,'maintenance'),(@qm,'M-08','single',1,'occupied'),(@qm,'M-09','single',1,'vacant'),
(@qm,'M-10','single',1,'occupied'),(@qm,'M-11','single',1,'occupied'),(@qm,'M-12','single',1,'occupied'),
(@qm,'M-13','double',2,'occupied'),(@qm,'M-14','double',2,'occupied'),(@qm,'M-15','double',2,'vacant'),
(@qm,'M-16','double',2,'occupied'),(@qm,'M-17','double',2,'occupied'),(@qm,'M-18','double',2,'occupied'),
(@qm,'M-19','flat',2,'occupied'),  (@qm,'M-20','flat',2,'occupied'),  (@qm,'M-21','flat',2,'vacant'),
(@qm,'M-22','flat',2,'occupied'),  (@qm,'M-23','flat',2,'occupied'),  (@qm,'M-24','flat',2,'occupied'),
(@qm,'M-25','flat',2,'vacant');

-- QUARTER O — 25 houses
INSERT IGNORE INTO houses (quarter_id,house_number,house_type,bedrooms,status) VALUES
(@qo,'O-01','single',1,'occupied'),(@qo,'O-02','single',1,'occupied'),(@qo,'O-03','single',1,'vacant'),
(@qo,'O-04','single',1,'occupied'),(@qo,'O-05','single',1,'occupied'),(@qo,'O-06','single',1,'occupied'),
(@qo,'O-07','single',1,'occupied'),(@qo,'O-08','single',1,'maintenance'),(@qo,'O-09','single',1,'vacant'),
(@qo,'O-10','single',1,'occupied'),(@qo,'O-11','single',1,'occupied'),(@qo,'O-12','single',1,'occupied'),
(@qo,'O-13','double',2,'occupied'),(@qo,'O-14','double',2,'occupied'),(@qo,'O-15','double',2,'vacant'),
(@qo,'O-16','double',2,'occupied'),(@qo,'O-17','double',2,'occupied'),(@qo,'O-18','double',2,'occupied'),
(@qo,'O-19','flat',2,'occupied'),  (@qo,'O-20','flat',2,'occupied'),  (@qo,'O-21','flat',2,'vacant'),
(@qo,'O-22','flat',2,'occupied'),  (@qo,'O-23','flat',2,'occupied'),  (@qo,'O-24','flat',2,'occupied'),
(@qo,'O-25','flat',2,'vacant');

-- SAMPLE OCCUPANTS — one per quarter
INSERT INTO occupants (house_id,staff_name,service_no,rank,department,phone,move_in_date,status,allocated_by) VALUES
((SELECT id FROM houses WHERE quarter_id=@qa AND house_number='A-01'),'Deputy Commissioner Byarugaba John','UPS-DC-0012','Deputy Commissioner','Administration','+256 77 100 0012','2022-01-15','active','Commissioner General'),
((SELECT id FROM houses WHERE quarter_id=@qb AND house_number='B-01'),'Principal Officer Nakato Sarah','UPS-PO-0041','Principal Officer','Custody','+256 70 200 0041','2021-06-01','active','OC'),
((SELECT id FROM houses WHERE quarter_id=@qc AND house_number='C-01'),'Inspector Kato David','UPS-IN-0089','Inspector','Security','+256 75 300 0089','2023-03-10','active','OC'),
((SELECT id FROM houses WHERE quarter_id=@qd AND house_number='D-01'),'Inspector Apio Grace','UPS-IN-0134','Inspector','Rehabilitation','+256 78 400 0134','2022-09-01','active','OC'),
((SELECT id FROM houses WHERE quarter_id=@qe AND house_number='E-01'),'SGT. Okello James','UPS-SG-0221','Sergeant','Gate 1','+256 70 600 0221','2023-01-05','active','Admin'),
((SELECT id FROM houses WHERE quarter_id=@qf AND house_number='F-01'),'SGT. Nakato Prossy','UPS-SG-0235','Sergeant','Visiting Hall','+256 75 700 0235','2022-11-15','active','Admin'),
((SELECT id FROM houses WHERE quarter_id=@qj AND house_number='J-01'),'Cpl. Ochieng Fred','UPS-CP-0312','Corporal','Perimeter','+256 71 800 0312','2024-02-01','active','Admin'),
((SELECT id FROM houses WHERE quarter_id=@qm AND house_number='M-01'),'Warder Mugisha Robert','UPS-WD-0891','Warder','Cell Block A','+256 76 900 0891','2023-08-12','active','OC'),
((SELECT id FROM houses WHERE quarter_id=@qo AND house_number='O-01'),'Wardress Nalwoga Eva','UPS-WD-0902','Wardress','Female Wing','+256 79 100 0902','2024-01-10','active','OC');

-- VEHICLE SEED DATA
INSERT IGNORE INTO vehicles (plate,make,model,colour,owner_name,owner_id,category) VALUES
('UAB 123D','Toyota','Corolla','Silver','John Mukasa','CM90100012345678','visitor'),
('UAN 456B','Toyota','Hilux','White','Staff Vehicle','UPS-FLEET-001','staff'),
('UBB 789C','Nissan','X-Trail','Black','Supplies Uganda Ltd','SUP-2024-001','supplier'),
('UAX 321K','Toyota','Land Cruiser','Grey','Commissioner General','UPS-CG-001','official');

INSERT IGNORE INTO vehicle_blacklist (plate,reason,added_by) VALUES
('UBF 007X','Contraband attempt — 14 Feb 2026','OC'),
('UAK 999Z','Stolen vehicle — police flag','SGT. Nakato'),
('UCC 111A','Intelligence flag — do not admit','Security Officer');

SELECT CONCAT('Quarters created: ', COUNT(*)) AS result FROM quarters;
SELECT CONCAT('Total houses: ', COUNT(*)) AS result FROM houses;
SELECT CONCAT('Vacant houses: ', SUM(status='vacant')) AS result FROM houses;
SELECT CONCAT('Occupants: ', COUNT(*)) AS result FROM occupants;
