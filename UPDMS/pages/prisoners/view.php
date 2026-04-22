<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireRole(['supervisor', 'hq_command', 'admin']);

$db = getDB();
$user = getCurrentUser();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ' . SITE_URL . '/pages/prisoners/list.php');
    exit;
}

$prisoner = $db->fetchOne("
    SELECT p.*, f.name as facility_name 
    FROM prisoners p 
    JOIN facilities f ON p.facility_id = f.id 
    WHERE p.id = ?", [$id]);

if (!$prisoner) {
    header('Location: ' . SITE_URL . '/pages/prisoners/list.php');
    exit;
}

// Get warrants
$warrants = $db->fetchAll("SELECT * FROM warrants WHERE prisoner_id = ? ORDER BY issue_date DESC", [$id]);

// Get sentences
$sentences = $db->fetchAll("SELECT * FROM sentences WHERE prisoner_id = ? ORDER BY sentence_start_date DESC", [$id]);

// Get court appearances
$courtDates = $db->fetchAll("SELECT * FROM court_appearances WHERE prisoner_id = ? ORDER BY hearing_date DESC", [$id]);

// Get visitor logs
$visitorLogs = $db->fetchAll("
    SELECT vl.*, v.first_name, v.last_name 
    FROM visitor_logs vl 
    JOIN visitors v ON vl.visitor_id = v.id 
    WHERE vl.prisoner_id = ? 
    ORDER BY vl.entry_time DESC LIMIT 10", [$id]);

$pageTitle = 'Prisoner Details';
$pageHeader = $prisoner['first_name'] . ' ' . $prisoner['last_name'];

include __DIR__ . '/../../includes/header.php';
?>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="bi bi-person-circle" style="font-size: 100px; color: #ccc;"></i>
                </div>
                <h4><?php echo htmlspecialchars($prisoner['first_name'] . ' ' . $prisoner['last_name']); ?></h4>
                <p class="text-muted"><?php echo htmlspecialchars($prisoner['prisoner_number']); ?></p>
                <span class="badge bg-<?php echo $prisoner['status'] === 'convicted' ? 'success' : 'warning'; ?>">
                    <?php echo ucfirst($prisoner['status']); ?>
                </span>
            </div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item">
                    <strong>Gender:</strong> <?php echo ucfirst($prisoner['gender']); ?>
                </li>
                <li class="list-group-item">
                    <strong>DOB:</strong> <?php echo $prisoner['date_of_birth'] ? formatDate($prisoner['date_of_birth']) : 'N/A'; ?>
                </li>
                <li class="list-group-item">
                    <strong>ID Number:</strong> <?php echo $prisoner['national_id'] ?: 'N/A'; ?>
                </li>
                <li class="list-group-item">
                    <strong>Facility:</strong> <?php echo htmlspecialchars($prisoner['facility_name']); ?>
                </li>
                <li class="list-group-item">
                    <strong>Cell:</strong> <?php echo htmlspecialchars($prisoner['cell_block'] . ' / ' . $prisoner['cell_number']); ?>
                </li>
                <li class="list-group-item">
                    <strong>Admission:</strong> <?php echo formatDate($prisoner['admission_date']); ?>
                </li>
            </ul>
        </div>
        
        <?php if (hasRole(['hq_command', 'admin'])): ?>
        <div class="card mt-3">
            <div class="card-body">
                <a href="<?php echo SITE_URL; ?>/pages/prisoners/edit.php?id=<?php echo $id; ?>" class="btn btn-primary w-100 mb-2">
                    <i class="bi bi-pencil me-2"></i>Edit Details
                </a>
                <a href="<?php echo SITE_URL; ?>/pages/prisoners/warrant.php?prisoner_id=<?php echo $id; ?>" class="btn btn-secondary w-100 mb-2">
                    <i class="bi bi-file-text me-2"></i>Add Warrant
                </a>
                <a href="<?php echo SITE_URL; ?>/pages/prisoners/sentence.php?prisoner_id=<?php echo $id; ?>" class="btn btn-secondary w-100">
                    <i class="bi bi-calculator me-2"></i>Calculate Sentence
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-8">
        <ul class="nav nav-tabs" id="prisonerTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#warrants">Warrants (<?php echo count($warrants); ?>)</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#sentences">Sentences (<?php echo count($sentences); ?>)</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#court">Court (<?php echo count($courtDates); ?>)</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#visitors">Visitors (<?php echo count($visitorLogs); ?>)</button>
            </li>
        </ul>
        
        <div class="tab-content mt-3">
            <div class="tab-pane fade show active" id="warrants">
                <?php if (hasRole(['hq_command', 'admin'])): ?>
                <a href="<?php echo SITE_URL; ?>/pages/prisoners/warrant.php?prisoner_id=<?php echo $id; ?>" class="btn btn-sm btn-primary mb-3">
                    <i class="bi bi-plus me-2"></i>Add Warrant
                </a>
                <?php endif; ?>
                
                <?php if ($warrants): ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Warrant #</th>
                                <th>Type</th>
                                <th>Court</th>
                                <th>Issue Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($warrants as $w): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($w['warrant_number']); ?></td>
                                <td><?php echo ucfirst($w['warrant_type']); ?></td>
                                <td><?php echo htmlspecialchars($w['issuing_court']); ?></td>
                                <td><?php echo formatDate($w['issue_date']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $w['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($w['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted">No warrants recorded</p>
                <?php endif; ?>
            </div>
            
            <div class="tab-pane fade" id="sentences">
                <?php if ($sentences): ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Release Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sentences as $s): ?>
                            <tr>
                                <td><?php echo formatDate($s['sentence_start_date']); ?></td>
                                <td><?php echo $s['sentence_end_date'] ? formatDate($s['sentence_end_date']) : 'N/A'; ?></td>
                                <td><strong><?php echo $s['release_date'] ? formatDate($s['release_date']) : 'TBD'; ?></strong></td>
                                <td>
                                    <span class="badge bg-<?php echo $s['status'] === 'serving' ? 'primary' : 'success'; ?>">
                                        <?php echo ucfirst($s['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted">No sentence records. <a href="<?php echo SITE_URL; ?>/pages/prisoners/sentence.php?prisoner_id=<?php echo $id; ?>">Calculate sentence</a></p>
                <?php endif; ?>
            </div>
            
            <div class="tab-pane fade" id="court">
                <?php if (hasRole(['supervisor', 'hq_command', 'admin'])): ?>
                <a href="<?php echo SITE_URL; ?>/pages/prisoners/court.php?prisoner_id=<?php echo $id; ?>" class="btn btn-sm btn-primary mb-3">
                    <i class="bi bi-plus me-2"></i>Add Court Date
                </a>
                <?php endif; ?>
                
                <?php if ($courtDates): ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Court</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Outcome</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courtDates as $c): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($c['court_name']); ?></td>
                                <td><?php echo formatDate($c['hearing_date']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $c['status'] === 'completed' ? 'success' : ($c['status'] === 'scheduled' ? 'primary' : 'warning'); ?>">
                                        <?php echo ucfirst($c['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $c['outcome'] ?: '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted">No court dates scheduled</p>
                <?php endif; ?>
            </div>
            
            <div class="tab-pane fade" id="visitors">
                <?php if ($visitorLogs): ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Visitor</th>
                                <th>Type</th>
                                <th>Entry</th>
                                <th>Exit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($visitorLogs as $v): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($v['first_name'] . ' ' . $v['last_name']); ?></td>
                                <td><?php echo ucfirst($v['visitor_type']); ?></td>
                                <td><?php echo formatDateTime($v['entry_time']); ?></td>
                                <td><?php echo $v['exit_time'] ? formatDateTime($v['exit_time']) : '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted">No visitor records</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
