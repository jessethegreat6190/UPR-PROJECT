<?php
require_once __DIR__ . '/../config/bootstrap.php';

$db = getDB();
$success = '';
$error = '';
$screen = isset($_GET['screen']) ? sanitize($_GET['screen']) : 'welcome';
$purpose = isset($_GET['purpose']) ? sanitize($_GET['purpose']) : '';
$reference = isset($_GET['ref']) ? sanitize($_GET['ref']) : '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action']);
    
    if ($action === 'register_visitor') {
        $visitor_type = sanitize($_POST['visitor_type']);
        $sub_type = sanitize($_POST['sub_type'] ?? '');
        
        // Create visitor
        $visitor_data = [
            'visitor_type' => $visitor_type,
            'national_id' => sanitize($_POST['national_id'] ?? null),
            'first_name' => sanitize($_POST['first_name']),
            'last_name' => sanitize($_POST['last_name']),
            'phone' => sanitize($_POST['phone'] ?? null),
            'address' => sanitize($_POST['address'] ?? null),
            'company_name' => sanitize($_POST['company_name'] ?? null)
        ];
        
        $visitor_id = $db->insert('visitors', $visitor_data);
        
        // Create visitor log
        $log_data = [
            'visitor_id' => $visitor_id,
            'facility_id' => 1, // Default facility
            'visitor_type' => $sub_type ?: $visitor_type,
            'entry_time' => date('Y-m-d H:i:s'),
            'cargo_description' => sanitize($_POST['cargo_description'] ?? ''),
            'cargo_checked' => 0,
            'status' => 'inside',
            'gate_officer_entry_id' => 1, // Kiosk mode
            'notes' => sanitize($_POST['purpose'] ?? '') . ' | ' . sanitize($_POST['who_visiting'] ?? '')
        ];
        
        $log_id = $db->insert('visitor_logs', $log_data);
        
        // Generate reference
        $ref = str_pad($log_id, 6, '0', STR_PAD_LEFT);
        
        header("Location: ?screen=confirmation&ref=" . $ref);
        exit;
    }
    
    if ($action === 'auto_vehicle') {
        $plate = strtoupper(sanitize($_POST['plate']));
        $driver_name = sanitize($_POST['driver_name'] ?? null);
        $company = sanitize($_POST['company'] ?? null);
        $cargo = sanitize($_POST['cargo'] ?? null);
        
        // Check if vehicle exists
        $vehicle = $db->fetchOne("SELECT * FROM vehicles WHERE plate_number = ?", [$plate]);
        
        if (!$vehicle) {
            // Create new vehicle
            $vehicle_id = $db->insert('vehicles', [
                'plate_number' => $plate,
                'driver_name' => $driver_name,
                'company' => $company,
                'last_driver_name' => $driver_name,
                'last_visit' => date('Y-m-d H:i:s'),
                'total_visits' => 1
            ]);
        } else {
            $vehicle_id = $vehicle['id'];
            $db->update('vehicles', [
                'last_driver_name' => $driver_name ?: $vehicle['last_driver_name'],
                'company' => $company ?: $vehicle['company'],
                'last_visit' => date('Y-m-d H:i:s'),
                'total_visits' => $vehicle['total_visits'] + 1
            ], 'id = :id', ['id' => $vehicle_id]);
        }
        
        // Check if already inside
        $existing = $db->fetchOne("SELECT * FROM vehicle_logs WHERE vehicle_id = ? AND status = 'inside'", [$vehicle_id]);
        
        if ($existing) {
            // Sign out
            $exit_time = date('Y-m-d H:i:s');
            $duration = (strtotime($exit_time) - strtotime($existing['entry_time'])) / 60;
            $db->update('vehicle_logs', [
                'exit_time' => $exit_time,
                'duration_minutes' => (int)$duration,
                'status' => 'exited'
            ], 'id = :id', ['id' => $existing['id']]);
            
            $ref = 'EXIT-' . str_pad($existing['id'], 6, '0', STR_PAD_LEFT);
        } else {
            // Sign in
            $log_id = $db->insert('vehicle_logs', [
                'vehicle_id' => $vehicle_id,
                'facility_id' => 1,
                'visitor_type' => 'delivery',
                'driver_name' => $driver_name,
                'entry_time' => date('Y-m-d H:i:s'),
                'cargo_description' => $cargo,
                'status' => 'inside',
                'gate_officer_entry_id' => 1
            ]);
            
            $ref = 'IN-' . str_pad($log_id, 6, '0', STR_PAD_LEFT);
        }
        
        header("Location: ?screen=confirmation&ref=" . $ref);
        exit;
    }
}

