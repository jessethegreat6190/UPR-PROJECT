<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireRole(['hq_command', 'admin']);

$db = getDB();
$user = getCurrentUser();

// Get stats for all facilities
$totalPrisoners = $db->fetchOne("SELECT COUNT(*) as c FROM prisoners WHERE status IN ('remand', 'convicted')")['c'];
$totalVisitors = $db->fetchOne("SELECT COUNT(*) as c FROM visitor_logs WHERE DATE(entry_time) = CURDATE()")['c'];
$vehiclesInside = $db->fetchOne("SELECT COUNT(*) as c FROM vehicle_logs WHERE status = 'inside'")['c'];
$overstays = $db->fetchOne("SELECT COUNT(*) as c FROM vehicle_logs WHERE status = 'inside' AND TIMESTAMPDIFF(HOUR, entry_time, NOW()) > 72")['c'];

// Get facility stats
$facilities = $db->fetchAll("
    SELECT f.*,
           (SELECT COUNT(*) FROM prisoners p WHERE p.facility_id = f.id AND p.status IN ('remand', 'convicted')) as prisoner_count,
           (SELECT COUNT(*) FROM visitor_logs vl WHERE vl.facility_id = f.id AND DATE(vl.entry_time) = CURDATE()) as today_visitors,
           (SELECT COUNT(*) FROM vehicle_logs vl WHERE vl.facility_id = f.id AND vl.status = 'inside') as vehicles_inside
    FROM facilities f
    WHERE f.is_active = 1
    ORDER BY f.name", []);

// Get upcoming releases
$releases = $db->fetchAll("
    SELECT p.prisoner_number, p.first_name, p.last_name, f.name as facility,
           s.release_date, DATEDIFF(s.release_date, CURDATE()) as days_left
    FROM prisoners p
    JOIN sentences s ON p.id = s.prisoner_id
    JOIN facilities f ON p.facility_id = f.id
    WHERE p.status = 'convicted'
    AND s.release_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ORDER BY s.release_date ASC", []);

// Get remand alerts
$remandAlerts = $db->fetchAll("
    SELECT p.prisoner_number, p.first_name, p.last_name, f.name as facility,
           p.admission_date, DATEDIFF(CURDATE(), p.admission_date) as days_on_remand
    FROM prisoners p
    JOIN facilities f ON p.facility_id = f.id
    WHERE p.status = 'remand'
    AND DATEDIFF(CURDATE(), p.admission_date) >= 330
    ORDER BY days_on_remand DESC", []);

// Recent activity
$recentActivity = $db->fetchAll("
    SELECT al.*, u.full_name
    FROM action_logs al
    JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
    LIMIT 20", []);

$pageTitle = 'HQ Dashboard';
$pageHeader = 'Command HQ Dashboard';

include __DIR__ . '/../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stat-card bg-primary text-white">
            <div class="card-body">
                <h6 class="card-title">Total Prisoners</h6>
                <h2><?php echo number_format($totalPrisoners); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-success text-white">
            <div class="card-body">
                <h6 class="card-title">Today's Visitors</h6>
                <h2><?php echo number_format($totalVisitors); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-info text-white">
            <div class="card-body">
                <h6 class="card-title">Vehicles Inside</h6>
                <h2><?php echo number_format($vehiclesInside); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card <?php echo $overstays > 0 ? 'bg-danger' : 'bg-warning'; ?> text-white">
            <div class="card-body">
                <h6 class="card-title">Overstay Alerts</h6>
                <h2><?php echo number_format($overstays); ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-building me-2"></i>Facility Overview</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Facility</th>
                                <th>Prisoners</th>
                                <th>Today's Visitors</th>
                                <th>Vehicles</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($facilities as $f): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($f['name']); ?></strong></td>
                                <td><?php echo number_format($f['prisoner_count']); ?></td>
                                <td><?php echo number_format($f['today_visitors']); ?></td>
                                <td><?php echo number_format($f['vehicles_inside']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-warning">
                <h5 class="mb-0"><i class="bi bi-bell me-2"></i>Upcoming Releases (7 Days)</h5>
            </div>
            <div class="card-body">
                <?php if ($releases): ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Prisoner</th>
                                <th>Facility</th>
                                <th>Release Date</th>
                                <th>Days</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($releases as $r): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($r['first_name'] . ' ' . $r['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($r['facility']); ?></td>
                                <td><?php echo formatDate($r['release_date']); ?></td>
                                <td><span class="badge bg-warning"><?php echo $r['days_left']; ?>d</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted mb-0">No releases in the next 7 days</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Remand Alerts (Approaching Limit)</h5>
            </div>
            <div class="card-body">
                <?php if ($remandAlerts): ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Prisoner</th>
                                <th>Facility</th>
                                <th>Days on Remand</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($remandAlerts as $a): ?>
                            <tr class="<?php echo $a['days_on_remand'] >= 365 ? 'table-danger' : 'table-warning'; ?>">
                                <td><?php echo htmlspecialchars($a['prisoner_number']); ?></td>
                                <td><?php echo htmlspecialchars($a['facility']); ?></td>
                                <td><strong><?php echo $a['days_on_remand']; ?> days</strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-success mb-0"><i class="bi bi-check-circle me-2"></i>No remand alerts</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Activity</h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <?php foreach ($recentActivity as $a): ?>
                    <div class="list-group-item px-0">
                        <small class="text-muted"><?php echo formatDateTime($a['created_at']); ?></small>
                        <br>
                        <strong><?php echo htmlspecialchars($a['full_name']); ?></strong>
                        <?php echo htmlspecialchars($a['action_type']); ?> on <?php echo htmlspecialchars($a['table_name']); ?>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($recentActivity)): ?>
                    <p class="text-muted mb-0">No recent activity</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
