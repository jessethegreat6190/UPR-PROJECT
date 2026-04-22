<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireRole(['admin']);

$db = getDB();

$logs = $db->fetchAll("
    SELECT al.*, u.full_name
    FROM action_logs al
    JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
    LIMIT 100", []);

$pageTitle = 'Audit Logs';
$pageHeader = 'System Audit Logs';
$pageActions = '<span class="badge bg-warning"><i class="bi bi-lock me-2"></i>Append-Only Records</span>';

include __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Table</th>
                        <th>Record ID</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo formatDateTime($log['created_at']); ?></td>
                        <td><?php echo htmlspecialchars($log['full_name']); ?></td>
                        <td><span class="badge bg-info"><?php echo htmlspecialchars($log['action_type']); ?></span></td>
                        <td><?php echo htmlspecialchars($log['table_name']); ?></td>
                        <td><?php echo $log['record_id'] ?: '-'; ?></td>
                        <td><small><?php echo htmlspecialchars($log['ip_address']); ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($logs)): ?>
                    <tr><td colspan="6" class="text-center text-muted">No logs yet</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
