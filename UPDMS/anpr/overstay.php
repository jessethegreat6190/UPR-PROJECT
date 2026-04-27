<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireRole(['gate_officer', 'supervisor', 'hq_command', 'admin']);

$db = getDB();
$user = getCurrentUser();

$overstays = $db->fetchAll("
    SELECT vl.*, v.plate_number, v.last_driver_name, v.is_blacklisted,
           TIMESTAMPDIFF(HOUR, vl.entry_time, NOW()) as hours_inside
    FROM vehicle_logs vl 
    JOIN vehicles v ON vl.vehicle_id = v.id 
    WHERE vl.status = 'inside' 
    AND TIMESTAMPDIFF(HOUR, vl.entry_time, NOW()) > 72
    ORDER BY hours_inside DESC", []);

$pageTitle = 'Overstay Alerts';
$pageHeader = '72-Hour Vehicle Overstay Alerts';

include __DIR__ . '/../../includes/header.php';
?>

<div class="alert alert-danger">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <strong>Attention Required:</strong> Vehicles that have been on site for more than 72 hours require immediate review.
</div>

<div class="card">
    <div class="card-header bg-danger text-white">
        <h5 class="mb-0"><i class="bi bi-clock me-2"></i>Vehicles Overstaying (<?php echo count($overstays); ?>)</h5>
    </div>
    <div class="card-body">
        <?php if ($overstays): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Plate</th>
                        <th>Driver</th>
                        <th>Type</th>
                        <th>Entry Time</th>
                        <th>Hours Inside</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($overstays as $v): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($v['plate_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($v['driver_name'] ?: $v['last_driver_name']); ?></td>
                        <td><span class="badge bg-secondary"><?php echo ucfirst($v['visitor_type']); ?></span></td>
                        <td><?php echo formatDateTime($v['entry_time']); ?></td>
                        <td><span class="badge bg-danger"><?php echo $v['hours_inside']; ?>h+</span></td>
                        <td>
                            <a href="exit.php" class="btn btn-sm btn-success">
                                <i class="bi bi-box-arrow-left"></i> Record Exit
                            </a>
                            <?php if (hasRole(['supervisor', 'hq_command', 'admin'])): ?>
                            <button class="btn btn-sm btn-danger" onclick="blacklistVehicle(<?php echo $v['id']; ?>)">
                                <i class="bi bi-slash-circle"></i> Blacklist
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-check-circle text-success" style="font-size: 60px;"></i>
            <h4 class="mt-3">No Overstaying Vehicles</h4>
            <p class="text-muted">All vehicles are within the 72-hour limit.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
