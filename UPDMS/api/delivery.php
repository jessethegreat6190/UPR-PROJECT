<?php
/**
 * Delivery API - XAMPP/MySQL Backend
 * Handles vehicle and visitor registration for local XAMPP deployment
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database configuration for XAMPP
$db_host = 'localhost';
$db_name = 'ups_delivery';
$db_user = 'root';
$db_pass = '';

$pdo = null;

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Try to create database
    try {
        $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$db_name`");
        
        // Create tables
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS vehicles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                company VARCHAR(200),
                vehicle_plate VARCHAR(50) UNIQUE,
                driver_name VARCHAR(100),
                driver_phone VARCHAR(50),
                vehicle_type VARCHAR(20) DEFAULT 'car',
                frequency VARCHAR(20) DEFAULT 'weekly',
                allowed_days VARCHAR(50) DEFAULT '1,2,3,4,5',
                allowed_hours_start TIME DEFAULT '06:00:00',
                allowed_hours_end TIME DEFAULT '18:00:00',
                qr_code VARCHAR(100),
                status VARCHAR(20) DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS frequent_visitors (
                id INT AUTO_INCREMENT PRIMARY KEY,
                full_name VARCHAR(200),
                id_number VARCHAR(50),
                phone VARCHAR(50),
                organization VARCHAR(200),
                visits_for VARCHAR(200),
                frequency VARCHAR(20) DEFAULT 'weekly',
                vehicle_plate VARCHAR(50),
                allowed_hours_start TIME DEFAULT '08:00:00',
                allowed_hours_end TIME DEFAULT '17:00:00',
                qr_code VARCHAR(100),
                status VARCHAR(20) DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS deliveries (
                id INT AUTO_INCREMENT PRIMARY KEY,
                vehicle_plate VARCHAR(50),
                qr_code VARCHAR(100),
                visitor_name VARCHAR(200),
                entry_time DATETIME DEFAULT CURRENT_TIMESTAMP,
                exit_time DATETIME,
                status VARCHAR(20) DEFAULT 'inside'
            )
        ");
    } catch (PDOException $e2) {
        echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e2->getMessage()]);
        exit;
    }
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

// Helper function to respond
function respond($data) {
    echo json_encode($data);
    exit;
}

switch ($action) {
    // ===== VEHICLES =====
    case 'list_vehicles':
        $stmt = $pdo->query("SELECT * FROM vehicles WHERE status = 'active' ORDER BY created_at DESC");
        respond(['success' => true, 'vehicles' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;
        
    case 'register_vehicle':
        $stmt = $pdo->prepare("
            INSERT INTO vehicles (company, vehicle_plate, driver_name, driver_phone, vehicle_type, frequency, allowed_days, allowed_hours_start, allowed_hours_end, qr_code, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
        ");
        
        $qr = 'DEL-' . strtoupper($input['vehicle_plate'] ?? 'VEH');
        
        $stmt->execute([
            $input['company'] ?? '',
            strtoupper($input['vehicle_plate'] ?? ''),
            $input['driver_name'] ?? '',
            $input['driver_phone'] ?? '',
            $input['vehicle_type'] ?? 'car',
            $input['frequency'] ?? 'weekly',
            $input['allowed_days'] ?? '1,2,3,4,5',
            $input['allowed_hours_start'] ?? '06:00',
            $input['allowed_hours_end'] ?? '18:00',
            $qr
        ]);
        
        respond(['success' => true, 'vehicle' => ['id' => $pdo->lastInsertId(), 'qr_code' => $qr]]);
        break;
        
    case 'check_vehicle':
        $plate = strtoupper($input['vehicle_plate'] ?? '');
        $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE UPPER(vehicle_plate) = ? AND status = 'active'");
        $stmt->execute([$plate]);
        $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($vehicle) {
            $day = date('w');
            $time = date('H:i');
            $allowed_days = explode(',', $vehicle['allowed_days']);
            
            $allowed = in_array($day, $allowed_days) && $time >= $vehicle['allowed_hours_start'] && $time <= $vehicle['allowed_hours_end'];
            
            respond(['success' => true, 'allowed' => $allowed, 'vehicle' => $vehicle]);
        }
        
        respond(['success' => true, 'allowed' => false, 'message' => 'Vehicle not registered']);
        break;
        
    // ===== VISITORS =====
    case 'list_visitors':
        $stmt = $pdo->query("SELECT * FROM frequent_visitors WHERE status = 'active' ORDER BY created_at DESC");
        respond(['success' => true, 'visitors' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;
        
    case 'register_visitor':
        $qr = 'FREQ-' . strtoupper(preg_replace('/\s/', '', substr($input['full_name'] ?? 'VIS', 0, 8)));
        
        $stmt = $pdo->prepare("
            INSERT INTO frequent_visitors (full_name, id_number, phone, organization, visits_for, frequency, vehicle_plate, allowed_hours_start, allowed_hours_end, qr_code, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
        ");
        
        $stmt->execute([
            $input['full_name'] ?? '',
            $input['id_number'] ?? '',
            $input['phone'] ?? '',
            $input['organization'] ?? '',
            $input['visits_for'] ?? '',
            $input['frequency'] ?? 'weekly',
            strtoupper($input['vehicle_plate'] ?? ''),
            $input['allowed_hours_start'] ?? '08:00',
            $input['allowed_hours_end'] ?? '17:00',
            $qr
        ]);
        
        respond(['success' => true, 'visitor' => ['id' => $pdo->lastInsertId(), 'qr_code' => $qr]]);
        break;
        
    case 'check_visitor':
        $code = $input['qr_code'] ?? $input['id_number'] ?? '';
        $stmt = $pdo->prepare("SELECT * FROM frequent_visitors WHERE (qr_code = ? OR id_number = ?) AND status = 'active'");
        $stmt->execute([$code, $code]);
        $visitor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($visitor) {
            respond(['success' => true, 'allowed' => true, 'visitor' => $visitor]);
        }
        
        respond(['success' => true, 'allowed' => false, 'message' => 'Visitor not registered']);
        break;
        
    // ===== DELIVERIES =====
    case 'record_entry':
        $stmt = $pdo->prepare("
            INSERT INTO deliveries (vehicle_plate, qr_code, visitor_name, entry_time, status)
            VALUES (?, ?, ?, NOW(), 'inside')
        ");
        
        $stmt->execute([
            strtoupper($input['vehicle_plate'] ?? ''),
            $input['qr_code'] ?? '',
            $input['visitor_name'] ?? ''
        ]);
        
        respond(['success' => true, 'delivery' => ['id' => $pdo->lastInsertId()]]);
        break;
        
    case 'record_exit':
        $code = $input['vehicle_plate'] ?? $input['qr_code'] ?? '';
        
        $stmt = $pdo->prepare("
            UPDATE deliveries SET status = 'exited', exit_time = NOW() 
            WHERE status = 'inside' AND (vehicle_plate = ? OR qr_code = ?)
            ORDER BY entry_time DESC LIMIT 1
        ");
        
        $stmt->execute([strtoupper($code), $code]);
        
        if ($stmt->rowCount() > 0) {
            respond(['success' => true]);
        }
        
        respond(['success' => false, 'error' => 'No active entry found']);
        break;
        
    case 'get_inside':
        $stmt = $pdo->query("SELECT * FROM deliveries WHERE status = 'inside' ORDER BY entry_time DESC");
        respond(['success' => true, 'deliveries' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;
        
    default:
        respond(['success' => false, 'error' => 'Unknown action: ' . $action]);
}