<?php
session_start();

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/database.php';

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    header('Location: ' . SITE_URL . '/pages/login.php');
    exit;
}
$_SESSION['last_activity'] = time();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/pages/login.php');
        exit;
    }
}

function hasRole($roles) {
    if (!isLoggedIn()) return false;
    if (is_string($roles)) $roles = [$roles];
    return in_array($_SESSION['role'], $roles);
}

function requireRole($roles) {
    requireLogin();
    if (!hasRole($roles)) {
        header('Location: ' . SITE_URL . '/pages/dashboard.php');
        exit;
    }
}

function generateCSRFToken() {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function verifyCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'],
        'role' => $_SESSION['role'],
        'facility_id' => $_SESSION['facility_id']
    ];
}

function logAction($action_type, $table_name, $record_id = null, $old_values = null, $new_values = null) {
    $db = getDB();
    $user = getCurrentUser();
    
    $db->insert('action_logs', [
        'user_id' => $user['id'],
        'user_name' => $user['full_name'],
        'action_type' => $action_type,
        'table_name' => $table_name,
        'record_id' => $record_id,
        'old_values' => $old_values ? json_encode($old_values) : null,
        'new_values' => $new_values ? json_encode($new_values) : null,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'device_info' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
}

function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function formatDate($date) {
    return date('d M Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('d M Y H:i', strtotime($datetime));
}

function formatTime($time) {
    return date('H:i', strtotime($time));
}

function calculateDuration($entry, $exit) {
    $diff = strtotime($exit) - strtotime($entry);
    $hours = floor($diff / 3600);
    $minutes = floor(($diff % 3600) / 60);
    return $hours . 'h ' . $minutes . 'm';
}
