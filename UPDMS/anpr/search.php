<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireRole(['gate_officer', 'supervisor', 'hq_command', 'admin']);

$db = getDB();
$user = getCurrentUser();

$search = sanitize($_GET['search'] ?? '');
$type = sanitize($_GET['type'] ?? '');

$where = "WHERE 1=1";
$params = [];

if ($search) {
    $where .= " AND (v.plate_number LIKE ? OR v.last_driver_name LIKE ? OR vl.driver_name LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

if ($type === 'visitor') {
    $where .= " AND sr.search_type != ''";
} elseif ($type === 'vehicle') {
    $where .= " AND vl.id IS NOT NULL";
}

$logs = $db->fetchAll("
    SELECT DISTINCT 
        'vehicle' as record_type,
        vl.id,
        v.plate_number,
        vl.driver_name,
        vl.visitor_type,
        vl.entry_time,
        vl.exit_time,
        vl.status,
        'Vehicle Log' as source
    FROM vehicle_logs vl
    JOIN vehicles v ON vl.vehicle_id = v.id
    $where
    ORDER BY vl.entry_time DESC
    LIMIT 100", $params);

$pageTitle = 'Search Records';
$pageHeader = 'Search Gate Records (Locked)';
$pageActions = '<span class="badge bg-warning text-dark"><i class="bi bi-lock me-2"></i>Records Cannot Be Deleted</span>';

include __DIR__ . '/../../includes/header.php';
?>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-8">
                <input type="text" name="search" class="form-control" placeholder="Search by plate number or driver name..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search me-2"></i>Search</button>
                <a href="<?php echo SITE_URL; ?>/pages/gate/search.php" class="btn btn-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card record-locked">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-file-text me-2"></i>Search Results (<?php echo count($logs); ?> records)</h5>
    </div>
    <div class="card-body">
        <?php if ($logs): ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Plate</th>
                        <th>Driver</th>
                        <th>Category</th>
                        <th>Entry Time</th>
                        <th>Exit Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><span class="badge bg-info">Vehicle</span></td>
                        <td><strong><?php echo htmlspecialchars($log['plate_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($log['driver_name'] ?: 'N/A'); ?></td>
                        <td><?php echo ucfirst($log['visitor_type']); ?></td>
                        <td><?php echo formatDateTime($log['entry_time']); ?></td>
                        <td><?php echo $log['exit_time'] ? formatDateTime($log['exit_time']) : '-'; ?></td>
                        <td>
                            <?php if ($log['status'] === 'inside'): ?>
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
        <div class="text-center text-muted py-5">
            <i class="bi bi-search" style="font-size: 50px;"></i>
            <p class="mt-3">No records found. Try a different search.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="alert alert-info mt-3">
    <i class="bi bi-info-circle me-2"></i>
    <strong>Note:</strong> All records shown here are locked and cannot be deleted or altered. 
    If a correction is needed, it must be requested through a supervisor.
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
