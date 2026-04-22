<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireRole(['hq_command', 'admin']);

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prisoner_id = (int)($_POST['prisoner_id'] ?? 0);
    $warrant_id = (int)($_POST['warrant_id'] ?? 0);
    
    $sentence_start = $_POST['sentence_start_date'];
    $years = (int)($_POST['sentence_years'] ?? 0);
    $months = (int)($_POST['sentence_months'] ?? 0);
    $days = (int)($_POST['sentence_days'] ?? 0);
    
    $total_days = $years * 365 + $months * 30 + $days;
    $release_date = date('Y-m-d', strtotime($sentence_start . ' +' . $total_days . ' days'));
    $parole_date = date('Y-m-d', strtotime($sentence_start . ' +' . floor($total_days * 0.5) . ' days'));
    
    $data = [
        'prisoner_id' => $prisoner_id,
        'warrant_id' => $warrant_id ?: null,
        'sentence_start_date' => $sentence_start,
        'sentence_end_date' => $release_date,
        'total_sentence_days' => $total_days,
        'release_date' => $release_date,
        'parole_eligible_date' => $parole_date,
        'status' => 'serving'
    ];
    
    $id = $db->insert('sentences', $data);
    
    // Update prisoner status to convicted
    $db->update('prisoners', ['status' => 'convicted'], 'id = :id', ['id' => $prisoner_id]);
    
    logAction('create', 'sentences', $id, null, $data);
    
    header('Location: ' . SITE_URL . '/pages/prisoners/view.php?id=' . $prisoner_id);
    exit;
}

$prisoner_id = (int)($_GET['prisoner_id'] ?? 0);
$prisoner = $db->fetchOne("SELECT * FROM prisoners WHERE id = ?", [$prisoner_id]);
$warrants = $db->fetchAll("SELECT * FROM warrants WHERE prisoner_id = ? AND status = 'active'", [$prisoner_id]);

$pageTitle = 'Calculate Sentence';
$pageHeader = 'Sentence Calculator - ' . ($prisoner ? $prisoner['first_name'] . ' ' . $prisoner['last_name'] : 'Unknown');

include __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="prisoner_id" value="<?php echo $prisoner_id; ?>">
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Related Warrant</label>
                    <select name="warrant_id" class="form-select">
                        <option value="">Select warrant (optional)</option>
                        <?php foreach ($warrants as $w): ?>
                        <option value="<?php echo $w['id']; ?>">
                            <?php echo htmlspecialchars($w['warrant_number'] . ' - ' . $w['offense_description']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Sentence Start Date *</label>
                    <input type="date" name="sentence_start_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>
            
            <h5>Sentence Length</h5>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Years</label>
                    <input type="number" name="sentence_years" class="form-control" min="0" value="0" id="years">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Months</label>
                    <input type="number" name="sentence_months" class="form-control" min="0" max="11" value="0" id="months">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Days</label>
                    <input type="number" name="sentence_days" class="form-control" min="0" max="30" value="0" id="days">
                </div>
            </div>
            
            <div class="alert alert-info">
                <h5>Calculated Dates</h5>
                <div id="calcResults">
                    <p><strong>Total Days:</strong> <span id="totalDays">0</span></p>
                    <p><strong>Release Date:</strong> <span id="releaseDate">Select start date</span></p>
                    <p><strong>Parole Eligible:</strong> <span id="paroleDate">-</span></p>
                </div>
            </div>
            
            <div class="text-end">
                <a href="<?php echo SITE_URL; ?>/pages/prisoners/view.php?id=<?php echo $prisoner_id; ?>" class="btn btn-secondary me-2">Cancel</a>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-circle me-2"></i>Save Sentence
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const years = document.getElementById('years');
    const months = document.getElementById('months');
    const days = document.getElementById('days');
    const startDate = document.querySelector('input[name="sentence_start_date"]');
    
    function calculate() {
        const totalDays = parseInt(years.value) * 365 + parseInt(months.value) * 30 + parseInt(days.value);
        document.getElementById('totalDays').textContent = totalDays;
        
        if (startDate.value && totalDays > 0) {
            const start = new Date(startDate.value);
            const release = new Date(start);
            release.setDate(release.getDate() + totalDays);
            document.getElementById('releaseDate').textContent = release.toLocaleDateString('en-GB');
            
            const parole = new Date(start);
            parole.setDate(parole.getDate() + Math.floor(totalDays * 0.5));
            document.getElementById('paroleDate').textContent = parole.toLocaleDateString('en-GB');
        }
    }
    
    [years, months, days, startDate].forEach(el => el.addEventListener('input', calculate));
    [years, months, days, startDate].forEach(el => el.addEventListener('change', calculate));
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
