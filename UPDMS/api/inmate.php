<?php
/**
 * Inmate API - XAMPP/MySQL Backend
 * Handles inmate data and visit bookings
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$db_host = 'localhost';
$db_name = 'ups_inmate';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    try {
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name`");
        $pdo->exec("USE `$db_name`");
        
        // Inmates table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS inmates (
                id INT AUTO_INCREMENT PRIMARY KEY,
                inmate_number VARCHAR(50) UNIQUE,
                first_name VARCHAR(100),
                last_name VARCHAR(100),
                gender VARCHAR(20),
                cell_block VARCHAR(20),
                cell_number VARCHAR(20),
                offense TEXT,
                sentence_date DATE,
                release_date DATE,
                status VARCHAR(20) DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Bookings table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS bookings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                booking_ref VARCHAR(50) UNIQUE,
                inmate_id INT,
                visitor_name VARCHAR(200),
                visitor_nin VARCHAR(50),
                visitor_phone VARCHAR(50),
                visitor_email VARCHAR(200),
                visit_date DATE,
                visit_time VARCHAR(20),
                status VARCHAR(20) DEFAULT 'pending',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (inmate_id) REFERENCES inmates(id)
            )
        ");
        
        // Insert sample inmates if empty
        $count = $pdo->query("SELECT COUNT(*) FROM inmates")->fetchColumn();
        if ($count == 0) {
            $sample = [
                ['INV/2024/001', 'JOHN', 'OKELLO', 'Male', 'A', 'A12', 'Theft', '2024-01-15', '2026-01-14'],
                ['INV/2024/002', 'MARY', 'ATWoki', 'Female', 'B', 'B05', 'Fraud', '2024-02-20', '2027-02-19'],
                ['INV/2024/003', 'PETER', 'SSALI', 'Male', 'C', 'C08', 'Assault', '2024-03-10', '2025-03-09'],
                ['INV/2024/004', 'SARAH', 'NAMUBiru', 'Female', 'A', 'A03', 'Robbery', '2024-04-05', '2026-04-04'],
                ['INV/2024/005', 'JAMES', 'OKEELO', 'Male', 'D', 'D15', 'Murder', '2024-05-01', '2030-04-30'],
            ];
            $stmt = $pdo->prepare("INSERT INTO inmates (inmate_number, first_name, last_name, gender, cell_block, cell_number, offense, sentence_date, release_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($sample as $s) {
                $stmt->execute($s);
            }
        }
        
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
    case 'search':
        $query = $input['q'] ?? '';
        $stmt = $pdo->prepare("
            SELECT * FROM inmates 
            WHERE status = 'active' AND (
                inmate_number LIKE ? OR 
                first_name LIKE ? OR 
                last_name LIKE ? OR
                CONCAT(first_name, ' ', last_name) LIKE ?
            )
            LIMIT 10
        ");
        $search = "%$query%";
        $stmt->execute([$search, $search, $search, $search]);
        respond(['success' => true, 'inmates' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;
        
    case 'get_inmate':
        $id = $input['id'] ?? 0;
        $stmt = $pdo->prepare("SELECT * FROM inmates WHERE id = ?");
        $stmt->execute([$id]);
        $inmate = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($inmate) {
            respond(['success' => true, 'inmate' => $inmate]);
        }
        respond(['success' => false, 'error' => 'Inmate not found']);
        break;
        
    case 'create_booking':
        $ref = 'BK-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("
            INSERT INTO bookings (booking_ref, inmate_id, visitor_name, visitor_nin, visitor_phone, visitor_email, visit_date, visit_time, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed')
        ");
        $stmt->execute([
            $ref,
            $input['inmate_id'],
            $input['visitor_name'],
            $input['visitor_nin'],
            $input['visitor_phone'],
            $input['visitor_email'],
            $input['visit_date'],
            $input['visit_time'] ?? '09:00'
        ]);
        respond(['success' => true, 'booking_ref' => $ref, 'id' => $pdo->lastInsertId()]);
        break;
        
    case 'get_booking':
        $ref = $input['ref'] ?? '';
        $phone = $input['phone'] ?? '';
        
        $sql = "SELECT b.*, i.first_name, i.last_name, i.cell_block, i.cell_number 
                FROM bookings b 
                JOIN inmates i ON b.inmate_id = i.id 
                WHERE 1=1";
        $params = [];
        
        if ($ref) {
            $sql .= " AND b.booking_ref = ?";
            $params[] = $ref;
        }
        if ($phone) {
            $sql .= " AND b.visitor_phone = ?";
            $params[] = $phone;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        respond(['success' => true, 'bookings' => $bookings]);
        break;
        
    case 'list_inmates':
        $stmt = $pdo->query("SELECT * FROM inmates WHERE status = 'active' ORDER BY last_name");
        respond(['success' => true, 'inmates' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;
        
    case 'list_bookings':
        $stmt = $pdo->query("SELECT b.*, i.first_name, i.last_name 
            FROM bookings b 
            LEFT JOIN inmates i ON b.inmate_id = i.id 
            ORDER BY b.created_at DESC");
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($bookings as &$b) {
            $b['inmate_name'] = $b['first_name'] . ' ' . $b['last_name'];
        }
        respond(['success' => true, 'bookings' => $bookings]);
        break;
        
    default:
        respond(['success' => false, 'error' => 'Unknown action']);
}