<?php
header('Content-Type: application/json');
require_once 'db.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'getAll':
        getAllMedicines($conn);
        break;
    case 'add':
        addMedicine($conn);
        break;
    case 'search':
        searchMedicines($conn);
        break;
    case 'delete':
        deleteMedicine($conn);
        break;
    case 'edit':
        editMedicine($conn);
        break;
    case 'get':
        getMedicine($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function getAllMedicines($conn) {
    $search = $_GET['search'] ?? '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM medicines WHERE name LIKE ?");
    $searchTerm = "%$search%";
    $countStmt->bind_param("s", $searchTerm);
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
    
    $stmt = $conn->prepare("SELECT * FROM medicines WHERE name LIKE ? ORDER BY name ASC LIMIT ? OFFSET ?");
    $stmt->bind_param("sii", $searchTerm, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $medicines = [];
    while ($row = $result->fetch_assoc()) {
        $medicines[] = $row;
    }
    echo json_encode([
        'success' => true, 
        'data' => [
            'medicines' => $medicines,
            'totalPages' => ceil($total / $limit),
            'currentPage' => $page
        ]
    ]);
}

function searchMedicines($conn) {
    $search = $_GET['q'] ?? '';
    $stmt = $conn->prepare("SELECT m.* FROM medicines m 
        LEFT JOIN batches b ON m.id = b.medicine_id AND b.expiry_date > CURDATE() AND b.remaining_qty > 0 
        WHERE m.name LIKE ? AND (m.total_stock > 0 OR b.remaining_qty IS NOT NULL) 
        GROUP BY m.id 
        HAVING SUM(COALESCE(b.remaining_qty, 0)) > 0 
        ORDER BY m.name ASC LIMIT 10");
    $searchTerm = "%$search%";
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    $medicines = [];
    while ($row = $result->fetch_assoc()) {
        $medicines[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $medicines]);
}

function addMedicine($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $name = $data['name'] ?? '';
    $category = $data['category'] ?? 'tablet';
    $unit_price = floatval($data['unit_price'] ?? 0);
    $cost_price = floatval($data['cost_price'] ?? 0);
    $total_stock = intval($data['total_stock'] ?? 0);
    $low_stock_threshold = intval($data['low_stock_threshold'] ?? 10);
    $batch_number = $data['batch_number'] ?? '';
    $expiry_date = $data['expiry_date'] ?? '';
    
    if (empty($name) || $unit_price <= 0 || $total_stock <= 0) {
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
        return;
    }
    
    $conn->begin_transaction();
    
    try {
        $stmt = $conn->prepare("INSERT INTO medicines (name, category, unit_price, cost_price, total_stock, low_stock_threshold) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssddii", $name, $category, $unit_price, $cost_price, $total_stock, $low_stock_threshold);
        $stmt->execute();
        $medicine_id = $conn->insert_id;
        
        $stmt = $conn->prepare("INSERT INTO batches (medicine_id, batch_number, expiry_date, quantity, remaining_qty) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issii", $medicine_id, $batch_number, $expiry_date, $total_stock, $total_stock);
        $stmt->execute();
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Medicine added successfully']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function deleteMedicine($conn) {
    $id = intval($_GET['id'] ?? 0);
    $stmt = $conn->prepare("DELETE FROM medicines WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo json_encode(['success' => true]);
}

function getMedicine($conn) {
    $id = intval($_GET['id'] ?? 0);
    $stmt = $conn->prepare("SELECT * FROM medicines WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $medicine = $result->fetch_assoc();
    if ($medicine) {
        echo json_encode(['success' => true, 'data' => $medicine]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Medicine not found']);
    }
}

function editMedicine($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $id = intval($data['id'] ?? 0);
    $name = $data['name'] ?? '';
    $category = $data['category'] ?? 'tablet';
    $unit_price = floatval($data['unit_price'] ?? 0);
    $cost_price = floatval($data['cost_price'] ?? 0);
    $low_stock_threshold = intval($data['low_stock_threshold'] ?? 10);
    
    if ($id <= 0 || empty($name) || $unit_price <= 0) {
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
        return;
    }
    
    $stmt = $conn->prepare("UPDATE medicines SET name = ?, category = ?, unit_price = ?, cost_price = ?, low_stock_threshold = ? WHERE id = ?");
    $stmt->bind_param("ssddii", $name, $category, $unit_price, $cost_price, $low_stock_threshold, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Medicine updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
    }
}
?>
