<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireRole(['gate_officer', 'supervisor', 'hq_command', 'admin']);

$db = getDB();
$user = getCurrentUser();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $visitor_type = sanitize($_POST['visitor_type']);
    
    // Create visitor record
    $visitor_data = [
        'visitor_type' => $visitor_type,
        'national_id' => sanitize($_POST['national_id']) ?: null,
        'first_name' => sanitize($_POST['first_name']),
        'last_name' => sanitize($_POST['last_name']) ?: sanitize($_POST['first_name']),
        'phone' => sanitize($_POST['phone']) ?: null,
        'address' => sanitize($_POST['address']) ?: null,
        'vehicle_plate' => isset($_POST['vehicle_plate']) ? strtoupper(sanitize($_POST['vehicle_plate'])) : null,
        'driver_name' => sanitize($_POST['driver_name']) ?: null,
        'company_name' => sanitize($_POST['company_name']) ?: null
    ];
    
    $visitor_id = $db->insert('visitors', $visitor_data);
    
    // Create visitor log
    $log_data = [
        'visitor_id' => $visitor_id,
        'prisoner_id' => isset($_POST['prisoner_id']) && $_POST['prisoner_id'] ? (int)$_POST['prisoner_id'] : null,
        'facility_id' => $user['facility_id'] ?: 1,
        'visitor_type' => $visitor_type,
        'entry_time' => date('Y-m-d H:i:s'),
        'cargo_checked' => isset($_POST['cargo_checked']) ? 1 : 0,
        'cargo_description' => sanitize($_POST['cargo_description'] ?? '') ?: sanitize($_POST['items'] ?? ''),
        'status' => 'inside',
        'gate_officer_entry_id' => $user['id'],
        'notes' => sanitize($_POST['purpose'] ?? '') . ' | ' . sanitize($_POST['who_visiting'] ?? '')
    ];
    
    $log_id = $db->insert('visitor_logs', $log_data);
    logAction('visitor_checkin', 'visitor_logs', $log_id, null, $log_data);
    
    // Redirect to print receipt
    header('Location: ' . SITE_URL . '/pages/visitors/print-receipt.php?log_id=' . $log_id);
    exit;
}

$pageTitle = 'Visitor Check In';
$pageHeader = 'Visitor Check In';

include __DIR__ . '/../../includes/header.php';
?>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="bi bi-check-circle me-2"></i><?php echo $success; ?> - Registration Successful!
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-person-plus me-2"></i>Check In Visitor</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="visitorForm">
                    <div class="mb-3">
                        <label class="form-label">Visitor Category *</label>
                        <select name="visitor_type" id="visitorType" class="form-select form-select-lg" required onchange="toggleFields()">
                            <option value="">Select category...</option>
                            <option value="inmate" <?php echo (isset($_GET['type']) && $_GET['type'] === 'inmate') ? 'selected' : ''; ?>>VISITATION - Inmate (Family/Friends)</option>
                            <option value="staff" <?php echo (isset($_GET['type']) && $_GET['type'] === 'staff') ? 'selected' : ''; ?>>VISITATION - Staff Member</option>
                            <option value="hospital" <?php echo (isset($_GET['type']) && $_GET['type'] === 'hospital') ? 'selected' : ''; ?>>Hospital Visit</option>
                            <option value="official" <?php echo (isset($_GET['type']) && $_GET['type'] === 'official') ? 'selected' : ''; ?>>Official Business</option>
                            <option value="delivery" <?php echo (isset($_GET['type']) && $_GET['type'] === 'delivery') ? 'selected' : ''; ?>>Delivery Vehicle</option>
                        </select>
                    </div>
                    
                    <div id="inmateFields" class="d-none">
                        <div class="mb-3">
                            <label class="form-label">Booking Reference</label>
                            <input type="text" name="booking_reference" class="form-control" placeholder="Enter booking number">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Select Inmate Being Visited *</label>
                            <input type="text" name="prisoner_search" id="prisonerSearch" class="form-control" placeholder="Start typing name or prisoner number..." autocomplete="off">
                            <input type="hidden" name="prisoner_id" id="prisonerId">
                            <div id="prisonerResults" class="list-group position-absolute w-100" style="z-index: 1000; max-height: 200px; overflow-y: auto;"></div>
                            <small class="text-muted">Search by name or prisoner number</small>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name *</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name *</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                    </div>
                    
                    <div id="idField">
                        <div class="mb-3">
                            <label class="form-label">National ID Number</label>
                            <input type="text" name="national_id" class="form-control">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" placeholder="e.g., 0771234567">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Home Address / District</label>
                            <input type="text" name="address" class="form-control" placeholder="e.g., Kira Municipality, Wakiso">
                        </div>
                    </div>
                    
                    <div id="vehicleFields" class="d-none">
                        <div class="mb-3">
                            <label class="form-label">Vehicle Plate</label>
                            <input type="text" name="vehicle_plate" id="vehiclePlate" class="form-control text-uppercase" placeholder="e.g., UAR 123X">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Driver Name</label>
                            <input type="text" name="driver_name" id="driverName" class="form-control">
                        </div>
                    </div>
                    
                    <div id="cargoFields" class="d-none">
                        <div class="mb-3">
                            <label class="form-label">Items Being Brought</label>
                            <textarea name="cargo_description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="form-check mb-3">
                            <input type="checkbox" name="cargo_checked" class="form-check-input" id="cargoChecked">
                            <label class="form-check-label" for="cargoChecked">Items have been inspected</label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg w-100 touch-btn">
                        <i class="bi bi-check-circle me-2"></i>Check In Visitor
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-people me-2"></i>Currently Inside</h5>
            </div>
            <div class="card-body">
                <?php
                $inside = $db->fetchAll("
                    SELECT vl.*, v.first_name, v.last_name 
                    FROM visitor_logs vl 
                    JOIN visitors v ON vl.visitor_id = v.id 
                    WHERE vl.status = 'inside' 
                    AND vl.facility_id = ? 
                    ORDER BY vl.entry_time DESC", [$user['facility_id'] ?: 1]);
                ?>
                
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Visiting</th>
                                <th>Entry</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inside as $v): 
                                $visitor = $db->fetchOne("SELECT * FROM visitors WHERE id = ?", [$v['visitor_id']]);
                                $hours = (time() - strtotime($v['entry_time'])) / 3600;
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($v['first_name'] . ' ' . $v['last_name']); ?></strong></td>
                                <td><span class="badge bg-<?php echo $v['visitor_type'] === 'inmate' ? 'primary' : ($v['visitor_type'] === 'delivery' ? 'warning' : 'secondary'); ?>"><?php echo ucfirst($v['visitor_type']); ?></span></td>
                                <td><?php echo $v['prisoner_id'] ? '<small>Prisoner #'.$v['prisoner_id'].'</small>' : '-'; ?></td>
                                <td><?php echo formatTime($v['entry_time']); ?></td>
                                <td><strong data-entry-time="<?php echo $v['entry_time']; ?>"><?php echo floor($hours) . 'h ' . floor(($hours - floor($hours)) * 60) . 'm'; ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($inside)): ?>
                            <tr><td colspan="5" class="text-center text-muted">No visitors inside</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let prisonerSearchTimeout;

