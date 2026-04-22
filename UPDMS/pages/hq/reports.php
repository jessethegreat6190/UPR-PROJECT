<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireRole(['hq_command', 'admin']);

$db = getDB();
$user = getCurrentUser();

$report = sanitize($_GET['report'] ?? 'overview');

$pageTitle = 'Reports';
$pageHeader = 'Reports & Analytics';

include __DIR__ . '/../../includes/header.php';
?>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link <?php echo $report === 'overview' ? 'active' : ''; ?>" href="?report=overview">Overview</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $report === 'visitors' ? 'active' : ''; ?>" href="?report=visitors">Visitor Report</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $report === 'vehicles' ? 'active' : ''; ?>" href="?report=vehicles">Vehicle Report</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $report === 'incidents' ? 'active' : ''; ?>" href="?report=incidents">Incident Report</a>
    </li>
</ul>

<?php if ($report === 'overview'): ?>
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Prisoner Statistics</h5>
            </div>
            <div class="card-body">
                <?php
                $stats = $db->fetchAll("
                    SELECT status, COUNT(*) as count 
                    FROM prisoners 
                    GROUP BY status", []);
                ?>
                <table class="table">
                    <?php foreach ($stats as $s): ?>
                    <tr>
                        <td><?php echo ucfirst($s['status']); ?></td>
                        <td><strong><?php echo number_format($s['count']); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Facility Breakdown</h5>
            </div>
            <div class="card-body">
                <?php
                $facilities = $db->fetchAll("
                    SELECT f.name, COUNT(p.id) as count
                    FROM facilities f
                    LEFT JOIN prisoners p ON f.id = p.facility_id AND p.status IN ('remand', 'convicted')
                    GROUP BY f.id, f.name
                    ORDER BY count DESC", []);
                ?>
                <table class="table">
                    <?php foreach ($facilities as $f): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($f['name']); ?></td>
                        <td><strong><?php echo number_format($f['count']); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<?php elseif ($report === 'visitors'): ?>
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Visitor Summary (Last 30 Days)</h5>
    </div>
    <div class="card-body">
        <?php
        $visitors = $db->fetchAll("
            SELECT visitor_type, COUNT(*) as count,
                   SUM(CASE WHEN status = 'inside' THEN 1 ELSE 0 END) as still_inside
            FROM visitor_logs 
            WHERE entry_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY visitor_type", []);
        ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Visitor Type</th>
                        <th>Total Visits</th>
                        <th>Currently Inside</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($visitors as $v): ?>
                    <tr>
                        <td><?php echo ucfirst($v['visitor_type']); ?></td>
                        <td><?php echo number_format($v['count']); ?></td>
                        <td><?php echo number_format($v['still_inside']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php elseif ($report === 'vehicles'): ?>
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Vehicle Activity (Last 30 Days)</h5>
    </div>
    <div class="card-body">
        <?php
        $vehicles = $db->fetchAll("
            SELECT v.plate_number, COUNT(*) as visits,
                   MAX(vl.entry_time) as last_visit,
                   SUM(CASE WHEN vl.status = 'inside' THEN 1 ELSE 0 END) as currently_inside
            FROM vehicle_logs vl
            JOIN vehicles v ON vl.vehicle_id = v.id
            WHERE vl.entry_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY v.id, v.plate_number
            ORDER BY visits DESC
            LIMIT 50", []);
        ?>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Plate</th>
                        <th>Total Visits</th>
                        <th>Last Visit</th>
                        <th>Inside</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vehicles as $v): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($v['plate_number']); ?></strong></td>
                        <td><?php echo $v['visits']; ?></td>
                        <td><?php echo formatDateTime($v['last_visit']); ?></td>
                        <td><?php echo $v['currently_inside'] > 0 ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php elseif ($report === 'incidents'): ?>
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Incident Summary (Last 30 Days)</h5>
    </div>
    <div class="card-body">
        <?php
        $incidents = $db->fetchAll("
            SELECT incident_type, COUNT(*) as count
            FROM incidents
            WHERE incident_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY incident_type", []);
        ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Incident Type</th>
                        <th>Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($incidents as $i): ?>
                    <tr>
                        <td><?php echo ucwords(str_replace('_', ' ', $i['incident_type'])); ?></td>
                        <td><strong><?php echo number_format($i['count']); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($incidents)): ?>
                    <tr><td colspan="2" class="text-center text-muted">No incidents reported</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
