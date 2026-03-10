<?php
header('Content-Type: application/json');
require_once 'db.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        createSale($conn);
        break;
    case 'getAll':
        getAllSales($conn);
        break;
}

function createSale($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $medicine_id = intval($data['medicine_id'] ?? 0);
    $qty_sold = intval($data['qty_sold'] ?? 0);
    $payment_method = $data['payment_method'] ?? 'Cash';
    
    if ($medicine_id <= 0 || $qty_sold <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT * FROM medicines WHERE id = ?");
    $stmt->bind_param("i", $medicine_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $medicine = $result->fetch_assoc();
    
    if (!$medicine) {
        echo json_encode(['success' => false, 'message' => 'Medicine not found']);
        return;
    }
    
    if ($medicine['total_stock'] < $qty_sold) {
        echo json_encode(['success' => false, 'message' => 'Insufficient stock. Available: ' . $medicine['total_stock']]);
        return;
    }
    
    $expired_check = $conn->prepare("SELECT SUM(remaining_qty) as expired_qty FROM batches WHERE medicine_id = ? AND expiry_date <= CURDATE()");
    $expired_check->bind_param("i", $medicine_id);
    $expired_check->execute();
    $expired_result = $expired_check->get_result()->fetch_assoc();
    $expired_qty = intval($expired_result['expired_qty'] ?? 0);
    if ($expired_qty > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot sell - medicine has expired stock. Please remove expired batches first.']);
        return;
    }
    
    $total_price = $medicine['unit_price'] * $qty_sold;
    $cost_amount = $medicine['cost_price'] * $qty_sold;
    $profit = $total_price - $cost_amount;
    
    $conn->begin_transaction();
    
    try {
        $stmt = $conn->prepare("INSERT INTO sales (medicine_id, qty_sold, total_price, cost_amount, profit, payment_method) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiddds", $medicine_id, $qty_sold, $total_price, $cost_amount, $profit, $payment_method);
        $stmt->execute();
        
        $remaining = $qty_sold;
        $stmt = $conn->prepare("SELECT * FROM batches WHERE medicine_id = ? AND remaining_qty > 0 ORDER BY expiry_date ASC, id ASC");
        $stmt->bind_param("i", $medicine_id);
        $stmt->execute();
        $batches = $stmt->get_result();
        
        while ($batch = $batches->fetch_assoc() && $remaining > 0) {
            $deduct = min($batch['remaining_qty'], $remaining);
            $new_remaining = $batch['remaining_qty'] - $deduct;
            $stmt = $conn->prepare("UPDATE batches SET remaining_qty = ? WHERE id = ?");
            $stmt->bind_param("ii", $new_remaining, $batch['id']);
            $stmt->execute();
            $remaining -= $deduct;
        }
        
        $new_total = $medicine['total_stock'] - $qty_sold;
        $stmt = $conn->prepare("UPDATE medicines SET total_stock = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_total, $medicine_id);
        $stmt->execute();
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Sale recorded successfully', 'profit' => $profit]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function getAllSales($conn) {
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    $countResult = $conn->query("SELECT COUNT(*) as total FROM sales");
    $total = $countResult->fetch_assoc()['total'];
    
    $stmt = $conn->prepare("SELECT s.*, m.name as medicine_name FROM sales s LEFT JOIN medicines m ON s.medicine_id = m.id ORDER BY s.sale_date DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $sales = [];
    while ($row = $result->fetch_assoc()) {
        $sales[] = $row;
    }
    echo json_encode([
        'success' => true, 
        'data' => [
            'sales' => $sales,
            'totalPages' => ceil($total / $limit),
            'currentPage' => $page
        ]
    ]);
}
?>
