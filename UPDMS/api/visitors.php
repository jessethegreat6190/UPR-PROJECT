<?php
require_once __DIR__ . '/../config/bootstrap.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST,GET,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'kiosk_register':
        kioskRegister();
        break;
    case 'check_booking':
        checkBooking();
        break;
    case 'list_bookings':
        listBookings();
        break;
    case 'get_booking':
        getBooking();
        break;
    case 'approve_booking':
        approveBooking();
        break;
    case 'reject_booking':
        rejectBooking();
        break;
    case 'approve_visitor':
        approveVisitor();
        break;
    case 'reject_visitor':
        rejectVisitor();
        break;
    case 'record_exit':
        recordExit();
        break;
    case 'list_whitelist':
        listWhitelist();
        break;
    default:
        echo json_encode(['error' => 'Unknown action']);
}

function kioskRegister() {
    $data = json_decode(file_get_contents('php://input'), true);
    $db = getDB();
    
    $refNumber = $data['ref_number'] ?? 'VIS-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    try {
        $id = $db->insert('visitor_logs', [
            'facility_id' => 1,
            'visitor_type' => $data['purpose'] ?? 'general',
            'entry_time' => date('Y-m-d H:i:s'),
            'status' => 'inside',
            'gate_officer_entry_id' => 1
        ]);
        
        $db->insert('visitors', [
            'visitor_type' => $data['purpose'] ?? 'official',
            'first_name' => explode(' ', $data['full_name'] ?? 'Unknown')[0],
            'last_name' => implode(' ', array_slice(explode(' ', $data['full_name'] ?? 'Unknown'), 1)),
            'national_id' => $data['national_id'] ?? null,
            'phone' => $data['phone'] ?? null,
            'vehicle_plate' => $data['plate'] ?? null
        ]);
        
        echo json_encode(['success' => true, 'id' => $id, 'ref_number' => $refNumber]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Registration failed', 'details' => $e->getMessage()]);
    }
}

function checkBooking() {
    $ref = sanitize($_GET['ref'] ?? '');
    $db = getDB();
    
    $booking = $db->fetchOne("SELECT * FROM visitor_bookings WHERE ref_number = ? AND status = 'approved'", [$ref]);
    
    if ($booking) {
        $visitor = $db->fetchOne("SELECT * FROM visitors WHERE id = ?", [$booking['visitor_id']]);
        echo json_encode([
            'found' => true,
            'name' => ($visitor['first_name'] ?? '') . ' ' . ($visitor['last_name'] ?? ''),
            'national_id' => $visitor['national_id'] ?? '',
            'phone' => $visitor['phone'] ?? '',
            'visit_date' => $booking['booking_date']
        ]);
    } else {
        echo json_encode(['found' => false]);
    }
}

function listBookings() {
    $db = getDB();
    $bookings = $db->fetchAll("
        SELECT vb.*, v.first_name, v.last_name, v.national_id, v.phone
        FROM visitor_bookings vb
        JOIN visitors v ON v.id = vb.visitor_id
        WHERE DATE(vb.booking_date) = CURDATE()
        ORDER BY vb.created_at DESC
    ");
    
    $result = array_map(function($b) {
        return [
            'id' => $b['id'],
            'ref_number' => $b['ref_number'],
            'full_name' => ($b['first_name'] ?? '') . ' ' . ($b['last_name'] ?? ''),
            'national_id' => $b['national_id'],
            'phone' => $b['phone'],
            'visit_type' => $b['visitor_type'],
            'destination' => $b['visit_purpose'],
            'time_slot' => $b['booking_time'],
            'status' => $b['status']
        ];
    }, $bookings);
    
    echo json_encode($result);
}

function getBooking() {
    $id = intval($_GET['id'] ?? 0);
    $db = getDB();
    
    $booking = $db->fetchOne("
        SELECT vb.*, v.first_name, v.last_name, v.national_id, v.phone
        FROM visitor_bookings vb
        JOIN visitors v ON v.id = vb.visitor_id
        WHERE vb.id = ?
    ", [$id]);
    
    if ($booking) {
        echo json_encode([
            'id' => $booking['id'],
            'ref_number' => $booking['ref_number'],
            'full_name' => ($booking['first_name'] ?? '') . ' ' . ($booking['last_name'] ?? ''),
            'national_id' => $booking['national_id'],
            'phone' => $booking['phone'],
            'visit_type' => $booking['visitor_type'],
            'destination' => $booking['visit_purpose'],
            'time_slot' => $booking['booking_time'],
            'status' => $booking['status']
        ]);
    } else {
        echo json_encode(['error' => 'Booking not found']);
    }
}

function approveBooking() {
    $id = intval($_GET['id'] ?? 0);
    $db = getDB();
    
    $user = getCurrentUser();
    
    $db->update('visitor_bookings', [
        'status' => 'approved',
        'approved_by' => $user['id'] ?? 1,
        'approved_at' => date('Y-m-d H:i:s')
    ], 'id = :id', ['id' => $id]);
    
    echo json_encode(['success' => true, 'message' => 'Booking approved']);
}

function rejectBooking() {
    $id = intval($_GET['id'] ?? 0);
    $db = getDB();
    
    $db->update('visitor_bookings', [
        'status' => 'rejected'
    ], 'id = :id', ['id' => $id]);
    
    echo json_encode(['success' => true, 'message' => 'Booking rejected']);
}

function approveVisitor() {
    $id = intval($_GET['id'] ?? 0);
    $db = getDB();
    
    $user = getCurrentUser();
    
    $db->update('visitor_logs', [
        'status' => 'inside'
    ], 'id = :id', ['id' => $id]);
    
    logAction('approve', 'visitor_logs', $id);
    
    echo json_encode(['success' => true, 'message' => 'Visitor approved']);
}

function rejectVisitor() {
    $id = intval($_GET['id'] ?? 0);
    $db = getDB();
    
    $db->update('visitor_logs', [
        'status' => 'blocked',
        'exit_time' => date('Y-m-d H:i:s')
    ], 'id = :id', ['id' => $id]);
    
    logAction('reject', 'visitor_logs', $id);
    
    echo json_encode(['success' => true, 'message' => 'Visitor rejected']);
}

function recordExit() {
    $id = intval($_GET['id'] ?? 0);
    $db = getDB();
    
    $log = $db->fetchOne("SELECT entry_time FROM visitor_logs WHERE id = ?", [$id]);
    
    $duration = 0;
    if ($log && $log['entry_time']) {
        $duration = (time() - strtotime($log['entry_time'])) / 60;
    }
    
    $db->update('visitor_logs', [
        'status' => 'exited',
        'exit_time' => date('Y-m-d H:i:s'),
        'duration_minutes' => intval($duration)
    ], 'id = :id', ['id' => $id]);
    
    logAction('exit', 'visitor_logs', $id);
    
    echo json_encode(['success' => true, 'message' => 'Exit recorded']);
}

function listWhitelist() {
    $db = getDB();
    
    $vehicles = $db->fetchAll("
        SELECT plate, owner_name, category, access_hours, expires_at
        FROM vehicles
        WHERE is_blacklisted = 0
        ORDER BY created_at DESC
    ");
    
    echo json_encode($vehicles);
}
