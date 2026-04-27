<?php
/**
 * Delivery & Frequent Visitor System
 * Handles vehicles AND person-based frequent visits
 */
session_start();
header('Content-Type: application/json');

$db_file = __DIR__ . '/delivery_db.json';

function getDB() {
    global $db_file;
    if (!file_exists($db_file)) {
        return ['vehicles' => [], 'frequent_visitors' => [], 'deliveries' => []];
    }
    return json_decode(file_get_contents($db_file), true) ?: ['vehicles' => [], 'frequent_visitors' => [], 'deliveries' => []];
}

function saveDB($data) {
    global $db_file;
    file_put_contents($db_file, json_encode($data, JSON_PRETTY_PRINT));
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$action = $_GET['action'] ?? $input['action'] ?? '';

switch ($action) {
    // ============ VEHICLES ============
    case 'list_vehicles':
        $db = getDB();
        echo json_encode(['success' => true, 'vehicles' => $db['vehicles']]);
        break;
        
    case 'register_vehicle':
        $db = getDB();
        $vehicle = [
            'id' => uniqid('DEL-'),
            'type' => 'vehicle',
            'company' => $input['company'] ?? '',
            'vehicle_plate' => strtoupper($input['vehicle_plate'] ?? ''),
            'driver_name' => $input['driver_name'] ?? '',
            'driver_phone' => $input['driver_phone'] ?? '',
            'vehicle_type' => $input['vehicle_type'] ?? 'car', // car, motorcycle
            'frequency' => $input['frequency'] ?? 'recurring',
            'allowed_days' => $input['allowed_days'] ?? '1,2,3,4,5',
            'allowed_hours_start' => $input['allowed_hours_start'] ?? '06:00',
            'allowed_hours_end' => $input['allowed_hours_end'] ?? '18:00',
            'qr_code' => 'DEL-' . strtoupper($input['vehicle_plate']),
            'created_at' => date('Y-m-d H:i:s'),
            'status' => 'active'
        ];
        
        foreach ($db['vehicles'] as $v) {
            if ($v['vehicle_plate'] === $vehicle['vehicle_plate']) {
                echo json_encode(['success' => false, 'error' => 'Vehicle already registered']);
                exit;
            }
        }
        
        $db['vehicles'][] = $vehicle;
        saveDB($db);
        echo json_encode(['success' => true, 'vehicle' => $vehicle]);
        break;
        
    case 'check_vehicle':
        $plate = strtoupper($input['vehicle_plate'] ?? '');
        $db = getDB();
        
        foreach ($db['vehicles'] as $v) {
            if ($v['vehicle_plate'] === $plate && $v['status'] === 'active') {
                $current_day = date('w');
                $current_time = date('H:i');
                $allowed_days = explode(',', $v['allowed_days']);
                $in_allowed_day = in_array($current_day, $allowed_days);
                $in_allowed_time = $current_time >= $v['allowed_hours_start'] && $current_time <= $v['allowed_hours_end'];
                
                if ($in_allowed_day && $in_allowed_time) {
                    echo json_encode(['success' => true, 'allowed' => true, 'vehicle' => $v, 'type' => 'vehicle']);
                } else {
                    $reason = !$in_allowed_day ? 'Outside allowed days' : 'Outside allowed hours (' . $v['allowed_hours_start'] . '-' . $v['allowed_hours_end'] . ')';
                    echo json_encode(['success' => true, 'allowed' => false, 'vehicle' => $v, 'type' => 'vehicle', 'message' => $reason]);
                }
                exit;
            }
        }
        echo json_encode(['success' => true, 'allowed' => false, 'message' => 'Vehicle not registered']);
        break;
        
    // ============ FREQUENT VISITORS (PERSONS) ============
    case 'list_visitors':
        $db = getDB();
        echo json_encode(['success' => true, 'visitors' => $db['frequent_visitors']]);
        break;
        
    case 'register_visitor':
        $db = getDB();
        $visitor = [
            'id' => uniqid('FREQ-'),
            'type' => 'person',
            'full_name' => $input['full_name'] ?? '',
            'id_number' => $input['id_number'] ?? '',
            'phone' => $input['phone'] ?? '',
            'organization' => $input['organization'] ?? '',
            'visits_for' => $input['visits_for'] ?? '', // person they're visiting
            'frequency' => $input['frequency'] ?? 'weekly',
            'allowed_days' => $input['allowed_days'] ?? '1,2,3,4,5',
            'allowed_hours_start' => $input['allowed_hours_start'] ?? '08:00',
            'allowed_hours_end' => $input['allowed_hours_end'] ?? '17:00',
            'vehicle_plate' => !empty($input['vehicle_plate']) ? strtoupper($input['vehicle_plate']) : null,
            'qr_code' => 'FREQ-' . strtoupper(str_replace(' ', '', substr($input['full_name'] ?? 'VIS', 0, 8))),
            'created_at' => date('Y-m-d H:i:s'),
            'status' => 'active'
        ];
        
        $db['frequent_visitors'][] = $visitor;
        saveDB($db);
        echo json_encode(['success' => true, 'visitor' => $visitor]);
        break;
        
    case 'check_visitor':
        $id = $input['qr_code'] ?? $input['id_number'] ?? '';
        $db = getDB();
        
        // Check by QR code or ID number
        foreach ($db['frequent_visitors'] as $v) {
            if (($v['qr_code'] === $id || $v['id_number'] === $id) && $v['status'] === 'active') {
                $current_day = date('w');
                $current_time = date('H:i');
                $allowed_days = explode(',', $v['allowed_days']);
                $in_allowed_day = in_array($current_day, $allowed_days);
                $in_allowed_time = $current_time >= $v['allowed_hours_start'] && $current_time <= $v['allowed_hours_end'];
                
                if ($in_allowed_day && $in_allowed_time) {
                    echo json_encode(['success' => true, 'allowed' => true, 'visitor' => $v, 'type' => 'person']);
                } else {
                    $reason = !$in_allowed_day ? 'Outside allowed days' : 'Outside allowed hours';
                    echo json_encode(['success' => true, 'allowed' => false, 'visitor' => $v, 'type' => 'person', 'message' => $reason]);
                }
                exit;
            }
        }
        echo json_encode(['success' => true, 'allowed' => false, 'message' => 'Visitor not registered in frequent visitor list']);
        break;
        
    // ============ ENTRY/EXIT ============
    case 'record_entry':
        $plate = strtoupper($input['vehicle_plate'] ?? '');
        $qr_code = $input['qr_code'] ?? '';
        $visitor_name = $input['visitor_name'] ?? '';
        $db = getDB();
        
        $delivery = [
            'id' => uniqid('DVY-'),
            'vehicle_plate' => $plate ?: null,
            'qr_code' => $qr_code ?: null,
            'visitor_name' => $visitor_name ?: null,
            'entry_time' => date('Y-m-d H:i:s'),
            'exit_time' => null,
            'status' => 'inside'
        ];
        
        $db['deliveries'][] = $delivery;
        saveDB($db);
        echo json_encode(['success' => true, 'delivery' => $delivery]);
        break;
        
    case 'record_exit':
        $plate = strtoupper($input['vehicle_plate'] ?? '');
        $qr_code = $input['qr_code'] ?? '';
        $db = getDB();
        
        // Find active entry by plate or QR code
        foreach (array_reverse($db['deliveries']) as $i => $d) {
            if ($d['status'] === 'inside') {
                $match = false;
                if (!empty($plate) && $d['vehicle_plate'] === $plate) $match = true;
                if (!empty($qr_code) && $d['qr_code'] === $qr_code) $match = true;
                
                if ($match) {
                    $db['deliveries'][$i]['exit_time'] = date('Y-m-d H:i:s');
                    $db['deliveries'][$i]['status'] = 'exited';
                    saveDB($db);
                    echo json_encode(['success' => true, 'delivery' => $db['deliveries'][$i]]);
                    exit;
                }
            }
        }
        echo json_encode(['success' => false, 'error' => 'No active entry found']);
        break;
        
    case 'get_inside':
        $db = getDB();
        $inside = array_filter($db['deliveries'], fn($d) => $d['status'] === 'inside');
        echo json_encode(['success' => true, 'deliveries' => array_values($inside)]);
        break;
        
    case 'deactivate':
        $id = $input['id'] ?? '';
        $db = getDB();
        
        foreach ($db['vehicles'] as $i => $v) {
            if ($v['id'] === $id) {
                $db['vehicles'][$i]['status'] = 'inactive';
                saveDB($db);
                echo json_encode(['success' => true]);
                exit;
            }
        }
        foreach ($db['frequent_visitors'] as $i => $v) {
            if ($v['id'] === $id) {
                $db['frequent_visitors'][$i]['status'] = 'inactive';
                saveDB($db);
                echo json_encode(['success' => true]);
                exit;
            }
        }
        echo json_encode(['success' => false]);
        break;
        
    case 'anpr_entry':
        // ANPR detected a plate - check and record
        $plate = strtoupper($input['plate'] ?? '');
        $db = getDB();
        
        // Check vehicles
        foreach ($db['vehicles'] as $v) {
            if ($v['vehicle_plate'] === $plate && $v['status'] === 'active') {
                $delivery = [
                    'id' => uniqid('ANPR-'),
                    'vehicle_plate' => $plate,
                    'qr_code' => null,
                    'visitor_name' => $v['driver_name'],
                    'entry_time' => date('Y-m-d H:i:s'),
                    'exit_time' => null,
                    'status' => 'inside',
                    'method' => 'ANPR'
                ];
                $db['deliveries'][] = $delivery;
                saveDB($db);
                echo json_encode(['success' => true, 'action' => 'GRANTED', 'vehicle' => $v, 'message' => 'Auto-entry via ANPR']);
                exit;
            }
        }
        
        echo json_encode(['success' => true, 'action' => 'DENIED', 'message' => 'Plate not in whitelist']);
        break;
        
    case 'anpr_exit':
        // ANPR at exit - auto sign out
        $plate = strtoupper($input['plate'] ?? '');
        $db = getDB();
        
        foreach (array_reverse($db['deliveries']) as $i => $d) {
            if ($d['vehicle_plate'] === $plate && $d['status'] === 'inside') {
                $db['deliveries'][$i]['exit_time'] = date('Y-m-d H:i:s');
                $db['deliveries'][$i]['status'] = 'exited';
                $db['deliveries'][$i]['exit_method'] = 'ANPR';
                saveDB($db);
                echo json_encode(['success' => true, 'action' => 'SIGNED_OUT', 'delivery' => $db['deliveries'][$i]]);
                exit;
            }
        }
        
        echo json_encode(['success' => true, 'action' => 'NOT_FOUND', 'message' => 'No active entry for this plate']);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $action]);
}