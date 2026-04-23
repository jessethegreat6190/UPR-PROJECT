<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireRole(['gate_officer', 'supervisor', 'hq_command', 'admin']);

$db = getDB();
$user = getCurrentUser();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plate = strtoupper(sanitize($_POST['plate_number']));
    
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
            
            $success = "Vehicle $plate exited successfully. Duration: " . floor($duration / 60) . "h " . ($duration % 60) . "m";
        }
    }
}

$pageTitle = 'Vehicle Exit';
$pageHeader = 'Gate Control - Vehicle Exit';
$pageActions = '<a href="' . SITE_URL . '/pages/gate/entry.php" class="btn btn-primary"><i class="bi bi-box-arrow-right me-2"></i>Vehicle Entry</a>';

include __DIR__ . '/../../includes/header.php';
?>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-box-arrow-left me-2"></i>Record Vehicle Exit</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-4">
                        <label class="form-label">Vehicle Plate Number *</label>
                        <input type="text" name="plate_number" id="plateInput" class="form-control form-control-lg text-uppercase" 
                               placeholder="e.g., UAR 123X" required autofocus onblur="checkVehicle(this.value)">
                    </div>
                    
                    <div id="vehicleDetails" class="d-none mb-4">
                        <div class="alert alert-info">
                            <strong>Driver:</strong> <span id="detailDriver"></span><br>
                            <strong>Entry Time:</strong> <span id="detailEntry"></span><br>
                            <strong>Duration:</strong> <span id="detailDuration"></span>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-success btn-lg w-100 touch-btn">
                        <i class="bi bi-check-circle me-2"></i>Record Exit
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-truck me-2"></i>Vehicles Currently Inside</h5>
            </div>
            <div class="card-body">
                <?php
                $insideVehicles = $db->fetchAll("
                    SELECT vl.*, v.plate_number, v.last_driver_name 
                    FROM vehicle_logs vl 
                    JOIN vehicles v ON vl.vehicle_id = v.id 
                    WHERE vl.status = 'inside' 
                    AND vl.facility_id = ? 
                    ORDER BY vl.entry_time ASC", [$user['facility_id'] ?: 1]);
                ?>
                
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Plate</th>
                                <th>Driver</th>
                                <th>Entry Time</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($insideVehicles as $v): 
                                $hours = (time() - strtotime($v['entry_time'])) / 3600;
                                $isOverstay = $hours > 72;
                            ?>
                            <tr class="<?php echo $isOverstay ? 'table-danger' : ''; ?>">
                                <td><strong><?php echo htmlspecialchars($v['plate_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($v['driver_name'] ?: $v['last_driver_name'] ?: 'N/A'); ?></td>
                                <td><?php echo formatTime($v['entry_time']); ?></td>
                                <td>
                                    <?php 
                                    $h = floor($hours);
                                    $m = floor(($hours - $h) * 60);
                                    echo $h . 'h ' . $m . 'm';
                                    if ($isOverstay) echo ' ⚠️';
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($insideVehicles)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">No vehicles inside</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function checkVehicle(plate) {
    if (!plate) return;
    plate = plate.toUpperCase();
    fetch('<?php echo SITE_URL; ?>/api/vehicles.php?action=check_inside&plate=' + encodeURIComponent(plate))
        .then(r => r.json())
        .then(data => {
            const detailsDiv = document.getElementById('vehicleDetails');
            if (data.found) {
                detailsDiv.classList.remove('d-none');
                document.getElementById('detailDriver').textContent = data.driver_name || 'N/A';
                document.getElementById('detailEntry').textContent = data.entry_time;
                
                const entryTime = new Date(data.entry_time);
                const now = new Date();
                const diffHours = (now - entryTime) / (1000 * 60 * 60);
                document.getElementById('detailDuration').textContent = Math.floor(diffHours) + 'h ' + Math.floor((diffHours % 1) * 60) + 'm';
            } else {
                detailsDiv.classList.add('d-none');
            }
        });
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
