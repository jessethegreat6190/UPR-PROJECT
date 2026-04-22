<?php
require_once __DIR__ . '/../config/bootstrap.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'check':
        $plate = strtoupper(sanitize($_GET['plate'] ?? ''));
        if (!$plate) {
            echo json_encode(['found' => false]);
            break;
        }
        
        $db = getDB();
        $vehicle = $db->fetchOne("SELECT * FROM vehicles WHERE plate_number = ?", [$plate]);
        
        if ($vehicle) {
            echo json_encode([
                'found' => true,
                'plate_number' => $vehicle['plate_number'],
                'last_driver_name' => $vehicle['last_driver_name'],
                'is_blacklisted' => (bool)$vehicle['is_blacklisted'],
                'total_visits' => $vehicle['total_visits']
            ]);
        } else {
            echo json_encode(['found' => false]);
        }
        break;
        
    case 'check_inside':
        $plate = strtoupper(sanitize($_GET['plate'] ?? ''));
        if (!$plate) {
            echo json_encode(['found' => false]);
            break;
        }
        
        $db = getDB();
        $vehicle = $db->fetchOne("SELECT * FROM vehicles WHERE plate_number = ?", [$plate]);
        
        if ($vehicle) {
            $log = $db->fetchOne("
                SELECT * FROM vehicle_logs 
                WHERE vehicle_id = ? AND status = 'inside' 
                ORDER BY entry_time DESC LIMIT 1", [$vehicle['id']]);
            
            if ($log) {
                echo json_encode([
                    'found' => true,
                    'driver_name' => $log['driver_name'] ?: $vehicle['last_driver_name'],
                    'entry_time' => $log['entry_time'],
                    'visitor_type' => $log['visitor_type']
                ]);
            } else {
                echo json_encode(['found' => false, 'message' => 'Vehicle not inside']);
            }
        } else {
            echo json_encode(['found' => false]);
        }
        break;
        
    case 'drivers':
        $db = getDB();
        $drivers = $db->fetchAll("
            SELECT DISTINCT last_driver_name 
            FROM vehicles 
            WHERE last_driver_name IS NOT NULL AND last_driver_name != ''
            ORDER BY last_driver_name
            LIMIT 50", []);
        echo json_encode($drivers);
        break;
        
    case 'blacklist':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $plate = strtoupper(sanitize($data['plate'] ?? ''));
            $reason = sanitize($data['reason'] ?? '');
            $user = getCurrentUser();
            
            $db = getDB();
            $db->update('vehicles', [
                'is_blacklisted' => 1,
                'blacklisted_reason' => $reason,
                'blacklisted_by' => $user['id'],
                'blacklisted_at' => date('Y-m-d H:i:s')
            ], 'plate_number = :plate', ['plate' => $plate]);
            
            echo json_encode(['success' => true]);
        }
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
