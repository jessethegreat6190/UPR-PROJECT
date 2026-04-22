<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireRole(['supervisor', 'hq_command', 'admin']);

$db = getDB();
$user = getCurrentUser();
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action']);
    
    if ($action === 'create') {
        $visitor_data = [
            'visitor_type' => 'inmate',
            'national_id' => sanitize($_POST['national_id']),
            'first_name' => sanitize($_POST['first_name']),
            'last_name' => sanitize($_POST['last_name']),
            'phone' => sanitize($_POST['phone'])
        ];
        
        $visitor_id = $db->insert('visitors', $visitor_data);
        
        $booking_data = [
            'visitor_id' => $visitor_id,
            'prisoner_id' => (int)$_POST['prisoner_id'],
            'facility_id' => $user['facility_id'] ?: 1,
            'booking_date' => sanitize($_POST['booking_date']),
            'booking_time' => sanitize($_POST['booking_time']),
            'visit_purpose' => sanitize($_POST['visit_purpose']),
            'status' => 'approved',
            'approved_by' => $user['id'],
            'approved_at' => date('Y-m-d H:i:s')
        ];
        
        $booking_id = $db->insert('visitor_bookings', $booking_data);
        logAction('booking_created', 'visitor_bookings', $booking_id, null, $booking_data);
        
        $success = "Booking created successfully! Reference: " . $booking_id;
    } elseif ($action === 'approve') {
        $booking_id = (int)$_POST['booking_id'];
        $db->update('visitor_bookings', [
            'status' => 'approved',
            'approved_by' => $user['id'],
            'approved_at' => date('Y-m-d H:i:s')
        ], 'id = :id', ['id' => $booking_id]);
        logAction('booking_approved', 'visitor_bookings', $booking_id);
        $success = "Booking approved";
    } elseif ($action === 'reject') {
        $booking_id = (int)$_POST['booking_id'];
        $db->update('visitor_bookings', [
            'status' => 'rejected',
            'notes' => sanitize($_POST['rejection_reason'])
        ], 'id = :id', ['id' => $booking_id]);
        logAction('booking_rejected', 'visitor_bookings', $booking_id);
        $success = "Booking rejected";
    }
}

$pendingBookings = $db->fetchAll("
    SELECT vb.*, v.first_name, v.last_name, v.phone,
           p.prisoner_number, p.first_name as prisoner_first, p.last_name as prisoner_last
    FROM visitor_bookings vb
    JOIN visitors v ON vb.visitor_id = v.id
    LEFT JOIN prisoners p ON vb.prisoner_id = p.id
    WHERE vb.status = 'pending'
    AND vb.booking_date >= CURDATE()
    ORDER BY vb.booking_date, vb.booking_time", []);

$todayBookings = $db->fetchAll("
    SELECT vb.*, v.first_name, v.last_name,
           p.prisoner_number, p.first_name as prisoner_first, p.last_name as prisoner_last
    FROM visitor_bookings vb
    JOIN visitors v ON vb.visitor_id = v.id
    LEFT JOIN prisoners p ON vb.prisoner_id = p.id
    WHERE vb.booking_date = CURDATE()
    ORDER BY vb.booking_time", []);

$pageTitle = 'Visitor Bookings';
$pageHeader = 'Visitor Booking Management';

include __DIR__ . '/../../includes/header.php';
?>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#pending">Pending (<?php echo count($pendingBookings); ?>)</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#today">Today's Bookings (<?php echo count($todayBookings); ?>)</button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#new">New Booking</button>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="pending">
        <?php if ($pendingBookings): ?>
        <div class="card">
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Ref</th>
                            <th>Visitor</th>
                            <th>Visiting</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingBookings as $b): ?>
                        <tr>
                            <td><strong><?php echo $b['id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($b['first_name'] . ' ' . $b['last_name']); ?></td>
                            <td><?php echo htmlspecialchars(($b['prisoner_first'] ?? '') . ' ' . ($b['prisoner_last'] ?? '')); ?></td>
                            <td><?php echo formatDate($b['booking_date']); ?></td>
                            <td><?php echo formatTime($b['booking_time']); ?></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                </form>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                    <input type="hidden" name="rejection_reason" value="Not approved">
                                    <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-info">No pending bookings</div>
        <?php endif; ?>
    </div>
    
    <div class="tab-pane fade" id="today">
        <?php if ($todayBookings): ?>
        <div class="card">
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Ref</th>
                            <th>Visitor</th>
                            <th>Visiting</th>
                            <th>Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($todayBookings as $b): ?>
                        <tr>
                            <td><strong><?php echo $b['id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($b['first_name'] . ' ' . $b['last_name']); ?></td>
                            <td><?php echo htmlspecialchars(($b['prisoner_first'] ?? '') . ' ' . ($b['prisoner_last'] ?? '')); ?></td>
                            <td><?php echo formatTime($b['booking_time']); ?></td>
                            <td><span class="badge bg-<?php echo $b['status'] === 'approved' ? 'success' : ($b['status'] === 'pending' ? 'warning' : 'secondary'); ?>">
                                <?php echo ucfirst($b['status']); ?>
                            </span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-info">No bookings for today</div>
        <?php endif; ?>
    </div>
    
    <div class="tab-pane fade" id="new">
        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    
                    <h5>Visitor Information</h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name *</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name *</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">National ID</label>
                            <input type="text" name="national_id" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                    </div>
                    
                    <h5>Visit Details</h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Prisoner *</label>
                            <input type="text" id="prisonerSearch" class="form-control" placeholder="Search prisoner...">
                            <input type="hidden" name="prisoner_id" id="prisonerId" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date *</label>
                            <input type="date" name="booking_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Time *</label>
                            <input type="time" name="booking_time" class="form-control" value="09:00" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Visit Purpose</label>
                        <textarea name="visit_purpose" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Create Booking
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
