<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$user = getCurrentUser();
$db = getDB();

// Get facility name
$facilityName = 'Visitor Registration';
if ($user && isset($user['facility_id']) && $user['facility_id']) {
    $facility = $db->fetchOne("SELECT name FROM facilities WHERE id = ?", [$user['facility_id']]);
    if ($facility) $facilityName = $facility['name'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/custom.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>/pages/dashboard.php">
                <i class="bi bi-shield-lock me-2"></i><?php echo SITE_NAME; ?>
            </a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3">
                    <i class="bi bi-building me-1"></i><?php echo $facilityName; ?>
                </span>
                <?php if ($user): ?>
                <div class="dropdown">
                    <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($user['full_name']); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text">
                            <small class="text-muted">Role: <?php echo ucwords(str_replace('_', ' ', $user['role'])); ?></small>
                        </span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </a></li>
                    </ul>
                </div>
                <?php else: ?>
                <a href="<?php echo SITE_URL; ?>/pages/login.php" class="btn btn-light btn-sm">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Login
                </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <?php if (isset($pageHeader)): ?>
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?php echo $pageHeader; ?></h1>
                    <?php if (isset($pageActions)): ?>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <?php echo $pageActions; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