$prisoners = $db->fetchAll("SELECT id, prisoner_number, first_name, last_name FROM prisoners WHERE status IN ('remand', 'convicted') ORDER BY last_name LIMIT 100");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Uganda Prisons - Visitor Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; touch-action: manipulation; }
        html, body { 
            margin: 0; 
            padding: 0; 
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            height: 100vh;
            width: 100vw;
            font-family: 'Segoe UI', sans-serif;
            overflow: hidden;
        }
        
        .kiosk-container {
            max-width: 800px;
            height: 100vh;
            margin: 0 auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }
        
        .welcome-screen {
            text-align: center;
            color: white;
        }
        .welcome-screen h1 {
            font-size: 2.2rem;
            margin-bottom: 10px;
        }
        .welcome-screen .subtitle {
            font-size: 1.1rem;
            opacity: 0.8;
            margin-bottom: 15px;
        }
        .welcome-screen .datetime {
            font-size: 1.1rem;
            margin-bottom: 25px;
            opacity: 0.8;
        }
        .welcome-screen .tap-hint {
            font-size: 1rem;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 1; }
        }
        
        .purpose-screen {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .purpose-screen h2 {
            color: white;
            text-align: center;
            font-size: 1.6rem;
            margin: auto 0 20px 0;
        }
        .purpose-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 1;
            min-height: 70px;
            margin-bottom: 10px;
            font-size: 1.2rem;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            transition: transform 0.2s;
            gap: 15px;
            max-height: 100px;
        }
        .purpose-btn:active { transform: scale(0.98); }
        .btn-visitation { background: #4361ee; color: white; }
        .btn-hospital { background: #2ec4b6; color: white; }
        .btn-official { background: #7209b7; color: white; }
        .btn-delivery { background: #f72585; color: white; }
        
        .back-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            margin-bottom: 15px;
            align-self: flex-start;
        }
        
        .sub-menu {
            display: flex;
            gap: 15px;
        }
        .sub-btn {
            flex: 1;
            padding: 25px;
            border-radius: 10px;
            border: none;
            font-size: 1rem;
            cursor: pointer;
            color: white;
        }
        .btn-inmate { background: #3a86ff; }
        .btn-staff { background: #8338ec; }
        
        .form-screen {
            background: white;
            border-radius: 15px;
            padding: 20px;
            max-height: calc(100vh - 30px);
            overflow-y: auto;
        }
        .form-screen h2 {
            color: #1a1a2e;
            text-align: center;
            margin-bottom: 20px;
            font-size: 1.3rem;
        }
        .form-screen .form-control {
            padding: 10px 12px;
            font-size: 1rem;
            border-radius: 8px;
            margin-bottom: 12px;
        }
        .form-screen .form-label {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .form-screen .mb-3 { margin-bottom: 12px !important; }
        .submit-btn {
            width: 100%;
            padding: 15px;
            font-size: 1.1rem;
            border-radius: 10px;
            border: none;
            background: #4361ee;
            color: white;
            cursor: pointer;
            margin-top: 15px;
        }
        
        .qr-section { text-align: center; margin-bottom: 20px; }
        .qr-camera {
            width: 120px;
            height: 120px;
            border: 3px dashed #4361ee;
            border-radius: 15px;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #4361ee;
            cursor: pointer;
        }
        
        .confirmation-screen {
            text-align: center;
            color: white;
        }
        .confirmation-screen .check-icon {
            font-size: 4rem;
            color: #2ec4b6;
        }
        .confirmation-screen h1 {
            font-size: 1.8rem;
            margin: 15px 0;
        }
        .confirmation-screen .ref-number {
            font-size: 1.5rem;
            background: rgba(255,255,255,0.2);
            padding: 15px 30px;
            border-radius: 10px;
            display: inline-block;
            margin: 15px 0;
        }
        
        .screensaver {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #000;
            z-index: 9999;
            display: none;
            cursor: pointer;
        }
        .screensaver.active { display: flex; }
        .screensaver-content {
            margin: auto;
            text-align: center;
            color: white;
        }
        .screensaver-content h1 { font-size: 3rem; margin-bottom: 20px; }
        
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

<!-- Screensaver -->
<div class="screensaver" id="screensaver">
    <div class="screensaver-content">
        <h1>UGANDA PRISONS</h1>
        <p style="font-size: 1.5rem; opacity: 0.6;">Touch anywhere to continue</p>
    </div>
</div>

<div class="kiosk-container">
<?php if ($screen === 'welcome'): ?>
    <!-- WELCOME SCREEN -->
    <div class="welcome-screen" id="welcomeScreen">
        <i class="bi bi-shield-lock" style="font-size: 5rem;"></i>
        <h1>UGANDA PRISONS</h1>
        <p class="subtitle">Visitor Registration System</p>
        <div class="datetime" id="dateTime"></div>
        <p class="tap-hint"><i class="bi bi-hand-index"></i> Touch anywhere to begin</p>
    </div>

<?php elseif ($screen === 'purpose'): ?>
    <!-- PURPOSE SELECTION -->
    <div class="purpose-screen">
        <h2>Select Purpose of Visit</h2>
        
        <div class="d-flex flex-column justify-content-center" style="flex: 1; gap: 12px;">
            <button class="purpose-btn btn-visitation" onclick="location.href='?screen=visitation'">
                <i class="bi bi-people-fill" style="font-size: 2rem;"></i> VISITATION
            </button>
            
            <button class="purpose-btn btn-hospital" onclick="location.href='?screen=form&purpose=hospital'">
                <i class="bi bi-heart-pulse" style="font-size: 2rem;"></i> HOSPITAL VISIT
            </button>
            
            <button class="purpose-btn btn-official" onclick="location.href='?screen=form&purpose=official'">
                <i class="bi bi-briefcase" style="font-size: 2rem;"></i> OFFICIAL BUSINESS
            </button>
            
            <button class="purpose-btn btn-delivery" onclick="location.href='?screen=delivery'">
                <i class="bi bi-truck" style="font-size: 2rem;"></i> DELIVERY / SCHOOL VAN
            </button>
        </div>
    </div>

<?php elseif ($screen === 'visitation'): ?>
    <!-- VISITATION SUB-MENU -->
    <div class="purpose-screen">
        <button class="back-btn" onclick="location.href='?screen=purpose'">
            <i class="bi bi-arrow-left"></i> Back
        </button>
        <h2>VISITATION</h2>
        <p style="color: white; text-align: center; margin-bottom: 30px;">Select who you are visiting:</p>
        
        <div class="sub-menu">
            <button class="sub-btn btn-inmate" onclick="location.href='?screen=form&purpose=inmate'">
                <i class="bi bi-person-badge" style="font-size: 2rem; display: block; margin-bottom: 8px;"></i>
                INMATE
            </button>
            
            <button class="sub-btn btn-staff" onclick="location.href='?screen=form&purpose=staff_visit'">
                <i class="bi bi-person" style="font-size: 2rem; display: block; margin-bottom: 8px;"></i>
                STAFF
            </button>
        </div>
    </div>

<?php elseif ($screen === 'form'): ?>
    <!-- REGISTRATION FORMS -->
    <div class="form-screen">
        <button class="back-btn" onclick="history.back()" style="background: #e0e0e0; color: #333;">
            <i class="bi bi-arrow-left"></i> Back
        </button>
        
        <?php if ($purpose === 'inmate'): ?>
            <h2><i class="bi bi-person-badge"></i> Inmate Visitor</h2>
            <form method="POST">
                <input type="hidden" name="action" value="register_visitor">
                <input type="hidden" name="visitor_type" value="inmate">
                <input type="hidden" name="sub_type" value="inmate">
                
                <div class="mb-3">
                    <label class="form-label">National ID Number *</label>
                    <input type="text" name="national_id" class="form-control" placeholder="Enter ID number" required>
                </div>
                
                <div class="row">
                    <div class="col-6">
                        <div class="mb-3">
                            <label class="form-label">First Name *</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label class="form-label">Last Name *</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone" class="form-control" placeholder="0771234567">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Home Address / District</label>
                    <input type="text" name="address" class="form-control" placeholder="e.g., Kira, Wakiso">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Who are you visiting? *</label>
                    <input type="text" name="who_visiting" class="form-control" placeholder="Enter prisoner name or number" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Booking Reference (if any)</label>
                    <input type="text" name="booking_ref" class="form-control" placeholder="Optional">
                </div>
                
                <button type="submit" class="submit-btn">
                    <i class="bi bi-check-circle"></i> REGISTER
                </button>
            </form>
            
        <?php elseif ($purpose === 'staff_visit'): ?>
            <h2><i class="bi bi-person"></i> Visit Staff Member</h2>
            <form method="POST">
                <input type="hidden" name="action" value="register_visitor">
                <input type="hidden" name="visitor_type" value="inmate">
                <input type="hidden" name="sub_type" value="staff">
                
                <div class="mb-3">
                    <label class="form-label">Your Name *</label>
                    <input type="text" name="first_name" class="form-control" placeholder="Enter your full name" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Home Address / Location</label>
                    <input type="text" name="address" class="form-control" placeholder="e.g., Kira, Kampala">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Purpose of Visit *</label>
                    <input type="text" name="purpose" class="form-control" placeholder="e.g., Family matter, Delivery, Meeting" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Who are you meeting? *</label>
                    <input type="text" name="who_visiting" class="form-control" placeholder="Staff member name" required>
                </div>
                
                <button type="submit" class="submit-btn">
                    <i class="bi bi-check-circle"></i> REGISTER
                </button>
            </form>
            
        <?php elseif ($purpose === 'hospital'): ?>
            <h2><i class="bi bi-heart-pulse"></i> Hospital Visitor</h2>
            <form method="POST">
                <input type="hidden" name="action" value="register_visitor">
                <input type="hidden" name="visitor_type" value="hospital">
                
                <div class="mb-3">
                    <label class="form-label">National ID Number *</label>
                    <input type="text" name="national_id" class="form-control" required>
                </div>
                
                <div class="row">
                    <div class="col-6">
                        <div class="mb-3">
                            <label class="form-label">First Name *</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label class="form-label">Last Name *</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone" class="form-control">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Home Address / District</label>
                    <input type="text" name="address" class="form-control">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Patient Name (Optional)</label>
                    <input type="text" name="who_visiting" class="form-control" placeholder="Optional">
                </div>
                
                <button type="submit" class="submit-btn">
                    <i class="bi bi-check-circle"></i> REGISTER
                </button>
            </form>
            
        <?php elseif ($purpose === 'official'): ?>
            <h2><i class="bi bi-briefcase"></i> Official Business</h2>
            <form method="POST">
                <input type="hidden" name="action" value="register_visitor">
                <input type="hidden" name="visitor_type" value="official">
                
                <div class="mb-3">
                    <label class="form-label">National ID Number *</label>
                    <input type="text" name="national_id" class="form-control" required>
                </div>
                
                <div class="row">
                    <div class="col-6">
                        <div class="mb-3">
                            <label class="form-label">First Name *</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label class="form-label">Last Name *</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Organization</label>
                    <input type="text" name="company_name" class="form-control" placeholder="e.g., Ministry, Red Cross, Lawyer's Office">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Purpose *</label>
                    <select name="purpose" class="form-control" required>
                        <option value="">Select purpose...</option>
                        <option value="Meeting">Meeting</option>
                        <option value="Inspection">Inspection</option>
                        <option value="NGO Work">NGO Work</option>
                        <option value="Legal/ Court">Legal / Court</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Who are you meeting? *</label>
                    <input type="text" name="who_visiting" class="form-control" required>
                </div>
                
                <button type="submit" class="submit-btn">
                    <i class="bi bi-check-circle"></i> REGISTER
                </button>
            </form>
        <?php endif; ?>
    </div>

<?php elseif ($screen === 'delivery'): ?>
    <!-- DELIVERY / SCHOOL VAN -->
    <div class="form-screen">
        <button class="back-btn" onclick="location.href='?screen=purpose'" style="background: #e0e0e0; color: #333;">
            <i class="bi bi-arrow-left"></i> Back
        </button>
        
        <h2><i class="bi bi-truck"></i> Delivery / School Van</h2>
        
        <div class="qr-section">
            <div class="qr-camera" onclick="scanQR()">
                <i class="bi bi-qr-code-scan"></i>
            </div>
            <p style="color: #666;">Tap to scan QR code or enter plate manually</p>
        </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="auto_vehicle">
            
            <div class="mb-3">
                <label class="form-label">Vehicle Plate Number *</label>
                <input type="text" name="plate" id="plateInput" class="form-control text-uppercase" 
                       placeholder="e.g., UAR 123X" required style="font-size: 1.5rem; text-align: center;">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Driver Name *</label>
                <input type="text" name="driver_name" class="form-control" placeholder="Enter driver name" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Company / School Name</label>
                <input type="text" name="company" class="form-control" placeholder="e.g., ABC School">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Items / Purpose</label>
                <input type="text" name="cargo" class="form-control" placeholder="e.g., School supplies, Documents">
            </div>
            
            <button type="submit" class="submit-btn">
                <i class="bi bi-check-circle"></i> REGISTER VEHICLE
            </button>
        </form>
    </div>

<?php elseif ($screen === 'confirmation'): ?>
    <!-- CONFIRMATION SCREEN -->
    <div class="confirmation-screen">
        <div class="check-icon">
            <i class="bi bi-check-circle-fill"></i>
        </div>
        <h1>Registration Successful!</h1>
        <p style="font-size: 1.3rem; opacity: 0.8;">Your reference number:</p>
        <div class="ref-number"><?php echo $reference; ?></div>
        <p style="margin-top: 30px;">Entry Time: <?php echo date('d M Y, H:i'); ?></p>
        <p style="opacity: 0.6; margin-top: 40px;">Returning to home screen...</p>
    </div>
<?php endif; ?>
</div>

<script>
let idleTimer;
const idleTimeout = 60000; // 60 seconds

function resetIdleTimer() {
    clearTimeout(idleTimer);
    idleTimer = setTimeout(showScreensaver, idleTimeout);
}

function showScreensaver() {
    document.getElementById('screensaver').classList.add('active');
}

function hideScreensaver() {
    document.getElementById('screensaver').classList.remove('active');
    resetIdleTimer();
}

// Screensaver - click to wake
document.getElementById('screensaver').addEventListener('click', hideScreensaver);

// Welcome screen - touch to go to purpose
document.getElementById('welcomeScreen')?.addEventListener('click', function() {
    window.location.href = '?screen=purpose';
});

// Update date/time
function updateDateTime() {
    const now = new Date();
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    document.getElementById('dateTime').textContent = now.toLocaleDateString('en-UG', options);
}
updateDateTime();
setInterval(updateDateTime, 1000);

// Start idle timer
resetIdleTimer();

// Auto-return to welcome after confirmation
<?php if ($screen === 'confirmation'): ?>
setTimeout(function() {
    window.location.href = '?screen=welcome';
}, 30000); // 30 seconds
<?php endif; ?>

function goBack() {
    history.back();
}

function scanQR() {
    // Placeholder for QR scanner
    alert('QR Scanner would open here.\nFor demo, enter plate number manually.');
    document.getElementById('plateInput').focus();
}

// Check for registered vehicle via plate
document.getElementById('plateInput')?.addEventListener('change', function() {
    const plate = this.value.toUpperCase();
    // Could add AJAX check here to verify if vehicle is registered
});
</script>

</body>
</html>
