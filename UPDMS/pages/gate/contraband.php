<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireRole(['supervisor', 'hq_command', 'admin']);

$db = getDB();
$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'item_description' => sanitize($_POST['item_description']),
        'quantity' => sanitize($_POST['quantity']),
        'unit' => sanitize($_POST['unit']),
        'location_found' => sanitize($_POST['location_found']),
        'prisoner_name' => sanitize($_POST['prisoner_name']),
        'seized_by' => $user['id'],
        'disposition' => sanitize($_POST['disposition']),
        'case_reference' => sanitize($_POST['case_reference']),
        'notes' => sanitize($_POST['notes'])
    ];
    
    if (!empty($_POST['visitor_log_id'])) {
        $data['visitor_log_id'] = (int)$_POST['visitor_log_id'];
    }
    if (!empty($_POST['vehicle_log_id'])) {
        $data['vehicle_log_id'] = (int)$_POST['vehicle_log_id'];
    }
    
    $id = $db->insert('contraband_seizures', $data);
    logAction('contraband_seized', 'contraband_seizures', $id, null, $data);
    
    $success = 'Contraband item logged successfully';
}

$seizures = $db->fetchAll("
    SELECT cs.*, u.full_name as officer_name 
    FROM contraband_seizures cs 
    JOIN users u ON cs.seized_by = u.id 
    ORDER BY cs.seized_at DESC LIMIT 50", []);

$pageTitle = 'Contraband Log';
$pageHeader = 'Contraband Seizure Log';
$pageActions = '<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus me-2"></i>Log Seizure</button>';

include __DIR__ . '/../../includes/header.php';
?>

<?php if (isset($success)): ?>
<div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Location</th>
                        <th>Officer</th>
                        <th>Disposition</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($seizures as $s): ?>
                    <tr>
                        <td><?php echo formatDateTime($s['seized_at']); ?></td>
                        <td><?php echo htmlspecialchars($s['item_description']); ?></td>
                        <td><?php echo htmlspecialchars($s['quantity'] . ' ' . $s['unit']); ?></td>
                        <td><?php echo htmlspecialchars($s['location_found']); ?></td>
                        <td><?php echo htmlspecialchars($s['officer_name']); ?></td>
                        <td><?php echo ucfirst($s['disposition']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($seizures)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">No contraband logged</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Log Contraband Seizure</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Item Description *</label>
                        <input type="text" name="item_description" class="form-control" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Quantity</label>
                            <input type="text" name="quantity" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Unit</label>
                            <input type="text" name="unit" class="form-control" placeholder="e.g., grams, pieces">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location Found</label>
                        <input type="text" name="location_found" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Prisoner Name (if applicable)</label>
                        <input type="text" name="prisoner_name" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Disposition</label>
                        <select name="disposition" class="form-select">
                            <option value="retained">Retained</option>
                            <option value="destroyed">Destroyed</option>
                            <option value="returned">Returned</option>
                            <option value="forwarded_police">Forwarded to Police</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Case Reference</label>
                        <input type="text" name="case_reference" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Log Seizure</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
