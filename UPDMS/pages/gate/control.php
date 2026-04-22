<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireRole(['gate_officer', 'supervisor', 'hq_command', 'admin']);

$db = getDB();
$user = getCurrentUser();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plate = strtoupper(sanitize($_POST['plate_number']));
    $driver_name = sanitize($_POST['driver_name']);
    $driver_id = sanitize($_POST['driver_id']);
    $visitor_type = sanitize($_POST['visitor_type']);
    $cargo_description = sanitize($_POST['cargo_description']);
    $cargo_checked = isset($_POST['cargo_checked']) ? 1 : 0;
    $notes = sanitize($_POST['notes']);
    
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
        
        if ($vehicle['is_blacklisted']) {
            $error = "Vehicle is blacklisted: " . $vehicle['blacklisted_reason'];
        } else {
            $db->update('vehicles', [
                'last_driver_name' => $driver_name,
                'last_visit' => date('Y-m-d H:i:s'),
                'total_visits' => $vehicle['total_visits'] + 1
            ], 'id = :id', ['id' => $vehicle_id]);
        }
    }
    
    if (!$error) {
        $photo_path = null;
        if (!empty($_POST['plate_photo'])) {
            $data = $_POST['plate_photo'];
            if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
                $data = substr($data, strpos($data, ',') + 1);
                $type = strtolower($type[1]);
                $data = base64_decode($data);
                
                $filename = 'plate_' . time() . '_' . $plate . '.jpg';
                $photo_path = 'uploads/vehicles/' . $filename;
                file_put_contents(__DIR__ . '/../../' . $photo_path, $data);
            }
        }

        $log_id = $db->insert('vehicle_logs', [
            'vehicle_id' => $vehicle_id,
            'facility_id' => $user['facility_id'] ?: 1,
            'visitor_type' => $visitor_type,
            'driver_name' => $driver_name,
            'driver_id' => $driver_id,
            'entry_time' => date('Y-m-d H:i:s'),
            'cargo_description' => $cargo_description,
            'cargo_checked' => $cargo_checked,
            'status' => 'inside',
            'gate_officer_entry_id' => $user['id'],
            'entry_photo_path' => $photo_path
        ]);
        
        logAction('vehicle_entry', 'vehicle_logs', $log_id);
        $success = "Vehicle $plate recorded successfully at " . date('H:i:s');
    }
}

$pageTitle = 'Gate Control';
include __DIR__ . '/../../includes/gate-header.php';
?>

