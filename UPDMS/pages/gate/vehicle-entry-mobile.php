<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireLogin();

$db = getDB();
$user = getCurrentUser();
$facility_id = $_SESSION['facility_id'] ?? 1;

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plate = strtoupper(sanitize($_POST['plate_number'] ?? ''));
    $driver_name = sanitize($_POST['driver_name'] ?? '');
    $visitor_type = sanitize($_POST['visitor_type'] ?? 'delivery');
    $cargo_description = sanitize($_POST['cargo_description'] ?? '');
    $cargo_checked = isset($_POST['cargo_checked']) ? 1 : 0;
    $car_color = sanitize($_POST['car_color'] ?? '');
    $car_model = sanitize($_POST['car_model'] ?? '');
    
    if (empty($plate)) {
        $error = "Plate number is required";
    } else {
        $vehicle = $db->fetchOne("SELECT * FROM vehicles WHERE plate_number = ?", [$plate]);
        
        if (!$vehicle) {
            $vehicle_id = $db->insert('vehicles', [
                'plate_number' => $plate,
                'driver_name' => $driver_name,
                'last_driver_name' => $driver_name,
                'last_visit' => date('Y-m-d H:i:s'),
                'total_visits' => 1,
                'color' => $car_color,
                'make_model' => $car_model
            ]);
        } else {
            $vehicle_id = $vehicle['id'];
            if ($vehicle['is_blacklisted']) {
                $error = "VEHICLE BLOCKED: " . ($vehicle['blacklisted_reason'] ?? 'Contact supervisor');
            } else {
                $db->update('vehicles', [
                    'last_driver_name' => $driver_name,
                    'last_visit' => date('Y-m-d H:i:s'),
                    'total_visits' => ($vehicle['total_visits'] ?? 0) + 1,
                    'color' => $car_color ?: $vehicle['color'],
                    'make_model' => $car_model ?: $vehicle['make_model']
                ], 'id = :id', ['id' => $vehicle_id]);
            }
        }
        
        if (empty($error)) {
            $log_id = $db->insert('vehicle_logs', [
                'vehicle_id' => $vehicle_id,
                'facility_id' => $facility_id,
                'visitor_type' => $visitor_type,
                'driver_name' => $driver_name,
                'entry_time' => date('Y-m-d H:i:s'),
                'cargo_description' => $cargo_description,
                'cargo_checked' => $cargo_checked,
                'status' => 'inside',
                'gate_officer_entry_id' => $user['id']
            ]);
            
            logAction('vehicle_entry', 'vehicle_logs', $log_id, null, [
                'plate' => $plate,
                'driver' => $driver_name,
                'type' => $visitor_type
            ]);
            
            $success = "VEHICLE REGISTERED: $plate";
        }
    }
}

