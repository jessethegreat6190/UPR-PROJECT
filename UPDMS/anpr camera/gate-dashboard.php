<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireLogin();

if (!isset($_SESSION['gate_mode'])) {
    header('Location: ' . SITE_URL . '/pages/gate/gate-login.php');
    exit;
}

$db = getDB();
$user = getCurrentUser();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plate = strtoupper(sanitize($_POST['plate_number']));
    $driver_name = sanitize($_POST['driver_name']);
    $visitor_type = sanitize($_POST['visitor_type']);
    
    $vehicle = $db->fetchOne("SELECT * FROM vehicles WHERE plate_number = ?", [$plate]);
    
    if (!$vehicle) {
        $vehicle_id = $db->insert('vehicles', [
            'plate_number' => $plate,
            'driver_name' => $driver_name,
            'last_driver_name' => $driver_name,
            'last_visit' => date('Y-m-d H:i:s'),
            'total_visits' => 1
        ]);
    } else {
        $vehicle_id = $vehicle['id'];
        $db->update('vehicles', [
            'last_driver_name' => $driver_name,
            'last_visit' => date('Y-m-d H:i:s'),
            'total_visits' => $vehicle['total_visits'] + 1
        ], 'id = :id', ['id' => $vehicle_id]);
    }
    
    $log_id = $db->insert('vehicle_logs', [
        'vehicle_id' => $vehicle_id,
        'facility_id' => $user['facility_id'] ?: 1,
        'visitor_type' => $visitor_type,
        'driver_name' => $driver_name,
        'entry_time' => date('Y-m-d H:i:s'),
        'status' => 'inside',
        'gate_officer_entry_id' => $user['id']
    ]);
    
    $success = "Vehicle $plate recorded - Entry OK";
}

