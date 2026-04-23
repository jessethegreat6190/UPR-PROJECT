<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireRole(['gate_officer', 'supervisor', 'hq_command', 'admin']);

$db = getDB();
$user = getCurrentUser();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plate = strtoupper(sanitize($_POST['plate_number']));
    $driver_name = sanitize($_POST['driver_name']);
    $driver_id = sanitize($_POST['driver_id']);
    $visitor_type = sanitize($_POST['visitor_type']);
    $cargo_description = sanitize($_POST['cargo_description']);
    $cargo_checked = isset($_POST['cargo_checked']) ? 1 : 0;
    $notes = sanitize($_POST['notes']);
    
    // Check if vehicle exists
    $vehicle = $db->fetchOne("SELECT * FROM vehicles WHERE plate_number = ?", [$plate]);
    
    if (!$vehicle) {
        // Create new vehicle
        $vehicle_id = $db->insert('vehicles', [
            'plate_number' => $plate,
            'last_driver_name' => $driver_name,
            'last_visit' => date('Y-m-d H:i:s'),
            'total_visits' => 1
        ]);
    } else {
        $vehicle_id = $vehicle['id'];
        
        // Check if blacklisted
        if ($vehicle['is_blacklisted']) {
            $error = "Vehicle is blacklisted: " . $vehicle['blacklisted_reason'];
        } else {
            // Update vehicle with new driver
            $db->update('vehicles', [
                'last_driver_name' => $driver_name,
                'last_visit' => date('Y-m-d H:i:s'),
                'total_visits' => $vehicle['total_visits'] + 1
            ], 'id = :id', ['id' => $vehicle_id]);
        }
    }
    
    if (!$error) {
        $photo_path = null;
        if (!empty($_POST['plate_photo'])) {
            $data = $_POST['plate_photo'];
            if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
                $data = substr($data, strpos($data, ',') + 1);
                $type = strtolower($type[1]); // jpg, png, gif
                $data = base64_decode($data);
                
                $filename = 'plate_' . time() . '_' . $plate . '.jpg';
                $photo_path = 'uploads/vehicles/' . $filename;
                file_put_contents(__DIR__ . '/../../' . $photo_path, $data);
            }
        }

        // Create vehicle log
        $log_id = $db->insert('vehicle_logs', [
            'vehicle_id' => $vehicle_id,
            'facility_id' => $user['facility_id'] ?: 1,
            'visitor_type' => $visitor_type,
            'driver_name' => $driver_name,
            'driver_id' => $driver_id,
            'entry_time' => date('Y-m-d H:i:s'),
            'cargo_description' => $cargo_description,
            'cargo_checked' => $cargo_checked,
            'status' => 'inside',
            'gate_officer_entry_id' => $user['id'],
            'entry_photo_path' => $photo_path
        ]);
        
        logAction('vehicle_entry', 'vehicle_logs', $log_id, null, [
            'plate' => $plate,
            'driver' => $driver_name,
            'type' => $visitor_type
        ]);
        
        $success = "Vehicle $plate recorded successfully at " . date('H:i:s');
    }
}

$pageTitle = 'Vehicle Entry';
$pageHeader = 'Gate Control - Vehicle Entry';
$pageActions = '<a href="' . SITE_URL . '/pages/gate/exit.php" class="btn btn-success"><i class="bi bi-box-arrow-left me-2"></i>Vehicle Exit</a>';

