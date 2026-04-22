<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireRole(['gate_officer', 'supervisor', 'hq_command', 'admin']);

$db = getDB();
$user = getCurrentUser();
$facility_id = $_SESSION['facility_id'] ?? 1;

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plate = strtoupper(sanitize($_POST['plate_number'] ?? ''));
    
    if (empty($plate)) {
        $error = "Plate number is required";
    } else {
        $vehicle = $db->fetchOne("SELECT * FROM vehicles WHERE plate_number = ?", [$plate]);
        
        if (!$vehicle) {
            $error = "Vehicle $plate not found in system";
        } else {
            $log = $db->fetchOne("
                SELECT * FROM vehicle_logs 
                WHERE vehicle_id = ? AND status = 'inside' 
                ORDER BY entry_time DESC LIMIT 1", [$vehicle['id']]);
            
            if (!$log) {
                $error = "No active entry found for vehicle $plate";
            } else {
                $exit_time = date('Y-m-d H:i:s');
                $duration = (strtotime($exit_time) - strtotime($log['entry_time'])) / 60;
                
                $db->update('vehicle_logs', [
                    'exit_time' => $exit_time,
                    'duration_minutes' => (int)$duration,
                    'status' => 'exited',
                    'gate_officer_exit_id' => $user['id']
                ], 'id = :id', ['id' => $log['id']]);
                
                logAction('vehicle_exit', 'vehicle_logs', $log['id'], null, [
                    'plate' => $plate,
                    'duration' => $duration
                ]);
                
                $hours = floor($duration / 60);
                $mins = $duration % 60;
                $success = "VEHICLE EXIT: $plate<br>Duration: {$hours}h {$mins}m";
            }
        }
    }
}

