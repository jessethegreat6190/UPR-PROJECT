<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireLogin();
logAction('logout', 'users', $_SESSION['user_id']);
unset($_SESSION['gate_mode']);
session_destroy();
header('Location: ' . SITE_URL . '/pages/gate/gate-login.php');
exit;