include __DIR__ . '/../../includes/header.php';
?>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-truck me-2"></i>Record Vehicle Entry</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="plate_photo" id="platePhoto">
                    <div class="mb-3">
                        <label class="form-label">Vehicle Plate Number *</label>
                        <div class="input-group">
                            <input type="text" name="plate_number" id="plateInput" class="form-control form-control-lg text-uppercase" 
                                   placeholder="e.g., UAR 123X" required autofocus onblur="checkPlate(this.value)">
                            <button type="button" class="btn btn-secondary" id="scanPlateBtn">
                                <i class="bi bi-camera me-1"></i>Scan
                            </button>
                        </div>
                        <small class="text-muted">Enter plate number or scan from camera</small>
                    </div>

                    <!-- Camera Section (Hidden by default) -->
                    <div id="cameraSection" class="mb-3 d-none">
                        <div class="position-relative bg-dark rounded overflow-hidden" style="height: 300px;">
                            <video id="video" class="w-100 h-100" style="object-fit: cover;"></video>
                            <div class="position-absolute top-50 start-50 translate-middle border border-success border-4" 
                                 style="width: 80%; height: 40%; pointer-events: none;"></div>
                            <div class="position-absolute bottom-0 start-0 end-0 p-2 bg-dark bg-opacity-50 text-center">
                                <button type="button" id="captureBtn" class="btn btn-light btn-sm rounded-circle p-3 shadow">
                                    <div class="bg-danger rounded-circle" style="width: 20px; height: 20px;"></div>
                                </button>
                                <button type="button" id="closeCameraBtn" class="btn btn-danger btn-sm float-end">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                        <canvas id="canvas" class="d-none"></canvas>
                        <div id="ocrStatus" class="mt-2 text-center text-primary d-none">
                            <div class="spinner-border spinner-border-sm me-2"></div>Reading plate...
                        </div>
                    </div>
                    
                    <div id="vehicleInfo" class="alert alert-info d-none">
                        <strong>Known Driver:</strong> <span id="knownDriver"></span><br>
                        <small>Driver name auto-filled from previous visit</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Driver Name *</label>
                        <input type="text" name="driver_name" id="driverName" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Driver ID (for deliveries)</label>
                        <input type="text" name="driver_id" class="form-control" placeholder="National ID">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Vehicle Category *</label>
                        <select name="visitor_type" class="form-select" required>
                            <option value="">Select category...</option>
                            <option value="inmate">Inmate Visitor Car</option>
                            <option value="hospital">Hospital Visitor Car</option>
                            <option value="staff">Staff Car</option>
                            <option value="official">Official Car</option>
                            <option value="delivery">Delivery Truck</option>
                        </select>
                    </div>
                    
                    <div id="cargoSection" class="d-none">
                        <div class="mb-3">
                            <label class="form-label">Cargo Description</label>
                            <textarea name="cargo_description" class="form-control" rows="2" placeholder="What's being delivered?"></textarea>
                        </div>
                        <div class="form-check mb-3">
                            <input type="checkbox" name="cargo_checked" class="form-check-input" id="cargoChecked">
                            <label class="form-check-label" for="cargoChecked">Cargo has been inspected</label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg w-100 touch-btn">
                        <i class="bi bi-arrow-right-circle me-2"></i>Record Entry
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Today's Entries</h5>
            </div>
            <div class="card-body">
                <?php
                $todayEntries = $db->fetchAll("
                    SELECT vl.*, v.plate_number, v.last_driver_name 
                    FROM vehicle_logs vl 
                    JOIN vehicles v ON vl.vehicle_id = v.id 
                    WHERE DATE(vl.entry_time) = CURDATE() 
                    AND vl.facility_id = ? 
                    ORDER BY vl.entry_time DESC", [$user['facility_id'] ?: 1]);
                ?>
                
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Photo</th>
                                <th>Plate</th>
                                <th>Driver</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($todayEntries as $e): ?>
                            <tr>
                                <td>
                                    <?php if ($e['entry_photo_path']): ?>
                                        <img src="<?php echo SITE_URL . '/' . $e['entry_photo_path']; ?>" 
                                             class="img-thumbnail" style="width: 80px; height: 40px; object-fit: cover; cursor: pointer;"
                                             onclick="window.open(this.src)">
                                    <?php else: ?>
                                        <span class="text-muted">No Photo</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($e['plate_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($e['driver_name'] ?: $e['last_driver_name']); ?></td>
                                <td><?php echo formatTime($e['entry_time']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($todayEntries)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">No entries today</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Overstay Alerts</h5>
            </div>
            <div class="card-body">
                <?php
                $overstays = $db->fetchAll("
                    SELECT vl.*, v.plate_number, v.last_driver_name 
                    FROM vehicle_logs vl 
                    JOIN vehicles v ON vl.vehicle_id = v.id 
                    WHERE vl.status = 'inside' 
                    AND TIMESTAMPDIFF(HOUR, vl.entry_time, NOW()) > 72 
                    AND vl.facility_id = ?", [$user['facility_id'] ?: 1]);
                ?>
                
                <?php if ($overstays): ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Plate</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($overstays as $o): ?>
                            <?php $hours = (time() - strtotime($o['entry_time'])) / 3600; ?>
                            <tr class="table-danger">
                                <td><strong><?php echo htmlspecialchars($o['plate_number']); ?></strong></td>
                                <td><?php echo floor($hours) . 'h+' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-success mb-0"><i class="bi bi-check-circle me-2"></i>No vehicles overstaying</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>

<script>
let stream = null;
const scanPlateBtn = document.getElementById('scanPlateBtn');
const cameraSection = document.getElementById('cameraSection');
const video = document.getElementById('video');
const canvas = document.getElementById('canvas');
const captureBtn = document.getElementById('captureBtn');
const closeCameraBtn = document.getElementById('closeCameraBtn');
const plateInput = document.getElementById('plateInput');
const ocrStatus = document.getElementById('ocrStatus');

scanPlateBtn.addEventListener('click', async () => {
    try {
        stream = await navigator.mediaDevices.getUserMedia({ 
            video: { 
                facingMode: 'environment',
                width: { ideal: 1280 },
                height: { ideal: 720 }
            } 
        });
        video.srcObject = stream;
        video.play();
        cameraSection.classList.remove('d-none');
        scanPlateBtn.disabled = true;
    } catch (err) {
        console.error("Error accessing camera:", err);
        alert("Could not access camera. Please check permissions.");
    }
});

closeCameraBtn.addEventListener('click', () => {
    stopCamera();
});

function stopCamera() {
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }
    cameraSection.classList.add('d-none');
    scanPlateBtn.disabled = false;
}

captureBtn.addEventListener('click', async () => {
    const context = canvas.getContext('2d');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    context.drawImage(video, 0, 0, canvas.width, canvas.height);
    
    // Zoom in on the center (where the green box is)
    const zoomX = canvas.width * 0.1;
    const zoomY = canvas.height * 0.3;
    const zoomW = canvas.width * 0.8;
    const zoomH = canvas.height * 0.4;
    
    const croppedCanvas = document.createElement('canvas');
    croppedCanvas.width = zoomW;
    croppedCanvas.height = zoomH;
    croppedCanvas.getContext('2d').drawImage(canvas, zoomX, zoomY, zoomW, zoomH, 0, 0, zoomW, zoomH);
    
    // Save photo to hidden field
    document.getElementById('platePhoto').value = croppedCanvas.toDataURL('image/jpeg');
    
    ocrStatus.classList.remove('d-none');
    
    try {
        const result = await Tesseract.recognize(croppedCanvas, 'eng', {
            logger: m => console.log(m)
        });
        
        // Clean up the OCR text (regex for Uganda plates: UXX 123X)
        const text = result.data.text.toUpperCase().replace(/[^A-Z0-9 ]/g, '').trim();
        const matches = text.match(/U[A-Z0-9]{2}\s?[A-Z0-9]{3}\s?[A-Z0-9]{1}/);
        
        if (matches) {
            plateInput.value = matches[0].replace(/\s/g, '');
            checkPlate(plateInput.value);
            stopCamera();
        } else {
            // If no perfect match, just take the first few lines
            const lines = text.split('\n');
            if (lines[0]) {
                plateInput.value = lines[0].replace(/\s/g, '');
                checkPlate(plateInput.value);
                stopCamera();
            } else {
                alert("Could not read plate clearly. Please try again or type manually.");
            }
        }
    } catch (err) {
        console.error("OCR Error:", err);
        alert("Error processing image.");
    } finally {
        ocrStatus.classList.add('d-none');
    }
});

function checkPlate(plate) {
    if (!plate) return;
    plate = plate.toUpperCase();
    fetch('<?php echo SITE_URL; ?>/api/vehicles.php?action=check&plate=' + encodeURIComponent(plate))
        .then(r => r.json())
        .then(data => {
            const infoDiv = document.getElementById('vehicleInfo');
            const driverInput = document.getElementById('driverName');
            const knownDriver = document.getElementById('knownDriver');
            
            if (data.found) {
                infoDiv.classList.remove('d-none');
                knownDriver.textContent = data.last_driver_name || 'Unknown';
                if (!driverInput.value && data.last_driver_name) {
                    driverInput.value = data.last_driver_name;
                }
            } else {
                infoDiv.classList.add('d-none');
            }
        });
}

document.querySelector('select[name="visitor_type"]').addEventListener('change', function() {
    const cargoSection = document.getElementById('cargoSection');
    if (this.value === 'delivery' || this.value === 'official') {
        cargoSection.classList.remove('d-none');
    } else {
        cargoSection.classList.add('d-none');
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
