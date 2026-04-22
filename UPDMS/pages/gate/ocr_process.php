<?php
header('Content-Type: text/plain');

$imageData = $_POST['image'] ?? '';

if (empty($imageData)) {
    echo 'NO_IMAGE';
    exit;
}

$uploadDir = __DIR__ . '/../../uploads/plates/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$filename = 'capture_' . date('Ymd_His') . '_' . substr(md5(time()), 0, 6) . '.jpg';
$fullPath = $uploadDir . $filename;

$image = base64_decode($imageData);

if ($image === false || strlen($image) < 1000) {
    echo 'INVALID_IMAGE';
    exit;
}

if (file_put_contents($fullPath, $image)) {
    echo 'SAVED:' . $filename;
} else {
    echo 'SAVE_FAILED';
}
