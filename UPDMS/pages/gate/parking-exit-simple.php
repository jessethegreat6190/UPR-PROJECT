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
            echo json_encode(['success' => false, 'message' => 'No entry found', 'noentry' => true]);
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
    <title>Parking - Exit</title>
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
            background: linear-gradient(135deg, #fd7e14 0%, #e8590c 100%);
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
            color: #fd7e14;
        }
        .plate-input:focus {
            outline: none;
            border-color: #fd7e14;
            box-shadow: 0 0 20px rgba(253, 126, 20, 0.3);
        }
        
        .btn-register {
            width: 100%;
            padding: 20px;
            margin-top: 20px;
            background: #fd7e14;
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
        .btn-register:disabled { background: #333; cursor: not-allowed; }
        
        .inside-box {
            background: #16213e;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .inside-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: #888;
            text-transform: uppercase;
            margin-bottom: 15px;
        }
        .inside-item {
            background: #1a1a2e;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid #fd7e14;
            cursor: pointer;
            transition: all 0.2s;
        }
        .inside-item:hover { background: #252540; }
        .inside-item .plate { font-weight: 700; font-size: 1.2rem; }
        .inside-item .time { font-size: 0.85rem; color: #888; margin-top: 5px; }
        .inside-item .duration { 
            font-size: 0.9rem; 
            color: #fd7e14; 
            font-weight: 600;
            margin-top: 5px;
        }
        
        .quick-btns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
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
        .quick-btn:hover { border-color: #fd7e14; }
        .quick-btn i { font-size: 1.5rem; display: block; margin-bottom: 5px; }
        .quick-btn span { font-size: 0.85rem; }
        
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
            background: #fd7e14;
            padding: 50px;
            border-radius: 30px;
            text-align: center;
        }
        .modal-box i { font-size: 6rem; }
        .modal-box .plate { font-size: 2.5rem; font-weight: 900; margin: 20px 0; letter-spacing: 5px; }
        .modal-box .duration { font-size: 1.8rem; color: #fff3cd; }
        .modal-box .times { font-size: 1rem; opacity: 0.9; margin-top: 10px; }
        
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
            <h1><i class="bi bi-p-circle-fill"></i> PARKING EXIT</h1>
            <a href="parking-entry-simple.php"><i class="bi bi-box-arrow-left"></i> ENTRY</a>
        </div>
        
        <div class="main-content">
            <div class="plate-input-box">
                <label>Enter Plate Number to Exit</label>
                <input type="text" id="plateInput" class="plate-input" placeholder="UAR 123X" autocomplete="off" autofocus>
                
                <button class="btn-register" id="btnRegister" onclick="registerExit()">
                    <i class="bi bi-check-circle"></i> REGISTER EXIT
                </button>
            </div>
            
            <div class="inside-box">
                <div class="inside-title">Vehicles Currently Inside (Tap to exit)</div>
                <div id="insideList">
                    <p style="text-align:center;color:#666;padding:30px;">Loading...</p>
                </div>
            </div>
            
            <div class="quick-btns">
                <a href="parking-entry-simple.php" class="quick-btn">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Vehicle Entry</span>
                </a>
                <a href="auto-gate.php" class="quick-btn">
                    <i class="bi bi-car-front"></i>
                    <span>Vehicle Entry</span>
                </a>
            </div>
        </div>
    </div>
    
    <div class="modal" id="successModal">
        <div class="modal-box">
            <i class="bi bi-check-circle-fill"></i>
            <div class="plate" id="modalPlate">---</div>
            <div class="duration" id="modalDuration">-- min</div>
            <div class="times" id="modalTimes">In: -- | Out: --</div>
        </div>
    </div>
    
    <div class="error-modal" id="errorModal">
        <div class="error-box">
            <i class="bi bi-x-circle-fill"></i>
            <div class="msg" id="errorMsg">Vehicle not found!</div>
            <button onclick="closeError()">OK</button>
        </div>
    </div>
    
    <script>
    const plateInput = document.getElementById('plateInput');
    const btnRegister = document.getElementById('btnRegister');
    
    plateInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && this.value.length >= 3) {
            registerExit();
        }
    });
    
    function registerExit() {
        const plate = plateInput.value.trim();
        
        if (plate.length < 3) {
            alert('Enter plate number');
            return;
        }
        
        btnRegister.disabled = true;
        btnRegister.innerHTML = '<i class="bi bi-hourglass"></i> Processing...';
        
        fetch(window.location.pathname, {
            method: 'POST',
            body: new URLSearchParams({ action: 'register_exit', plate: plate })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showSuccess(data);
                playSuccess();
                refreshInside();
            } else {
                showError(data.notfound ? 'Vehicle not registered' : 'No entry found');
                playError();
            }
            
            btnRegister.disabled = false;
            btnRegister.innerHTML = '<i class="bi bi-check-circle"></i> REGISTER EXIT';
        })
        .catch(err => {
            console.error(err);
            btnRegister.disabled = false;
            btnRegister.innerHTML = '<i class="bi bi-check-circle"></i> REGISTER EXIT';
        });
    }
    
    function showSuccess(data) {
        document.getElementById('modalPlate').textContent = data.plate;
        document.getElementById('modalDuration').textContent = data.duration;
        document.getElementById('modalTimes').textContent = 'In: ' + data.entry_time + ' | Out: ' + data.exit_time;
        document.getElementById('successModal').classList.add('show');
        
        setTimeout(() => {
            document.getElementById('successModal').classList.remove('show');
            plateInput.value = '';
            plateInput.focus();
        }, 2500);
    }
    
    function showError(msg) {
        document.getElementById('errorMsg').textContent = msg;
        document.getElementById('errorModal').classList.add('show');
    }
    
    function closeError() {
        document.getElementById('errorModal').classList.remove('show');
        plateInput.focus();
    }
    
    function refreshInside() {
        fetch(window.location.pathname, {
            method: 'POST',
            body: new URLSearchParams({ action: 'get_inside' })
        })
        .then(r => r.json())
        .then(data => {
            const list = document.getElementById('insideList');
            
            if (data.length === 0) {
                list.innerHTML = '<p style="text-align:center;color:#666;padding:30px;">No vehicles inside</p>';
                return;
            }
            
            list.innerHTML = '';
            
            data.forEach(v => {
                const entryTime = new Date(v.entry_time);
                const now = new Date();
                const parked = Math.floor((now - entryTime) / 60000);
                const hours = Math.floor(parked / 60);
                const mins = parked % 60;
                const duration = (hours > 0 ? hours + 'h ' : '') + mins + ' min';
                
                list.innerHTML += `
                    <div class="inside-item" onclick="selectPlate('${v.plate_number}')">
                        <div class="plate">${v.plate_number}</div>
                        <div class="time">In: ${entryTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
                        <div class="duration">${duration}</div>
                    </div>
                `;
            });
        });
    }
    
    function selectPlate(plate) {
        plateInput.value = plate;
        registerExit();
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
                gain.connect(ctx.destination);
                osc.frequency.value = freq;
                gain.gain.value = 0.2;
                osc.start(ctx.currentTime + i * 0.2);
                osc.stop(ctx.currentTime + i * 0.2 + 0.2);
            });
        } catch(e) {}
    }
    
    refreshInside();
    setInterval(refreshInside, 5000);
    plateInput.focus();
    </script>
</body>
</html>
