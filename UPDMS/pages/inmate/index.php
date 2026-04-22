<?php
require_once __DIR__ . '/../../config/bootstrap.php';
requireLogin();

$db = getDB();
$user = getCurrentUser();
$facility_id = $_SESSION['facility_id'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = sanitize($_POST['action']);
    
    switch($action) {
        case 'register_inmate':
            $firstName = sanitize($_POST['firstName'] ?? '');
            $surname = sanitize($_POST['surname'] ?? '');
            $nin = sanitize($_POST['nin'] ?? '');
            $dob = sanitize($_POST['dateOfBirth'] ?? '');
            $gender = sanitize($_POST['gender'] ?? '');
            $facility = sanitize($_POST['facilityName'] ?? '');
            
            if (empty($firstName) || empty($surname) || empty($nin)) {
                echo json_encode(['success' => false, 'message' => 'Required fields missing']);
                exit;
            }
            
            $ninHash = hash('sha256', $nin);
            $existing = $db->fetchOne("SELECT id FROM inmates WHERE nin_hash = ?", [$ninHash]);
            if ($existing) {
                echo json_encode(['success' => false, 'message' => 'NIN already registered']);
                exit;
            }
            
            $inmateId = 'INM-' . strtoupper(substr(md5(time()), 0, 8)) . '-' . strtoupper(substr(md5(rand()), 0, 4));
            
            $id = $db->insert('inmates', [
                'first_name' => $firstName,
                'surname' => $surname,
                'nin_hash' => $ninHash,
                'nin_encrypted' => encrypt($nin),
                'date_of_birth' => $dob,
                'gender' => $gender,
                'facility_name' => $facility,
                'inmate_id' => $inmateId,
                'facility_id' => $facility_id,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            echo json_encode(['success' => true, 'inmateId' => $inmateId, 'id' => $id]);
            break;
            
        case 'search_inmates':
            $query = sanitize($_POST['query'] ?? '');
            $inmates = $db->fetchAll("SELECT * FROM inmates WHERE first_name LIKE ? OR surname LIKE ? OR inmate_id LIKE ? LIMIT 50", 
                ["%$query%", "%$query%", "%$query%"]);
            echo json_encode(['success' => true, 'data' => $inmates]);
            break;
            
        case 'request_visit':
            $inmate_id = intval($_POST['inmate_id'] ?? 0);
            $visitor_name = sanitize($_POST['visitor_name'] ?? '');
            $relationship = sanitize($_POST['relationship'] ?? '');
            $visit_date = sanitize($_POST['visit_date'] ?? '');
            
            $id = $db->insert('visit_requests', [
                'inmate_id' => $inmate_id,
                'visitor_name' => $visitor_name,
                'relationship' => $relationship,
                'visit_date' => $visit_date,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            echo json_encode(['success' => true, 'requestId' => $id]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inmate Visiting - UPDMS</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --primary: #000;
    --secondary: #333;
    --success: #22c55e;
    --danger: #ef4444;
    --warning: #f59e0b;
    --white: #fff;
    --light: #f8f9fa;
    --gray: #6b7280;
    --border: #e5e7eb;
    --shadow: 0 4px 6px rgba(0,0,0,0.1);
    --radius: 8px;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Inter', sans-serif; background: var(--light); color: var(--primary); min-height: 100vh; }
.header { background: var(--primary); color: var(--white); padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; }
.header h1 { font-size: 18px; font-weight: 600; }
.main { max-width: 1200px; margin: 24px auto; padding: 0 16px; }
.cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; }
.card { background: var(--white); border-radius: var(--radius); padding: 20px; box-shadow: var(--shadow); }
.card h3 { font-size: 16px; font-weight: 600; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid var(--primary); }
.form-group { margin-bottom: 16px; }
.form-group label { display: block; font-size: 13px; font-weight: 500; color: var(--gray); margin-bottom: 6px; }
.form-group input, .form-group select { width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 6px; font-size: 14px; }
.form-group input:focus { outline: none; border-color: var(--primary); }
.btn { padding: 10px 20px; background: var(--primary); color: var(--white); border: none; border-radius: 6px; font-size: 14px; font-weight: 500; cursor: pointer; }
.btn:hover { opacity: 0.9; }
.btn-outline { background: var(--white); color: var(--primary); border: 2px solid var(--primary); }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border); }
th { font-size: 12px; font-weight: 600; color: var(--gray); text-transform: uppercase; }
.status { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; }
.status.active { background: #dcfce7; color: #166534; }
.status.pending { background: #fef3c7; color: #92400e; }
.search-bar { display: flex; gap: 12px; margin-bottom: 20px; }
.search-bar input { flex: 1; padding: 12px; border: 1px solid var(--border); border-radius: 6px; }
.tabs { display: flex; gap: 8px; margin-bottom: 20px; border-bottom: 2px solid var(--border); }
.tab { padding: 12px 20px; font-size: 14px; font-weight: 500; cursor: pointer; border-bottom: 2px solid transparent; }
.tab.active { border-bottom-color: var(--primary); color: var(--primary); }
.hidden { display: none; }
</style>
</head>
<body>
<header class="header">
<h1>Inmate Visiting Management</h1>
<a href="index.php" class="btn btn-outline" style="color:var(--white); border-color:var(--white);">Back</a>
</header>

<main class="main">
<div class="tabs">
<div class="tab active" onclick="showTab('register')">Register Inmate</div>
<div class="tab" onclick="showTab('requests')">Visit Requests</div>
<div class="tab" onclick="showTab('records')">Records</div>
</div>

<div id="tab-register" class="card">
<h3>Register New Inmate</h3>
<form id="registerForm">
<div class="form-group"><label>First Name *</label><input type="text" name="firstName" required></div>
<div class="form-group"><label>Surname *</label><input type="text" name="surname" required></div>
<div class="form-group"><label>National ID (NIN) *</label><input type="text" name="nin" required></div>
<div class="form-group"><label>Date of Birth</label><input type="date" name="dateOfBirth"></div>
<div class="form-group"><label>Gender</label><select name="gender"><option value="male">Male</option><option value="female">Female</option></select></div>
<div class="form-group"><label>Facility</label><input type="text" name="facilityName"></div>
<button type="submit" class="btn">Register Inmate</button>
</form>
</div>

<div id="tab-requests" class="card hidden">
<h3>Request Visit</h3>
<form id="requestForm">
<div class="form-group"><label>Inmate ID</label><input type="text" name="inmate_id"></div>
<div class="form-group"><label>Visitor Name</label><input type="text" name="visitor_name"></div>
<div class="form-group"><label>Relationship</label><select name="relationship"><option>Family</option><option>Friend</option><option>Legal</option><option>Other</option></select></div>
<div class="form-group"><label>Visit Date</label><input type="date" name="visit_date"></div>
<button type="submit" class="btn">Submit Request</button>
</form>
</div>

<div id="tab-records" class="card hidden">
<div class="search-bar">
<input type="text" id="searchInput" placeholder="Search by name or ID...">
<button class="btn" onclick="searchInmates()">Search</button>
</div>
<table>
<thead><tr><th>ID</th><th>Name</th><th>NIN</th><th>Facility</th><th>Status</th></tr></thead>
<tbody id="inmatesList"></tbody>
</table>
</div>
</div>
</main>

<script>
function showTab(tab) {
document.querySelectorAll('.card').forEach(c => c.classList.add('hidden'));
document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
document.getElementById('tab-' + tab).classList.remove('hidden');
document.querySelector('[onclick="showTab(\'' + tab + '\')"]').classList.add('active');
}

document.getElementById('registerForm').onsubmit = async (e) => {
e.preventDefault();
const form = new FormData(e.target);
form.append('action', 'register_inmate');
const res = await fetch('index.php', { method: 'POST', body: form });
const data = await res.json();
alert(data.success ? 'Registered: ' + data.inmateId : data.message);
if (data.success) e.target.reset();
};

document.getElementById('requestForm').onsubmit = async (e) => {
e.preventDefault();
const form = new FormData(e.target);
form.append('action', 'request_visit');
const res = await fetch('index.php', { method: 'POST', body: form });
const data = await res.json();
alert(data.success ? 'Request submitted' : data.message);
};

async function searchInmates() {
const q = document.getElementById('searchInput').value;
const form = new FormData();
form.append('action', 'search_inmates');
form.append('query', q);
const res = await fetch('index.php', { method: 'POST', body: form });
const data = await res.json();
document.getElementById('inmatesList').innerHTML = data.data.map(m => 
`<tr><td>${m.inmate_id}</td><td>${m.first_name} ${m.surname}</td><td>${m.nin_hash?.substring(0,8)}...</td><td>${m.facility_name}</td><td><span class="status ${m.status}">${m.status}</span></td></tr>`
).join('') || '<tr><td colspan="5">No results</td></tr>';
}
</script>
</body>
</html>