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
            ORDER BY vl.entry_time DESC LIMIT 10", []);
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
    <title>Auto Parking - Entry</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
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
        }
        .header h1 { font-size: 1.1rem; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        .header a { background: rgba(255,255,255,0.2); padding: 8px 15px; border-radius: 20px; color: white; text-decoration: none; font-size: 0.85rem; }
        
        .camera-view {
            background: #000;
            flex: 1;
            position: relative;
            min-height: 350px;
        }
        #camera-video { width: 100%; height: 100%; object-fit: cover; }
        
        .scan-frame {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 80%;
            height: 25%;
            border: 5px solid rgba(255,255,255,0.8);
            border-radius: 15px;
            background: rgba(255,255,255,0.05);
        }
        .scan-frame::after {
            content: 'ALIGN LICENSE PLATE INSIDE';
            position: absolute;
            bottom: -30px;
            left: 50%;
            transform: translateX(-50%);
            color: white;
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
            text-shadow: 1px 1px 3px black;
        }
        
        .capture-btn {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #198754;
            border: 4px solid white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 25px rgba(25, 135, 84, 0.5);
            transition: all 0.2s;
        }
        .capture-btn:active {
            transform: translateX(-50%) scale(0.9);
            background: #146c43;
        }
        .capture-btn i { font-size: 2rem; color: white; }
        
        .status-bar {
            position: absolute;
            top: 15px;
            left: 50%;
            transform: translateX(-50%);
            padding: 10px 25px;
            border-radius: 30px;
            font-weight: 700;
            font-size: 0.9rem;
            background: rgba(0,0,0,0.7);
        }
        .status-bar.ready { background: #198754; }
        .status-bar.processing { background: #ffc107; color: #000; }
        .status-bar.success { background: #198754; }
        .status-bar.error { background: #dc3545; }
        
        /* Captured Image View */
        .captured-view {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: #000;
            display: none;
        }
        .captured-view.show { display: block; }
        #captured-image { width: 100%; height: 70%; object-fit: contain; }
        
        .detected-box {
            position: absolute;
            bottom: 120px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.95);
            padding: 20px 40px;
            border-radius: 15px;
            text-align: center;
            min-width: 280px;
            border: 3px solid #198754;
        }
        .detected-box .label { font-size: 0.75rem; color: #888; margin-bottom: 5px; }
        .detected-box .plate { font-size: 2.5rem; font-weight: 900; color: #198754; letter-spacing: 4px; }
        
        .action-btns {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 15px;
        }
        .action-btn {
            padding: 15px 30px;
            border-radius: 10px;
            border: none;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
        }
        .action-btn.confirm {
            background: #198754;
            color: white;
        }
        .action-btn.retry {
            background: #6c757d;
            color: white;
        }
        
        /* Bottom Panel */
        .bottom-panel {
            background: #16213e;
            padding: 15px 20px;
            flex-shrink: 0;
        }
        
        .stats-row {
            display: flex;
            justify-content: space-around;
            margin-bottom: 12px;
        }
        .stat { text-align: center; }
        .stat .num { font-size: 2rem; font-weight: 800; color: #198754; }
        .stat .lbl { font-size: 0.65rem; color: #888; text-transform: uppercase; }
        
        .nav-btns {
            display: flex;
            gap: 10px;
        }
        .nav-btn {
            flex: 1;
            padding: 12px;
            border-radius: 10px;
            text-align: center;
            background: #1a1a2e;
            border: 2px solid #333;
            color: #888;
            font-weight: 600;
            font-size: 0.85rem;
            text-decoration: none;
        }
        .nav-btn:hover { border-color: #198754; color: white; }
        .nav-btn.active { background: #198754; border-color: #198754; color: white; }
        .nav-btn.exit { border-color: #fd7e14; }
        
        .recent-list {
            margin-top: 10px;
            max-height: 80px;
            overflow-y: auto;
        }
        .recent-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            background: #1a1a2e;
            border-radius: 8px;
            margin-bottom: 5px;
            font-size: 0.85rem;
        }
        .recent-item .plate { font-weight: 700; }
        .recent-item .badge { padding: 2px 8px; border-radius: 10px; font-size: 0.65rem; }
        .recent-item .badge.in { background: #198754; }
        
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
            animation: pop 0.3s;
        }
        @keyframes pop {
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
        .result-box .msg { font-size: 1.2rem; color: #888; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="bi bi-car-front"></i> PARKING ENTRY</h1>
            <a href="parking-exit-capture.php"><i class="bi bi-box-arrow-right"></i> EXIT</a>
        </div>
        
        <div class="camera-view" id="cameraView">
            <video id="camera-video" autoplay playsinline></video>
            <div class="scan-frame"></div>
            
            <div class="status-bar ready" id="statusBar">
                <i class="bi bi-camera"></i> READY - Press to Capture
            </div>
            
            <button class="capture-btn" id="captureBtn" onclick="captureAndRead()">
                <i class="bi bi-camera-fill"></i>
            </button>
            
            <div class="captured-view" id="capturedView">
                <img id="captured-image" alt="Captured">
                <div class="detected-box" id="detectedBox">
                    <div class="label">DETECTED PLATE</div>
                    <div class="plate" id="detectedPlate">---</div>
                </div>
                <div class="action-btns">
                    <button class="action-btn retry" onclick="retryCapture()">
                        <i class="bi bi-arrow-clockwise"></i> Retry
                    </button>
                    <button class="action-btn confirm" id="confirmBtn" onclick="confirmEntry()">
                        <i class="bi bi-check-circle"></i> Register Entry
                    </button>
                </div>
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
            
            <div class="nav-btns">
                <a href="parking-entry-capture.php" class="nav-btn active">
                    <i class="bi bi-box-arrow-right"></i> Entry
                </a>
                <a href="parking-exit-capture.php" class="nav-btn exit">
                    <i class="bi bi-box-arrow-left"></i> Exit
                </a>
            </div>
            
            <div class="recent-list" id="recentList"></div>
        </div>
    </div>
    
    <div class="result-overlay" id="resultOverlay">
        <div class="result-box">
            <div class="icon green" id="resultIcon"><i class="bi bi-check-circle-fill"></i></div>
            <div class="plate green" id="resultPlate">---</div>
            <div class="msg" id="resultMsg">Entry Registered</div>
        </div>
    </div>
    
    <div class="result-overlay" id="errorOverlay">
        <div class="result-box">
            <div class="icon red"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div class="plate red" id="errorPlate">---</div>
            <div class="msg" id="errorMsg">Already Inside!</div>
        </div>
    </div>

    <script>
    const video = document.getElementById('camera-video');
    const cameraView = document.getElementById('cameraView');
    const capturedView = document.getElementById('capturedView');
    const capturedImage = document.getElementById('captured-image');
    const detectedPlate = document.getElementById('detectedPlate');
    const statusBar = document.getElementById('statusBar');
    const confirmBtn = document.getElementById('confirmBtn');
    
    let currentPlate = '';
    let canvas = document.createElement('canvas');
    let stream = null;
    
    async function initCamera() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment' }
            });
            video.srcObject = stream;
            setStatus('READY - Press to Capture', 'ready');
        } catch (err) {
            console.error('Camera error:', err);
            setStatus('Camera Error', 'error');
        }
    }
    
    function setStatus(text, type) {
        statusBar.innerHTML = '<i class="bi bi-camera"></i> ' + text;
        statusBar.className = 'status-bar ' + type;
    }
    
    async function captureAndRead() {
        if (!video.videoWidth) {
            alert('Camera not ready');
            return;
        }
        
        setStatus('Processing...', 'processing');
        
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0);
        
        capturedImage.src = canvas.toDataURL('image/jpeg', 0.9);
        capturedView.classList.add('show');
        
        setStatus('Reading plate...', 'processing');
        
        const imageData = canvas.toDataURL('image/jpeg', 0.5);
        
        try {
            const result = await Tesseract.recognize(imageData, 'eng', {
                logger: m => {
                    if (m.status === 'recognizing text') {
                        setStatus('Reading: ' + Math.round(m.progress * 100) + '%', 'processing');
                    }
                }
            });
            
            const text = result.data.text;
            console.log('Raw text:', text);
            
            const plate = extractPlate(text);
            console.log('Extracted:', plate);
            
            if (plate) {
                currentPlate = plate;
                detectedPlate.textContent = plate;
                setStatus('Plate detected!', 'success');
                playBeep();
            } else {
                detectedPlate.textContent = 'NOT DETECTED';
                setStatus('Could not read - type manually', 'error');
            }
        } catch (err) {
            console.error('OCR error:', err);
            detectedPlate.textContent = 'ERROR';
            setStatus('OCR Error - type manually', 'error');
        }
    }
    
    function extractPlate(text) {
        text = text.toUpperCase().replace(/[^A-Z0-9\s]/g, ' ').replace(/\s+/g, ' ').trim();
        
        const patterns = [
            'UG\\s?[A-Z]{2,3}\\s?[0-9]{3,4}[A-Z]?',
            '[A-Z]{2,3}\\s?[0-9]{3,5}',
            '[A-Z]{2}\\s?[0-9]{4,5}',
        ];
        
        for (const p of patterns) {
            const m = text.match(new RegExp(p));
            if (m) {
                let plate = m[0].replace(/\s+/g, ' ').trim();
                if (/[A-Z]/.test(plate) && /[0-9]/.test(plate) && plate.length >= 4) {
                    return plate;
                }
            }
        }
        
        return null;
    }
    
    function retryCapture() {
        capturedView.classList.remove('show');
        currentPlate = '';
        setStatus('READY - Press to Capture', 'ready');
    }
    
    function confirmEntry() {
        if (!currentPlate || currentPlate === 'NOT DETECTED') {
            alert('No valid plate detected');
            return;
        }
        
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="bi bi-hourglass"></i> Processing...';
        
        fetch(window.location.pathname, {
            method: 'POST',
            body: new URLSearchParams({ action: 'register_entry', plate: currentPlate })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showResult(data.plate);
                updateStats(data.time);
                refreshRecent();
            } else if (data.duplicate) {
                showError('Already Inside!');
            } else {
                showError(data.message);
            }
            
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="bi bi-check-circle"></i> Register Entry';
        })
        .catch(err => {
            console.error(err);
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="bi bi-check-circle"></i> Register Entry';
        });
    }
    
    function showResult(plate) {
        document.getElementById('resultPlate').textContent = plate;
        document.getElementById('resultOverlay').classList.add('show');
        playSuccess();
        
        setTimeout(() => {
            document.getElementById('resultOverlay').classList.remove('show');
            capturedView.classList.remove('show');
            currentPlate = '';
            setStatus('READY - Press to Capture', 'ready');
        }, 2500);
    }
    
    function showError(msg) {
        document.getElementById('errorMsg').textContent = msg;
        document.getElementById('errorOverlay').classList.add('show');
        playError();
        
        setTimeout(() => {
            document.getElementById('errorOverlay').classList.remove('show');
        }, 2500);
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
            const list = document.getElementById('recentList');
            list.innerHTML = '';
            
            data.slice(0, 5).forEach(e => {
                const time = new Date(e.entry_time).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
                list.innerHTML += `
                    <div class="recent-item">
                        <span class="plate">${e.plate_number}</span>
                        <span>${time}</span>
                        <span class="badge in">IN</span>
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
    </script>
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@3/dist/tesseract.min.js"></script>
</body>
</html>
