<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireLogin();

$db = getDB();
$user = getCurrentUser();
$facility_id = $_SESSION['facility_id'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    
    if ($action === 'register_exit') {
        $plate = strtoupper(sanitize($_POST['plate'] ?? ''));
        
        if (empty($plate)) {
            echo json_encode(['success' => false, 'message' => 'Plate required']);
            exit;
        }
        
        $vehicle = $db->fetchOne("SELECT * FROM vehicles WHERE plate_number = ?", [$plate]);
        
        if (!$vehicle) {
            echo json_encode(['success' => false, 'message' => 'Vehicle not found', 'notfound' => true]);
            exit;
        }
        
        $log = $db->fetchOne("
            SELECT * FROM vehicle_logs 
            WHERE vehicle_id = ? AND status = 'inside' AND visitor_type = 'parking' 
            ORDER BY entry_time DESC LIMIT 1", [$vehicle['id']]);
        
        if (!$log) {
            echo json_encode(['success' => false, 'message' => 'No active entry found', 'noentry' => true]);
            exit;
        }
        
        $exit_time = date('Y-m-d H:i:s');
        $duration_minutes = (strtotime($exit_time) - strtotime($log['entry_time'])) / 60;
        
        $db->update('vehicle_logs', [
            'exit_time' => $exit_time,
            'duration_minutes' => (int)$duration_minutes,
            'status' => 'exited',
            'gate_officer_exit_id' => $user['id']
        ], 'id = :id', ['id' => $log['id']]);
        
        $hours = floor($duration_minutes / 60);
        $mins = floor($duration_minutes % 60);
        
        echo json_encode([
            'success' => true,
            'plate' => $plate,
            'entry_time' => date('H:i', strtotime($log['entry_time'])),
            'exit_time' => date('H:i:s'),
            'duration' => ($hours > 0 ? $hours . 'h ' : '') . $mins . ' min',
            'message' => 'EXIT REGISTERED'
        ]);
        exit;
    }
    
    if ($action === 'get_inside') {
        $vehicles = $db->fetchAll("
            SELECT vl.*, v.plate_number 
            FROM vehicle_logs vl 
            JOIN vehicles v ON vl.vehicle_id = v.id 
            WHERE vl.status = 'inside' AND vl.visitor_type = 'parking' 
            ORDER BY vl.entry_time DESC", []);
        echo json_encode($vehicles);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Parking - Exit</title>
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
            background: linear-gradient(135deg, #fd7e14 0%, #e8590c 100%);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 { font-size: 1.3rem; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        .header a { background: rgba(255,255,255,0.2); padding: 8px 15px; border-radius: 20px; color: white; text-decoration: none; }
        
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
            background: #fd7e14;
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
            min-width: 280px;
            border: 3px solid #fd7e14;
            display: none;
        }
        .plate-display.show { display: block; animation: slideUp 0.3s; }
        @keyframes slideUp {
            from { transform: translateX(-50%) translateY(30px); opacity: 0; }
            to { transform: translateX(-50%) translateY(0); opacity: 1; }
        }
        .plate-display .plate { font-size: 2.5rem; font-weight: 900; color: #fd7e14; letter-spacing: 4px; }
        .plate-display .info { font-size: 0.9rem; color: #888; margin-top: 10px; }
        
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
        .success-box { text-align: center; }
        .success-box .icon { font-size: 8rem; color: #fd7e14; }
        .success-box .plate { font-size: 3rem; font-weight: 900; color: #fd7e14; letter-spacing: 5px; margin: 20px 0; }
        .success-box .duration { font-size: 2rem; color: #ffc107; }
        .success-box .times { font-size: 1rem; color: #888; margin-top: 10px; }
        .success-box .msg { font-size: 1.2rem; color: #198754; margin-top: 15px; }
        
        /* Not Found Overlay */
        .notfound-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.95);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .notfound-overlay.show { display: flex; }
        .notfound-box { text-align: center; }
        .notfound-box .icon { font-size: 6rem; color: #dc3545; }
        .notfound-box .plate { font-size: 2rem; font-weight: 900; color: #ffc107; letter-spacing: 3px; margin: 20px 0; }
        .notfound-box .msg { font-size: 1rem; color: #888; }
        .notfound-box button {
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
            justify-content: center;
        }
        .stat { text-align: center; margin: 0 30px; }
        .stat .num { font-size: 2.5rem; font-weight: 800; color: #fd7e14; }
        .stat .lbl { font-size: 0.75rem; color: #888; text-transform: uppercase; }
        
        .inside-list {
            padding: 15px;
            flex: 1;
            overflow-y: auto;
        }
        .inside-title {
            font-size: 0.8rem;
            font-weight: 700;
            color: #888;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .inside-item {
            background: #16213e;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 8px;
            border-left: 4px solid #fd7e14;
        }
        .inside-item .plate { font-weight: 700; font-size: 1.2rem; }
        .inside-item .time { font-size: 0.85rem; color: #888; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="bi bi-p-circle-fill"></i> PARKING EXIT</h1>
            <a href="parking-entry.php"><i class="bi bi-box-arrow-left"></i> ENTRY</a>
        </div>
        
        <div class="camera-box">
            <video id="camera-preview" autoplay playsinline></video>
            <div class="scan-area"></div>
            <div class="scan-status" id="scan-status">
                <i class="bi bi-search"></i> SCANNING FOR EXIT...
            </div>
            <div class="plate-display" id="plate-display">
                <div class="plate" id="display-plate">--- ---</div>
                <div class="info" id="display-info">Processing...</div>
            </div>
        </div>
        
        <div class="stats-bar">
            <div class="stat">
                <div class="num" id="stat-inside">0</div>
                <div class="lbl">Vehicles Inside</div>
            </div>
        </div>
        
        <div class="inside-list">
            <div class="inside-title">Currently Parked</div>
            <div id="inside-container">
                <p style="text-align:center;color:#666;padding:30px;">Loading...</p>
            </div>
        </div>
    </div>
    
    <div class="success-overlay" id="success-overlay">
        <div class="success-box">
            <div class="icon"><i class="bi bi-check-circle-fill"></i></div>
            <div class="plate" id="success-plate">---</div>
            <div class="duration" id="success-duration">-- min</div>
            <div class="times" id="success-times">In: --:-- Out: --:--</div>
            <div class="msg">EXIT REGISTERED</div>
        </div>
    </div>
    
    <div class="notfound-overlay" id="notfound-overlay">
        <div class="notfound-box">
            <div class="icon"><i class="bi bi-x-circle-fill"></i></div>
            <div class="plate" id="notfound-plate">---</div>
            <div class="msg" id="notfound-msg">Vehicle not found inside!</div>
            <button onclick="closeNotFound()">OK</button>
        </div>
    </div>
    
    <script>
    const video = document.getElementById('camera-preview');
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    
    let lastPlate = '';
    let lastScan = 0;
    let lastExit = 0;
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
            console.error(err);
        }
    }
    
    function startScanning() {
        setInterval(scan, 2000);
    }
    
    async function scan() {
        if (!video.videoWidth || !worker) return;
        if (Date.now() - lastScan < 2500) return;
        lastScan = Date.now();
        
        ctx.drawImage(video, 0, 0, video.videoWidth, video.videoHeight);
        
        document.getElementById('scan-status').innerHTML = '<i class="bi bi-gear"></i> Reading...';
        
        try {
            const result = await worker.recognize(canvas);
            const plate = extractPlate(result.data.text);
            console.log('Exit OCR:', result.data.text, '-> Plate:', plate);
            
            if (plate && plate !== lastPlate) {
                lastPlate = plate;
                showDetected(plate);
                
                if (Date.now() - lastExit > 5000) {
                    registerExit(plate);
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
        
        const patterns = [
            'UG\\s?([A-Z]{2,3})\\s?([0-9]{3,4})',
            'U\\s?([A-Z]{3})\\s?([0-9]{3,4})',
            '([A-Z]{2,3})\\s?([0-9]{3,5})',
            '([A-Z]{3})([0-9]{3,4})',
        ];
        
        for (const p of patterns) {
            const m = text.match(new RegExp(p));
            if (m && m[0].length >= 4) return m[0].replace(/\s+/g, ' ').trim();
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
    
    function showDetected(plate) {
        const display = document.getElementById('plate-display');
        document.getElementById('display-plate').textContent = plate;
        document.getElementById('display-info').textContent = 'Auto-registering exit...';
        display.classList.add('show');
        
        document.getElementById('scan-status').innerHTML = '<i class="bi bi-check-circle"></i> ' + plate;
        
        playBeep();
    }
    
    function registerExit(plate) {
        lastExit = Date.now();
        
        const formData = new FormData();
        formData.append('action', 'register_exit');
        formData.append('plate', plate);
        
        fetch(window.location.pathname, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showSuccess(data);
                } else if (data.noentry) {
                    showNotFound(plate, 'No entry found for this vehicle');
                    lastPlate = '';
                } else if (data.notfound) {
                    showNotFound(plate, 'Vehicle not registered');
                    lastPlate = '';
                }
            })
            .catch(err => {
                console.error(err);
                showNotFound(plate, 'Error processing');
                lastPlate = '';
            });
    }
    
    function showSuccess(data) {
        document.getElementById('success-plate').textContent = data.plate;
        document.getElementById('success-duration').textContent = data.duration;
        document.getElementById('success-times').textContent = 'In: ' + data.entry_time + ' | Out: ' + data.exit_time;
        document.getElementById('success-overlay').classList.add('show');
        
        playSuccess();
        updateCount();
        refreshInside();
        
        setTimeout(() => {
            document.getElementById('success-overlay').classList.remove('show');
            document.getElementById('plate-display').classList.remove('show');
            lastPlate = '';
            lastExit = 0;
            document.getElementById('scan-status').innerHTML = '<i class="bi bi-search"></i> SCANNING FOR EXIT...';
        }, 3000);
    }
    
    function showNotFound(plate, msg) {
        document.getElementById('notfound-plate').textContent = plate;
        document.getElementById('notfound-msg').textContent = msg;
        document.getElementById('notfound-overlay').classList.add('show');
        playError();
    }
    
    function closeNotFound() {
        document.getElementById('notfound-overlay').classList.remove('show');
        document.getElementById('plate-display').classList.remove('show');
        document.getElementById('scan-status').innerHTML = '<i class="bi bi-search"></i> SCANNING FOR EXIT...';
    }
    
    function updateCount() {
        const el = document.getElementById('stat-inside');
        el.textContent = Math.max(0, parseInt(el.textContent) - 1);
    }
    
    function refreshInside() {
        fetch(window.location.pathname, {
            method: 'POST',
            body: new URLSearchParams({ action: 'get_inside' })
        })
        .then(r => r.json())
        .then(data => {
            const container = document.getElementById('inside-container');
            
            if (data.length === 0) {
                container.innerHTML = '<p style="text-align:center;color:#666;padding:30px;">No vehicles inside</p>';
                return;
            }
            
            container.innerHTML = '';
            data.forEach(v => {
                const entryTime = new Date(v.entry_time);
                const now = new Date();
                const parked = Math.floor((now - entryTime) / 60000);
                const hours = Math.floor(parked / 60);
                const mins = parked % 60;
                const duration = (hours > 0 ? hours + 'h ' : '') + mins + ' min';
                
                container.innerHTML += `
                    <div class="inside-item">
                        <div class="plate">${v.plate_number}</div>
                        <div class="time">In: ${entryTime.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})} | ${duration}</div>
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
    
    function playError() {
        try {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            [400, 300, 400].forEach((freq, i) => {
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.frequency.value = freq;
                gain.gain.value = 0.2;
                osc.start(audioCtx.currentTime + i * 0.2);
                osc.stop(audioCtx.currentTime + i * 0.2 + 0.15);
            });
        } catch(e) {}
    }
    
    initCamera();
    refreshInside();
    setInterval(refreshInside, 10000);
    </script>
</body>
</html>
