<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireRole(['supervisor', 'hq_command', 'admin']);

$db = getDB();
$user = getCurrentUser();

$search = sanitize($_GET['search'] ?? '');
$date = sanitize($_GET['date'] ?? date('Y-m-d'));
$type = sanitize($_GET['type'] ?? '');

$where = "WHERE 1=1";
$params = [];

if ($user['role'] === 'supervisor' || $user['role'] === 'gate_officer') {
    $where .= " AND vl.facility_id = ?";
    $params[] = $user['facility_id'];
}

if ($search) {
    $where .= " AND (v.first_name LIKE ? OR v.last_name LIKE ? OR v.national_id LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

if ($date) {
    $where .= " AND DATE(vl.entry_time) = ?";
    $params[] = $date;
}

if ($type) {
    $where .= " AND vl.visitor_type = ?";
    $params[] = $type;
}

$logs = $db->fetchAll("
    SELECT vl.*, v.first_name, v.last_name, v.national_id, v.phone, v.address, v.vehicle_plate,
           p.prisoner_number, p.first_name as prisoner_first, p.last_name as prisoner_last
    FROM visitor_logs vl
    JOIN visitors v ON vl.visitor_id = v.id
    LEFT JOIN prisoners p ON vl.prisoner_id = p.id
    $where
    ORDER BY vl.entry_time DESC
    LIMIT 100", $params);

$pageTitle = 'Visitor Logs';
$pageHeader = 'Visitor Records';

include __DIR__ . '/../../includes/header.php';
?>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search by name, ID, plate..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <input type="date" name="date" class="form-control" value="<?php echo $date; ?>">
            </div>
            <div class="col-md-3">
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    <option value="inmate" <?php echo $type === 'inmate' ? 'selected' : ''; ?>>Inmate Visitor</option>
                    <option value="hospital" <?php echo $type === 'hospital' ? 'selected' : ''; ?>>Hospital Visitor</option>
                    <option value="staff" <?php echo $type === 'staff' ? 'selected' : ''; ?>>Staff Visitor</option>
                    <option value="official" <?php echo $type === 'official' ? 'selected' : ''; ?>>Official Visitor</option>
                    <option value="delivery" <?php echo $type === 'delivery' ? 'selected' : ''; ?>>Delivery</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search me-2"></i>Search</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Visitor Name</th>
                        <th>Address / District</th>
                        <th>Phone</th>
                        <th>ID Number</th>
                        <th>Type</th>
                        <th>Visiting Prisoner</th>
                        <th>Vehicle</th>
                        <th>Entry Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><strong><?php echo $log['id']; ?></strong></td>
                        <td><?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?></td>
                        <td><small><?php echo $log['address'] ? htmlspecialchars($log['address']) : '-'; ?></small></td>
                        <td><?php echo $log['phone'] ?: '-'; ?></td>
                        <td><?php echo $log['national_id'] ?: '-'; ?></td>
                        <td><span class="badge bg-<?php echo $log['visitor_type'] === 'inmate' ? 'primary' : ($log['visitor_type'] === 'delivery' ? 'warning' : 'secondary'); ?>"><?php echo ucfirst($log['visitor_type']); ?></span></td>
                        <td>
                            <?php if ($log['prisoner_first']): ?>
                                <strong><?php echo htmlspecialchars($log['prisoner_first'] . ' ' . $log['prisoner_last']); ?></strong>
                            <?php else: ?>
                                -
                    <?php endforeach; ?>
                    <?php if (empty($logs)): ?>
                    <tr><td colspan="10" class="text-center text-muted">No records found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
