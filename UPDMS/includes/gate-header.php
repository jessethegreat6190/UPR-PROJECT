<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$user = getCurrentUser();
$db = getDB();

$facilityName = 'Gate Control';
if ($user['facility_id']) {
    $facility = $db->fetchOne("SELECT name FROM facilities WHERE id = ?", [$user['facility_id']]);
    if ($facility) $facilityName = $facility['name'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Gate Control - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/custom.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .gate-header { background: linear-gradient(135deg, #198754 0%, #146c43 100%); }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark gate-header">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>/pages/gate/gate-dashboard.php">
                <i class="bi bi-car me-2"></i>GATE CONTROL
            </a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3">
                    <i class="bi bi-building me-1"></i><?php echo $facilityName; ?>
                </span>
                <div class="dropdown">
                    <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i><?php echo $user['full_name']; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text">
                            <small class="text-muted">Role: <?php echo ucwords(str_replace('_', ' ', $user['role'])); ?></small>
                        </span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/gate/gate-dashboard.php">
                            <i class="bi bi-speedometer2 me-2"></i>Dashboard
                        </a></li>
                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/gate/gate-logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid py-3">
