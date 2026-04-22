<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireRole(['hq_command', 'admin']);

$db = getDB();
$user = getCurrentUser();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'prisoner_number' => sanitize($_POST['prisoner_number']),
        'national_id' => sanitize($_POST['national_id']),
        'first_name' => sanitize($_POST['first_name']),
        'last_name' => sanitize($_POST['last_name']),
        'gender' => sanitize($_POST['gender']),
        'date_of_birth' => sanitize($_POST['date_of_birth']),
        'nationality' => sanitize($_POST['nationality'] ?: 'Ugandan'),
        'marital_status' => sanitize($_POST['marital_status']),
        'address' => sanitize($_POST['address']),
        'next_of_kin_name' => sanitize($_POST['next_of_kin_name']),
        'next_of_kin_phone' => sanitize($_POST['next_of_kin_phone']),
        'next_of_kin_relation' => sanitize($_POST['next_of_kin_relation']),
        'admission_date' => sanitize($_POST['admission_date']),
        'facility_id' => $user['facility_id'] ?: sanitize($_POST['facility_id']),
        'cell_block' => sanitize($_POST['cell_block']),
        'cell_number' => sanitize($_POST['cell_number']),
        'status' => 'remand'
    ];
    
    if (empty($data['prisoner_number']) || empty($data['first_name']) || empty($data['last_name']) || empty($data['gender'])) {
        $error = 'Please fill all required fields';
    } else {
        try {
            $id = $db->insert('prisoners', $data);
            logAction('create', 'prisoners', $id, null, $data);
            $success = 'Prisoner added successfully';
            header('Location: ' . SITE_URL . '/pages/prisoners/view.php?id=' . $id);
            exit;
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

$facilities = $db->fetchAll("SELECT * FROM facilities WHERE is_active = 1 ORDER BY name");

$pageTitle = 'Add Prisoner';
$pageHeader = 'Add New Prisoner';
$pageActions = '<a href="' . SITE_URL . '/pages/prisoners/list.php" class="btn btn-secondary"><i class="bi bi-arrow-left me-2"></i>Back</a>';

include __DIR__ . '/../../includes/header.php';
?>

<?php if ($error): ?>
<div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Prisoner Number *</label>
                    <input type="text" name="prisoner_number" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">National ID</label>
                    <input type="text" name="national_id" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Gender *</label>
                    <select name="gender" class="form-select" required>
                        <option value="">Select...</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">First Name *</label>
                    <input type="text" name="first_name" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Last Name *</label>
                    <input type="text" name="last_name" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control">
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Nationality</label>
                    <input type="text" name="nationality" class="form-control" value="Ugandan">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Marital Status</label>
                    <select name="marital_status" class="form-select">
                        <option value="">Select...</option>
                        <option value="single">Single</option>
                        <option value="married">Married</option>
                        <option value="divorced">Divorced</option>
                        <option value="widowed">Widowed</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Admission Date *</label>
                    <input type="date" name="admission_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Address</label>
                    <input type="text" name="address" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cell Block</label>
                    <input type="text" name="cell_block" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cell Number</label>
                    <input type="text" name="cell_number" class="form-control">
                </div>
            </div>
            
            <hr>
            <h5>Next of Kin</h5>
            
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Name</label>
                    <input type="text" name="next_of_kin_name" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Phone</label>
                    <input type="text" name="next_of_kin_phone" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Relationship</label>
                    <input type="text" name="next_of_kin_relation" class="form-control">
                </div>
            </div>
            
            <div class="text-end">
                <a href="<?php echo SITE_URL; ?>/pages/prisoners/list.php" class="btn btn-secondary me-2">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Save Prisoner
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
