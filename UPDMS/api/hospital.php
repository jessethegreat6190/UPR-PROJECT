<?php
/**
 * Hospital API - XAMPP/MySQL Backend
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$db_host = 'localhost';
$db_name = 'ups_hospital';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    try {
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
        $pdo->exec("USE `$db_name`");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS visits (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ref_number VARCHAR(50) UNIQUE,
                name VARCHAR(200),
                phone VARCHAR(50),
                visit_type VARCHAR(20),
                destination VARCHAR(200),
                reason TEXT,
                time_in DATETIME DEFAULT CURRENT_TIMESTAMP,
                time_out DATETIME NULL,
                status VARCHAR(20) DEFAULT 'active'
            )
        ");
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

function respond($data) {
    echo json_encode($data);
    exit;
}

switch ($action) {
    case 'register':
        $ref = 'HV-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("
            INSERT INTO visits (ref_number, name, phone, visit_type, destination, reason)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $ref,
            $input['name'],
            $input['phone'] ?? '',
            $input['type'],
            $input['destination'],
            $input['reason'] ?? ''
        ]);
        respond(['success' => true, 'ref' => $ref]);
        break;
        
    case 'list':
        $stmt = $pdo->query("SELECT * FROM visits ORDER BY time_in DESC LIMIT 50");
        respond(['success' => true, 'visits' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;
        
    case 'stats':
        $today = date('Y-m-d');
        $stmt = $pdo->query("SELECT * FROM visits WHERE DATE(time_in) = '$today'");
        $visits = $stmt->fetchAll(PDO::FETCH_ASSOC);
        respond([
            'success' => true,
            'total' => count($visits),
            'patients' => count(array_filter($visits, fn($v) => $v['visit_type'] === 'community')),
            'inmates' => count(array_filter($visits, fn($v) => $v['visit_type'] === 'inmate')),
            'staff' => count(array_filter($visits, fn($v) => $v['visit_type'] === 'staff'))
        ]);
        break;
        
    default:
        respond(['success' => false, 'error' => 'Unknown action']);
}