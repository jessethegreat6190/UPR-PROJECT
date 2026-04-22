<?php
require_once __DIR__ . '/../config/bootstrap.php';
requireLogin();
logAction('logout', 'users', $_SESSION['user_id']);
session_destroy();
header('Location: ' . SITE_URL . '/pages/login.php?logout=1');
exit;