function toggleFields() {
    const type = document.getElementById('visitorType').value;
    const inmateFields = document.getElementById('inmateFields');
    const vehicleFields = document.getElementById('vehicleFields');
    const cargoFields = document.getElementById('cargoFields');
    const idField = document.getElementById('idField');
    
    inmateFields.classList.add('d-none');
    vehicleFields.classList.add('d-none');
    cargoFields.classList.add('d-none');
    idField.classList.add('d-none');
    
    if (type === 'inmate') {
        inmateFields.classList.remove('d-none');
        vehicleFields.classList.remove('d-none');
        cargoFields.classList.remove('d-none');
        idField.classList.remove('d-none');
    } else if (type === 'hospital' || type === 'official') {
        vehicleFields.classList.remove('d-none');
        cargoFields.classList.remove('d-none');
        idField.classList.remove('d-none');
    } else if (type === 'staff') {
        vehicleFields.classList.remove('d-none');
    } else if (type === 'delivery') {
        vehicleFields.classList.remove('d-none');
        idField.classList.remove('d-none');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    toggleFields();
    
    // Prisoner search with autocomplete
    const prisonerSearch = document.getElementById('prisonerSearch');
    const prisonerResults = document.getElementById('prisonerResults');
    const prisonerId = document.getElementById('prisonerId');
    
    if (prisonerSearch) {
        prisonerSearch.addEventListener('input', function() {
            const query = this.value.trim();
            
            clearTimeout(prisonerSearchTimeout);
            
            if (query.length < 2) {
                prisonerResults.innerHTML = '';
                return;
            }
            
            prisonerSearchTimeout = setTimeout(() => {
                fetch('<?php echo SITE_URL; ?>/api/dashboard.php?action=search_prisoners&q=' + encodeURIComponent(query))
                    .then(r => r.json())
                    .then(data => {
                        if (data.length === 0) {
                            prisonerResults.innerHTML = '<div class="list-group-item text-muted">No inmates found</div>';
                            return;
                        }
                        
                        prisonerResults.innerHTML = data.map(p => 
                            `<a href="#" class="list-group-item list-group-item-action" onclick="selectPrisoner(${p.id}, '${p.prisoner_number}', '${p.first_name} ${p.last_name}', '${p.status}')">
                                <strong>${p.prisoner_number}</strong> - ${p.first_name} ${p.last_name}
                                <span class="badge bg-${p.status === 'convicted' ? 'success' : 'warning'} ms-2">${p.status}</span>
                            </a>`
                        ).join('');
                    })
                    .catch(() => {
                        prisonerResults.innerHTML = '<div class="list-group-item text-danger">Error searching</div>';
                    });
            }, 300);
        });
        
        // Hide results when clicking outside
        document.addEventListener('click', function(e) {
            if (!prisonerSearch.contains(e.target) && !prisonerResults.contains(e.target)) {
                prisonerResults.innerHTML = '';
            }
        });
    }
    
    function selectPrisoner(id, number, name, status) {
        prisonerId.value = id;
        prisonerSearch.value = number + ' - ' + name;
        prisonerResults.innerHTML = '';
    }
    
    // Make function globally available
    window.selectPrisoner = selectPrisoner;
    
    // Check if form was submitted successfully
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success')) {
        showSuccessMessage(urlParams.get('type'));
        setTimeout(() => {
            window.location.href = '<?php echo SITE_URL; ?>/pages/dashboard.php';
        }, 5000);
    }
});

function showSuccessMessage(type) {
    const messages = {
        'inmate': 'Inmate visitor checked in successfully!',
        'staff': 'Staff member checked in successfully!',
        'hospital': 'Hospital visitor checked in successfully!',
        'official': 'Official visitor checked in successfully!',
        'delivery': 'Delivery vehicle checked in successfully!'
    };
    alert(messages[type] || 'Visitor checked in successfully!');
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
