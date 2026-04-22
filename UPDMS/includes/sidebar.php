<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$user = getCurrentUser();
?>
<nav class="col-md-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/pages/dashboard.php">
                    <i class="bi bi-house me-2"></i>Registration
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'control' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/pages/gate/control.php">
                    <i class="bi bi-car me-2"></i>Gate Control
                </a>
            </li>
            
            <?php if ($user && hasRole(['supervisor', 'hq_command', 'admin'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo in_array($currentPage, ['list', 'add', 'view']) ? 'active' : ''; ?>" 
                   href="<?php echo SITE_URL; ?>/pages/prisoners/list.php">
                    <i class="bi bi-people me-2"></i>Prisoners
                </a>
            </li>
            <?php endif; ?>
            
            <?php if ($user && hasRole(['hq_command', 'admin'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'reports' ? 'active' : ''; ?>" 
                   href="<?php echo SITE_URL; ?>/pages/hq/reports.php">
                    <i class="bi bi-graph-up me-2"></i>Reports
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'remand_alerts' ? 'active' : ''; ?>" 
                   href="<?php echo SITE_URL; ?>/pages/prisoners/remand_alerts.php">
                    <i class="bi bi-bell me-2"></i>Remand Alerts
                </a>
            </li>
            <?php endif; ?>
            
            <?php if ($user && hasRole(['admin'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo in_array($currentPage, ['users', 'facilities', 'audit']) ? 'active' : ''; ?>" 
                   href="<?php echo SITE_URL; ?>/pages/admin/users.php">
                    <i class="bi bi-gear me-2"></i>Settings
                </a>
            </li>
            <?php endif; ?>
            
            <li class="nav-item">
                <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                </a>
            </li>
        </ul>
    </div>
</nav>
