<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireLogin();

$db = getDB();
$user = getCurrentUser();
$facility_id = $_SESSION['facility_id'] ?? 1;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'register') {
        $plate = strtoupper(sanitize($_POST['plate'] ?? ''));
        $car_color = sanitize($_POST['color'] ?? '');
        $car_model = sanitize($_POST['model'] ?? '');
        
        if (empty($plate)) {
            echo json_encode(['success' => false, 'message' => 'Plate required']);
            exit;
        }
        
        $vehicle = $db->fetchOne("SELECT * FROM vehicles WHERE plate_number = ?", [$plate]);
        
        if (!$vehicle) {
            $vehicle_id = $db->insert('vehicles', [
                'plate_number' => $plate,
                'color' => $car_color,
                'make_model' => $car_model,
                'last_visit' => date('Y-m-d H:i:s'),
                'total_visits' => 1
            ]);
        } else {
            $vehicle_id = $vehicle['id'];
            if ($vehicle['is_blacklisted']) {
                echo json_encode(['success' => false, 'message' => 'VEHICLE BLOCKED', 'blocked' => true]);
                exit;
            }
            $db->update('vehicles', [
                'color' => $car_color ?: $vehicle['color'],
                'make_model' => $car_model ?: $vehicle['make_model'],
                'last_visit' => date('Y-m-d H:i:s'),
                'total_visits' => ($vehicle['total_visits'] ?? 0) + 1
            ], 'id = :id', ['id' => $vehicle_id]);
        }
        
        $log_id = $db->insert('vehicle_logs', [
            'vehicle_id' => $vehicle_id,
            'facility_id' => $facility_id,
            'visitor_type' => 'delivery',
            'entry_time' => date('Y-m-d H:i:s'),
            'status' => 'inside',
            'gate_officer_entry_id' => $user['id']
        ]);
        
        logAction('vehicle_entry', 'vehicle_logs', $log_id, null, [
            'plate' => $plate,
            'color' => $car_color,
            'model' => $car_model
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'VEHICLE REGISTERED',
            'plate' => $plate,
            'time' => date('H:i:s')
        ]);
        exit;
    }
    
    if ($action === 'get_entries') {
        $entries = $db->fetchAll("
            SELECT vl.*, v.plate_number, v.color, v.make_model 
            FROM vehicle_logs vl 
            JOIN vehicles v ON vl.vehicle_id = v.id 
            WHERE DATE(vl.entry_time) = CURDATE() AND vl.facility_id = ?
            ORDER BY vl.entry_time DESC LIMIT 20", [$facility_id]);
        
        echo json_encode($entries);
        exit;
    }
}

