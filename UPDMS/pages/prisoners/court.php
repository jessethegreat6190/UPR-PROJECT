<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireRole(['supervisor', 'hq_command', 'admin']);

$db = getDB();
$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prisoner_id = (int)($_POST['prisoner_id'] ?? 0);
    $data = [
        'prisoner_id' => $prisoner_id,
        'court_name' => sanitize($_POST['court_name']),
        'case_number' => sanitize($_POST['case_number']),
        'hearing_date' => sanitize($_POST['hearing_date']),
        'hearing_time' => sanitize($_POST['hearing_time']),
        'purpose' => sanitize($_POST['purpose']),
        'status' => 'scheduled'
    ];
    
    $id = $db->insert('court_appearances', $data);
    logAction('create', 'court_appearances', $id, null, $data);
    
    header('Location: ' . SITE_URL . '/pages/prisoners/view.php?id=' . $prisoner_id);
    exit;
}

$prisoner_id = (int)($_GET['prisoner_id'] ?? 0);
$prisoner = $db->fetchOne("SELECT * FROM prisoners WHERE id = ?", [$prisoner_id]);

$pageTitle = 'Add Court Date';
$pageHeader = 'Add Court Date - ' . ($prisoner ? $prisoner['first_name'] . ' ' . $prisoner['last_name'] : 'Unknown');

include __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="prisoner_id" value="<?php echo $prisoner_id; ?>">
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Court Name *</label>
                    <input type="text" name="court_name" class="form-control" required placeholder="e.g., Kampala Chief Magistrate Court">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Case Number</label>
                    <input type="text" name="case_number" class="form-control">
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Hearing Date *</label>
                    <input type="date" name="hearing_date" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Hearing Time</label>
                    <input type="time" name="hearing_time" class="form-control">
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Purpose</label>
                <textarea name="purpose" class="form-control" rows="2" placeholder="e.g., Mentioning, Trial, Judgment"></textarea>
            </div>
            
            <div class="text-end">
                <a href="<?php echo SITE_URL; ?>/pages/prisoners/view.php?id=<?php echo $prisoner_id; ?>" class="btn btn-secondary me-2">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Schedule Court Date
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
