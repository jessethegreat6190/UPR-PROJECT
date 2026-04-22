<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireRole(['admin']);

$db = getDB();
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'facility_code' => strtoupper(sanitize($_POST['facility_code'])),
        'name' => sanitize($_POST['name']),
        'region' => sanitize($_POST['region']),
        'type' => sanitize($_POST['type']),
        'capacity' => (int)$_POST['capacity'],
        'address' => sanitize($_POST['address']),
        'contact_phone' => sanitize($_POST['contact_phone']),
        'contact_email' => sanitize($_POST['contact_email'])
    ];
    
    $id = $db->insert('facilities', $data);
    logAction('facility_created', 'facilities', $id, null, $data);
    $success = 'Facility added successfully';
}

$facilities = $db->fetchAll("
    SELECT f.*, 
           (SELECT COUNT(*) FROM prisoners p WHERE p.facility_id = f.id AND p.status IN ('remand', 'convicted')) as prisoner_count,
           (SELECT COUNT(*) FROM users u WHERE u.facility_id = f.id) as staff_count
    FROM facilities f
    ORDER BY f.name", []);

$pageTitle = 'Facilities';
$pageHeader = 'Facility Management';

include __DIR__ . '/../../includes/header.php';
?>

<?php if ($success): ?>
<div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-building me-2"></i>Add New Facility</h5>
    </div>
    <div class="card-body">
        <form method="POST" class="row g-3">
            <div class="col-md-2">
                <label class="form-label">Code *</label>
                <input type="text" name="facility_code" class="form-control text-uppercase" maxlength="10" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Name *</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Region</label>
                <input type="text" name="region" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label">Type</label>
                <select name="type" class="form-select">
                    <option value="medium">Medium</option>
                    <option value="maximum">Maximum</option>
                    <option value="minimum">Minimum</option>
                    <option value="rehabilitation">Rehabilitation</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Capacity</label>
                <input type="number" name="capacity" class="form-control" value="500">
            </div>
            <div class="col-md-4">
                <label class="form-label">Address</label>
                <input type="text" name="address" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">Phone</label>
                <input type="text" name="contact_phone" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">Email</label>
                <input type="email" name="contact_email" class="form-control">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-primary">Add</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Facility</th>
                        <th>Region</th>
                        <th>Type</th>
                        <th>Capacity</th>
                        <th>Prisoners</th>
                        <th>Staff</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($facilities as $f): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($f['facility_code']); ?></strong></td>
                        <td><?php echo htmlspecialchars($f['name']); ?></td>
                        <td><?php echo htmlspecialchars($f['region'] ?: '-'); ?></td>
                        <td><span class="badge bg-secondary"><?php echo ucfirst($f['type']); ?></span></td>
                        <td><?php echo number_format($f['capacity']); ?></td>
                        <td><?php echo number_format($f['prisoner_count']); ?></td>
                        <td><?php echo number_format($f['staff_count']); ?></td>
                        <td>
                            <?php if ($f['is_active']): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
