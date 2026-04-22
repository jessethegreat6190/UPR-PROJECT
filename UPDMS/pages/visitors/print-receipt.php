<?php
require_once __DIR__ . '/../../config/bootstrap.php';

$log_id = (int)($_GET['log_id'] ?? 0);
$db = getDB();

$log = $db->fetchOne("
    SELECT vl.*, v.first_name, v.last_name, v.national_id, v.visitor_type, v.phone, v.address,
           p.prisoner_number, p.first_name as inmate_first, p.last_name as inmate_last,
           f.name as facility_name, u.full_name as gate_officer
    FROM visitor_logs vl
    JOIN visitors v ON vl.visitor_id = v.id
    LEFT JOIN prisoners p ON vl.prisoner_id = p.id
    JOIN facilities f ON vl.facility_id = f.id
    LEFT JOIN users u ON vl.gate_officer_entry_id = u.id
    WHERE vl.id = ?", [$log_id]);

if (!$log) {
    die('Receipt not found');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Receipt - <?php echo $log['visitor_type']; ?></title>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 20px; }
        }
        body {
            font-family: 'Courier New', monospace;
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .receipt {
            background: white;
            border: 2px dashed #333;
            padding: 20px;
            text-align: center;
        }
        .header {
            border-bottom: 2px dashed #333;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            text-transform: uppercase;
        }
        .header p {
            margin: 5px 0;
            font-size: 12px;
        }
        .qr-code {
            width: 150px;
            height: 150px;
            margin: 15px auto;
            background: #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #ccc;
        }
        .qr-code span {
            color: #999;
        }
        .details {
            text-align: left;
            margin: 15px 0;
        }
        .details .row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px dotted #ccc;
        }
        .details .label {
            font-weight: bold;
            font-size: 12px;
        }
        .details .value {
            font-size: 12px;
            text-align: right;
        }
        .footer {
            border-top: 2px dashed #333;
            padding-top: 15px;
            margin-top: 15px;
            font-size: 10px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #0d6efd;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #0b5ed7;
        }
        .visitor-type {
            display: inline-block;
            padding: 5px 15px;
            background: #198754;
            color: white;
            text-transform: uppercase;
            font-weight: bold;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="receipt" id="receipt">
        <div class="header">
            <h1>UPDMS Visitor Pass</h1>
            <p><?php echo htmlspecialchars($log['facility_name']); ?></p>
            <p>Uganda Prisons Service</p>
        </div>
        
        <div class="qr-code">
            <span>[QR CODE]<br><?php echo $log_id; ?></span>
        </div>
        
        <div class="visitor-type"><?php echo ucfirst($log['visitor_type']); ?> Visit</div>
        
        <div class="details">
            <div class="row">
                <span class="label">Receipt #:</span>
                <span class="value"><?php echo str_pad($log_id, 6, '0', STR_PAD_LEFT); ?></span>
            </div>
            <div class="row">
                <span class="label">Date:</span>
                <span class="value"><?php echo date('d M Y', strtotime($log['entry_time'])); ?></span>
            </div>
            <div class="row">
                <span class="label">Time:</span>
                <span class="value"><?php echo date('H:i:s', strtotime($log['entry_time'])); ?></span>
            </div>
            <div class="row">
                <span class="label">Visitor:</span>
                <span class="value"><?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?></span>
            </div>
            <?php if ($log['national_id']): ?>
            <div class="row">
                <span class="label">ID No:</span>
                <span class="value"><?php echo htmlspecialchars($log['national_id']); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($log['prisoner_number']): ?>
            <div class="row">
                <span class="label">Visiting:</span>
                <span class="value"><?php echo htmlspecialchars($log['prisoner_number']); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($log['inmate_first']): ?>
            <div class="row">
                <span class="label">Inmate:</span>
                <span class="value"><?php echo htmlspecialchars($log['inmate_first'] . ' ' . $log['inmate_last']); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($log['vehicle_plate']): ?>
            <div class="row">
                <span class="label">Vehicle:</span>
                <span class="value"><?php echo htmlspecialchars($log['vehicle_plate']); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($log['phone']): ?>
            <div class="row">
                <span class="label">Phone:</span>
                <span class="value"><?php echo htmlspecialchars($log['phone']); ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            <p><strong>Gate Officer:</strong> <?php echo htmlspecialchars($log['gate_officer']); ?></p>
            <p>Please keep this receipt and present it when leaving.</p>
            <p>Thank you for visiting.</p>
        </div>
    </div>
    
    <div class="text-center no-print" style="margin-top: 20px;">
        <button onclick="window.print()" class="btn">
            <i class="bi bi-printer"></i> Print Receipt
        </button>
        <a href="<?php echo SITE_URL; ?>/pages/dashboard.php" class="btn">
            <i class="bi bi-house"></i> Back to Dashboard
        </a>
    </div>
</body>
</html>
