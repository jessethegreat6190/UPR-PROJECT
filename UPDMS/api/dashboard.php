<?php
require_once __DIR__ . '/../config/bootstrap.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'stats':
        requireLogin();
        $db = getDB();
        $user = getCurrentUser();
        
        $facilityFilter = '';
        $params = [];
        if ($user['role'] === 'supervisor' || $user['role'] === 'gate_officer') {
            $facilityFilter = 'AND facility_id = ?';
            $params = [$user['facility_id']];
        }
        
        $prisoners = $db->fetchOne("SELECT COUNT(*) as c FROM prisoners WHERE status IN ('remand', 'convicted') $facilityFilter", $params)['c'];
        $todayVisitors = $db->fetchOne("SELECT COUNT(*) as c FROM visitor_logs WHERE DATE(entry_time) = CURDATE() $facilityFilter", $params)['c'];
        $vehiclesInside = $db->fetchOne("SELECT COUNT(*) as c FROM vehicle_logs WHERE status = 'inside' $facilityFilter", $params)['c'];
        $overstays = $db->fetchOne("SELECT COUNT(*) as c FROM vehicle_logs WHERE status = 'inside' AND TIMESTAMPDIFF(HOUR, entry_time, NOW()) > 72 $facilityFilter", $params)['c'];
        
        echo json_encode([
            'prisoners' => (int)$prisoners,
            'today_visitors' => (int)$todayVisitors,
            'vehicles_inside' => (int)$vehiclesInside,
            'overstay_alerts' => (int)$overstays
        ]);
        break;
        
    case 'prisoner_count':
        requireLogin();
        $db = getDB();
        
        if (hasRole('hq_command') || hasRole('admin')) {
            $counts = $db->fetchAll("
                SELECT f.name, f.id, 
                       SUM(CASE WHEN p.status = 'remand' THEN 1 ELSE 0 END) as remand_count,
                       SUM(CASE WHEN p.status = 'convicted' THEN 1 ELSE 0 END) as convicted_count,
                       COUNT(*) as total
                FROM facilities f
                LEFT JOIN prisoners p ON f.id = p.facility_id AND p.status IN ('remand', 'convicted')
                GROUP BY f.id, f.name
                ORDER BY total DESC", []);
        } else {
            $counts = [];
        }
        
        echo json_encode($counts);
        break;
        
    case 'today_visitors':
        requireLogin();
        $db = getDB();
        $user = getCurrentUser();
        
        $facilityFilter = '';
        $params = [date('Y-m-d')];
        if ($user['role'] === 'supervisor' || $user['role'] === 'gate_officer') {
            $facilityFilter = 'AND facility_id = ?';
            $params[] = $user['facility_id'];
        }
        
        $visitors = $db->fetchAll("
            SELECT visitor_type, COUNT(*) as count 
            FROM visitor_logs 
            WHERE DATE(entry_time) = ? $facilityFilter
            GROUP BY visitor_type", $params);
        
        echo json_encode($visitors);
        break;
        
    case 'current_vehicles':
        requireLogin();
        $db = getDB();
        $user = getCurrentUser();
        
        $facilityFilter = '';
        $params = [];
        if ($user['role'] === 'supervisor' || $user['role'] === 'gate_officer') {
            $facilityFilter = 'AND vl.facility_id = ?';
            $params = [$user['facility_id']];
        }
        
        $vehicles = $db->fetchAll("
            SELECT vl.*, v.plate_number, v.last_driver_name
            FROM vehicle_logs vl
            JOIN vehicles v ON vl.vehicle_id = v.id
            WHERE vl.status = 'inside' $facilityFilter
            ORDER BY vl.entry_time DESC", $params);
        
        echo json_encode($vehicles);
        break;
        
    case 'overstay':
        requireLogin();
        $db = getDB();
        $user = getCurrentUser();
        
        $facilityFilter = '';
        $params = [];
        if ($user['role'] === 'supervisor' || $user['role'] === 'gate_officer') {
            $facilityFilter = 'AND vl.facility_id = ?';
            $params = [$user['facility_id']];
        }
        
        $overstays = $db->fetchAll("
            SELECT vl.*, v.plate_number, v.last_driver_name,
                   TIMESTAMPDIFF(HOUR, vl.entry_time, NOW()) as hours_inside
            FROM vehicle_logs vl
            JOIN vehicles v ON vl.vehicle_id = v.id
            WHERE vl.status = 'inside' 
            AND TIMESTAMPDIFF(HOUR, vl.entry_time, NOW()) > 72 
            $facilityFilter
            ORDER BY hours_inside DESC", $params);
        
        echo json_encode($overstays);
        break;
        
    case 'upcoming_releases':
        requireLogin();
        $db = getDB();
        
        $releases = $db->fetchAll("
            SELECT p.*, s.release_date
            FROM prisoners p
            JOIN sentences s ON p.id = s.prisoner_id
            WHERE p.status = 'convicted'
            AND s.release_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            ORDER BY s.release_date ASC
            LIMIT 20", []);
        
        echo json_encode($releases);
        break;
        
    case 'search_prisoners':
        requireLogin();
        $db = getDB();
        $user = getCurrentUser();
        $query = sanitize($_GET['q'] ?? '');
        
        if (strlen($query) < 2) {
            echo json_encode([]);
            break;
        }
        
        $facilityFilter = '';
        $params = ["%$query%", "%$query%", "%$query%"];
        if ($user['role'] === 'supervisor' || $user['role'] === 'gate_officer') {
            $facilityFilter = 'AND facility_id = ?';
            $params[] = $user['facility_id'];
        }
        
        $prisoners = $db->fetchAll("
            SELECT id, prisoner_number, first_name, last_name, status
            FROM prisoners 
            WHERE status IN ('remand', 'convicted')
            AND (prisoner_number LIKE ? OR first_name LIKE ? OR last_name LIKE ?)
            $facilityFilter
            ORDER BY first_name ASC
            LIMIT 10", $params);
        
        echo json_encode($prisoners);
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
