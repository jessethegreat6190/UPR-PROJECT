<?php
require_once __DIR__ . '/../config/bootstrap.php';

$db = getDB();
$user = getCurrentUser();

// Handle success message
$success_msg = '';
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $type = sanitize($_GET['type'] ?? '');
    $type_names = [
        'inmate' => 'Inmate Visitor',
        'staff' => 'Staff Visitor',
        'hospital' => 'Hospital Visitor',
        'official' => 'Official Visitor',
        'delivery' => 'Delivery Vehicle'
    ];
    $success_msg = isset($type_names[$type]) ? $type_names[$type] : 'Visitor';
}

$pageTitle = 'Visitor Registration';

include __DIR__ . '/../includes/header.php';
?>

<style>
body { background: #f0f2f5; }
.main-container { max-width: 900px; margin: 20px auto; padding: 0 15px; }
.section-title {
    color: #1a1a2e; font-size: 1rem; font-weight: 600;
    margin: 15px 0 10px 0; padding-bottom: 5px; border-bottom: 2px solid #4361ee;
}
.grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px; }
.reg-btn {
    padding: 15px 10px; border-radius: 10px; border: none;
    font-size: 0.9rem; font-weight: 600; cursor: pointer;
    transition: all 0.2s; text-align: center; text-decoration: none;
    color: white; display: flex; flex-direction: column; align-items: center; gap: 5px;
}
.reg-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); color: white; }
.reg-btn i { font-size: 1.5rem; }
.reg-btn small { font-weight: 400; font-size: 0.7rem; opacity: 0.9; }
.btn-inmate { background: #4361ee; }
.btn-staff { background: #7209b7; }
.btn-hospital { background: #2ec4b6; }
.btn-official { background: #f72585; }
.btn-delivery { background: #f77f00; }
.btn-checkout { background: #1a1a2e; }
</style>

<div class="main-container">
    <!-- Success Message -->
    <?php if ($success_msg): ?>
    <div class="alert alert-success">
        <i class="bi bi-check-circle-fill"></i>
        <strong>Registration Successful!</strong> - <?php echo $success_msg; ?> registered at <?php echo date('H:i:s'); ?>
    </div>
    <?php endif; ?>
    
    <!-- VISITATION -->
    <div class="section-title"><i class="bi bi-people"></i> VISITATION</div>
    <div class="grid-3">
        <a href="<?php echo SITE_URL; ?>/pages/visitors/checkin.php?type=inmate" class="reg-btn btn-inmate">
            <i class="bi bi-person-badge"></i>
            INMATE
            <small>Family/Friends</small>
        </a>
        
        <a href="<?php echo SITE_URL; ?>/pages/visitors/checkin.php?type=staff" class="reg-btn btn-staff">
            <i class="bi bi-person"></i>
            STAFF
            <small>Visit Staff</small>
        </a>
        
        <a href="<?php echo SITE_URL; ?>/pages/visitors/checkout.php" class="reg-btn btn-checkout">
            <i class="bi bi-box-arrow-left"></i>
            CHECK OUT
            <small>Visitor Sign Out</small>
        </a>
    </div>
    
    <!-- OTHER VISITS -->
    <div class="section-title"><i class="bi bi-clipboard"></i> OTHER VISITS</div>
    <div class="grid-3">
        <a href="<?php echo SITE_URL; ?>/pages/visitors/checkin.php?type=hospital" class="reg-btn btn-hospital">
            <i class="bi bi-heart-pulse"></i>
            HOSPITAL
            <small>Visit Patient</small>
        </a>
        
        <a href="<?php echo SITE_URL; ?>/pages/visitors/checkin.php?type=official" class="reg-btn btn-official">
            <i class="bi bi-briefcase"></i>
            OFFICIAL
            <small>Business/Inspect</small>
        </a>
        
        <a href="<?php echo SITE_URL; ?>/pages/visitors/checkin.php?type=delivery" class="reg-btn btn-delivery">
            <i class="bi bi-truck"></i>
            DELIVERY
            <small>Vehicle/School Van</small>
        </a>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
