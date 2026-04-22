<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireRole(['gate_officer', 'supervisor', 'hq_command', 'admin']);

$db = getDB();
$user = getCurrentUser();
$facility_id = $_SESSION['facility_id'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'exit') {
        $plate = strtoupper(sanitize($_POST['plate'] ?? ''));
        
        $vehicle = $db->fetchOne("SELECT * FROM vehicles WHERE plate_number = ?", [$plate]);
        
        if (!$vehicle) {
            echo json_encode(['success' => false, 'message' => 'Vehicle not found']);
            exit;
        }
        
        $log = $db->fetchOne("
            SELECT * FROM vehicle_logs 
            WHERE vehicle_id = ? AND status = 'inside' 
            ORDER BY entry_time DESC LIMIT 1", [$vehicle['id']]);
        
        if (!$log) {
            echo json_encode(['success' => false, 'message' => 'No active entry found']);
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
        
        logAction('vehicle_exit', 'vehicle_logs', $log['id'], null, ['plate' => $plate]);
        
        echo json_encode([
            'success' => true,
            'message' => 'EXIT RECORDED',
            'plate' => $plate,
            'duration' => floor($duration / 60) . 'h ' . ($duration % 60) . 'm'
        ]);
        exit;
    }
    
    if ($_POST['action'] === 'get_inside') {
        $vehicles = $db->fetchAll("
            SELECT vl.*, v.plate_number, v.color, v.make_model 
            FROM vehicle_logs vl 
            JOIN vehicles v ON vl.vehicle_id = v.id 
            WHERE vl.status = 'inside' AND vl.facility_id = ?
            ORDER BY vl.entry_time ASC", [$facility_id]);
        
        echo json_encode($vehicles);
        exit;
    }
}

$insideCount = $db->fetchOne("SELECT COUNT(*) as cnt FROM vehicle_logs WHERE status = 'inside' AND facility_id = ?", [$facility_id])['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Auto Gate Exit - <?php echo SITE_NAME; ?></title>
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
            background: linear-gradient(135deg, #fd7e14 0%, #e8590c 100%);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 { font-size: 1.3rem; font-weight: 700; }
        .header .badge { background: rgba(255,255,255,0.2); padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; }
        .header a { color: white; text-decoration: none; }
        
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
            box-shadow: 0 0 40px rgba(253, 126, 20, 0.5);
        }
        .scan-frame.active {
            border-color: #fd7e14;
            animation: glow 1s infinite;
        }
        @keyframes glow {
            0%, 100% { box-shadow: 0 0 30px rgba(253, 126, 20, 0.5); }
            50% { box-shadow: 0 0 60px rgba(253, 126, 20, 0.9); }
        }
        
        .scan-status {
            position: absolute;
            top: 15px;
            left: 50%;
            transform: translateX(-50%);
            background: #fd7e14;
            color: white;
            padding: 8px 25px;
            border-radius: 25px;
            font-weight: 700;
            font-size: 1rem;
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
            border: 2px solid #fd7e14;
            display: none;
        }
        .detected-plate.show { display: block; }
        .detected-plate .plate-num { font-size: 2rem; font-weight: 800; color: #fd7e14; letter-spacing: 3px; }
        
        .result-overlay, .notfound-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.95);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .result-overlay.show, .notfound-overlay.show { display: flex; }
        .result-box, .notfound-box {
            background: #1a1a2e;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        .result-box { border: 3px solid #fd7e14; }
        .notfound-box { border: 3px solid #dc3545; }
        .result-box .icon { font-size: 4rem; color: #fd7e14; margin-bottom: 20px; }
        .notfound-box .icon { font-size: 4rem; color: #dc3545; margin-bottom: 20px; }
        .result-box .plate, .notfound-box .plate { font-size: 2rem; font-weight: 800; color: #fd7e14; letter-spacing: 3px; }
        .result-box .info { color: #888; margin-top: 10px; }
        .btn-action {
            background: #fd7e14;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            margin-top: 20px;
            cursor: pointer;
        }
        .btn-action.red { background: #dc3545; }
        
        .stats-bar {
            background: #1a1a2e;
            padding: 15px 20px;
            display: flex;
            justify-content: center;
        }
        .stat-item { text-align: center; }
        .stat-item .num { font-size: 2rem; font-weight: 800; color: #fd7e14; }
        .stat-item .label { font-size: 0.8rem; color: #888; text-transform: uppercase; }
        
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
            border-left: 4px solid #fd7e14;
        }
        .entry-row .plate { font-weight: 700; }
        .entry-row .time { font-size: 0.8rem; color: #888; }
        .entry-row .dur { color: #fd7e14; font-weight: 600; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="bi bi-box-arrow-left"></i> AUTO EXIT</h1>
            <a href="auto-gate.php" class="badge"><i class="bi bi-arrow-left"></i> Entry</a>
        </div>
        
        <div class="camera-box">
            <video id="camera-preview" autoplay playsinline></video>
            <div class="scan-frame" id="scan-frame"></div>
            <div class="scan-status" id="scan-status">
                <i class="bi bi-search"></i> SCAN EXIT PLATE
            </div>
            <div class="detected-plate" id="detected-plate">
                <div class="plate-num" id="plate-display">--- ---</div>
                <div style="font-size:0.8rem;color:#888;margin-top:5px;">Processing...</div>
            </div>
        </div>
        
        <div class="stats-bar">
            <div class="stat-item">
                <div class="num" id="inside-count"><?php echo $insideCount; ?></div>
                <div class="label">Vehicles Inside</div>
            </div>
        </div>
        
        <div class="entries-list">
            <div class="entries-title">Vehicles Currently Inside</div>
            <div id="entries-container"></div>
        </div>
    </div>
    
    <div class="result-overlay" id="result-overlay">
        <div class="result-box">
            <div class="icon"><i class="bi bi-check-circle-fill"></i></div>
            <div class="plate" id="result-plate">---</div>
            <div class="info" id="result-duration">Duration: --</div>
            <button class="btn-action" onclick="closeResult()">
                <i class="bi bi-arrow-clockwise"></i> SCAN NEXT
            </button>
        </div>
    </div>
    
    <div class="notfound-overlay" id="notfound-overlay">
        <div class="notfound-box">
            <div class="icon"><i class="bi bi-question-circle-fill"></i></div>
            <div class="plate" id="notfound-plate">---</div>
            <p style="color:#888;margin-top:10px;">Vehicle not registered inside</p>
            <button class="btn-action red" onclick="closeNotFound()">
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
        
        let lastPlate = '';
        let isProcessing = false;
        let lastDetection = 0;
        let scanInterval = null;
        
        async function initCamera() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'environment' },
                    audio: false
                });
                video.srcObject = stream;
                startScanning();
            } catch (err) {
                scanStatus.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Camera Error';
            }
        }
        
        function startScanning() {
            scanFrame.classList.add('active');
            scanInterval = setInterval(scanFrame, 1500);
        }
        
        async function scanFrame() {
            if (isProcessing || !video.videoWidth) return;
            
            const now = Date.now();
            if (now - lastDetection < 6000) return;
            
            isProcessing = true;
            scanStatus.innerHTML = '<i class="bi bi-gear"></i> Reading...';
            
            ctx.drawImage(video, 0, 0, video.videoWidth, video.videoHeight);
            
            try {
                const result = await Tesseract.recognize(canvas, 'eng');
                const plate = extractPlate(result.data.text);
                
                if (plate && plate !== lastPlate) {
                    lastPlate = plate;
                    lastDetection = now;
                    
                    plateDisplay.textContent = plate;
                    detectedPlate.classList.add('show');
                    scanStatus.innerHTML = '<i class="bi bi-check-circle"></i> PROCESSING EXIT...';
                    
                    playBeep();
                    setTimeout(() => processExit(plate), 800);
                } else {
                    scanStatus.innerHTML = '<i class="bi bi-search"></i> SCAN EXIT PLATE';
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
            ];
            
            for (const p of patterns) {
                const m = text.match(new RegExp(p));
                if (m && m[0].length >= 4) return m[0].replace(/\s+/g, ' ').trim();
            }
            
            return null;
        }
        
        function processExit(plate) {
            detectedPlate.classList.remove('show');
            
            const formData = new FormData();
            formData.append('action', 'exit');
            formData.append('plate', plate);
            
            fetch(window.location.pathname, {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showResult(data.plate, data.duration);
                    updateCount();
                    refreshList();
                } else {
                    showNotFound(plate);
                }
            })
            .catch(() => showNotFound(plate));
        }
        
        function showResult(plate, duration) {
            document.getElementById('result-plate').textContent = plate;
            document.getElementById('result-duration').textContent = 'Duration: ' + duration;
            document.getElementById('result-overlay').classList.add('show');
            lastPlate = '';
            lastDetection = 0;
        }
        
        function showNotFound(plate) {
            document.getElementById('notfound-plate').textContent = plate;
            document.getElementById('notfound-overlay').classList.add('show');
            lastPlate = '';
            lastDetection = 0;
        }
        
        function closeResult() {
            document.getElementById('result-overlay').classList.remove('show');
        }
        
        function closeNotFound() {
            document.getElementById('notfound-overlay').classList.remove('show');
        }
        
        function updateCount() {
            const el = document.getElementById('inside-count');
            el.textContent = Math.max(0, parseInt(el.textContent) - 1);
        }
        
        function refreshList() {
            fetch(window.location.pathname, {
                method: 'POST',
                body: new URLSearchParams({ action: 'get_inside' })
            })
            .then(r => r.json())
            .then(data => {
                const container = document.getElementById('entries-container');
                container.innerHTML = '';
                
                data.forEach(v => {
                    const entryTime = new Date(v.entry_time);
                    const hours = (Date.now() - entryTime) / 3600000;
                    const duration = Math.floor(hours) + 'h ' + Math.floor((hours % 1) * 60) + 'm';
                    
                    container.innerHTML += `
                        <div class="entry-row">
                            <div>
                                <div class="plate">${v.plate_number}</div>
                                <div class="time">In: ${entryTime.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}</div>
                            </div>
                            <div class="dur">${duration}</div>
                        </div>
                    `;
                });
                
                if (data.length === 0) {
                    container.innerHTML = '<p style="text-align:center;color:#666;padding:30px;">No vehicles inside</p>';
                }
            });
        }
        
        function playBeep() {
            try {
                const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.frequency.value = 660;
                gain.gain.value = 0.3;
                osc.start();
                setTimeout(() => osc.stop(), 150);
            } catch(e) {}
        }
        
        initCamera();
        refreshList();
    })();
    </script>
</body>
</html>