$insideVehicles = $db->fetchAll("
    SELECT vl.*, v.plate_number, v.last_driver_name 
    FROM vehicle_logs vl 
    JOIN vehicles v ON vl.vehicle_id = v.id 
    WHERE vl.status = 'inside' 
    AND vl.facility_id = ? 
    ORDER BY vl.entry_time ASC", [$facility_id]);

$overstays = array_filter($insideVehicles, function($v) {
    $hours = (time() - strtotime($v['entry_time'])) / 3600;
    return $hours > 72;
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Vehicle Exit - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { 
            background: #1a1a2e; 
            margin: 0; 
            padding: 0; 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
        }
        .app-container {
            max-width: 500px;
            margin: 0 auto;
            min-height: 100vh;
            background: #f0f2f5;
        }
        .header-bar {
            background: linear-gradient(135deg, #fd7e14 0%, #e8590c 100%);
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-bar h1 { margin: 0; font-size: 1.2rem; font-weight: 600; }
        .header-bar .badge { background: rgba(255,255,255,0.2); font-size: 0.75rem; }
        
        .form-section { padding: 15px; }
        
        .alert-box {
            padding: 15px;
            border-radius: 10px;
            margin: 15px;
            text-align: center;
        }
        .alert-success { background: #d1e7dd; color: #0a3622; border: 1px solid #a3cfbb; }
        .alert-danger { background: #f8d7da; color: #58151c; border: 1px solid #f1aeb5; }
        
        .plate-input-group { position: relative; margin-bottom: 15px; }
        .plate-input {
            font-size: 1.8rem;
            font-weight: bold;
            text-align: center;
            letter-spacing: 3px;
            padding: 20px;
            text-transform: uppercase;
            border: 3px solid #fd7e14;
            border-radius: 12px;
            background: #fff;
        }
        .plate-input:focus { 
            border-color: #fd7e14;
            box-shadow: 0 0 0 4px rgba(253, 126, 20, 0.2);
            outline: none;
        }
        
        .vehicle-info {
            background: #e7f5ff;
            border: 1px solid #74c0fc;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            display: none;
        }
        .vehicle-info.show { display: block; }
        .vehicle-info .plate { font-size: 1.3rem; font-weight: 700; }
        .vehicle-info .detail { color: #495057; font-size: 0.9rem; }
        
        .btn-exit {
            background: linear-gradient(135deg, #fd7e14 0%, #e8590c 100%);
            color: white;
            border: none;
            padding: 18px;
            font-size: 1.1rem;
            font-weight: 700;
            border-radius: 10px;
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .list-section { padding: 0 15px 15px; }
        .section-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #ddd;
        }
        
        .vehicle-card {
            background: white;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.2s;
        }
        .vehicle-card:active { transform: scale(0.98); background: #f8f9fa; }
        .vehicle-card.overstay { border-left: 4px solid #dc3545; }
        .vehicle-card .plate { font-weight: 700; font-size: 1.1rem; }
        .vehicle-card .meta { font-size: 0.8rem; color: #666; }
        .vehicle-card .duration { 
            font-size: 0.9rem; 
            font-weight: 600;
            color: #dc3545;
        }
        .vehicle-card .duration.normal { color: #198754; }
        
        .overstay-warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 10px;
            margin: 15px;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            padding: 15px;
        }
        .quick-btn {
            padding: 15px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            color: white;
        }
        .quick-btn.entry { background: #198754; }
        .quick-btn.records { background: #6c757d; }
        .quick-btn i { font-size: 1.3rem; }
        
        .count-badge {
            background: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 8px;
            font-size: 0.75rem;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <div class="header-bar">
            <div>
                <h1><i class="bi bi-box-arrow-left"></i> VEHICLE EXIT</h1>
            </div>
            <div>
                <span class="badge"><i class="bi bi-person"></i> <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Officer'); ?></span>
            </div>
        </div>
        
        <?php if (!empty($overstays)): ?>
        <div class="overstay-warning">
            <strong><i class="bi bi-exclamation-triangle"></i> OVERSTAY ALERT (<?php echo count($overstays); ?>)</strong>
        </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="alert-box alert-success">
            <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
            <div class="mt-2"><?php echo $success; ?></div>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert-box alert-danger">
            <i class="bi bi-exclamation-triangle" style="font-size: 2rem;"></i>
            <div class="mt-2"><?php echo $error; ?></div>
        </div>
        <?php endif; ?>
        
        <div class="quick-actions">
            <a href="vehicle-entry-mobile.php" class="quick-btn entry">
                <i class="bi bi-box-arrow-right"></i>
                VEHICLE IN
            </a>
            <a href="search.php" class="quick-btn records">
                <i class="bi bi-search"></i>
                SEARCH
            </a>
        </div>
        
        <div class="form-section">
            <form method="POST" id="exit-form">
                <div class="plate-input-group">
                    <input type="text" name="plate_number" id="plate_number" 
                           class="form-control plate-input" 
                           placeholder="UAR 123X" 
                           required 
                           autocomplete="off"
                           autofocus>
                </div>
                
                <div class="vehicle-info" id="vehicleInfo">
                    <div class="plate" id="infoPlate">-</div>
                    <div class="detail mt-2">
                        <div><i class="bi bi-person"></i> Driver: <span id="infoDriver">-</span></div>
                        <div><i class="bi bi-clock"></i> Entry: <span id="infoEntry">-</span></div>
                        <div><i class="bi bi-hourglass"></i> Duration: <span id="infoDuration">-</span></div>
                    </div>
                </div>
                
                <button type="submit" class="btn-exit" id="submitBtn">
                    <i class="bi bi-check-circle"></i> RECORD EXIT
                </button>
            </form>
        </div>
        
        <div class="list-section">
            <div class="section-title">
                INSIDE NOW 
                <span class="count-badge"><?php echo count($insideVehicles); ?></span>
            </div>
            
            <?php if ($insideVehicles): ?>
                <?php foreach ($insideVehicles as $v): 
                    $hours = (time() - strtotime($v['entry_time'])) / 3600;
                    $isOverstay = $hours > 72;
                ?>
                <div class="vehicle-card <?php echo $isOverstay ? 'overstay' : ''; ?>" 
                     onclick="selectVehicle('<?php echo $v['plate_number']; ?>')">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <div class="plate"><?php echo $v['plate_number']; ?></div>
                            <div class="meta">
                                <?php echo htmlspecialchars($v['driver_name'] ?: $v['last_driver_name'] ?: 'N/A'); ?> 
                                • <?php echo date('H:i', strtotime($v['entry_time'])); ?>
                            </div>
                        </div>
                        <div class="duration <?php echo $isOverstay ? '' : 'normal'; ?>">
                            <?php 
                            $h = floor($hours);
                            $m = floor(($hours - $h) * 60);
                            echo "{$h}h {$m}m";
                            if ($isOverstay) echo ' ⚠️';
                            ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center text-muted py-4">
                    <i class="bi bi-car-front" style="font-size: 3rem;"></i>
                    <p class="mt-2 mb-0">No vehicles inside</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    const plateInput = document.getElementById('plate_number');
    const vehicleInfo = document.getElementById('vehicleInfo');
    const submitBtn = document.getElementById('submitBtn');
    
    let checkTimeout = null;
    
    function selectVehicle(plate) {
        plateInput.value = plate;
        plateInput.dispatchEvent(new Event('input'));
    }
    
    function checkVehicle(plate) {
        if (!plate) {
            vehicleInfo.classList.remove('show');
            submitBtn.disabled = false;
            return;
        }
        
        fetch('<?php echo SITE_URL; ?>/api/vehicles.php?action=check_inside&plate=' + encodeURIComponent(plate))
            .then(r => r.json())
            .then(data => {
                if (data.found) {
                    document.getElementById('infoPlate').textContent = plate;
                    document.getElementById('infoDriver').textContent = data.driver_name || 'N/A';
                    document.getElementById('infoEntry').textContent = new Date(data.entry_time).toLocaleString('en-UG');
                    
                    const entryTime = new Date(data.entry_time);
                    const now = new Date();
                    const diffMs = now - entryTime;
                    const diffHrs = diffMs / (1000 * 60 * 60);
                    document.getElementById('infoDuration').textContent = 
                        Math.floor(diffHrs) + 'h ' + Math.floor((diffHrs % 1) * 60) + 'm';
                    
                    vehicleInfo.classList.add('show');
                    submitBtn.disabled = false;
                } else {
                    vehicleInfo.classList.remove('show');
                    submitBtn.disabled = false;
                }
            })
            .catch(() => {
                vehicleInfo.classList.remove('show');
            });
    }
    
    plateInput.addEventListener('input', function() {
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9 ]/g, '');
        
        clearTimeout(checkTimeout);
        checkTimeout = setTimeout(() => {
            checkVehicle(this.value);
        }, 500);
    });
    
    document.getElementById('exit-form').addEventListener('submit', function() {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> PROCESSING...';
    });
    
    plateInput.focus();
    </script>
</body>
</html>
