<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireRole(['admin']);

$db = getDB();
$user = getCurrentUser();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action']);
    
    if ($action === 'create') {
        $data = [
            'username' => sanitize($_POST['username']),
            'password_hash' => password_hash($_POST['password'], PASSWORD_DEFAULT),
            'full_name' => sanitize($_POST['full_name']),
            'badge_number' => sanitize($_POST['badge_number']),
            'role' => sanitize($_POST['role']),
            'facility_id' => !empty($_POST['facility_id']) ? (int)$_POST['facility_id'] : null,
            'phone' => sanitize($_POST['phone']),
            'email' => sanitize($_POST['email'])
        ];
        
        try {
            $id = $db->insert('users', $data);
            logAction('user_created', 'users', $id, null, $data);
            $success = 'User created successfully';
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    } elseif ($action === 'toggle') {
        $user_id = (int)$_POST['user_id'];
        $current = $db->fetchOne("SELECT is_active FROM users WHERE id = ?", [$user_id]);
        $new_status = $current['is_active'] ? 0 : 1;
        $db->update('users', ['is_active' => $new_status], 'id = :id', ['id' => $user_id]);
        logAction('user_status_changed', 'users', $user_id, null, ['is_active' => $new_status]);
        $success = 'User status updated';
    } elseif ($action === 'reset_password') {
        $user_id = (int)$_POST['user_id'];
        $new_password = password_hash('admin123', PASSWORD_DEFAULT);
        $db->update('users', ['password_hash' => $new_password], 'id = :id', ['id' => $user_id]);
        logAction('password_reset', 'users', $user_id);
        $success = 'Password reset to admin123';
    }
}

$users = $db->fetchAll("
    SELECT u.*, f.name as facility_name
    FROM users u
    LEFT JOIN facilities f ON u.facility_id = f.id
    ORDER BY u.is_active DESC, u.full_name", []);

$facilities = $db->fetchAll("SELECT * FROM facilities WHERE is_active = 1 ORDER BY name");

$pageTitle = 'User Management';
$pageHeader = 'User Management';

include __DIR__ . '/../../includes/header.php';
?>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-person-plus me-2"></i>Add New User</h5>
    </div>
    <div class="card-body">
        <form method="POST" class="row g-3">
            <input type="hidden" name="action" value="create">
            
            <div class="col-md-2">
                <label class="form-label">Username *</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Password *</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Full Name *</label>
                <input type="text" name="full_name" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Badge #</label>
                <input type="text" name="badge_number" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">Role *</label>
                <select name="role" class="form-select" required>
                    <option value="">Select...</option>
                    <option value="gate_officer">Gate Officer</option>
                    <option value="supervisor">Supervisor/OC</option>
                    <option value="hq_command">HQ Command</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Facility</label>
                <select name="facility_id" class="form-select">
                    <option value="">HQ Level</option>
                    <?php foreach ($facilities as $f): ?>
                    <option value="<?php echo $f['id']; ?>"><?php echo htmlspecialchars($f['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Add User</button>
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
                        <th>User</th>
                        <th>Username</th>
                        <th>Badge</th>
                        <th>Role</th>
                        <th>Facility</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr class="<?php echo $u['is_active'] ? '' : 'table-secondary'; ?>">
                        <td><strong><?php echo htmlspecialchars($u['full_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($u['username']); ?></td>
                        <td><?php echo htmlspecialchars($u['badge_number'] ?: '-'); ?></td>
                        <td><span class="badge bg-<?php 
                            echo $u['role'] === 'admin' ? 'danger' : ($u['role'] === 'hq_command' ? 'primary' : ($u['role'] === 'supervisor' ? 'success' : 'secondary'));
                        ?>"><?php echo ucwords(str_replace('_', ' ', $u['role'])); ?></span></td>
                        <td><?php echo htmlspecialchars($u['facility_name'] ?: 'HQ'); ?></td>
                        <td>
                            <?php if ($u['is_active']): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $u['last_login'] ? formatDateTime($u['last_login']) : 'Never'; ?></td>
                        <td>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-<?php echo $u['is_active'] ? 'warning' : 'success'; ?>">
                                    <?php echo $u['is_active'] ? 'Disable' : 'Enable'; ?>
                                </button>
                            </form>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="reset_password">
                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-info">Reset Pwd</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