<style>
body { background: #f0f2f5; }
.main-container { max-width: 900px; margin: 20px auto; padding: 0 15px; }
.header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.header-bar h2 { margin: 0; color: #1a1a2e; }
.section-title {
    color: #1a1a2e; font-size: 1rem; font-weight: 600;
    margin: 15px 0 10px 0; padding-bottom: 5px; border-bottom: 2px solid #4361ee;
}
.grid-3 {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 10px;
    margin-bottom: 20px;
}
.action-btn {
    padding: 15px 10px; border-radius: 10px; border: none;
    font-size: 0.9rem; font-weight: 600; cursor: pointer;
    transition: all 0.2s; text-align: center; text-decoration: none;
    color: white; display: flex; flex-direction: column; align-items: center; gap: 5px;
}
.action-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); color: white; }
.action-btn i { font-size: 1.5rem; }
.action-btn small { font-weight: 400; font-size: 0.75rem; opacity: 0.9; }
.btn-vehicle-in { background: #198754; }
.btn-vehicle-out { background: #fd7e14; }
.btn-overstay { background: #dc3545; }
.btn-incident { background: #dc3545; }
.btn-records { background: #6c757d; }
.btn-logs { background: #4361ee; }
</style>

<div class="main-container">
    <div class="header-bar">
        <h2><i class="bi bi-car"></i> Gate Control</h2>
        <a href="<?php echo SITE_URL; ?>/pages/dashboard.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Registration
        </a>
    </div>
    
    <?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <!-- Quick Actions -->
    <div class="section-title"><i class="bi bi-lightning"></i> QUICK ACTIONS</div>
    <div class="grid-3">
        <a href="<?php echo SITE_URL; ?>/pages/gate/exit.php" class="action-btn btn-vehicle-out">
            <i class="bi bi-arrow-left-circle"></i>
            VEHICLE OUT
            <small>Record Exit</small>
        </a>
        
        <a href="<?php echo SITE_URL; ?>/pages/gate/overstay.php" class="action-btn btn-overstay">
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
        
        <a href="<?php echo SITE_URL; ?>/pages/gate/contraband.php" class="action-btn btn-records">
            <i class="bi bi-bag-x"></i>
            CONTRABAND
            <small>Log Seizures</small>
        </a>
    </div>
    
    <!-- Vehicle Entry Form -->
    <div class="section-title"><i class="bi bi-arrow-right-circle"></i> VEHICLE ENTRY</div>
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="plate_photo" id="platePhoto">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Plate Number *</label>
                        <div class="input-group">
                            <input type="text" name="plate_number" id="plateInput" class="form-control text-uppercase" 
                                   placeholder="UAR 123X" required autofocus>
                            <button type="button" class="btn btn-outline-primary" id="scanPlateBtn">
                                <i class="bi bi-camera"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Driver Name *</label>
                        <input type="text" name="driver_name" id="driverName" class="form-control" placeholder="Driver name" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Category</label>
                        <select name="visitor_type" class="form-select" required>
                            <option value="delivery">Delivery</option>
                            <option value="inmate">Inmate Visitor</option>
                            <option value="hospital">Hospital Visitor</option>
                            <option value="official">Official</option>
                            <option value="staff">Staff Car</option>
                        </select>
                    </div>
                </div>

                <!-- Camera Section (Hidden by default) -->
                <div id="cameraSection" class="mb-3 d-none">
                    <div class="position-relative bg-dark rounded overflow-hidden" style="height: 250px;">
                        <video id="video" class="w-100 h-100" style="object-fit: cover;"></video>
                        <div class="position-absolute top-50 start-50 translate-middle border border-success border-4" 
                             style="width: 80%; height: 40%; pointer-events: none;"></div>
                        <div class="position-absolute bottom-0 start-0 end-0 p-2 bg-dark bg-opacity-50 text-center">
                            <button type="button" id="captureBtn" class="btn btn-light btn-sm rounded-circle p-2 shadow">
                                <div class="bg-danger rounded-circle" style="width: 15px; height: 15px;"></div>
                            </button>
                            <button type="button" id="closeCameraBtn" class="btn btn-danger btn-sm float-end">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                    <canvas id="canvas" class="d-none"></canvas>
                    <div id="ocrStatus" class="mt-2 text-center text-primary d-none small">
                        <div class="spinner-border spinner-border-sm me-2"></div>Reading plate...
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Driver ID</label>
                        <input type="text" name="driver_id" class="form-control" placeholder="National ID">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Items / Purpose</label>
                        <input type="text" name="cargo_description" class="form-control" placeholder="What's being delivered">
                    </div>
                </div>
                <button type="submit" class="btn btn-success btn-lg w-100">
                    <i class="bi bi-check-circle"></i> RECORD VEHICLE ENTRY
                </button>
            </form>
        </div>
    </div>
    
    <!-- Today's Entries -->
    <div class="section-title"><i class="bi bi-clock-history"></i> TODAY'S VEHICLE ENTRIES</div>
    <?php
    $todayEntries = $db->fetchAll("
        SELECT vl.*, v.plate_number, v.last_driver_name 
        FROM vehicle_logs vl 
        JOIN vehicles v ON vl.vehicle_id = v.id 
        WHERE DATE(vl.entry_time) = CURDATE() 
        ORDER BY vl.entry_time DESC LIMIT 10", []);
    ?>
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
                            <th>Entry Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($todayEntries as $e): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($e['plate_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($e['driver_name'] ?: $e['last_driver_name']); ?></td>
                            <td><span class="badge bg-secondary"><?php echo ucfirst($e['visitor_type']); ?></span></td>
                            <td><?php echo formatTime($e['entry_time']); ?></td>
                            <td>
                                <?php if ($e['status'] === 'inside'): ?>
                                    <span class="badge bg-success">Inside</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Exited</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p class="text-muted text-center mb-0">No vehicle entries today</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/gate-footer.php'; ?>
