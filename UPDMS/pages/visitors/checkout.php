<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireRole(['gate_officer', 'supervisor', 'hq_command', 'admin']);

$db = getDB();
$user = getCurrentUser();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $log_id = (int)$_POST['log_id'];
    
    $log = $db->fetchOne("SELECT * FROM visitor_logs WHERE id = ? AND status = 'inside'", [$log_id]);
    
    if (!$log) {
        $error = "Visitor record not found or already checked out";
    } else {
        $exit_time = date('Y-m-d H:i:s');
        $duration = (strtotime($exit_time) - strtotime($log['entry_time'])) / 60;
        
        $db->update('visitor_logs', [
            'exit_time' => $exit_time,
            'duration_minutes' => (int)$duration,
            'status' => 'exited',
            'gate_officer_exit_id' => $user['id']
        ], 'id = :id', ['id' => $log_id]);
        
        logAction('visitor_checkout', 'visitor_logs', $log_id, null, ['duration' => $duration]);
        
        $success = "Visitor checked out successfully. Duration: " . floor($duration / 60) . "h " . round($duration % 60) . "m";
    }
}

$inside = $db->fetchAll("
    SELECT vl.*, v.first_name, v.last_name 
    FROM visitor_logs vl 
    JOIN visitors v ON vl.visitor_id = v.id 
    WHERE vl.status = 'inside' 
    AND vl.facility_id = ? 
    ORDER BY vl.entry_time DESC", [$user['facility_id'] ?: 1]);

$pageTitle = 'Visitor Check Out';
$pageHeader = 'Visitor Check Out';

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
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-box-arrow-left me-2"></i>Check Out Visitor</h5>
            </div>
            <div class="card-body">
                <?php if ($inside): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Entry Time</th>
                                <th>Duration</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inside as $v): ?>
                            <?php $hours = (time() - strtotime($v['entry_time'])) / 3600; ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($v['first_name'] . ' ' . $v['last_name']); ?></strong></td>
                                <td><span class="badge bg-secondary"><?php echo ucfirst($v['visitor_type']); ?></span></td>
                                <td><?php echo formatTime($v['entry_time']); ?></td>
                                <td data-entry-time="<?php echo $v['entry_time']; ?>"><?php echo floor($hours) . 'h ' . floor(($hours - floor($hours)) * 60) . 'm'; ?></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="log_id" value="<?php echo $v['id']; ?>">
                                        <button type="submit" class="btn btn-success btn-sm touch-btn">
                                            <i class="bi bi-check-lg me-1"></i>Check Out
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-people text-muted" style="font-size: 60px;"></i>
                    <h4 class="mt-3">No Visitors Inside</h4>
                    <p class="text-muted">All visitors have checked out.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