$todayEntries = $db->fetchAll("
    SELECT vl.*, v.plate_number, v.last_driver_name 
    FROM vehicle_logs vl 
    JOIN vehicles v ON vl.vehicle_id = v.id 
    WHERE DATE(vl.entry_time) = CURDATE() 
    ORDER BY vl.entry_time DESC LIMIT 10", []);

$overstays = $db->fetchAll("
    SELECT vl.*, v.plate_number, v.last_driver_name,
           TIMESTAMPDIFF(HOUR, vl.entry_time, NOW()) as hours_inside
    FROM vehicle_logs vl 
    JOIN vehicles v ON vl.vehicle_id = v.id 
    WHERE vl.status = 'inside' AND TIMESTAMPDIFF(HOUR, vl.entry_time, NOW()) > 72
    ORDER BY hours_inside DESC", []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gate Control - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { background: #f0f2f5; margin: 0; padding: 0; }
        .header-bar {
            background: #198754;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-bar h1 { margin: 0; font-size: 1.5rem; }
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            text-decoration: none;
        }
        .logout-btn:hover { background: rgba(255,255,255,0.3); color: white; }
        .main-container { max-width: 1000px; margin: 20px auto; padding: 0 15px; }
        .section-title {
            color: #1a1a2e;
            font-size: 1rem;
            font-weight: 600;
            margin: 15px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 2px solid #198754;
        }
        .grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px; }
        .action-btn {
            padding: 15px 10px; border-radius: 10px; border: none;
            font-size: 0.9rem; font-weight: 600; cursor: pointer;
            transition: all 0.2s; text-align: center; text-decoration: none;
            color: white; display: flex; flex-direction: column; align-items: center; gap: 5px;
        }
        .action-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); color: white; }
        .action-btn i { font-size: 1.5rem; }
        .action-btn small { font-weight: 400; font-size: 0.7rem; opacity: 0.9; }
        .btn-vehicle-out { background: #fd7e14; }
        .btn-overstay { background: #dc3545; }
        .btn-incident { background: #dc3545; }
        .btn-records { background: #6c757d; }
        .btn-logs { background: #4361ee; }
        .btn-contraband { background: #7209b7; }
        .alert-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-with-badge { position: relative; }
    </style>
</head>
<body>
    <div class="header-bar">
        <h1><i class="bi bi-car"></i> GATE CONTROL</h1>
        <a href="<?php echo SITE_URL; ?>/pages/gate/gate-logout.php" class="logout-btn">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
    
    <div class="main-container">
        <?php if ($success): ?>
        <div class="alert alert-success"><i class="bi bi-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>
        
        <!-- Overstay Alert -->
        <?php if ($overstays): ?>
        <div class="alert alert-danger">
            <strong><i class="bi bi-exclamation-triangle"></i> OVERSTAY ALERT!</strong>
            <?php foreach ($overstays as $o): ?>
            <div>- <?php echo $o['plate_number']; ?> (<?php echo $o['hours_inside']; ?>h inside)</div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Quick Actions -->
        <div class="section-title"><i class="bi bi-lightning"></i> QUICK ACTIONS</div>
        <div class="grid-3">
            <a href="<?php echo SITE_URL; ?>/pages/gate/exit.php" class="action-btn btn-vehicle-out">
                <i class="bi bi-arrow-left-circle"></i>
                VEHICLE OUT
                <small>Record Exit</small>
            </a>
            
            <a href="<?php echo SITE_URL; ?>/pages/gate/overstay.php" class="action-btn btn-overstay btn-with-badge">
                <?php if ($overstays): ?><span class="alert-count"><?php echo count($overstays); ?></span><?php endif; ?>
                <i class="bi bi-exclamation-triangle"></i>
                OVERSTAY
                <small>72+ Hours</small>
            </a>
            
            <a href="<?php echo SITE_URL; ?>/pages/incidents/report.php" class="action-btn btn-incident">
                <i class="bi bi-exclamation-circle"></i>
                INCIDENT
                <small>Report Issue</small>
            </a>
        </div>
        
        <div class="grid-3">
            <a href="<?php echo SITE_URL; ?>/pages/gate/search.php" class="action-btn btn-records">
                <i class="bi bi-search"></i>
                SEARCH
                <small>Find Records</small>
            </a>
            
            <a href="<?php echo SITE_URL; ?>/pages/visitors/logs.php" class="action-btn btn-logs">
                <i class="bi bi-journal-text"></i>
                LOGS
                <small>Visitor Records</small>
            </a>
            
            <a href="<?php echo SITE_URL; ?>/pages/gate/contraband.php" class="action-btn btn-contraband">
                <i class="bi bi-bag-x"></i>
                CONTRABAND
                <small>Log Seizures</small>
            </a>
        </div>
        
        <!-- Vehicle Entry -->
        <div class="section-title"><i class="bi bi-arrow-right-circle"></i> VEHICLE ENTRY</div>
        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <label class="form-label">Plate Number *</label>
                            <input type="text" name="plate_number" class="form-control text-uppercase" 
                                   placeholder="UAR 123X" required autofocus>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="form-label">Driver Name *</label>
                            <input type="text" name="driver_name" class="form-control" required>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label">Category</label>
                            <select name="visitor_type" class="form-select">
                                <option value="delivery">Delivery</option>
                                <option value="inmate">Inmate Visitor</option>
                                <option value="hospital">Hospital</option>
                                <option value="official">Official</option>
                                <option value="staff">Staff Car</option>
                            </select>
                        </div>
                        <div class="col-md-1 mb-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-check-lg"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Today's Entries -->
        <div class="section-title"><i class="bi bi-clock-history"></i> TODAY'S ENTRIES (<?php echo count($todayEntries); ?>)</div>
        <div class="card">
            <div class="card-body">
                <?php if ($todayEntries): ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Plate</th>
                                <th>Driver</th>
                                <th>Type</th>
                                <th>Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($todayEntries as $e): ?>
                            <tr class="<?php echo $e['status'] === 'inside' ? '' : 'table-secondary'; ?>">
                                <td><strong><?php echo $e['plate_number']; ?></strong></td>
                                <td><?php echo $e['driver_name'] ?: $e['last_driver_name']; ?></td>
                                <td><?php echo ucfirst($e['visitor_type']); ?></td>
                                <td><?php echo date('H:i', strtotime($e['entry_time'])); ?></td>
                                <td>
                                    <?php if ($e['status'] === 'inside'): ?>
                                        <span class="badge bg-success">IN</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">OUT</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center mb-0">No entries today</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
