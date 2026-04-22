<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireRole(['gate_officer', 'supervisor', 'hq_command', 'admin']);

$db = getDB();
$user = getCurrentUser();
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'facility_id' => $user['facility_id'] ?: 1,
        'incident_type' => sanitize($_POST['incident_type']),
        'incident_date' => sanitize($_POST['incident_date']),
        'incident_time' => sanitize($_POST['incident_time']),
        'location' => sanitize($_POST['location']),
        'description' => sanitize($_POST['description']),
        'persons_involved' => sanitize($_POST['persons_involved']),
        'action_taken' => sanitize($_POST['action_taken']),
        'reported_by' => $user['id'],
        'status' => 'reported'
    ];
    
    $id = $db->insert('incidents', $data);
    logAction('incident_reported', 'incidents', $id, null, $data);
    
    $success = 'Incident reported successfully';
}

$pageTitle = 'Report Incident';
$pageHeader = 'Report Security Incident';

include __DIR__ . '/../../includes/header.php';
?>

<?php if ($success): ?>
<div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Incident Type *</label>
                    <select name="incident_type" class="form-select" required>
                        <option value="">Select type...</option>
                        <option value="security">Security Breach</option>
                        <option value="assault">Assault</option>
                        <option value="contraband">Contraband</option>
                        <option value="escape_attempt">Escape Attempt</option>
                        <option value="riot">Riot/Disturbance</option>
                        <option value="fire">Fire</option>
                        <option value="health">Health Emergency</option>
                        <option value="death">Death</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date *</label>
                    <input type="date" name="incident_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Time *</label>
                    <input type="time" name="incident_time" class="form-control" value="<?php echo date('H:i'); ?>" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Location *</label>
                <input type="text" name="location" class="form-control" placeholder="e.g., Cell Block B, Main Gate" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Description *</label>
                <textarea name="description" class="form-control" rows="4" placeholder="Describe what happened..." required></textarea>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Persons Involved</label>
                <textarea name="persons_involved" class="form-control" rows="2" placeholder="Names and roles of people involved"></textarea>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Action Taken</label>
                <textarea name="action_taken" class="form-control" rows="2" placeholder="What action was taken?"></textarea>
            </div>
            
            <button type="submit" class="btn btn-danger btn-lg">
                <i class="bi bi-exclamation-triangle me-2"></i>Report Incident
            </button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
