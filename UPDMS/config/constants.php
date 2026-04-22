<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'updms_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('SITE_NAME', 'UPDMS');
define('SITE_URL', 'http://localhost/UPDMS');

define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('PHOTO_PATH', UPLOAD_PATH . 'photos/');
define('VEHICLE_PATH', UPLOAD_PATH . 'vehicles/');

define('SESSION_TIMEOUT', 3600);
define('CSRF_TOKEN_NAME', 'csrf_token');

define('OVERSTAY_HOURS', 72);
define('REMAND_LIMIT_DAYS', 365);
define('REMAND_ALERT_DAYS', 330);

$VISITOR_TYPES = [
    'inmate' => 'Inmate Visitor',
    'hospital' => 'Hospital Visitor',
    'staff' => 'Staff Visitor',
    'official' => 'Official Visitor',
    'delivery' => 'Delivery Vehicle'
];

$USER_ROLES = [
    'admin' => 'Administrator',
    'hq_command' => 'HQ Command',
    'supervisor' => 'Supervisor/Officer in Charge',
    'gate_officer' => 'Gate Officer'
];
