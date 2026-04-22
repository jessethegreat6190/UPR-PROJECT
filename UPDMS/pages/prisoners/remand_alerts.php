<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireRole(['hq_command', 'admin']);

$db = getDB();

$alerts = $db->fetchAll("
    SELECT ra.*, p.prisoner_number, p.first_name, p.last_name, p.gender,
           f.name as facility_name
    FROM remand_alerts ra
    JOIN prisoners p ON ra.prisoner_id = p.id
    JOIN facilities f ON p.facility_id = f.id
    WHERE ra.status = 'pending'
    ORDER BY ra.days_on_remand DESC", []);

if (isset($_POST['acknowledge'])) {
    $alert_id = (int)$_POST['alert_id'];
    $action = sanitize($_POST['action_taken']);
    $user = getCurrentUser();
    
    $db->update('remand_alerts', [
        'status' => 'acknowledged',
        'action_taken' => $action,
        'acknowledged_by' => $user['id'],
        'acknowledged_at' => date('Y-m-d H:i:s')
    ], 'id = :id', ['id' => $alert_id]);
    
    header('Location: remand_alerts.php');
    exit;
}

$pageTitle = 'Remand Alerts';
$pageHeader = 'Remand Period Alerts';

include __DIR__ . '/../../includes/header.php';
?>

<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <strong>Alert:</strong> Prisoners approaching or exceeding the 365-day remand limit require immediate attention.
</div>

<div class="card">
    <div class="card-header bg-warning">
        <h5 class="mb-0"><i class="bi bi-bell me-2"></i>Pending Remand Alerts (<?php echo count($alerts); ?>)</h5>
    </div>
    <div class="card-body">
        <?php if ($alerts): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Prisoner</th>
                        <th>Facility</th>
                        <th>Gender</th>
                        <th>Days on Remand</th>
                        <th>Alert Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alerts as $a): ?>
                    <?php $isOverdue = $a['days_on_remand'] >= 365; ?>
                    <tr class="<?php echo $isOverdue ? 'table-danger' : 'table-warning'; ?>">
                        <td>
                            <strong><?php echo htmlspecialchars($a['prisoner_number']); ?></strong><br>
                            <small><?php echo htmlspecialchars($a['first_name'] . ' ' . $a['last_name']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($a['facility_name']); ?></td>
                        <td><?php echo ucfirst($a['gender']); ?></td>
                        <td><strong><?php echo $a['days_on_remand']; ?> days</strong></td>
                        <td><?php echo formatDate($a['alert_date']); ?></td>
                        <td>
                            <?php if ($isOverdue): ?>
                                <span class="badge bg-danger">OVER LIMIT</span>
                            <?php else: ?>
                                <span class="badge bg-warning">Approaching</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="alert_id" value="<?php echo $a['id']; ?>">
                                <input type="text" name="action_taken" class="form-control form-control-sm mb-1" placeholder="Action taken">
                                <button type="submit" name="acknowledge" class="btn btn-sm btn-success">Acknowledge</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-check-circle text-success" style="font-size: 60px;"></i>
            <h4 class="mt-3">No Remand Alerts</h4>
            <p class="text-muted">All prisoners are within the remand limit.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
