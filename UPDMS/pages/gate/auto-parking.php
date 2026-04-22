<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireLogin();

$db = getDB();
$user = getCurrentUser();
$facility_id = $_SESSION['facility_id'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    
    if ($action === 'register_entry') {
        $plate = strtoupper(sanitize($_POST['plate'] ?? ''));
        
        if (empty($plate)) {
            echo json_encode(['success' => false, 'message' => 'Plate required']);
            exit;
        }
        
        $check = $db->fetchOne("
            SELECT vl.* FROM vehicle_logs vl 
            JOIN vehicles v ON vl.vehicle_id = v.id 
            WHERE v.plate_number = ? AND vl.status = 'inside' AND vl.visitor_type = 'parking'", [$plate]);
        
        if ($check) {
            echo json_encode(['success' => false, 'message' => 'Already inside', 'duplicate' => true]);
            exit;
        }
        
        $vehicle = $db->fetchOne("SELECT * FROM vehicles WHERE plate_number = ?", [$plate]);
        
        if (!$vehicle) {
            $vehicle_id = $db->insert('vehicles', [
                'plate_number' => $plate,
                'last_visit' => date('Y-m-d H:i:s'),
                'total_visits' => 1
            ]);
        } else {
            $vehicle_id = $vehicle['id'];
            $db->update('vehicles', [
                'last_visit' => date('Y-m-d H:i:s'),
                'total_visits' => ($vehicle['total_visits'] ?? 0) + 1
            ], 'id = :id', ['id' => $vehicle_id]);
        }
        
        $log_id = $db->insert('vehicle_logs', [
            'vehicle_id' => $vehicle_id,
            'facility_id' => $facility_id,
            'visitor_type' => 'parking',
            'entry_time' => date('Y-m-d H:i:s'),
            'status' => 'inside',
            'gate_officer_entry_id' => $user['id']
        ]);
        
        echo json_encode([
            'success' => true,
            'plate' => $plate,
            'time' => date('H:i:s')
        ]);
        exit;
    }
    
    if ($action === 'register_exit') {
        $plate = strtoupper(sanitize($_POST['plate'] ?? ''));
        
        $vehicle = $db->fetchOne("SELECT * FROM vehicles WHERE plate_number = ?", [$plate]);
        
        if (!$vehicle) {
            echo json_encode(['success' => false, 'message' => 'Not found', 'notfound' => true]);
            exit;
        }
        
        $log = $db->fetchOne("
            SELECT * FROM vehicle_logs 
            WHERE vehicle_id = ? AND status = 'inside' AND visitor_type = 'parking' 
            ORDER BY entry_time DESC LIMIT 1", [$vehicle['id']]);
        
        if (!$log) {
            echo json_encode(['success' => false, 'message' => 'No entry', 'noentry' => true]);
            exit;
        }
        
        $exit_time = date('Y-m-d H:i:s');
        $duration = (strtotime($exit_time) - strtotime($log['entry_time'])) / 60;
        
        $db->update('vehicle_logs', [
            'exit_time' => $exit_time,
            'duration_minutes' => (int)$duration,
            'status' => 'exited',
            'gate_officer_exit_id' => $user['id']
        ], 'id = :id', ['id' => $log['id']]);
        
        $hours = floor($duration / 60);
        $mins = floor($duration % 60);
        
        echo json_encode([
            'success' => true,
            'plate' => $plate,
            'entry_time' => date('H:i', strtotime($log['entry_time'])),
            'exit_time' => date('H:i:s'),
            'duration' => ($hours > 0 ? $hours . 'h ' : '') . $mins . 'm'
        ]);
        exit;
    }
    
    if ($action === 'get_stats') {
        $today = $db->fetchOne("SELECT COUNT(*) as cnt FROM vehicle_logs WHERE DATE(entry_time) = CURDATE() AND visitor_type = 'parking'", [])['cnt'];
        $inside = $db->fetchOne("SELECT COUNT(*) as cnt FROM vehicle_logs WHERE status = 'inside' AND visitor_type = 'parking'", [])['cnt'];
        echo json_encode(['today' => $today, 'inside' => $inside]);
        exit;
    }
    
    if ($action === 'get_recent') {
        $entries = $db->fetchAll("
            SELECT vl.*, v.plate_number 
            FROM vehicle_logs vl 
            JOIN vehicles v ON vl.vehicle_id = v.id 
            WHERE vl.visitor_type = 'parking' 
            ORDER BY vl.entry_time DESC LIMIT 15", []);
        echo json_encode($entries);
        exit;
    }
    
    if ($action === 'get_inside') {
        $entries = $db->fetchAll("
            SELECT vl.*, v.plate_number 
            FROM vehicle_logs vl 
            JOIN vehicles v ON vl.vehicle_id = v.id 
            WHERE vl.status = 'inside' AND vl.visitor_type = 'parking' 
            ORDER BY vl.entry_time DESC", []);
        echo json_encode($entries);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Auto Parking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@aspectron/lp-recognition@1.0.0/dist/lp-recognition.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            background: #0a0a1a; 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            color: white;
            overflow: hidden;
        }
        .container { max-width: 100%; height: 100vh; display: flex; flex-direction: column; }
        
        .header {
            background: linear-gradient(135deg, #198754 0%, #0d6e3f 100%);
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }
        .header h1 { font-size: 1.1rem; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        .header a { background: rgba(255,255,255,0.2); padding: 8px 15px; border-radius: 20px; color: white; text-decoration: none; font-size: 0.85rem; }
        
        .camera-container {
            background: #000;
            flex: 1;
            position: relative;
            overflow: hidden;
            min-height: 300px;
        }
        #camera-preview { width: 100%; height: 100%; object-fit: cover; }
        #overlay-canvas { 
            position: absolute; 
            top: 0; left: 0; 
            width: 100%; height: 100%;
            pointer-events: none;
        }
        
        .scan-box {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 85%;
            height: 28%;
            border: 4px solid #198754;
            border-radius: 15px;
            box-shadow: 0 0 40px rgba(25, 135, 84, 0.5);
        }
        .scan-box::before {
            content: 'ALIGN LICENSE PLATE';
            position: absolute;
            bottom: -35px;
            left: 50%;
            transform: translateX(-50%);
            color: white;
            font-size: 0.85rem;
            font-weight: 600;
            text-shadow: 1px 1px 3px black;
        }
        
        .status-banner {
            position: absolute;
            top: 15px;
            left: 50%;
            transform: translateX(-50%);
            padding: 10px 25px;
            border-radius: 30px;
            font-weight: 700;
            font-size: 0.95rem;
            background: #198754;
            z-index: 10;
        }
        .status-banner.scanning { background: #198754; animation: pulse 1.5s infinite; }
        .status-banner.detected { background: #ffc107; color: #000; }
        .status-banner.success { background: #198754; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }
        
        .plate-display {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.9);
            padding: 20px 40px;
            border-radius: 15px;
            text-align: center;
            border: 3px solid #198754;
            min-width: 250px;
            display: none;
        }
        .plate-display.show { display: block; animation: slideUp 0.3s; }
        @keyframes slideUp {
            from { transform: translateX(-50%) translateY(50px); opacity: 0; }
            to { transform: translateX(-50%) translateY(0); opacity: 1; }
        }
        .plate-display .label { font-size: 0.75rem; color: #888; margin-bottom: 5px; }
        .plate-display .plate { font-size: 2.5rem; font-weight: 900; color: #198754; letter-spacing: 4px; }
        .plate-display .time { font-size: 1rem; color: #888; margin-top: 8px; }
        
        .bottom-panel {
            background: #16213e;
            padding: 15px 20px;
            flex-shrink: 0;
        }
        
        .stats-row {
            display: flex;
            justify-content: space-around;
            margin-bottom: 15px;
        }
        .stat { text-align: center; }
        .stat .num { font-size: 2rem; font-weight: 800; color: #198754; }
        .stat .lbl { font-size: 0.65rem; color: #888; text-transform: uppercase; }
        
        .mode-tabs {
            display: flex;
            gap: 10px;
        }
        .mode-tab {
            flex: 1;
            padding: 12px;
            border-radius: 10px;
            text-align: center;
            background: #1a1a2e;
            border: 2px solid #333;
            color: #888;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            text-decoration: none;
        }
        .mode-tab.active { background: #198754; border-color: #198754; color: white; }
        .mode-tab.exit { border-color: #fd7e14; }
        .mode-tab.exit.active { background: #fd7e14; border-color: #fd7e14; }
        
        /* Result Overlay */
        .result-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.95);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 100;
        }
        .result-overlay.show { display: flex; }
        .result-box {
            text-align: center;
            animation: popIn 0.3s;
        }
        @keyframes popIn {
            from { transform: scale(0.5); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        .result-box .icon { font-size: 8rem; }
        .result-box .icon.green { color: #198754; }
        .result-box .icon.orange { color: #fd7e14; }
        .result-box .icon.red { color: #dc3545; }
        .result-box .plate { font-size: 3rem; font-weight: 900; letter-spacing: 5px; margin: 20px 0; }
        .result-box .plate.green { color: #198754; }
        .result-box .plate.orange { color: #fd7e14; }
        .result-box .plate.red { color: #dc3545; }
        .result-box .info { font-size: 1.2rem; color: #888; }
        .result-box .msg { font-size: 1rem; color: #ffc107; margin-top: 10px; }
        .result-box .auto-msg {
            font-size: 0.9rem;
            color: #888;
            margin-top: 15px;
            animation: blink 1s infinite;
        }
        @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        
        .recent-scroll {
            max-height: 120px;
            overflow-y: auto;
            margin-top: 10px;
        }
        .recent-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 12px;
            background: #1a1a2e;
            border-radius: 8px;
            margin-bottom: 6px;
            font-size: 0.85rem;
        }
        .recent-item .plate { font-weight: 700; }
        .recent-item .time { color: #888; font-size: 0.75rem; }
        .recent-item .badge { padding: 2px 8px; border-radius: 10px; font-size: 0.65rem; font-weight: 600; }
        .recent-item .badge.in { background: #198754; }
        .recent-item .badge.out { background: #6c757d; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="bi bi-qr-code-scan"></i> AUTO PARKING</h1>
            <a href="auto-parking-exit.php"><i class="bi bi-box-arrow-right"></i> EXIT</a>
        </div>
        
        <div class="camera-container">
            <video id="camera-preview" autoplay playsinline></video>
            <canvas id="overlay-canvas"></canvas>
            <div class="scan-box"></div>
            <div class="status-banner scanning" id="statusBanner">
                <i class="bi bi-search"></i> SCANNING...
            </div>
            <div class="plate-display" id="plateDisplay">
                <div class="label">DETECTED PLATE</div>
                <div class="plate" id="detectedPlate">---</div>
                <div class="time" id="detectedTime">--:--:--</div>
            </div>
        </div>
        
        <div class="bottom-panel">
            <div class="stats-row">
                <div class="stat">
                    <div class="num" id="statToday">0</div>
                    <div class="lbl">Today</div>
                </div>
                <div class="stat">
                    <div class="num" id="statInside">0</div>
                    <div class="lbl">Inside</div>
                </div>
                <div class="stat">
                    <div class="num" id="statLast">--:--</div>
                    <div class="lbl">Last</div>
                </div>
            </div>
            
            <div class="mode-tabs">
                <div class="mode-tab active">
                    <i class="bi bi-box-arrow-right"></i> ENTRY
                </div>
                <a href="auto-parking-exit.php" class="mode-tab exit">
                    <i class="bi bi-box-arrow-left"></i> EXIT
                </a>
            </div>
            
            <div class="recent-scroll" id="recentScroll"></div>
        </div>
    </div>
    
    <div class="result-overlay" id="successOverlay">
        <div class="result-box">
            <div class="icon green"><i class="bi bi-check-circle-fill"></i></div>
            <div class="plate green" id="successPlate">---</div>
            <div class="info">Entry Registered</div>
            <div class="msg">Vehicle Inside Successfully</div>
            <div class="auto-msg"><i class="bi bi-arrow-clockwise"></i> Scanning for next vehicle...</div>
        </div>
    </div>
    
    <div class="result-overlay" id="errorOverlay">
        <div class="result-box">
            <div class="icon red"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div class="plate red" id="errorPlate">---</div>
            <div class="info" id="errorInfo">Already Inside!</div>
            <div class="msg" id="errorMsg">This vehicle has already entered</div>
        </div>
    </div>

    <script>
    (function() {
        const video = document.getElementById('camera-preview');
        const statusBanner = document.getElementById('statusBanner');
        const plateDisplay = document.getElementById('plateDisplay');
        const detectedPlate = document.getElementById('detectedPlate');
        const detectedTime = document.getElementById('detectedTime');
        const successOverlay = document.getElementById('successOverlay');
        const errorOverlay = document.getElementById('errorOverlay');
        
        let currentPlate = '';
        let lastProcessed = 0;
        let scanActive = false;
        
        async function initCamera() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: { 
                        facingMode: 'environment',
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    }
                });
                video.srcObject = stream;
                await video.play();
                startScanning();
            } catch (err) {
                console.error('Camera error:', err);
                setStatus('Camera Error', 'red');
            }
        }
        
        function startScanning() {
            if (scanActive) return;
            scanActive = true;
            setInterval(processFrame, 1500);
        }
        
        async function processFrame() {
            if (!video.videoWidth || Date.now() - lastProcessed < 2000) return;
            
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0);
            
            setStatus('Analyzing...', 'scanning');
            
            try {
                const plate = await recognizePlate(canvas);
                
                if (plate && plate !== currentPlate) {
                    currentPlate = plate;
                    showDetected(plate);
                    registerEntry(plate);
                    lastProcessed = Date.now();
                } else if (!plate) {
                    setStatus('SCANNING...', 'scanning');
                }
            } catch (err) {
                console.error('Recognition error:', err);
                setStatus('SCANNING...', 'scanning');
            }
        }
        
        async function recognizePlate(canvas) {
            // Try license plate recognition library
            if (typeof LPR !== 'undefined') {
                try {
                    const result = await LPR.recognize(canvas);
                    if (result && result.plate) {
                        return result.plate.toUpperCase().replace(/[^A-Z0-9]/g, '');
                    }
                } catch (e) {}
            }
            
            // Fallback: Simple pattern matching on pixel data
            return simplePlateDetection(canvas);
        }
        
        function simplePlateDetection(canvas) {
            const ctx = canvas.getContext('2d');
            const width = canvas.width;
            const height = canvas.height;
            
            // Sample a horizontal strip where plates usually are
            const stripY = Math.floor(height * 0.45);
            const stripHeight = Math.floor(height * 0.15);
            
            // Look for high contrast regions (white text on dark background)
            const data = ctx.getImageData(0, stripY, width, stripHeight).data;
            
            let textRegion = '';
            let inText = false;
            let textStart = 0;
            const threshold = 200;
            
            for (let x = 0; x < width; x++) {
                let brightPixels = 0;
                for (let y = 0; y < stripHeight; y++) {
                    const idx = (y * width + x) * 4;
                    const r = data[idx];
                    const g = data[idx + 1];
                    const b = data[idx + 2];
                    const brightness = (r + g + b) / 3;
                    if (brightness > threshold) brightPixels++;
                }
                
                const ratio = brightPixels / stripHeight;
                
                if (ratio > 0.3 && !inText) {
                    inText = true;
                    textStart = x;
                } else if (ratio < 0.1 && inText) {
                    inText = false;
                }
            }
            
            // Use Tesseract.js as primary OCR
            return tesseractOCR(canvas);
        }
        
        async function tesseractOCR(canvas) {
            // Load Tesseract if not loaded
            if (!window.tesseractWorker) {
                window.tesseractWorker = await Tesseract.createWorker('eng', 1, {
                    logger: m => console.log(m)
                });
            }
            
            const { data: { text } } = await window.tesseractWorker.recognize(canvas);
            
            // Extract plate pattern from text
            const patterns = [
                /UG\s?[A-Z]{2,3}\s?[0-9]{3,4}/i,
                /[A-Z]{2,3}\s?[0-9]{3,5}/g,
                /[A-Z]{2}\s?[0-9]{4,5}/g,
                /[0-9]{3,4}\s?[A-Z]{2,3}/g
            ];
            
            for (const pattern of patterns) {
                const match = text.match(pattern);
                if (match) {
                    let plate = match[0].toUpperCase().replace(/\s+/g, ' ').trim();
                    // Validate: should have both letters and numbers
                    if (/[A-Z]/.test(plate) && /[0-9]/.test(plate)) {
                        return plate;
                    }
                }
            }
            
            // Fallback: look for any alphanumeric pattern that could be a plate
            const allMatches = text.match(/[A-Z0-9]{4,10}/g);
            if (allMatches) {
                for (const m of allMatches) {
                    if (/[A-Z]{2,3}/.test(m) && /[0-9]{3,5}/.test(m)) {
                        return m;
                    }
                }
            }
            
            return null;
        }
        
        function setStatus(text, type) {
            statusBanner.textContent = text;
            statusBanner.className = 'status-banner ' + type;
        }
        
        function showDetected(plate) {
            detectedPlate.textContent = plate;
            detectedTime.textContent = new Date().toLocaleTimeString();
            plateDisplay.classList.add('show');
            setStatus(plate, 'detected');
            playBeep();
        }
        
        function registerEntry(plate) {
            fetch(window.location.pathname, {
                method: 'POST',
                body: new URLSearchParams({ action: 'register_entry', plate: plate })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showSuccess(plate);
                    updateStats(data.time);
                    refreshRecent();
                } else if (data.duplicate) {
                    showError(plate, 'Already Inside', 'This vehicle has already entered');
                    setTimeout(() => hideError(), 3000);
                }
            })
            .catch(err => {
                console.error('Register error:', err);
            });
        }
        
        function showSuccess(plate) {
            document.getElementById('successPlate').textContent = plate;
            successOverlay.classList.add('show');
            playSuccess();
            
            setTimeout(() => {
                successOverlay.classList.remove('show');
                plateDisplay.classList.remove('show');
                currentPlate = '';
                lastProcessed = 0;
            }, 2500);
        }
        
        function showError(plate, info, msg) {
            document.getElementById('errorPlate').textContent = plate;
            document.getElementById('errorInfo').textContent = info;
            document.getElementById('errorMsg').textContent = msg;
            errorOverlay.classList.add('show');
            playError();
        }
        
        function hideError() {
            errorOverlay.classList.remove('show');
            plateDisplay.classList.remove('show');
            currentPlate = '';
        }
        
        function updateStats(time) {
            document.getElementById('statToday').textContent = parseInt(document.getElementById('statToday').textContent) + 1;
            document.getElementById('statInside').textContent = parseInt(document.getElementById('statInside').textContent) + 1;
            document.getElementById('statLast').textContent = time;
        }
        
        function refreshRecent() {
            fetch(window.location.pathname, {
                method: 'POST',
                body: new URLSearchParams({ action: 'get_recent' })
            })
            .then(r => r.json())
            .then(data => {
                const scroll = document.getElementById('recentScroll');
                scroll.innerHTML = '';
                
                data.slice(0, 8).forEach(e => {
                    const time = new Date(e.entry_time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    scroll.innerHTML += `
                        <div class="recent-item">
                            <span class="plate">${e.plate_number}</span>
                            <span class="time">${time}</span>
                            <span class="badge ${e.status}">${e.status === 'inside' ? 'IN' : 'OUT'}</span>
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
                gain.gain.value = 0.2;
                osc.start();
                setTimeout(() => osc.stop(), 100);
            } catch(e) {}
        }
        
        function playSuccess() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                [523, 659, 784].forEach((f, i) => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.frequency.value = f;
                    gain.gain.value = 0.15;
                    osc.start(ctx.currentTime + i * 0.1);
                    osc.stop(ctx.currentTime + i * 0.1 + 0.15);
                });
            } catch(e) {}
        }
        
        function playError() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                [400, 300].forEach((f, i) => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.frequency.value = f;
                    gain.gain.value = 0.2;
                    osc.start(ctx.currentTime + i * 0.2);
                    osc.stop(ctx.currentTime + i * 0.2 + 0.2);
                });
            } catch(e) {}
        }
        
        // Load Tesseract.js
        const tesseractScript = document.createElement('script');
        tesseractScript.src = 'https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js';
        tesseractScript.onload = () => {
            initCamera();
        };
        tesseractScript.onerror = () => {
            setStatus('OCR Load Failed', 'red');
        };
        document.head.appendChild(tesseractScript);
        
        refreshRecent();
        fetch(window.location.pathname, {
            method: 'POST',
            body: new URLSearchParams({ action: 'get_stats' })
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('statToday').textContent = data.today;
            document.getElementById('statInside').textContent = data.inside;
        });
        
        setInterval(refreshRecent, 10000);
    })();
    </script>
</body>
</html>
