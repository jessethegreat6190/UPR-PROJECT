<?php
// ==========================================
// UPS Staff & Housing Management System
// Database configuration - adjust as needed
// ==========================================
session_start();
header('Content-Type: text/html; charset=utf-8');

// Database settings
$db_host = 'localhost';
$db_name = 'ups_housing';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$db_name`");
    
    // Create tables
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `staff` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `staff_code` VARCHAR(50) UNIQUE NOT NULL,
            `name` VARCHAR(100) NOT NULL,
            `department` VARCHAR(100),
            `quarter` CHAR(1) NOT NULL,
            `house_number` VARCHAR(5) NOT NULL,
            `phone` VARCHAR(20),
            `status` VARCHAR(50) DEFAULT 'On Duty',
            UNIQUE KEY `unique_quarter_house` (`quarter`, `house_number`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `visits` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `visitor_name` VARCHAR(100) NOT NULL,
            `visitor_phone` VARCHAR(20) NOT NULL,
            `dest_type` ENUM('staff', 'housing', 'custom_staff') NOT NULL,
            `dest_ref` VARCHAR(100),
            `dest_display` VARCHAR(200) NOT NULL,
            `purpose` VARCHAR(100),
            `time_in` DATETIME NOT NULL,
            INDEX `idx_time` (`time_in`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    // Insert demo staff if table is empty
    $check = $pdo->query("SELECT COUNT(*) FROM staff")->fetchColumn();
    if ($check == 0) {
        $demoStaff = [
            ['UPS/A/0012', 'SSALI BRIAN', 'Administration', 'B', '5', '+256 772 123456', 'On Duty'],
            ['UPS/S/1034', 'NYAKATO SARAH', 'Security', 'A', '2', '+256 773 234567', 'On Duty'],
            ['UPS/G/4521', 'SGT. OKELLO JAMES', 'Security', 'D', '12', '+256 774 345678', 'Off Duty'],
            ['UPS/M/2210', 'DR. NAMUTEBI ROSE', 'Medical', 'C', '8', '+256 775 456789', 'On Duty'],
            ['UPS/W/3312', 'KALEMA PETER', 'Welfare', 'E', '3', '+256 776 567890', 'On Leave'],
            ['UPS/L/4415', 'WASSWA FRED', 'Logistics', 'O', '15', '+256 777 678901', 'On Duty'],
        ];
        $stmt = $pdo->prepare("INSERT INTO staff (staff_code, name, department, quarter, house_number, phone, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($demoStaff as $staff) {
            $stmt->execute($staff);
        }
    }
    
    // Insert demo visits if empty
    $checkVisits = $pdo->query("SELECT COUNT(*) FROM visits")->fetchColumn();
    if ($checkVisits == 0) {
        $demoVisits = [
            ['John Mukasa', '+256 712 345678', 'staff', 'UPS/A/0012', 'SSALI BRIAN', 'Personal', '2025-03-18 08:30:00'],
            ['Grace Tendo', '+256 782 987654', 'staff', 'UPS/S/1034', 'NYAKATO SARAH', 'Official Business', '2025-03-18 09:15:00'],
            ['Peter Opiyo', '+256 701 234567', 'housing', 'A:7', 'Quarter A, House 7', 'Family Visit', '2025-03-18 10:00:00'],
        ];
        $stmt = $pdo->prepare("INSERT INTO visits (visitor_name, visitor_phone, dest_type, dest_ref, dest_display, purpose, time_in) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($demoVisits as $visit) {
            $stmt->execute($visit);
        }
    }
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// ==========================================
// API Endpoints (JSON responses)
// ==========================================
$action = $_GET['action'] ?? $_POST['action'] ?? null;

if ($action) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Invalid action'];
    
    try {
        // Get staff list
        if ($action === 'getStaff') {
            $stmt = $pdo->query("SELECT id, staff_code, name, department, quarter, house_number, phone, status FROM staff ORDER BY name");
            $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'staff' => $staff]);
            exit;
        }
        
        // Get visits log
        if ($action === 'getVisits') {
            $stmt = $pdo->query("SELECT id, visitor_name, visitor_phone, dest_display, purpose, time_in FROM visits ORDER BY time_in DESC");
            $visits = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'visits' => $visits]);
            exit;
        }
        
        // Register new visit
        if ($action === 'registerVisit') {
            $data = json_decode(file_get_contents('php://input'), true);
            $visitorName = trim($data['visitorName'] ?? '');
            $visitorPhone = trim($data['visitorPhone'] ?? '');
            $destType = $data['destType'] ?? '';
            $purpose = $data['purpose'] ?? 'Personal / Family';
            
            if (empty($visitorName) || empty($visitorPhone)) {
                throw new Exception('Visitor name and phone are required');
            }
            
            $destDisplay = '';
            $destRef = null;
            
            if ($destType === 'staff') {
                $staffId = $data['staffId'] ?? '';
                $customName = trim($data['customStaffName'] ?? '');
                if (!empty($staffId)) {
                    $stmt = $pdo->prepare("SELECT name, quarter, house_number FROM staff WHERE staff_code = ?");
                    $stmt->execute([$staffId]);
                    $staff = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($staff) {
                        $destDisplay = $staff['name'] . " ({$staff['quarter']} House {$staff['house_number']})";
                        $destRef = $staffId;
                        $destType = 'staff';
                    } else {
                        throw new Exception('Selected staff not found');
                    }
                } elseif (!empty($customName)) {
                    $destDisplay = $customName . " (External Visitor)";
                    $destRef = $customName;
                    $destType = 'custom_staff';
                } else {
                    throw new Exception('Please select a staff member or enter a name');
                }
            } 
            elseif ($destType === 'housing') {
                $quarter = $data['quarter'] ?? '';
                $house = $data['houseNumber'] ?? '';
                if (empty($quarter) || empty($house)) {
                    throw new Exception('Please select quarter and house number');
                }
                $destDisplay = "Quarter $quarter, House $house";
                $destRef = "$quarter:$house";
                $destType = 'housing';
            }
            else {
                throw new Exception('Invalid destination type');
            }
            
            $stmt = $pdo->prepare("INSERT INTO visits (visitor_name, visitor_phone, dest_type, dest_ref, dest_display, purpose, time_in) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$visitorName, $visitorPhone, $destType, $destRef, $destDisplay, $purpose]);
            
            echo json_encode(['success' => true, 'message' => 'Visit registered successfully']);
            exit;
        }
        
        // Get housing overview (quarters with houses and occupants)
        if ($action === 'getHousingOverview') {
            $quarters = ['A', 'B', 'C', 'D', 'E', 'F', 'J', 'M', 'O'];
            $maxHouses = 20;
            $overview = [];
            
            $stmt = $pdo->query("SELECT quarter, house_number, name, department FROM staff ORDER BY quarter, CAST(house_number AS UNSIGNED)");
            $occupants = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $occupants[$row['quarter']][$row['house_number']] = $row;
            }
            
            foreach ($quarters as $q) {
                $houses = [];
                for ($i = 1; $i <= $maxHouses; $i++) {
                    $houseNum = (string)$i;
                    if (isset($occupants[$q][$houseNum])) {
                        $houses[] = [
                            'number' => $houseNum,
                            'occupied' => true,
                            'name' => $occupants[$q][$houseNum]['name'],
                            'department' => $occupants[$q][$houseNum]['department']
                        ];
                    } else {
                        $houses[] = ['number' => $houseNum, 'occupied' => false];
                    }
                }
                $overview[] = ['quarter' => $q, 'houses' => $houses];
            }
            echo json_encode(['success' => true, 'overview' => $overview, 'quarters' => $quarters]);
            exit;
        }
        
        // Transfer staff to new housing
        if ($action === 'transferStaff') {
            $data = json_decode(file_get_contents('php://input'), true);
            $staffCode = $data['staffCode'] ?? '';
            $newQuarter = $data['newQuarter'] ?? '';
            $newHouse = $data['newHouse'] ?? '';
            
            if (empty($staffCode) || empty($newQuarter) || empty($newHouse)) {
                throw new Exception('Missing transfer details');
            }
            
            // Check if target house is occupied by another staff
            $stmt = $pdo->prepare("SELECT id, name FROM staff WHERE quarter = ? AND house_number = ? AND staff_code != ?");
            $stmt->execute([$newQuarter, $newHouse, $staffCode]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                throw new Exception("House {$newQuarter}:{$newHouse} is already occupied by {$existing['name']}. Please choose another house.");
            }
            
            $stmt = $pdo->prepare("UPDATE staff SET quarter = ?, house_number = ? WHERE staff_code = ?");
            $stmt->execute([$newQuarter, $newHouse, $staffCode]);
            
            echo json_encode(['success' => true, 'message' => 'Staff transferred successfully']);
            exit;
        }
        
        // Add new staff member
        if ($action === 'addStaff') {
            $data = json_decode(file_get_contents('php://input'), true);
            $staffCode = trim($data['staffCode'] ?? '');
            $name = trim($data['name'] ?? '');
            $department = trim($data['department'] ?? '');
            $quarter = $data['quarter'] ?? '';
            $houseNumber = $data['houseNumber'] ?? '';
            $phone = trim($data['phone'] ?? '');
            $status = $data['status'] ?? 'On Duty';
            
            if (empty($staffCode) || empty($name) || empty($quarter) || empty($houseNumber)) {
                throw new Exception('Staff code, name, quarter and house number are required');
            }
            
            // Check if quarter/house is taken
            $stmt = $pdo->prepare("SELECT id FROM staff WHERE quarter = ? AND house_number = ?");
            $stmt->execute([$quarter, $houseNumber]);
            if ($stmt->fetch()) {
                throw new Exception("Quarter $quarter, House $houseNumber is already occupied");
            }
            
            // Check unique staff code
            $stmt = $pdo->prepare("SELECT id FROM staff WHERE staff_code = ?");
            $stmt->execute([$staffCode]);
            if ($stmt->fetch()) {
                throw new Exception("Staff code $staffCode already exists");
            }
            
            $stmt = $pdo->prepare("INSERT INTO staff (staff_code, name, department, quarter, house_number, phone, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$staffCode, $name, $department, $quarter, $houseNumber, $phone, $status]);
            
            echo json_encode(['success' => true, 'message' => 'Staff added successfully']);
            exit;
        }
        
        // Get staff for transfer list
        if ($action === 'getStaffForTransfer') {
            $stmt = $pdo->query("SELECT staff_code, name, quarter, house_number FROM staff ORDER BY name");
            $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'staff' => $staff]);
            exit;
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// ==========================================
// Main HTML/CSS/JS Interface
// ==========================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>UPS — Staff Visitor & Housing Management</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
    
      font-family: 'Montserrat', sans-serif;
      background: #F5F5F5;
      color: #111;
      padding: 24px;
    }
    h1 {
  font-weight: 800;
  letter-spacing: -0.5px;
}

.header-bar h2 {
  font-weight: 700;
}

.btn-primary {
  font-family: 'Montserrat', sans-serif;
}

    .container {
      max-width: 1400px;
      margin: 0 auto;
    }

    /* ========== BLACK & WHITE / GRAYSCALE THEME ========== */
    :root {
      --black: #000000;
      --white: #ffffff;
      --gray-100: #f8f8f8;
      --gray-200: #eaeaea;
      --gray-300: #d4d4d4;
      --gray-400: #b0b0b0;
      --gray-500: #7a7a7a;
      --gray-600: #525252;
      --gray-700: #2e2e2e;
      --gray-800: #1a1a1a;
      --gray-900: #0a0a0a;
      --text-dark: #111111;
      --text-light: #eeeeee;
    }

    h1 {
      font-family: 'Syne', sans-serif;
      color: var(--gray-900);
      margin-bottom: 8px;
      font-weight: 800;
      letter-spacing: -0.5px;
    }

    .subtitle {
      color: var(--gray-500);
      margin-bottom: 24px;
      font-size: 14px;
      font-weight: 400;
    }

    /* Header */
    .header-bar {
      background: var(--gray-800);
      border-radius: 16px;
      padding: 20px 28px;
      display: flex;
      align-items: center;
      gap: 16px;
      margin-bottom: 28px;
      border-bottom: 3px solid var(--gray-600);
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .logo-placeholder {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      background: var(--gray-500);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      border: 1px solid var(--gray-400);
    }
    .header-bar h2 {
      color: white;
      font-family: 'Syne', sans-serif;
      font-weight: 800;
      letter-spacing: -0.3px;
    }
    .header-bar p {
      color: var(--gray-300);
      font-size: 12px;
      margin-top: 4px;
    }
    #liveClock {
      color: var(--gray-200);
      font-size: 13px;
      font-weight: 500;
      background: rgba(255,255,255,0.08);
      padding: 6px 12px;
      border-radius: 40px;
    }

    /* Tabs */
    .tabs {
      display: flex;
      gap: 8px;
      margin-bottom: 24px;
      border-bottom: 2px solid var(--gray-300);
      padding-bottom: 8px;
      flex-wrap: wrap;
    }
    .tab {
      padding: 12px 24px;
      border-radius: 40px;
      font-weight: 600;
      cursor: pointer;
      background: var(--white);
      border: 1px solid var(--gray-300);
      color: var(--gray-600);
      transition: all 0.2s ease;
    }
    .tab:hover {
      background: var(--gray-200);
      border-color: var(--gray-500);
      color: var(--black);
    }
    .tab.active {
      background: var(--black);
      color: white;
      border-color: var(--black);
    }

    /* Filter bar (log only) */
    .filter-bar {
      display: flex;
      gap: 12px;
      margin-bottom: 24px;
      flex-wrap: wrap;
    }
    .filter-bar input, .filter-bar select {
      padding: 12px 16px;
      border: 1.5px solid var(--gray-300);
      border-radius: 12px;
      font-size: 14px;
      font-family: 'DM Sans', sans-serif;
      background: white;
      transition: 0.15s;
    }
    .filter-bar input:focus, .filter-bar select:focus {
      outline: none;
      border-color: var(--gray-700);
    }
    .btn-primary {
      background: var(--black);
      color: white;
      border: none;
      border-radius: 12px;
      padding: 12px 24px;
      font-weight: 700;
      cursor: pointer;
      font-family: 'Syne', sans-serif;
      transition: all 0.2s;
    }
    .btn-primary:hover {
      background: var(--gray-700);
      transform: translateY(-1px);
    }
    .btn-secondary {
      background: white;
      color: var(--black);
      border: 1.5px solid var(--gray-600);
      border-radius: 12px;
      padding: 12px 24px;
      font-weight: 600;
      cursor: pointer;
      transition: 0.2s;
    }
    .btn-secondary:hover {
      background: var(--gray-200);
      border-color: var(--black);
    }

    /* Tables */
    .log-table {
      background: white;
      border-radius: 20px;
      border: 1px solid var(--gray-300);
      overflow-x: auto;
      margin-top: 20px;
    }
    .log-table table {
      width: 100%;
      border-collapse: collapse;
      min-width: 600px;
    }
    .log-table th {
      background: var(--gray-800);
      color: white;
      padding: 14px 16px;
      text-align: left;
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .log-table td {
      padding: 12px 16px;
      border-bottom: 1px solid var(--gray-200);
      font-size: 13px;
    }
    .log-table tr:last-child td {
      border-bottom: none;
    }
    .log-table tr:hover td {
      background: var(--gray-100);
    }

    /* Housing grid */
    .housing-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    .quarter-card {
      border: 1px solid var(--gray-300);
      border-radius: 20px;
      padding: 12px;
      background: white;
    }
    .quarter-title {
      background: var(--gray-800);
      color: white;
      padding: 6px 14px;
      border-radius: 40px;
      display: inline-block;
      margin-bottom: 12px;
      font-weight: 700;
    }
    .house-list {
      max-height: 300px;
      overflow-y: auto;
    }
    .house-item {
      border-bottom: 1px solid var(--gray-200);
      padding: 6px 0;
    }
    .vacant {
      color: var(--gray-500);
    }

    /* Modal */
    .modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0,0,0,0.7);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 1000;
    }
    .modal {
      background: white;
      border-radius: 24px;
      width: 90%;
      max-width: 550px;
      box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    }
    .modal-header {
      background: var(--gray-800);
      padding: 20px 24px;
      color: white;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-radius: 24px 24px 0 0;
    }
    .modal-header h3 {
      font-family: 'Syne', sans-serif;
      font-weight: 700;
    }
    .close-modal {
      background: none;
      border: none;
      color: white;
      font-size: 28px;
      cursor: pointer;
      line-height: 1;
    }
    .modal-body {
      padding: 24px;
    }
    .form-group {
      margin-bottom: 18px;
    }
    .form-group label {
      display: block;
      font-size: 12px;
      font-weight: 700;
      color: var(--gray-600);
      margin-bottom: 6px;
      text-transform: uppercase;
      letter-spacing: 0.3px;
    }
    .form-group input, .form-group select {
      width: 100%;
      padding: 12px 14px;
      border: 1.5px solid var(--gray-300);
      border-radius: 12px;
      font-size: 14px;
      background: white;
    }
    .radio-group {
      display: flex;
      gap: 24px;
      margin-top: 6px;
    }
    .radio-group label {
      font-weight: 500;
      text-transform: none;
      display: flex;
      align-items: center;
      gap: 6px;
      color: var(--gray-800);
    }
    hr {
      border-color: var(--gray-200);
      margin: 12px 0;
    }
    .inline-hint {
      font-size: 11px;
      color: var(--gray-500);
      margin-top: 4px;
    }
    .btn-card {
      padding: 8px 10px;
      border-radius: 10px;
      border: 1.5px solid var(--gray-300);
      background: white;
      font-weight: 600;
      font-size: 12px;
      cursor: pointer;
      transition: all 0.15s;
      text-align: center;
    }
    .btn-card:hover {
      border-color: var(--gray-700);
      color: var(--black);
      background: var(--gray-100);
    }
  </style>
</head>
<body>
<div class="container">
  
  <div class="header-bar">
    <div class="logo-placeholder">👥</div>
    <div>
      <h2>UGANDA PRISONS SERVICE</h2>
      <p>Staff Visitor Management · Housing & Gate Access</p>
    </div>
    <div style="margin-left:auto;" id="liveClock"></div>
  </div>

  <h1>🏛️ Staff Visits</h1>
  <p class="subtitle">Manage staff housing quarters (A,B,C,D,E,F,J,M,O) · Visitor destinations for staff or residential quarters.</p>

  <div class="tabs">
    <div class="tab active" onclick="switchTab('register')">✏️ Register Visit</div>
    <div class="tab" onclick="switchTab('log')">📊 Visit Log</div>
    <div class="tab" onclick="switchTab('housing')">🏘️ Housing & Transfers</div>
  </div>

  <!-- REGISTER VISIT TAB -->
  <div id="tab-register" class="tab-content" style="display:block;">
    <div style="background:white;border-radius:20px;padding:28px;max-width:800px;margin:0 auto;border:1px solid var(--gray-300);">
      <h3 style="margin-bottom:20px;color:var(--gray-900);">📝 Register a Visitor Entry</h3>
      <div class="form-group">
        <label>Destination Type *</label>
        <div class="radio-group">
          <label><input type="radio" name="destType" value="staff" checked onchange="toggleDestFields()"> 🧑‍💼 Staff Member</label>
          <label><input type="radio" name="destType" value="housing" onchange="toggleDestFields()"> 🏠 Housing Quarter (House/Flat)</label>
        </div>
      </div>
      <div id="staffDestDiv" class="form-group">
        <label>Staff Member Being Visited *</label>
        <select id="staffSelect">
          <option value="">Select staff...</option>
        </select>
        <div class="inline-hint">— or type name if not listed —</div>
        <input type="text" id="customStaffName" placeholder="Enter staff name (e.g., Lt. Kato)" style="margin-top: 8px;">
      </div>
      <div id="housingDestDiv" style="display:none;" class="form-group">
        <label>Housing Quarter & House Number *</label>
        <div style="display:flex;gap:12px;">
          <select id="housingQuarter" style="flex:1;"><option value="">Select Quarter</option></select>
          <input type="text" id="houseNumber" placeholder="House No (e.g. 12)" style="flex:1;">
        </div>
        <small style="color:var(--gray-500);">Destination for visitors — residential quarters A,B,C,D,E,F,J,M,O</small>
      </div>
      <div class="form-group"><label>Visitor Full Name *</label><input type="text" id="visitorName" placeholder="e.g. John Mukasa"></div>
      <div class="form-group"><label>Visitor Phone *</label><input type="tel" id="visitorPhone" placeholder="+256 7XX XXX XXX"></div>
      <div class="form-group"><label>Purpose of Visit</label><select id="visitPurpose"><option>Personal / Family</option><option>Official Business</option><option>Delivery</option><option>Meeting</option><option>Other</option></select></div>
      <div style="display:flex;gap:12px;margin-top:24px;">
        <button class="btn-primary" style="flex:1;" onclick="registerWalkIn()">🚪 Register Walk-In</button>
      </div>
    </div>
  </div>

  <!-- VISIT LOG TAB (simplified columns) -->
  <div id="tab-log" class="tab-content" style="display:none;">
    <div class="filter-bar">
      <input type="text" id="logSearch" placeholder="🔍 Search visitor or staff/housing..." oninput="filterLog()">
      <button class="btn-secondary" onclick="resetLogFilters()">Clear</button>
    </div>
    <div class="log-table">
      <table id="visitLogTable">
        <thead>
          <tr><th>Time In</th><th>Visitor</th><th>Staff / Housing</th><th>Purpose</th></tr>
        </thead>
        <tbody id="visitLogBody"></tbody>
      </table>
    </div>
  </div>

  <!-- HOUSING MANAGEMENT TAB -->
  <div id="tab-housing" class="tab-content" style="display:none;">
    <div style="background:white;border-radius:20px;padding:20px;margin-bottom:24px;">
      <h3 style="color:var(--gray-900);">🏘️ Staff Housing Quarters (A,B,C,D,E,F,J,M,O)</h3>
      <p style="margin-bottom:16px; color: var(--gray-600);">Manage staff assignments. Transfer staff to new quarter/house.</p>
      <div id="housingOverview" class="housing-grid"></div>
    </div>
    <div class="log-table">
      <table>
        <thead><tr><th>Staff</th><th>Current Quarter</th><th>Current House</th><th>Transfer Action</th></tr></thead>
        <tbody id="transferStaffList"></tbody>
      </table>
    </div>
  </div>
</div>

<!-- TRANSFER MODAL (only modal used) -->
<div class="modal-overlay" id="transferModal"><div class="modal"><div class="modal-header"><h3>Transfer Staff Housing</h3><button class="close-modal" onclick="closeTransferModal()">&times;</button></div><div class="modal-body" id="transferModalBody"></div></div></div>

<script>
  // ---------- QUARTERS ----------
  const QUARTERS = ['A','B','C','D','E','F','J','M','O'];
  const MAX_HOUSES = 20;

  // Staff DB (used for housing & registration)
  let staffDB = [
    { id: 'UPS/A/0012', name: 'SSALI BRIAN', dept: 'Administration', location: 'Quarter B, House 5', status: 'On Duty', phone: '+256 772 123456', quarter: 'B', houseNumber: '5' },
    { id: 'UPS/S/1034', name: 'NYAKATO SARAH', dept: 'Security', location: 'Quarter A, House 2', status: 'On Duty', phone: '+256 773 234567', quarter: 'A', houseNumber: '2' },
    { id: 'UPS/G/4521', name: 'SGT. OKELLO JAMES', dept: 'Security', location: 'Quarter D, House 12', status: 'Off Duty', phone: '+256 774 345678', quarter: 'D', houseNumber: '12' },
    { id: 'UPS/M/2210', name: 'DR. NAMUTEBI ROSE', dept: 'Medical', location: 'Quarter C, House 8', status: 'On Duty', phone: '+256 775 456789', quarter: 'C', houseNumber: '8' },
    { id: 'UPS/W/3312', name: 'KALEMA PETER', dept: 'Welfare', location: 'Quarter E, House 3', status: 'On Leave', phone: '+256 776 567890', quarter: 'E', houseNumber: '3' },
    { id: 'UPS/L/4415', name: 'WASSWA FRED', dept: 'Logistics', location: 'Quarter O, House 15', status: 'On Duty', phone: '+256 777 678901', quarter: 'O', houseNumber: '15' },
  ];

  // Visit log: simplified, no duration/status
  let visitLog = [
    { id: 'V001', timeIn: '08:30', visitor: 'John Mukasa', destType: 'staff', destRef: 'UPS/G/4521', destDisplay: 'Quarter D, House 12', purpose: 'Personal' },
    { id: 'V002', timeIn: '09:15', visitor: 'Grace Tendo', destType: 'staff', destRef: 'UPS/A/0012', destDisplay: 'Quarter B, House 5', purpose: 'Official' },
    { id: 'V003', timeIn: '10:00', visitor: 'Peter Opiyo', destType: 'housing', destRef: 'A:7', destDisplay: 'Quarter A, House 7', purpose: 'Family Visit' },
  ];

  // ---------- CLOCK ----------
  function updateClock(){ const n=new Date(); document.getElementById('liveClock').textContent=n.toLocaleTimeString('en-GB',{hour:'2-digit',minute:'2-digit'})+' · '+n.toLocaleDateString('en-GB',{weekday:'short',day:'numeric',month:'short'});}
  updateClock(); setInterval(updateClock,1000);

  // ---------- DESTINATION TOGGLE (staff/housing) ----------
  function toggleDestFields() {
    const isStaff = document.querySelector('input[name="destType"]:checked').value === 'staff';
    document.getElementById('staffDestDiv').style.display = isStaff ? 'block' : 'none';
    document.getElementById('housingDestDiv').style.display = isStaff ? 'none' : 'block';
  }

  function populateStaffSelect() {
    const select = document.getElementById('staffSelect');
    if (!select) return;
    select.innerHTML = '<option value="">Select staff...</option>' + staffDB.map(s => `<option value="${s.id}">${s.name} (${s.dept}) · ${s.quarter} House ${s.houseNumber}</option>`).join('');
  }
  function populateQuarterSelect() {
    const select = document.getElementById('housingQuarter');
    select.innerHTML = '<option value="">Select Quarter</option>' + QUARTERS.map(q => `<option value="${q}">Quarter ${q}</option>`).join('');
  }

  // ---------- TRANSFER MODAL ----------
  function openTransferModal(staffId){
    const staff = staffDB.find(s=>s.id===staffId);
    if(!staff) return;
    const modalBody = document.getElementById('transferModalBody');
    modalBody.innerHTML = `<div class="form-group"><label>Staff: ${staff.name} (${staff.id})</label><label>Current Housing: Quarter ${staff.quarter}, House ${staff.houseNumber}</label></div>
    <div class="form-group"><label>New Quarter</label><select id="newQuarter">${QUARTERS.map(q=>`<option value="${q}" ${staff.quarter===q?'selected':''}>Quarter ${q}</option>`).join('')}</select></div>
    <div class="form-group"><label>New House Number (1-20)</label><input type="number" id="newHouse" min="1" max="20" value="${staff.houseNumber}"></div>
    <button class="btn-primary" onclick="confirmTransfer('${staff.id}')">Confirm Transfer</button>`;
    document.getElementById('transferModal').style.display='flex';
  }
  function confirmTransfer(staffId){
    const newQuarter = document.getElementById('newQuarter').value;
    let newHouse = document.getElementById('newHouse').value;
    if(!newQuarter || !newHouse) return alert('Select quarter and house');
    newHouse = newHouse.toString();
    const existing = staffDB.find(s=>s.id!==staffId && s.quarter===newQuarter && s.houseNumber===newHouse);
    if(existing) alert(`⚠️ Warning: ${existing.name} already occupies Quarter ${newQuarter}, House ${newHouse}. Transfer will proceed.`);
    const staff = staffDB.find(s=>s.id===staffId);
    staff.quarter = newQuarter;
    staff.houseNumber = newHouse;
    staff.location = `Quarter ${newQuarter}, House ${newHouse}`;
    renderHousingOverview();
    renderTransferStaffList();
    populateStaffSelect();
    closeTransferModal();
    alert(`✅ ${staff.name} transferred to Quarter ${newQuarter}, House ${newHouse}`);
  }
  function closeTransferModal(){ document.getElementById('transferModal').style.display='none'; }

  // ---------- HOUSING OVERVIEW & TRANSFER LIST ----------
  function renderHousingOverview(){
    const container = document.getElementById('housingOverview');
    let html='';
    QUARTERS.forEach(q=>{
      const staffInQuarter = staffDB.filter(s=>s.quarter===q);
      let housesHtml='';
      for(let i=1;i<=MAX_HOUSES;i++){
        const occupant = staffInQuarter.find(s=>parseInt(s.houseNumber)===i);
        if(occupant) housesHtml+=`<div class="house-item"><strong>House ${i}</strong>: ${occupant.name} (${occupant.dept})</div>`;
        else housesHtml+=`<div class="house-item vacant">House ${i}: <em>Vacant</em></div>`;
      }
      html+=`<div class="quarter-card"><div class="quarter-title">Quarter ${q}</div><div class="house-list">${housesHtml}</div></div>`;
    });
    container.innerHTML = html;
  }
  function renderTransferStaffList(){
    const tbody = document.getElementById('transferStaffList');
    tbody.innerHTML = staffDB.map(s=>`<tr><td><strong>${s.name}</strong><br><small>${s.id}</small></td><td>Quarter ${s.quarter}</td><td>House ${s.houseNumber}</td><td><button class="btn-card" onclick="openTransferModal('${s.id}')">✈️ Transfer Housing</button></td></tr>`).join('');
  }

  // ---------- VISIT REGISTRATION (supports custom staff name) ----------
  function getDestinationFromForm(){
    const isStaff = document.querySelector('input[name="destType"]:checked').value === 'staff';
    if(isStaff){
      const selectedStaffId = document.getElementById('staffSelect').value;
      const customName = document.getElementById('customStaffName').value.trim();
      if(selectedStaffId) {
        const staff = staffDB.find(s=>s.id===selectedStaffId);
        if(staff) return { type:'staff', ref:staff.id, display:staff.name, extraLoc: `${staff.quarter} House ${staff.houseNumber}` };
      }
      if(customName !== "") {
        return { type:'staff_custom', ref:null, display:customName, extraLoc: 'Custom staff' };
      }
      return null;
    } else {
      const quarter = document.getElementById('housingQuarter').value;
      const house = document.getElementById('houseNumber').value.trim();
      if(!quarter || !house) return null;
      return { type:'housing', ref:`${quarter}:${house}`, display:`Quarter ${quarter}, House ${house}`, extraLoc:`Quarter ${quarter}, House ${house}` };
    }
  }

  function registerWalkIn(){
    const name = document.getElementById('visitorName').value.trim();
    const phone = document.getElementById('visitorPhone').value.trim();
    if(!name || !phone) return alert('Visitor name and phone required.');
    const dest = getDestinationFromForm();
    if(!dest) return alert('Please select a valid destination: choose a staff (or type custom name) OR select quarter/house.');
    const newVisit = {
      id: 'V' + (visitLog.length+1).toString().padStart(3,'0'),
      timeIn: new Date().toLocaleTimeString('en-GB',{hour:'2-digit', minute:'2-digit'}),
      visitor: name,
      destType: dest.type,
      destRef: dest.ref,
      destDisplay: dest.display,
      purpose: document.getElementById('visitPurpose').value,
    };
    visitLog.push(newVisit);
    alert(`✅ Walk-in registered. ${name} → ${dest.display}`);
    switchTab('log');
    renderVisitLog();
    clearRegisterForm();
  }

  function clearRegisterForm(){
    document.getElementById('visitorName').value='';
    document.getElementById('visitorPhone').value='';
    document.getElementById('staffSelect').value='';
    document.getElementById('customStaffName').value='';
    document.getElementById('housingQuarter').value='';
    document.getElementById('houseNumber').value='';
  }

  // ---------- VISIT LOG (simplified columns) ----------
  function renderVisitLog(filtered=null){
    const data = filtered || visitLog;
    const tbody = document.getElementById('visitLogBody');
    tbody.innerHTML = data.map(v => `
      <tr>
        <td>${escapeHtml(v.timeIn)}</td>
        <td><strong>${escapeHtml(v.visitor)}</strong></td>
        <td>${escapeHtml(v.destDisplay)}</td>
        <td>${escapeHtml(v.purpose)}</td>
      </tr>
    `).join('');
  }

  function escapeHtml(str) { return str.replace(/[&<>]/g, function(m){if(m==='&') return '&amp;'; if(m==='<') return '&lt;'; if(m==='>') return '&gt;'; return m;}); }

  function filterLog(){
    const search = document.getElementById('logSearch').value.toLowerCase();
    const filtered = visitLog.filter(v => v.visitor.toLowerCase().includes(search) || v.destDisplay.toLowerCase().includes(search));
    renderVisitLog(filtered);
  }
  function resetLogFilters(){ document.getElementById('logSearch').value=''; renderVisitLog(visitLog); }

  // ---------- TAB SWITCHING (no staff directory) ----------
  function switchTab(tab){
    document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c=>c.style.display='none');
    if(tab==='register'){
      document.querySelectorAll('.tab')[0].classList.add('active');
      document.getElementById('tab-register').style.display='block';
      populateStaffSelect();
      populateQuarterSelect();
      toggleDestFields();
    }
    else if(tab==='log'){
      document.querySelectorAll('.tab')[1].classList.add('active');
      document.getElementById('tab-log').style.display='block';
      renderVisitLog();
    }
    else if(tab==='housing'){
      document.querySelectorAll('.tab')[2].classList.add('active');
      document.getElementById('tab-housing').style.display='block';
      renderHousingOverview();
      renderTransferStaffList();
    }
  }

  // ---------- INITIAL LOAD (staff directory removed) ----------
  populateStaffSelect();
  populateQuarterSelect();
  renderVisitLog();
  renderHousingOverview();
  renderTransferStaffList();
  toggleDestFields();        // ensure register form initial state
  // Ensure register tab is visible by default (active already set in HTML)
  document.getElementById('tab-register').style.display = 'block';
  document.getElementById('tab-log').style.display = 'none';
  document.getElementById('tab-housing').style.display = 'none';

  // close transfer modal when clicking outside
  window.onclick = function(e){ if(e.target===document.getElementById('transferModal')) closeTransferModal(); };
</script>
</body>
</html>
