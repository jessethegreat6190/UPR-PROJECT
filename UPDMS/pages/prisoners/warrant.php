<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireRole(['hq_command', 'admin']);

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prisoner_id = (int)($_POST['prisoner_id'] ?? 0);
    $data = [
        'prisoner_id' => $prisoner_id,
        'warrant_number' => sanitize($_POST['warrant_number']),
        'warrant_type' => sanitize($_POST['warrant_type']),
        'issuing_court' => sanitize($_POST['issuing_court']),
        'issuing_magistrate' => sanitize($_POST['issuing_magistrate']),
        'case_number' => sanitize($_POST['case_number']),
        'offense_description' => sanitize($_POST['offense_description']),
        'sentence_years' => (int)($_POST['sentence_years'] ?? 0),
        'sentence_months' => (int)($_POST['sentence_months'] ?? 0),
        'sentence_days' => (int)($_POST['sentence_days'] ?? 0),
        'issue_date' => sanitize($_POST['issue_date']),
        'status' => 'active'
    ];
    
    $id = $db->insert('warrants', $data);
    logAction('create', 'warrants', $id, null, $data);
    
    header('Location: ' . SITE_URL . '/pages/prisoners/view.php?id=' . $prisoner_id);
    exit;
}

$prisoner_id = (int)($_GET['prisoner_id'] ?? 0);
$prisoner = $db->fetchOne("SELECT * FROM prisoners WHERE id = ?", [$prisoner_id]);

$pageTitle = 'Add Warrant';
$pageHeader = 'Add Warrant - ' . ($prisoner ? $prisoner['first_name'] . ' ' . $prisoner['last_name'] : 'Unknown');
$pageActions = '<a href="' . SITE_URL . '/pages/prisoners/list.php" class="btn btn-secondary"><i class="bi bi-arrow-left me-2"></i>Back</a>';

include __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="prisoner_id" value="<?php echo $prisoner_id; ?>">
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Warrant Number *</label>
                    <input type="text" name="warrant_number" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Warrant Type *</label>
                    <select name="warrant_type" class="form-select" required>
                        <option value="">Select...</option>
                        <option value="remand">Remand</option>
                        <option value="conviction">Conviction</option>
                        <option value="transfer">Transfer</option>
                        <option value="parole">Parole</option>
                        <option value="release">Release</option>
                    </select>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Issuing Court</label>
                    <input type="text" name="issuing_court" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Issuing Magistrate</label>
                    <input type="text" name="issuing_magistrate" class="form-control">
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Case Number</label>
                    <input type="text" name="case_number" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Issue Date *</label>
                    <input type="date" name="issue_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Offense Description</label>
                <textarea name="offense_description" class="form-control" rows="3"></textarea>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Sentence Years</label>
                    <input type="number" name="sentence_years" class="form-control" min="0" value="0">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sentence Months</label>
                    <input type="number" name="sentence_months" class="form-control" min="0" max="11" value="0">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sentence Days</label>
                    <input type="number" name="sentence_days" class="form-control" min="0" max="30" value="0">
                </div>
            </div>
            
            <div class="text-end">
                <a href="<?php echo SITE_URL; ?>/pages/prisoners/view.php?id=<?php echo $prisoner_id; ?>" class="btn btn-secondary me-2">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Save Warrant
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
