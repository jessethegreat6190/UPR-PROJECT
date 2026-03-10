<?php
header('Content-Type: application/json');
require_once 'db.php';

function getDashboardData($conn) {
    $today = date('Y-m-d');
    $week_start = date('Y-m-d', strtotime('-7 days'));
    $month_start = date('Y-m-01');
    
    $stmt = $conn->prepare("SELECT COALESCE(SUM(total_price), 0) as total, COALESCE(SUM(profit), 0) as profit FROM sales WHERE DATE(sale_date) = ?");
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $daily = $stmt->get_result()->fetch_assoc();
    
    $stmt = $conn->prepare("SELECT COALESCE(SUM(total_price), 0) as total, COALESCE(SUM(profit), 0) as profit FROM sales WHERE DATE(sale_date) >= ?");
    $stmt->bind_param("s", $week_start);
    $stmt->execute();
    $weekly = $stmt->get_result()->fetch_assoc();
    
    $stmt = $conn->prepare("SELECT COALESCE(SUM(total_price), 0) as total, COALESCE(SUM(profit), 0) as profit FROM sales WHERE DATE(sale_date) >= ?");
    $stmt->bind_param("s", $month_start);
    $stmt->execute();
    $monthly = $stmt->get_result()->fetch_assoc();
    
    $stmt = $conn->prepare("SELECT * FROM medicines WHERE total_stock <= low_stock_threshold ORDER BY total_stock ASC");
    $stmt->execute();
    $low_stock = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $expiry_warning = date('Y-m-d', strtotime('+90 days'));
    $stmt = $conn->prepare("SELECT b.*, m.name as medicine_name FROM batches b LEFT JOIN medicines m ON b.medicine_id = m.id WHERE b.expiry_date <= ? AND b.expiry_date > CURDATE() AND b.remaining_qty > 0 ORDER BY b.expiry_date ASC");
    $stmt->bind_param("s", $expiry_warning);
    $stmt->execute();
    $expiring = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'daily' => ['total' => floatval($daily['total']), 'profit' => floatval($daily['profit'])],
            'weekly' => ['total' => floatval($weekly['total']), 'profit' => floatval($weekly['profit'])],
            'monthly' => ['total' => floatval($monthly['total']), 'profit' => floatval($monthly['profit'])],
            'low_stock' => $low_stock,
            'expiring' => $expiring,
            'alert_count' => count($low_stock) + count($expiring)
        ]
    ]);
}

getDashboardData($conn);
?>