$todayCount = $db->fetchOne("
    SELECT COUNT(*) as cnt FROM vehicle_logs 
    WHERE DATE(entry_time) = CURDATE() AND facility_id = ?", [$facility_id])['cnt'];

$insideCount = $db->fetchOne("
    SELECT COUNT(*) as cnt FROM vehicle_logs 
    WHERE status = 'inside' AND facility_id = ?", [$facility_id])['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Auto Gate - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@4/dist/tesseract.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            background: #0a0a1a; 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            color: white;
        }
        .container {
            max-width: 100%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .header {
            background: linear-gradient(135deg, #198754 0%, #0d6e3f 100%);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 { font-size: 1.3rem; font-weight: 700; }
        .header .nav-links { display: flex; gap: 10px; }
        .header a { background: rgba(255,255,255,0.2); padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; color: white; text-decoration: none; }
        
        .camera-box {
            background: #000;
            aspect-ratio: 16/9;
            position: relative;
            overflow: hidden;
            flex: 1;
            max-height: 50vh;
        }
        #camera-preview {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .scan-frame {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 85%;
            height: 35%;
            border: 4px solid #fff;
            border-radius: 15px;
            background: rgba(255,255,255,0.05);
            box-shadow: 0 0 40px rgba(25, 135, 84, 0.5);
        }
        .scan-frame.active {
            border-color: #198754;
            animation: glow 1s infinite;
        }
        @keyframes glow {
            0%, 100% { box-shadow: 0 0 30px rgba(25, 135, 84, 0.5); }
            50% { box-shadow: 0 0 60px rgba(25, 135, 84, 0.9); }
        }
        
        .scan-status {
            position: absolute;
            top: 15px;
            left: 50%;
            transform: translateX(-50%);
            background: #198754;
            color: white;
            padding: 8px 25px;
            border-radius: 25px;
            font-weight: 700;
            font-size: 1rem;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .detected-plate {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.9);
            padding: 15px 30px;
            border-radius: 10px;
            text-align: center;
            border: 2px solid #198754;
            display: none;
        }
        .detected-plate.show { display: block; animation: slideUp 0.3s; }
        @keyframes slideUp {
            from { transform: translateX(-50%) translateY(50px); opacity: 0; }
            to { transform: translateX(-50%) translateY(0); opacity: 1; }
        }
        .detected-plate .plate-num { font-size: 2rem; font-weight: 800; color: #198754; letter-spacing: 3px; }
        .detected-plate .status { font-size: 0.8rem; color: #aaa; margin-top: 5px; }
        
        .result-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.95);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .result-overlay.show { display: flex; }
        .result-box {
            background: #1a1a2e;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            border: 3px solid #198754;
            max-width: 400px;
            width: 90%;
        }
        .result-box .icon { font-size: 4rem; color: #198754; margin-bottom: 20px; }
        .result-box .plate { font-size: 2.5rem; font-weight: 800; color: #198754; letter-spacing: 4px; }
        .result-box .time { font-size: 1.2rem; color: #888; margin-top: 10px; }
        .result-box .msg { font-size: 1rem; color: #ccc; margin-top: 15px; }
        
        .blocked-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(220, 53, 69, 0.95);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .blocked-overlay.show { display: flex; }
        .blocked-box {
            background: #fff;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        .blocked-box .icon { font-size: 4rem; color: #dc3545; margin-bottom: 20px; }
        .blocked-box .plate { font-size: 2rem; font-weight: 800; color: #dc3545; }
        
        .stats-bar {
            background: #1a1a2e;
            padding: 15px 20px;
            display: flex;
            justify-content: space-around;
        }
        .stat-item { text-align: center; }
        .stat-item .num { font-size: 1.8rem; font-weight: 800; color: #198754; }
        .stat-item .label { font-size: 0.75rem; color: #888; text-transform: uppercase; }
        
        .entries-list {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
        }
        .entries-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #333;
        }
        .entry-row {
            background: #1a1a2e;
            border-radius: 10px;
            padding: 12px 15px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .entry-row .plate { font-weight: 700; font-size: 1.1rem; }
        .entry-row .time { font-size: 0.8rem; color: #888; }
        .entry-row .badge-in { background: #198754; color: white; padding: 3px 10px; border-radius: 10px; font-size: 0.7rem; }
        .entry-row .badge-out { background: #6c757d; color: white; padding: 3px 10px; border-radius: 10px; font-size: 0.7rem; }
        
        .btn-restart {
            background: #198754;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            margin-top: 20px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="bi bi-car-front"></i> AUTO GATE</h1>
            <div class="nav-links">
                <a href="auto-exit.php"><i class="bi bi-box-arrow-left"></i> EXIT</a>
                <span style="background:rgba(255,255,255,0.2);padding:5px 12px;border-radius:20px;font-size:0.85rem;">
                    <i class="bi bi-person"></i> <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Officer'); ?>
                </span>
            </div>
        </div>
        
        <div class="camera-box">
            <video id="camera-preview" autoplay playsinline></video>
            <div class="scan-frame" id="scan-frame"></div>
            <div class="scan-status" id="scan-status">
                <i class="bi bi-search"></i> SCANNING...
            </div>
            <div class="detected-plate" id="detected-plate">
                <div class="plate-num" id="plate-display">--- ---</div>
                <div class="status">Processing...</div>
            </div>
        </div>
        
        <div class="stats-bar">
            <div class="stat-item">
                <div class="num" id="today-count"><?php echo $todayCount; ?></div>
                <div class="label">Today</div>
            </div>
            <div class="stat-item">
                <div class="num" id="inside-count"><?php echo $insideCount; ?></div>
                <div class="label">Inside</div>
            </div>
            <div class="stat-item">
                <div class="num" id="last-time">--:--</div>
                <div class="label">Last Entry</div>
            </div>
        </div>
        
        <div class="entries-list">
            <div class="entries-title">Today's Entries</div>
            <div id="entries-container"></div>
        </div>
    </div>
    
    <div class="result-overlay" id="result-overlay">
        <div class="result-box">
            <div class="icon"><i class="bi bi-check-circle-fill"></i></div>
            <div class="plate" id="result-plate">---</div>
            <div class="time" id="result-time">--:--:--</div>
            <div class="msg">Vehicle Registered Successfully</div>
            <button class="btn-restart" onclick="closeResult()">
                <i class="bi bi-arrow-clockwise"></i> SCAN NEXT
            </button>
        </div>
    </div>
    
    <div class="blocked-overlay" id="blocked-overlay">
        <div class="blocked-box">
            <div class="icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div class="plate" id="blocked-plate">---</div>
            <p style="color:#333;margin-top:10px;">Vehicle is blocked!</p>
            <button class="btn-restart" onclick="closeBlocked()" style="background:#dc3545;">
                CLOSE
            </button>
        </div>
    </div>
    
    <script>
    (function() {
        const video = document.getElementById('camera-preview');
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        const scanFrame = document.getElementById('scan-frame');
        const scanStatus = document.getElementById('scan-status');
        const detectedPlate = document.getElementById('detected-plate');
        const plateDisplay = document.getElementById('plate-display');
        
        let stream = null;
        let lastFrame = null;
        let lastPlate = '';
        let isProcessing = false;
        let lastDetection = 0;
        let scanInterval = null;
        
        async function initCamera() {
            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'environment', width: { ideal: 1280 } },
                    audio: false
                });
                video.srcObject = stream;
                startScanning();
            } catch (err) {
                console.error('Camera error:', err);
                scanStatus.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Camera Error';
            }
        }
        
        function startScanning() {
            scanFrame.classList.add('active');
            scanStatus.innerHTML = '<i class="bi bi-search"></i> SCANNING...';
            
            scanInterval = setInterval(scanFrame, 1500);
        }
        
        async function scanFrame() {
            if (isProcessing || !video.videoWidth) return;
            
            const now = Date.now();
            if (now - lastDetection < 8000) return;
            
            isProcessing = true;
            
            ctx.drawImage(video, 0, 0, video.videoWidth, video.videoHeight);
            
            try {
                scanStatus.innerHTML = '<i class="bi bi-gear"></i> Reading...';
                
                const result = await Tesseract.recognize(canvas, 'eng', {
                    logger: m => {
                        if (m.status === 'recognizing text') {
                            scanStatus.innerHTML = `<i class="bi bi-gear"></i> ${Math.round(m.progress * 100)}%`;
                        }
                    }
                });
                
                const plate = extractPlate(result.data.text);
                
                if (plate && plate !== lastPlate) {
                    lastPlate = plate;
                    lastDetection = now;
                    
                    plateDisplay.textContent = plate;
                    detectedPlate.classList.add('show');
                    scanStatus.innerHTML = '<i class="bi bi-check-circle"></i> PLATE DETECTED!';
                    
                    playBeep();
                    
                    setTimeout(() => registerVehicle(plate), 1000);
                } else {
                    scanStatus.innerHTML = '<i class="bi bi-search"></i> SCANNING...';
                }
            } catch (err) {
                console.error('OCR error:', err);
            }
            
            isProcessing = false;
        }
        
        function extractPlate(text) {
            text = text.toUpperCase().replace(/[^A-Z0-9\s]/g, ' ').replace(/\s+/g, ' ').trim();
            
            const patterns = [
                'UG\\s?([A-Z]{2,3})\\s?([0-9]{3,4}[A-Z]?)',
                'U\\s?([A-Z]{3})\\s?([0-9]{3,4})',
                '([A-Z]{2,3})\\s?([0-9]{3,5})',
                '([A-Z]{3})([0-9]{3,4})',
            ];
            
            for (const p of patterns) {
                const m = text.match(new RegExp(p));
                if (m) {
                    let plate = m[0].replace(/\s+/g, ' ').trim();
                    if (plate.length >= 4) return plate;
                }
            }
            
            const nums = text.match(/[0-9]{3,5}/g);
            const lets = text.match(/[A-Z]{2,3}/g);
            if (nums && lets) {
                for (const l of lets) {
                    for (const n of nums) {
                        const p = `${l} ${n}`;
                        if (p.length >= 5) return p;
                    }
                }
            }
            
            return null;
        }
        
        function registerVehicle(plate) {
            detectedPlate.classList.remove('show');
            
            const formData = new FormData();
            formData.append('action', 'register');
            formData.append('plate', plate);
            formData.append('color', '');
            formData.append('model', '');
            
            fetch(window.location.pathname, {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showResult(data.plate, data.time);
                    updateStats();
                    refreshEntries();
                } else if (data.blocked) {
                    showBlocked(plate);
                } else {
                    scanStatus.innerHTML = '<i class="bi bi-x-circle"></i> Error - Retry';
                    lastPlate = '';
                    setTimeout(() => {
                        lastDetection = 0;
                        lastPlate = '';
                    }, 3000);
                }
            })
            .catch(err => {
                console.error('Register error:', err);
                scanStatus.innerHTML = '<i class="bi bi-x-circle"></i> Network Error';
            });
        }
        
        function showResult(plate, time) {
            document.getElementById('result-plate').textContent = plate;
            document.getElementById('result-time').textContent = 'Entry Time: ' + time;
            document.getElementById('result-overlay').classList.add('show');
            lastPlate = '';
            lastDetection = 0;
        }
        
        function showBlocked(plate) {
            document.getElementById('blocked-plate').textContent = plate;
            document.getElementById('blocked-overlay').classList.add('show');
        }
        
        function closeResult() {
            document.getElementById('result-overlay').classList.remove('show');
        }
        
        function closeBlocked() {
            document.getElementById('blocked-overlay').classList.remove('show');
            lastPlate = '';
            lastDetection = 0;
        }
        
        function updateStats() {
            const todayEl = document.getElementById('today-count');
            const insideEl = document.getElementById('inside-count');
            const lastTimeEl = document.getElementById('last-time');
            
            todayEl.textContent = parseInt(todayEl.textContent) + 1;
            insideEl.textContent = parseInt(insideEl.textContent) + 1;
            lastTimeEl.textContent = new Date().toLocaleTimeString('en-US', { hour12: false, hour: '2-digit', minute: '2-digit' });
        }
        
        function refreshEntries() {
            fetch(window.location.pathname + '?action=get_entries', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_entries'
            })
            .then(r => r.json())
            .then(data => {
                const container = document.getElementById('entries-container');
                container.innerHTML = '';
                
                data.forEach(e => {
                    const time = new Date(e.entry_time).toLocaleTimeString('en-US', { hour12: false, hour: '2-digit', minute: '2-digit' });
                    container.innerHTML += `
                        <div class="entry-row">
                            <div>
                                <div class="plate">${e.plate_number}</div>
                                <div class="time">${time}</div>
                            </div>
                            <span class="badge-${e.status}">${e.status === 'inside' ? 'IN' : 'OUT'}</span>
                        </div>
                    `;
                });
            });
        }
        
        function playBeep() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.frequency.value = 880;
                gain.gain.value = 0.3;
                osc.start();
                setTimeout(() => osc.stop(), 150);
            } catch(e) {}
        }
        
        initCamera();
        refreshEntries();
        setInterval(refreshEntries, 10000);
    })();
    </script>
</body>
</html>
