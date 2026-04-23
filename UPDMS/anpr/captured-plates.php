<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireRole(['admin', 'gate_officer', 'supervisor']);

$db = getDB();
$facility_id = $_SESSION['facility_id'] ?? 1;

$uploadDir = __DIR__ . '/../../uploads/plates/';
$images = [];

if (is_dir($uploadDir)) {
    $files = glob($uploadDir . '*.{jpg,jpeg,png,JPG,JPEG,PNG}', GLOB_BRACE);
    usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    
    foreach (array_slice($files, 0, 50) as $file) {
        $images[] = [
            'image_path' => basename($file),
            'captured_at' => date('Y-m-d H:i:s', filemtime($file))
        ];
    }
}

$stats = [
    'today' => count(array_filter($images, function($img) {
        return date('Y-m-d') === date('Y-m-d', strtotime($img['captured_at']));
    })),
    'total' => count($images),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Captured Plates - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .stat-card .number { font-size: 2rem; font-weight: 700; color: #198754; }
        .stat-card .label { color: #666; font-size: 0.85rem; }
        .plate-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            gap: 15px;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .plate-thumb {
            width: 120px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            background: #000;
        }
        .plate-info { flex: 1; }
        .plate-number { font-size: 1.3rem; font-weight: 700; color: #1a1a2e; }
        .plate-meta { font-size: 0.85rem; color: #666; }
        .plate-time { font-size: 0.75rem; color: #999; }
        .confidence { 
            padding: 3px 10px; 
            border-radius: 20px; 
            font-size: 0.75rem;
            font-weight: 600;
        }
        .conf-high { background: #d1e7dd; color: #146c43; }
        .conf-medium { background: #fff3cd; color: #856404; }
        .conf-low { background: #f8d7da; color: #58151c; }
        .nav-link { color: #1a1a2e; }
        .nav-link:hover { color: #198754; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/header.php'; ?>
    
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <h4><i class="bi bi-camera"></i> Captured Plate Images</h4>
                <a href="vehicle-entry-mobile.php" class="btn btn-success">
                    <i class="bi bi-plus-circle"></i> New Capture
                </a>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="number"><?php echo $stats['today']; ?></div>
                    <div class="label">Captured Today</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="number"><?php echo $stats['total']; ?></div>
                    <div class="label">Total Captures</div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Recent Captures</h5>
            </div>
            <div class="card-body">
                <?php if (empty($images)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-camera" style="font-size: 4rem;"></i>
                        <p class="mt-3">No plates captured yet</p>
                        <a href="vehicle-entry-mobile.php" class="btn btn-success">Start Capturing</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($images as $img): ?>
                    <div class="plate-card">
                        <img src="/UPDMS/uploads/plates/<?php echo htmlspecialchars($img['image_path']); ?>" 
                             class="plate-thumb" alt="Captured">
                        <div class="plate-info">
                            <div class="plate-number">Pending Review</div>
                            <div class="plate-time">
                                <i class="bi bi-clock"></i> <?php echo date('M d, Y H:i', strtotime($img['captured_at'])); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
