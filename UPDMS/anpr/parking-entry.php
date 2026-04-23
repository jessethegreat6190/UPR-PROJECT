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
        
        $check = $db->fetchOne("SELECT * FROM vehicle_logs WHERE vehicle_id IN (SELECT id FROM vehicles WHERE plate_number = ?) AND status = 'inside' AND visitor_type = 'parking'", [$plate]);
        
        if ($check) {
            echo json_encode(['success' => false, 'message' => 'Vehicle already inside', 'duplicate' => true]);
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
            'time' => date('H:i:s'),
            'message' => 'ENTRY REGISTERED'
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
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Parking - Entry</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            background: #0a0a1a; 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            color: white;
        }
        .container { max-width: 100%; min-height: 100vh; display: flex; flex-direction: column; }
        
        .header {
            background: linear-gradient(135deg, #198754 0%, #0d6e3f 100%);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 { font-size: 1.3rem; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        .header a { background: rgba(255,255,255,0.2); padding: 8px 15px; border-radius: 20px; color: white; text-decoration: none; font-size: 0.9rem; }
        
        .camera-box {
            background: #000;
            aspect-ratio: 16/10;
            position: relative;
            flex: 1;
        }
        #camera-preview { width: 100%; height: 100%; object-fit: cover; }
        
        .scan-area {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 85%;
            height: 30%;
            border: 4px solid rgba(255,255,255,0.8);
            border-radius: 15px;
            background: rgba(255,255,255,0.03);
        }
        
        .scan-status {
            position: absolute;
            top: 15px;
            left: 50%;
            transform: translateX(-50%);
            padding: 10px 25px;
            border-radius: 30px;
            font-weight: 700;
            font-size: 1rem;
            background: #198754;
        }
        
        .plate-display {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.9);
            padding: 20px 40px;
            border-radius: 15px;
            text-align: center;
            min-width: 250px;
            border: 3px solid #198754;
            display: none;
        }
        .plate-display.show { display: block; animation: slideUp 0.3s; }
        @keyframes slideUp {
            from { transform: translateX(-50%) translateY(30px); opacity: 0; }
            to { transform: translateX(-50%) translateY(0); opacity: 1; }
        }
        .plate-display .label { font-size: 0.8rem; color: #888; margin-bottom: 5px; }
        .plate-display .plate { font-size: 2.5rem; font-weight: 900; color: #198754; letter-spacing: 4px; }
        .plate-display .time { font-size: 1.2rem; color: #888; margin-top: 10px; }
        .plate-display .status { font-size: 1rem; color: #ffc107; margin-top: 8px; }
        
        /* Success Overlay */
        .success-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.95);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .success-overlay.show { display: flex; }
        .success-box {
            text-align: center;
            animation: popIn 0.3s;
        }
        @keyframes popIn {
            from { transform: scale(0.5); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        .success-box .icon { font-size: 8rem; color: #198754; }
        .success-box .plate { font-size: 3rem; font-weight: 900; color: #198754; letter-spacing: 5px; margin: 20px 0; }
        .success-box .time { font-size: 1.5rem; color: #888; }
        .success-box .msg { font-size: 1.2rem; color: #ffc107; margin-top: 15px; }
        
        /* Duplicate Overlay */
        .duplicate-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.95);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .duplicate-overlay.show { display: flex; }
        .duplicate-box { text-align: center; }
        .duplicate-box .icon { font-size: 6rem; color: #ffc107; }
        .duplicate-box .plate { font-size: 2.5rem; font-weight: 900; color: #ffc107; letter-spacing: 4px; margin: 20px 0; }
        .duplicate-box .msg { font-size: 1.2rem; color: #888; }
        .duplicate-box button {
            margin-top: 30px;
            padding: 15px 50px;
            border-radius: 10px;
            border: none;
            background: #6c757d;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
        }
        
        .stats-bar {
            background: #16213e;
            padding: 15px 20px;
            display: flex;
            justify-content: space-around;
        }
        .stat { text-align: center; }
        .stat .num { font-size: 2rem; font-weight: 800; color: #198754; }
        .stat .lbl { font-size: 0.7rem; color: #888; text-transform: uppercase; }
        
        .recent-list {
            padding: 15px;
            flex: 1;
            overflow-y: auto;
        }
        .recent-title {
            font-size: 0.8rem;
            font-weight: 700;
            color: #888;
            text-transform: uppercase;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #333;
        }
        .recent-item {
            background: #16213e;
            border-radius: 10px;
            padding: 12px 15px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .recent-item .plate { font-weight: 700; font-size: 1.1rem; }
        .recent-item .time { font-size: 0.8rem; color: #888; }
        .recent-item .badge { padding: 4px 12px; border-radius: 15px; font-size: 0.75rem; font-weight: 600; }
        .recent-item .badge-in { background: #198754; color: white; }
        .recent-item .badge-out { background: #6c757d; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="bi bi-p-circle"></i> PARKING ENTRY</h1>
            <a href="parking-exit.php"><i class="bi bi-box-arrow-right"></i> EXIT</a>
        </div>
        
        <div class="camera-box">
            <video id="camera-preview" autoplay playsinline></video>
            <div class="scan-area"></div>
            <div class="scan-status" id="scan-status">
                <i class="bi bi-search"></i> SCANNING...
            </div>
            <div class="plate-display" id="plate-display">
                <div class="label">DETECTED PLATE</div>
                <div class="plate" id="display-plate">--- ---</div>
                <div class="time" id="display-time">--:--:--</div>
                <div class="status">Auto-registering...</div>
            </div>
            <button style="position:absolute;bottom:120px;left:50%;transform:translateX(-50%);background:#198754;color:white;border:none;padding:12px 30px;border-radius:10px;font-weight:700;font-size:0.9rem;cursor:pointer;" onclick="manualScan()">
                <i class="bi bi-camera"></i> SNAP & READ
            </button>
        </div>
        
        <div class="stats-bar">
            <div class="stat">
                <div class="num" id="stat-today">0</div>
                <div class="lbl">Today</div>
            </div>
            <div class="stat">
                <div class="num" id="stat-inside">0</div>
                <div class="lbl">Inside</div>
            </div>
            <div class="stat">
                <div class="num" id="stat-last">--:--</div>
                <div class="lbl">Last Entry</div>
            </div>
        </div>
        
        <div class="recent-list">
            <div class="recent-title">Recent Entries</div>
            <div id="recent-container"></div>
        </div>
    </div>
    
    <div class="success-overlay" id="success-overlay">
        <div class="success-box">
            <div class="icon"><i class="bi bi-check-circle-fill"></i></div>
            <div class="plate" id="success-plate">---</div>
            <div class="time" id="success-time">--:--:--</div>
            <div class="msg">ENTRY REGISTERED</div>
        </div>
    </div>
    
    <div class="duplicate-overlay" id="duplicate-overlay">
        <div class="duplicate-box">
            <div class="icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div class="plate" id="duplicate-plate">---</div>
            <div class="msg">Vehicle already inside!</div>
            <button onclick="closeDuplicate()">OK</button>
        </div>
    </div>
    
    <script>
    const video = document.getElementById('camera-preview');
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    
    let lastPlate = '';
    let lastScan = 0;
    let lastRegister = 0;
    let scanInterval = null;
    let worker = null;
    
    async function initCamera() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment', width: { ideal: 1280 } }
            });
            video.srcObject = stream;
            
            document.getElementById('scan-status').innerHTML = '<i class="bi bi-hourglass"></i> Loading OCR...';
            
            worker = await Tesseract.createWorker('eng');
            document.getElementById('scan-status').innerHTML = '<i class="bi bi-search"></i> SCANNING...';
            startScanning();
        } catch (err) {
            document.getElementById('scan-status').innerHTML = '<i class="bi bi-x-circle"></i> Camera Error';
            console.error('Init error:', err);
        }
    }
    
    function startScanning() {
        scanInterval = setInterval(scan, 2000);
    }
    
    async function scan() {
        if (!video.videoWidth || !worker) return;
        if (Date.now() - lastScan < 2500) return;
        lastScan = Date.now();
        
        ctx.drawImage(video, 0, 0, video.videoWidth, video.videoHeight);
        
        document.getElementById('scan-status').innerHTML = '<i class="bi bi-gear"></i> Reading...';
        
        try {
            const result = await worker.recognize(canvas);
            const rawText = result.data.text;
            console.log('Raw OCR:', rawText);
            
            const plate = extractPlate(rawText);
            console.log('Extracted plate:', plate);
            
            if (plate && plate !== lastPlate) {
                lastPlate = plate;
                showDetected(plate);
                
                if (Date.now() - lastRegister > 5000) {
                    registerEntry(plate);
                }
            } else {
                document.getElementById('scan-status').innerHTML = '<i class="bi bi-search"></i> SCANNING...';
            }
        } catch (err) {
            console.error('Scan error:', err);
            document.getElementById('scan-status').innerHTML = '<i class="bi bi-search"></i> SCANNING...';
        }
    }
    
    function extractPlate(text) {
        text = text.toUpperCase().replace(/[^A-Z0-9\s]/g, ' ').replace(/\s+/g, ' ').trim();
        console.log('Cleaned text:', text);
        
        // Try all common Uganda plate patterns
        const patterns = [
            'UG\\s?[A-Z]{2,3}\\s?[0-9]{3,4}[A-Z]?',
            'U\\s?[A-Z]{3}\\s?[0-9]{3,4}',
            '[A-Z]{2,3}\\s?[0-9]{4,5}',
            '[A-Z]{3}\\s?[0-9]{3,4}',
            '[A-Z]{2}\\s?[0-9]{4,5}',
            '[0-9]{3}[A-Z]{3}[0-9]{1,4}',
        ];
        
        for (const p of patterns) {
            const m = text.match(new RegExp(p));
            if (m) {
                let plate = m[0].replace(/\s+/g, ' ').trim();
                // Ensure it has both letters and numbers
                if (/[A-Z]/.test(plate) && /[0-9]/.test(plate)) {
                    return plate;
                }
            }
        }
        
        // Fallback: look for any 2-3 letters followed by 3-5 numbers
        const fallback = text.match(/[A-Z]{2,3}\s?[0-9]{3,5}/);
        if (fallback) {
            return fallback[0].replace(/\s+/g, ' ').trim();
        }
        
        return null;
    }
    
    function showDetected(plate) {
        const display = document.getElementById('plate-display');
        document.getElementById('display-plate').textContent = plate;
        document.getElementById('display-time').textContent = new Date().toLocaleTimeString();
        display.classList.add('show');
        
        document.getElementById('scan-status').innerHTML = '<i class="bi bi-check-circle"></i> ' + plate;
        document.getElementById('scan-status').style.background = '#198754';
        
        playBeep();
    }
    
    function registerEntry(plate) {
        lastRegister = Date.now();
        
        const formData = new FormData();
        formData.append('action', 'register_entry');
        formData.append('plate', plate);
        
        fetch(window.location.pathname, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showSuccess(data.plate, data.time);
                    updateStats(data.time);
                    refreshRecent();
                } else if (data.duplicate) {
                    showDuplicate(plate);
                    lastPlate = '';
                    setTimeout(() => {
                        lastRegister = 0;
                    }, 5000);
                }
            })
            .catch(err => console.error(err));
    }
    
    function showSuccess(plate, time) {
        document.getElementById('success-plate').textContent = plate;
        document.getElementById('success-time').textContent = 'Entry: ' + time;
        document.getElementById('success-overlay').classList.add('show');
        
        playSuccess();
        
        setTimeout(() => {
            document.getElementById('success-overlay').classList.remove('show');
            document.getElementById('plate-display').classList.remove('show');
            lastPlate = '';
            lastRegister = 0;
            document.getElementById('scan-status').innerHTML = '<i class="bi bi-search"></i> SCANNING...';
            document.getElementById('scan-status').style.background = '#198754';
        }, 2500);
    }
    
    function showDuplicate(plate) {
        document.getElementById('duplicate-plate').textContent = plate;
        document.getElementById('duplicate-overlay').classList.add('show');
        playWarning();
    }
    
    function closeDuplicate() {
        document.getElementById('duplicate-overlay').classList.remove('show');
        document.getElementById('plate-display').classList.remove('show');
        document.getElementById('scan-status').innerHTML = '<i class="bi bi-search"></i> SCANNING...';
        document.getElementById('scan-status').style.background = '#198754';
    }
    
    function updateStats(time) {
        document.getElementById('stat-today').textContent = parseInt(document.getElementById('stat-today').textContent) + 1;
        document.getElementById('stat-inside').textContent = parseInt(document.getElementById('stat-inside').textContent) + 1;
        document.getElementById('stat-last').textContent = time;
    }
    
    function refreshRecent() {
        fetch(window.location.pathname, {
            method: 'POST',
            body: new URLSearchParams({ action: 'get_recent' })
        })
        .then(r => r.json())
        .then(data => {
            const container = document.getElementById('recent-container');
            container.innerHTML = '';
            
            data.forEach(e => {
                const time = new Date(e.entry_time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                container.innerHTML += `
                    <div class="recent-item">
                        <div>
                            <div class="plate">${e.plate_number}</div>
                            <div class="time">In: ${time}</div>
                        </div>
                        <span class="badge badge-${e.status}">${e.status === 'inside' ? 'IN' : 'OUT'}</span>
                    </div>
                `;
            });
        });
    }
    
    function playBeep() {
        try {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.frequency.value = 880;
            gain.gain.value = 0.2;
            osc.start();
            setTimeout(() => osc.stop(), 100);
        } catch(e) {}
    }
    
    function playSuccess() {
        try {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            [523, 659, 784].forEach((freq, i) => {
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.frequency.value = freq;
                gain.gain.value = 0.15;
                osc.start(audioCtx.currentTime + i * 0.1);
                osc.stop(audioCtx.currentTime + i * 0.1 + 0.15);
            });
        } catch(e) {}
    }
    
    async function manualScan() {
        if (!video.videoWidth || !worker) {
            alert('Camera or OCR not ready');
            return;
        }
        
        ctx.drawImage(video, 0, 0, video.videoWidth, video.videoHeight);
        document.getElementById('scan-status').innerHTML = '<i class="bi bi-gear"></i> Reading...';
        
        try {
            const result = await worker.recognize(canvas);
            const rawText = result.data.text;
            console.log('Manual OCR:', rawText);
            
            const plate = extractPlate(rawText);
            console.log('Manual plate:', plate);
            
            if (plate) {
                lastPlate = plate;
                showDetected(plate);
                registerEntry(plate);
            } else {
                document.getElementById('scan-status').innerHTML = '<i class="bi bi-search"></i> No plate found';
            }
        } catch (err) {
            console.error('Manual scan error:', err);
            document.getElementById('scan-status').innerHTML = '<i class="bi bi-search"></i> Error reading';
        }
    }
    
    function playWarning() {
        try {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            [400, 300].forEach((freq, i) => {
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.frequency.value = freq;
                gain.gain.value = 0.2;
                osc.start(audioCtx.currentTime + i * 0.2);
                osc.stop(audioCtx.currentTime + i * 0.2 + 0.2);
            });
        } catch(e) {}
    }
    
    initCamera();
    refreshRecent();
    setInterval(refreshRecent, 10000);
    </script>
</body>
</html>
