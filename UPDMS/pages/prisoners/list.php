<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireRole(['supervisor', 'hq_command', 'admin']);

$db = getDB();
$user = getCurrentUser();

$pageTitle = 'Prisoner List';
$pageHeader = 'Prisoner Management';

$search = sanitize($_GET['search'] ?? '');
$status = sanitize($_GET['status'] ?? '');

$where = "WHERE p.status IN ('remand', 'convicted')";
$params = [];

if ($user['role'] === 'supervisor' || $user['role'] === 'gate_officer') {
    $where .= " AND p.facility_id = ?";
    $params[] = $user['facility_id'];
}

if ($search) {
    $where .= " AND (p.prisoner_number LIKE ? OR p.first_name LIKE ? OR p.last_name LIKE ? OR p.national_id LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
}

if ($status) {
    $where .= " AND p.status = ?";
    $params[] = $status;
}

$prisoners = $db->fetchAll("
    SELECT p.*, f.name as facility_name 
    FROM prisoners p 
    JOIN facilities f ON p.facility_id = f.id 
    $where 
    ORDER BY p.admission_date DESC", $params);

include __DIR__ . '/../../includes/header.php';
?>

<div class="row mb-3">
    <div class="col-md-8">
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="search" class="form-control" placeholder="Search by name, prisoner number, or ID..." value="<?php echo htmlspecialchars($search); ?>">
            <select name="status" class="form-select" style="width: auto;">
                <option value="">All Status</option>
                <option value="remand" <?php echo $status === 'remand' ? 'selected' : ''; ?>>Remand</option>
                <option value="convicted" <?php echo $status === 'convicted' ? 'selected' : ''; ?>>Convicted</option>
            </select>
            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
        </form>
    </div>
    <?php if (hasRole(['hq_command', 'admin'])): ?>
    <div class="col-md-4 text-end">
        <a href="<?php echo SITE_URL; ?>/pages/prisoners/add.php" class="btn btn-success">
            <i class="bi bi-plus-circle me-2"></i>Add Prisoner
        </a>
    </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Prisoner #</th>
                        <th>Name</th>
                        <th>Gender</th>
                        <th>ID Number</th>
                        <th>Status</th>
                        <th>Facility</th>
                        <th>Admission Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($prisoners as $p): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($p['prisoner_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></td>
                        <td><?php echo ucfirst($p['gender']); ?></td>
                        <td><?php echo htmlspecialchars($p['national_id'] ?: 'N/A'); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $p['status'] === 'convicted' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($p['status']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($p['facility_name']); ?></td>
                        <td><?php echo formatDate($p['admission_date']); ?></td>
                        <td>
                            <a href="<?php echo SITE_URL; ?>/pages/prisoners/view.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                            <?php if (hasRole(['hq_command', 'admin'])): ?>
                            <a href="<?php echo SITE_URL; ?>/pages/prisoners/edit.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-secondary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($prisoners)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">No prisoners found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
