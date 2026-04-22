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
    
    if ($action === 'search_vehicles') {
        $q = sanitize($_POST['q'] ?? '');
        
        if (strlen($q) < 2) {
            echo json_encode([]);
            exit;
        }
        
        $vehicles = $db->fetchAll("
            SELECT plate_number, total_visits, last_visit 
            FROM vehicles 
            WHERE plate_number LIKE ? 
            ORDER BY last_visit DESC 
            LIMIT 10", ['%' . $q . '%']);
        
        echo json_encode($vehicles);
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
            ORDER BY vl.entry_time DESC LIMIT 20", []);
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
    <title>Parking - Entry</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
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
        .header h1 { font-size: 1.2rem; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        .header a { background: rgba(255,255,255,0.2); padding: 8px 15px; border-radius: 20px; color: white; text-decoration: none; }
        
        .main-content { padding: 20px; flex: 1; }
        
        .plate-input-box {
            background: #16213e;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 20px;
        }
        .plate-input-box label {
            display: block;
            font-size: 0.9rem;
            color: #888;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .plate-input {
            width: 100%;
            padding: 25px 20px;
            font-size: 2rem;
            font-weight: 900;
            text-align: center;
            letter-spacing: 5px;
            text-transform: uppercase;
            background: #0a0a1a;
            border: 3px solid #333;
            border-radius: 15px;
            color: #198754;
        }
        .plate-input:focus {
            outline: none;
            border-color: #198754;
            box-shadow: 0 0 20px rgba(25, 135, 84, 0.3);
        }
        
        .plate-suggestions {
            margin-top: 15px;
            max-height: 150px;
            overflow-y: auto;
            display: none;
        }
        .plate-suggestions.show { display: block; }
        .suggestion {
            padding: 12px 15px;
            background: #1a1a2e;
            border-radius: 10px;
            margin-bottom: 8px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #333;
            transition: all 0.2s;
        }
        .suggestion:hover {
            background: #198754;
            border-color: #198754;
        }
        .suggestion .plate { font-weight: 700; font-size: 1.1rem; }
        .suggestion .visits { font-size: 0.8rem; color: #888; }
        .suggestion:hover .visits { color: rgba(255,255,255,0.7); }
        
        .btn-register {
            width: 100%;
            padding: 20px;
            margin-top: 20px;
            background: #198754;
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 1.3rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .btn-register:active { transform: scale(0.98); }
        .btn-register:disabled {
            background: #333;
            cursor: not-allowed;
        }
        
        .stats-bar {
            background: #16213e;
            border-radius: 15px;
            padding: 20px;
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
        }
        .stat { text-align: center; }
        .stat .num { font-size: 2.5rem; font-weight: 800; color: #198754; }
        .stat .lbl { font-size: 0.7rem; color: #888; text-transform: uppercase; }
        
        .quick-btns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        .quick-btn {
            background: #16213e;
            border: 2px solid #333;
            border-radius: 15px;
            padding: 15px;
            text-align: center;
            color: white;
            text-decoration: none;
        }
        .quick-btn:hover { border-color: #198754; }
        .quick-btn i { font-size: 1.5rem; display: block; margin-bottom: 5px; }
        .quick-btn span { font-size: 0.85rem; }
        
        .recent-box {
            background: #16213e;
            border-radius: 15px;
            padding: 15px;
            flex: 1;
        }
        .recent-title {
            font-size: 0.8rem;
            font-weight: 700;
            color: #888;
            text-transform: uppercase;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #333;
        }
        .recent-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: #1a1a2e;
            border-radius: 10px;
            margin-bottom: 8px;
        }
        .recent-item .plate { font-weight: 700; }
        .recent-item .time { font-size: 0.8rem; color: #888; }
        .recent-item .status { padding: 3px 10px; border-radius: 10px; font-size: 0.7rem; font-weight: 600; }
        .recent-item .status.in { background: #198754; }
        .recent-item .status.out { background: #6c757d; }
        
        /* Success Modal */
        .modal {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.95);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal.show { display: flex; }
        .modal-box {
            background: #198754;
            padding: 50px;
            border-radius: 30px;
            text-align: center;
            animation: pop 0.3s;
        }
        @keyframes pop {
            from { transform: scale(0.5); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        .modal-box i { font-size: 6rem; }
        .modal-box .plate { font-size: 2.5rem; font-weight: 900; margin: 20px 0; letter-spacing: 5px; }
        .modal-box .time { font-size: 1.2rem; opacity: 0.9; }
        
        /* Error Modal */
        .error-modal {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.95);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .error-modal.show { display: flex; }
        .error-box {
            background: #dc3545;
            padding: 50px;
            border-radius: 30px;
            text-align: center;
        }
        .error-box i { font-size: 5rem; }
        .error-box .msg { font-size: 1.2rem; margin-top: 15px; }
        .error-box button {
            margin-top: 30px;
            padding: 15px 50px;
            background: white;
            color: #dc3545;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="bi bi-p-circle"></i> PARKING</h1>
            <a href="parking-exit-simple.php"><i class="bi bi-box-arrow-right"></i> EXIT</a>
        </div>
        
        <div class="main-content">
            <div class="plate-input-box">
                <label>Enter Plate Number</label>
                <input type="text" id="plateInput" class="plate-input" placeholder="UAR 123X" autocomplete="off" autofocus>
                
                <div class="plate-suggestions" id="suggestions"></div>
                
                <button class="btn-register" id="btnRegister" onclick="registerEntry()">
                    <i class="bi bi-check-circle"></i> REGISTER ENTRY
                </button>
            </div>
            
            <div class="stats-bar">
                <div class="stat">
                    <div class="num" id="statToday">0</div>
                    <div class="lbl">Today</div>
                </div>
                <div class="stat">
                    <div class="num" id="statInside">0</div>
                    <div class="lbl">Inside</div>
                </div>
            </div>
            
            <div class="quick-btns">
                <a href="parking-exit-simple.php" class="quick-btn">
                    <i class="bi bi-box-arrow-left"></i>
                    <span>Vehicle Exit</span>
                </a>
                <a href="auto-gate.php" class="quick-btn">
                    <i class="bi bi-car-front"></i>
                    <span>Vehicle Entry</span>
                </a>
            </div>
            
            <div class="recent-box">
                <div class="recent-title">Recent Entries</div>
                <div id="recentList"></div>
            </div>
        </div>
    </div>
    
    <div class="modal" id="successModal">
        <div class="modal-box">
            <i class="bi bi-check-circle-fill"></i>
            <div class="plate" id="modalPlate">---</div>
            <div class="time" id="modalTime">Entry: --:--:--</div>
        </div>
    </div>
    
    <div class="error-modal" id="errorModal">
        <div class="error-box">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div class="msg" id="errorMsg">Vehicle already inside!</div>
            <button onclick="closeError()">OK</button>
        </div>
    </div>
    
    <script>
    const plateInput = document.getElementById('plateInput');
    const suggestions = document.getElementById('suggestions');
    const btnRegister = document.getElementById('btnRegister');
    
    let lastPlate = '';
    
    plateInput.addEventListener('input', function() {
        let val = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        this.value = val;
        
        if (val.length >= 2) {
            searchVehicles(val);
        } else {
            suggestions.classList.remove('show');
        }
    });
    
    plateInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && this.value.length >= 3) {
            registerEntry();
        }
    });
    
    function searchVehicles(q) {
        fetch(window.location.pathname, {
            method: 'POST',
            body: new URLSearchParams({ action: 'search_vehicles', q: q })
        })
        .then(r => r.json())
        .then(data => {
            suggestions.innerHTML = '';
            
            if (data.length > 0) {
                data.forEach(v => {
                    suggestions.innerHTML += `
                        <div class="suggestion" onclick="selectPlate('${v.plate_number}')">
                            <span class="plate">${v.plate_number}</span>
                            <span class="visits">${v.total_visits || 1} visits</span>
                        </div>
                    `;
                });
                suggestions.classList.add('show');
            } else {
                suggestions.classList.remove('show');
            }
        });
    }
    
    function selectPlate(plate) {
        plateInput.value = plate;
        suggestions.classList.remove('show');
        registerEntry();
    }
    
    function registerEntry() {
        const plate = plateInput.value.trim();
        
        if (plate.length < 3) {
            alert('Enter plate number');
            return;
        }
        
        btnRegister.disabled = true;
        btnRegister.innerHTML = '<i class="bi bi-hourglass"></i> Processing...';
        
        fetch(window.location.pathname, {
            method: 'POST',
            body: new URLSearchParams({ action: 'register_entry', plate: plate })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showSuccess(data.plate, data.time);
                playSuccess();
                updateStats();
                refreshRecent();
                plateInput.value = '';
            } else if (data.duplicate) {
                showError('Vehicle already inside!');
                playError();
            } else {
                showError(data.message);
            }
            
            btnRegister.disabled = false;
            btnRegister.innerHTML = '<i class="bi bi-check-circle"></i> REGISTER ENTRY';
        })
        .catch(err => {
            console.error(err);
            btnRegister.disabled = false;
            btnRegister.innerHTML = '<i class="bi bi-check-circle"></i> REGISTER ENTRY';
        });
    }
    
    function showSuccess(plate, time) {
        document.getElementById('modalPlate').textContent = plate;
        document.getElementById('modalTime').textContent = 'Entry: ' + time;
        document.getElementById('successModal').classList.add('show');
        
        setTimeout(() => {
            document.getElementById('successModal').classList.remove('show');
            plateInput.focus();
        }, 2000);
    }
    
    function showError(msg) {
        document.getElementById('errorMsg').textContent = msg;
        document.getElementById('errorModal').classList.add('show');
    }
    
    function closeError() {
        document.getElementById('errorModal').classList.remove('show');
        plateInput.focus();
    }
    
    function updateStats() {
        fetch(window.location.pathname, {
            method: 'POST',
            body: new URLSearchParams({ action: 'get_stats' })
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('statToday').textContent = data.today;
            document.getElementById('statInside').textContent = data.inside;
        });
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
            
            data.slice(0, 10).forEach(e => {
                const time = new Date(e.entry_time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                list.innerHTML += `
                    <div class="recent-item">
                        <div>
                            <div class="plate">${e.plate_number}</div>
                            <div class="time">${time}</div>
                        </div>
                        <span class="status ${e.status}">${e.status === 'inside' ? 'IN' : 'OUT'}</span>
                    </div>
                `;
            });
            
            if (data.length === 0) {
                list.innerHTML = '<p style="text-align:center;color:#666;padding:20px;">No entries yet</p>';
            }
        });
    }
    
    function playSuccess() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            [523, 659, 784].forEach((freq, i) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.frequency.value = freq;
                gain.gain.value = 0.15;
                osc.start(ctx.currentTime + i * 0.1);
                osc.stop(ctx.currentTime + i * 0.1 + 0.15);
            });
        } catch(e) {}
    }
    
    function playError() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            [400, 300].forEach((freq, i) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.frequency.value = freq;
                gain.gain.value = 0.2;
                osc.start(audioCtx.currentTime + i * 0.2);
                osc.stop(audioCtx.currentTime + i * 0.2 + 0.2);
            });
        } catch(e) {}
    }
    
    updateStats();
    refreshRecent();
    setInterval(refreshRecent, 5000);
    plateInput.focus();
    </script>
</body>
</html>