$todayEntries = $db->fetchAll("
    SELECT vl.*, v.plate_number, v.last_driver_name, v.color, v.make_model 
    FROM vehicle_logs vl 
    JOIN vehicles v ON vl.vehicle_id = v.id 
    WHERE DATE(vl.entry_time) = CURDATE() AND vl.facility_id = ?
    ORDER BY vl.entry_time DESC LIMIT 20", [$facility_id]);

$overstays = $db->fetchAll("
    SELECT vl.*, v.plate_number, v.last_driver_name,
           TIMESTAMPDIFF(HOUR, vl.entry_time, NOW()) as hours_inside
    FROM vehicle_logs vl 
    JOIN vehicles v ON vl.vehicle_id = v.id 
    WHERE vl.status = 'inside' AND vl.facility_id = ? AND TIMESTAMPDIFF(HOUR, vl.entry_time, NOW()) > 72
    ORDER BY hours_inside DESC LIMIT 5", [$facility_id]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Vehicle Entry - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@4/dist/tesseract.min.js"></script>
    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { 
            background: #1a1a2e; 
            margin: 0; 
            padding: 0; 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
        }
        .app-container {
            max-width: 500px;
            margin: 0 auto;
            min-height: 100vh;
            background: #f0f2f5;
        }
        .header-bar {
            background: linear-gradient(135deg, #198754 0%, #146c43 100%);
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-bar h1 { margin: 0; font-size: 1.2rem; font-weight: 600; }
        .header-bar .badge { background: rgba(255,255,255,0.2); font-size: 0.75rem; }
        
        .camera-section {
            background: #000;
            position: relative;
            aspect-ratio: 16/9;
            overflow: hidden;
            display: none;
        }
        .camera-section.active { display: block; }
        .camera-section.active #camera-preview { display: block !important; }
        #camera-preview {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        #canvas-capture { display: none; }
        
        .camera-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            pointer-events: none;
        }
        .plate-frame {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 80%;
            height: 30%;
            border: 4px dashed rgba(255,255,255,0.9);
            border-radius: 15px;
            background: rgba(0,0,0,0.15);
            box-shadow: 0 0 30px rgba(255,255,255,0.2);
            transition: all 0.3s;
        }
        .plate-frame::after {
            content: 'ALIGN LICENSE PLATE HERE';
            position: absolute;
            bottom: -35px;
            left: 50%;
            transform: translateX(-50%);
            color: white;
            font-size: 0.85rem;
            font-weight: 600;
            white-space: nowrap;
            text-shadow: 2px 2px 4px black;
        }
        
        .camera-controls {
            position: absolute;
            bottom: 15px;
            left: 0; right: 0;
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        .cam-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 3px solid white;
            background: rgba(255,255,255,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .cam-btn:active { transform: scale(0.95); background: rgba(255,255,255,0.5); }
        .cam-btn i { color: white; font-size: 1.5rem; }
        .cam-btn.capture { background: #198754; border-color: #198754; }
        .cam-btn.capture:active { background: #146c43; }
        
        .auto-badge {
            position: absolute;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            background: #198754;
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 700;
            animation: pulse 1.5s infinite;
            box-shadow: 0 3px 10px rgba(0,0,0,0.3);
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: translateX(-50%) scale(1); }
            50% { opacity: 0.8; transform: translateX(-50%) scale(1.05); }
        }
        .camera-mode-toggle {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
        }
        .toggle-switch {
            position: relative;
            width: 60px;
            height: 30px;
        }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            border-radius: 30px;
            transition: 0.3s;
        }
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background: white;
            border-radius: 50%;
            transition: 0.3s;
        }
        .toggle-switch input:checked + .toggle-slider { background: #198754; }
        .toggle-switch input:checked + .toggle-slider:before { transform: translateX(30px); }
        .auto-label {
            color: white;
            font-size: 0.7rem;
            text-align: center;
            margin-top: 2px;
        }
        
        .captured-image {
            display: none;
            width: 100%;
            aspect-ratio: 16/9;
            object-fit: cover;
            border: 4px solid #198754;
            border-radius: 10px;
            margin: 10px 0;
        }
        
        .form-section { padding: 15px; }
        .plate-input-group { position: relative; margin-bottom: 15px; }
        .plate-input {
            font-size: 1.5rem;
            font-weight: bold;
            text-align: center;
            letter-spacing: 2px;
            padding: 15px;
            text-transform: uppercase;
            border: 2px solid #198754;
            border-radius: 10px;
        }
        .plate-input:focus { 
            border-color: #198754;
            box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.2);
        }
        
        .form-group { margin-bottom: 12px; }
        .form-label { font-weight: 600; font-size: 0.85rem; color: #444; }
        .form-control, .form-select {
            padding: 12px;
            font-size: 1rem;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .form-control:focus, .form-select:focus {
            border-color: #198754;
            box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.1);
        }
        
        .btn-scan {
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            color: white;
            border: none;
            padding: 15px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 10px;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            margin-bottom: 15px;
        }
        .btn-scan:active { transform: scale(0.98); }
        
        .btn-submit {
            background: linear-gradient(135deg, #198754 0%, #146c43 100%);
            color: white;
            border: none;
            padding: 18px;
            font-size: 1.1rem;
            font-weight: 700;
            border-radius: 10px;
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .btn-submit:active { transform: scale(0.98); }
        
        .alert-box {
            padding: 15px;
            border-radius: 10px;
            margin: 15px;
            text-align: center;
        }
        .alert-success { background: #d1e7dd; color: #0a3622; border: 1px solid #a3cfbb; }
        .alert-danger { background: #f8d7da; color: #58151c; border: 1px solid #f1aeb5; }
        .alert-info { background: #cff4fc; color: #055160; border: 1px solid #b6effb; }
        
        .recent-section { padding: 0 15px 15px; }
        .section-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #ddd;
        }
        
        .entry-card {
            background: white;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .entry-plate { font-weight: 700; font-size: 1.1rem; color: #1a1a2e; }
        .entry-details { font-size: 0.8rem; color: #666; }
        .entry-time { font-size: 0.75rem; color: #999; }
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .status-in { background: #d1e7dd; color: #146c43; }
        .status-out { background: #e9ecef; color: #6c757d; }
        
        .overstay-warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 10px;
            margin: 15px;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            padding: 15px;
        }
        .quick-btn {
            padding: 15px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            color: white;
        }
        .quick-btn i { font-size: 1.3rem; }
        .quick-btn.exit { background: #fd7e14; }
        .quick-btn.records { background: #6c757d; }
        .quick-btn.overstay { background: #dc3545; }
        .quick-btn.incident { background: #7209b7; }
        
        #ocr-result {
            text-align: center;
            padding: 15px;
            margin-top: 10px;
            border-radius: 10px;
            display: none;
            font-size: 1.1rem;
            font-weight: 600;
        }
        #ocr-result.loading { background: #fff3cd; color: #856404; }
        #ocr-result.found { background: #d1e7dd; color: #0a3622; border: 2px solid #198754; }
        #ocr-result.not-found { background: #f8d7da; color: #58151c; }
        
        .car-preview {
            background: #fff;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            gap: 15px;
            align-items: center;
            border: 2px solid #dee2e6;
        }
        .car-preview .car-icon {
            width: 80px;
            height: 60px;
            background: #f8f9fa;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }
        .car-preview .car-info { flex: 1; }
        .car-preview .car-plate { font-weight: 700; font-size: 1.2rem; }
        .car-preview .car-model { color: #666; }
    </style>
</head>
<body>
    <div class="app-container">
        <div class="header-bar">
            <div>
                <h1><i class="bi bi-car-front"></i> VEHICLE ENTRY</h1>
            </div>
            <div>
                <span class="badge"><i class="bi bi-person"></i> <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Officer'); ?></span>
            </div>
        </div>
        
        <?php if ($success): ?>
        <div class="alert-box alert-success">
            <i class="bi bi-check-circle" style="font-size: 2rem;"></i><br>
            <strong><?php echo $success; ?></strong>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert-box alert-danger">
            <i class="bi bi-exclamation-triangle" style="font-size: 2rem;"></i><br>
            <strong><?php echo $error; ?></strong>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($overstays)): ?>
        <div class="overstay-warning">
            <strong><i class="bi bi-exclamation-triangle"></i> OVERSTAY ALERT</strong>
            <?php foreach ($overstays as $o): ?>
            <div class="small"><?php echo $o['plate_number']; ?> - <?php echo $o['hours_inside']; ?>h inside</div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div class="quick-actions">
            <a href="exit-mobile.php" class="quick-btn exit">
                <i class="bi bi-arrow-left-circle"></i>
                VEHICLE OUT
            </a>
            <a href="search.php" class="quick-btn records">
                <i class="bi bi-search"></i>
                SEARCH
            </a>
            <a href="overstay.php" class="quick-btn overstay">
                <i class="bi bi-clock"></i>
                OVERSTAY
            </a>
            <a href="../incidents/report.php" class="quick-btn incident">
                <i class="bi bi-exclamation-circle"></i>
                INCIDENT
            </a>
        </div>
        
        <div class="camera-section" id="camera-section">
            <video id="camera-preview" autoplay playsinline></video>
            <div class="camera-overlay">
                <div class="plate-frame" id="plate-frame"></div>
            </div>
            <div id="scan-status" class="auto-badge">
                <i class="bi bi-search"></i> SCANNING...
            </div>
            <div id="detection-info" style="position:absolute;bottom:80px;left:0;right:0;text-align:center;color:white;font-size:0.9rem;text-shadow:1px 1px 2px black;">
                <span id="motion-text">Point camera at vehicle plate</span>
            </div>
            <div class="camera-controls" id="camera-controls">
                <button type="button" class="cam-btn" id="btn-switch-cam" title="Switch Camera">
                    <i class="bi bi-camera-fill"></i>
                </button>
                <button type="button" class="cam-btn" id="btn-close-cam" title="Close Camera">
                    <i class="bi bi-x-circle"></i>
                </button>
            </div>
        </div>
        
        <img id="captured-image" class="captured-image" alt="Captured">
        <canvas id="canvas-capture"></canvas>
        
        <div class="form-section">
            <button type="button" class="btn-scan" id="btn-open-camera">
                <i class="bi bi-camera-video"></i> START AUTO-SCAN
            </button>
            
            <div id="ocr-result"></div>
            
            <form method="POST" id="entry-form">
                <div class="plate-input-group">
                    <input type="text" name="plate_number" id="plate_number" 
                           class="form-control plate-input" 
                           placeholder="UAR 123X" 
                           required 
                           autocomplete="off"
                           autofocus>
                </div>
                
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">Car Color</label>
                            <select name="car_color" class="form-select" id="car_color">
                                <option value="">Select Color</option>
                                <option value="White">White</option>
                                <option value="Black">Black</option>
                                <option value="Silver">Silver/Grey</option>
                                <option value="Blue">Blue</option>
                                <option value="Red">Red</option>
                                <option value="Green">Green</option>
                                <option value="Brown">Brown/Beige</option>
                                <option value="Yellow">Yellow/Gold</option>
                                <option value="Orange">Orange</option>
                                <option value="Purple">Purple</option>
                                <option value="Pink">Pink</option>
                                <option value="Maroon">Maroon/Burgundy</option>
                                <option value="Navy">Navy/Dark Blue</option>
                                <option value="Turquoise">Turquoise</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">Car Model</label>
                            <select name="car_model" class="form-select" id="car_model">
                                <option value="">Select Model</option>
                                <option value="Toyota Noah">Toyota Noah</option>
                                <option value="Toyota Wish">Toyota Wish</option>
                                <option value="Toyota Alphard">Toyota Alphard</option>
                                <option value="Toyota Corolla">Toyota Corolla</option>
                                <option value="Toyota Premio">Toyota Premio</option>
                                <option value="Toyota IST">Toyota IST</option>
                                <option value="Toyota Runx">Toyota Runx</option>
                                <option value="Toyota Crown">Toyota Crown</option>
                                <option value="Toyota Landcruiser">Toyota Landcruiser</option>
                                <option value="Toyota Prado">Toyota Prado</option>
                                <option value="Toyota Hilux">Toyota Hilux</option>
                                <option value="Toyota Harrier">Toyota Harrier</option>
                                <option value="Toyota RAV4">Toyota RAV4</option>
                                <option value="Nissan Tiida">Nissan Tiida</option>
                                <option value="Nissan Sentra">Nissan Sentra</option>
                                <option value="Nissan X-Trail">Nissan X-Trail</option>
                                <option value="Nissan Patrol">Nissan Patrol</option>
                                <option value="Honda Accord">Honda Accord</option>
                                <option value="Honda Civic">Honda Civic</option>
                                <option value="Honda CRV">Honda CR-V</option>
                                <option value="Honda Fit">Honda Fit</option>
                                <option value="Honda Pilot">Honda Pilot</option>
                                <option value="Subaru Forester">Subaru Forester</option>
                                <option value="Mitsubishi Pajero">Mitsubishi Pajero</option>
                                <option value="Mitsubishi L200">Mitsubishi L200</option>
                                <option value="Isuzu D-Max">Isuzu D-Max</option>
                                <option value="Ford Ranger">Ford Ranger</option>
                                <option value="Mercedes-Benz">Mercedes-Benz</option>
                                <option value="BMW">BMW</option>
                                <option value="Audi">Audi</option>
                                <option value="Land Rover">Land Rover</option>
                                <option value="Jeep Cherokee">Jeep Cherokee</option>
                                <option value="Hyundai Tucson">Hyundai Tucson</option>
                                <option value="Hyundai Santa Fe">Hyundai Santa Fe</option>
                                <option value="Kia Sportage">Kia Sportage</option>
                                <option value="Tata Pickup">Tata Pickup</option>
                                <option value="Minibus">Minibus</option>
                                <option value="Bus">Bus</option>
                                <option value="Truck">Truck</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Driver Name *</label>
                    <input type="text" name="driver_name" id="driver_name" 
                           class="form-control" 
                           placeholder="Enter driver name"
                           required
                           autocomplete="off">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="visitor_type" class="form-select">
                        <option value="delivery">Delivery</option>
                        <option value="inmate">Inmate Visitor</option>
                        <option value="hospital">Hospital/Emergency</option>
                        <option value="official">Official Visit</option>
                        <option value="staff">Staff Vehicle</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Cargo Description</label>
                    <input type="text" name="cargo_description" class="form-control" 
                           placeholder="Describe goods if delivery">
                </div>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="cargo_checked" id="cargo_checked" value="1">
                    <label class="form-check-label" for="cargo_checked">Cargo checked & cleared</label>
                </div>
                
                <button type="submit" class="btn-submit mt-3">
                    <i class="bi bi-check-circle"></i> REGISTER ENTRY
                </button>
            </form>
        </div>
        
        <div class="recent-section">
            <div class="section-title"><i class="bi bi-clock-history"></i> TODAY'S ENTRIES (<?php echo count($todayEntries); ?>)</div>
            
            <?php if ($todayEntries): ?>
                <?php foreach (array_slice($todayEntries, 0, 10) as $e): ?>
                <div class="entry-card">
                    <div>
                        <div class="entry-plate"><?php echo $e['plate_number']; ?></div>
                        <div class="entry-details">
                            <?php echo htmlspecialchars($e['driver_name'] ?: $e['last_driver_name']); ?> 
                            <?php if ($e['make_model']): ?>• <?php echo $e['make_model']; ?><?php endif; ?>
                            <?php if ($e['color']): ?>• <?php echo $e['color']; ?><?php endif; ?>
                        </div>
                        <div class="entry-time"><?php echo date('H:i', strtotime($e['entry_time'])); ?></div>
                    </div>
                    <span class="status-badge <?php echo $e['status'] === 'inside' ? 'status-in' : 'status-out'; ?>">
                        <?php echo $e['status'] === 'inside' ? 'IN' : 'OUT'; ?>
                    </span>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted text-center">No entries recorded today</p>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    (function() {
        const video = document.getElementById('camera-preview');
        const canvas = document.getElementById('canvas-capture');
        const capturedImg = document.getElementById('captured-image');
        const cameraSection = document.getElementById('camera-section');
        const ocrResult = document.getElementById('ocr-result');
        const plateInput = document.getElementById('plate_number');
        const driverInput = document.getElementById('driver_name');
        const scanStatus = document.getElementById('scan-status');
        const motionText = document.getElementById('motion-text');
        const plateFrame = document.getElementById('plate-frame');
        
        let stream = null;
        let isProcessing = false;
        let lastFrame = null;
        let lastPlate = '';
        let scanCooldown = false;
        let continuousScanInterval = null;
        
        async function openCamera() {
            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } },
                    audio: false
                });
                video.srcObject = stream;
                video.style.display = 'block';
                capturedImg.style.display = 'none';
                cameraSection.classList.add('active');
                ocrResult.style.display = 'none';
                scanStatus.style.display = 'block';
                scanStatus.innerHTML = '<i class="bi bi-search"></i> SCANNING...';
                scanStatus.style.background = '#198754';
                
                startContinuousScan();
            } catch (err) {
                alert('Camera access denied or unavailable');
                console.error(err);
            }
        }
        
        function closeCamera() {
            stopContinuousScan();
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
            cameraSection.classList.remove('active');
        }
        
        function startContinuousScan() {
            if (continuousScanInterval) return;
            
            lastFrame = null;
            
            continuousScanInterval = setInterval(async () => {
                if (isProcessing || scanCooldown || !video.videoWidth) return;
                
                isProcessing = true;
                
                try {
                    const motionDetected = detectMotion();
                    
                    if (motionDetected) {
                        motionText.textContent = 'Vehicle detected! Analyzing...';
                        plateFrame.style.borderColor = '#ffc107';
                        
                        const plate = await quickOCR();
                        
                        if (plate && plate !== lastPlate) {
                            lastPlate = plate;
                            plateInput.value = plate;
                            plateInput.focus();
                            
                            scanStatus.innerHTML = '<i class="bi bi-check-circle"></i> PLATE READ!';
                            scanStatus.style.background = '#198754';
                            motionText.textContent = `DETECTED: ${plate}`;
                            plateFrame.style.borderColor = '#198754';
                            plateFrame.style.borderStyle = 'solid';
                            
                            playBeep();
                            
                            scanCooldown = true;
                            setTimeout(() => {
                                scanCooldown = false;
                                lastPlate = '';
                                motionText.textContent = 'Point camera at vehicle plate';
                                plateFrame.style.borderColor = 'rgba(255,255,255,0.8)';
                                plateFrame.style.borderStyle = 'dashed';
                                scanStatus.innerHTML = '<i class="bi bi-search"></i> SCANNING...';
                            }, 5000);
                        }
                    } else {
                        motionText.textContent = 'Point camera at vehicle plate';
                        plateFrame.style.borderColor = 'rgba(255,255,255,0.8)';
                    }
                } catch (err) {
                    console.log('Scan error:', err);
                }
                
                isProcessing = false;
            }, 800);
        }
        
        function stopContinuousScan() {
            if (continuousScanInterval) {
                clearInterval(continuousScanInterval);
                continuousScanInterval = null;
            }
        }
        
        function detectMotion() {
            if (!video.videoWidth) return false;
            
            const ctx = canvas.getContext('2d');
            const w = 160;
            const h = 90;
            canvas.width = w;
            canvas.height = h;
            ctx.drawImage(video, 0, 0, w, h);
            
            const currentFrame = ctx.getImageData(0, 0, w, h);
            
            if (!lastFrame) {
                lastFrame = currentFrame;
                return false;
            }
            
            let diff = 0;
            const threshold = 30;
            
            for (let i = 0; i < currentFrame.data.length; i += 16) {
                const r1 = currentFrame.data[i];
                const g1 = currentFrame.data[i + 1];
                const b1 = currentFrame.data[i + 2];
                
                const r2 = lastFrame.data[i];
                const g2 = lastFrame.data[i + 1];
                const b2 = lastFrame.data[i + 2];
                
                diff += Math.abs(r1 - r2) + Math.abs(g1 - g2) + Math.abs(b1 - b2);
            }
            
            lastFrame = currentFrame;
            
            const avgDiff = diff / (currentFrame.data.length / 4);
            
            return avgDiff > threshold;
        }
        
        async function quickOCR() {
            if (!video.videoWidth) return null;
            
            const ctx = canvas.getContext('2d');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            ctx.drawImage(video, 0, 0);
            
            try {
                const result = await Tesseract.recognize(canvas, 'eng', {
                    logger: m => console.log(m.status, m.progress)
                });
                
                const text = result.data.text;
                return extractPlate(text);
            } catch (err) {
                console.log('OCR error:', err);
                return null;
            }
        }
        
        function extractPlate(text) {
            text = text.toUpperCase().replace(/[^A-Z0-9\s]/g, ' ');
            text = text.replace(/\s+/g, ' ').trim();
            
            const patterns = [
                'UG\\s?([A-Z]{2,3})\\s?([0-9]{3,4}[A-Z]?)',
                'U\\s?([A-Z]{3})\\s?([0-9]{3,4}[A-Z]?)',
                '([A-Z]{2})\\s?([0-9]{4,5})',
                '([A-Z]{3})\\s?([0-9]{3,4})',
                '([0-9]{3,4}[A-Z]?)',
                '([A-Z]{2}[A-Z0-9]?\\s?[0-9]{3,4})'
            ];
            
            for (const pattern of patterns) {
                const regex = new RegExp(pattern);
                const match = text.match(regex);
                if (match) {
                    let plate = match[0].replace(/\s+/g, ' ').trim();
                    plate = plate.replace(/[^A-Z0-9\s]/g, '').trim();
                    if (plate.length >= 4 && /[A-Z]/.test(plate) && /[0-9]/.test(plate)) {
                        return plate;
                    }
                }
            }
            
            return null;
        }
        
        function playBeep() {
            try {
                const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioCtx.createOscillator();
                const gainNode = audioCtx.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(audioCtx.destination);
                
                oscillator.frequency.value = 880;
                oscillator.type = 'sine';
                gainNode.gain.value = 0.3;
                
                oscillator.start();
                setTimeout(() => {
                    oscillator.stop();
                }, 200);
                
                setTimeout(() => {
                    const osc2 = audioCtx.createOscillator();
                    osc2.connect(gainNode);
                    osc2.frequency.value = 1100;
                    osc2.start();
                    setTimeout(() => osc2.stop(), 150);
                }, 150);
            } catch (e) {
                console.log('Audio not available');
            }
        }
        
        document.getElementById('btn-open-camera').addEventListener('click', openCamera);
        document.getElementById('btn-close-cam').addEventListener('click', closeCamera);
        
        plateInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9\s]/g, '');
        });
    })();
    </script>
</body>
</html>
