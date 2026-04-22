// Staff Portal JavaScript
let vehiclesOnSite = [];
let autoExitCount = 0;

document.addEventListener('DOMContentLoaded', function() {
    initPage();
    updateClock();
    setInterval(updateClock, 1000);
    initANPR();
});

function updateClock() {
    const clockEl = document.getElementById('clock');
    if (clockEl) {
        clockEl.textContent = new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
    }
}

function initANPR() {
    setInterval(simulateANPR, 5000);
}

function simulateANPR() {
    const plates = ['UAR 123X', 'UBA 456Y', 'KLA 789Z', 'UGA 001A', 'RDB 234B', '— — —'];
    const randomPlate = plates[Math.floor(Math.random() * plates.length)];
    
    const plateDisplay = document.getElementById('detectedPlate');
    const plateDisplay2 = document.getElementById('anprPlateDisplay');
    const lastCapture = document.getElementById('lastCapture');
    
    if (plateDisplay) {
        if (randomPlate === '— — —') {
            plateDisplay.textContent = 'NO PLATE';
            plateDisplay.style.color = '#ff0000';
        } else {
            plateDisplay.textContent = randomPlate;
            plateDisplay.style.color = '#00ff00';
            
            if (lastCapture) {
                lastCapture.textContent = new Date().toLocaleTimeString('en-GB');
            }
            
            processPlateDetection(randomPlate);
        }
    }
    
    if (plateDisplay2) {
        plateDisplay2.textContent = randomPlate === '— — —' ? 'NO PLATE' : randomPlate;
    }
}

function processPlateDetection(plate) {
    const now = new Date();
    const existingVehicle = vehiclesOnSite.find(v => v.plate === plate && !v.exited);
    
    if (existingVehicle) {
        existingVehicle.exitTime = now;
        existingVehicle.exited = true;
        autoExitCount++;
        
        const exitCountEl = document.getElementById('vehiclesAutoExit');
        if (exitCountEl) exitCountEl.textContent = autoExitCount;
        
        showToast(`ANPR: ${plate} AUTO-EXIT registered at ${formatTime(now)}`);
        updateVehiclesTable();
    } else if (!vehiclesOnSite.find(v => v.plate === plate)) {
        const newVehicle = {
            plate: plate,
            driver: 'Driver ' + (vehiclesOnSite.length + 1),
            purpose: 'General',
            entryTime: now,
            exitTime: null,
            exited: false
        };
        vehiclesOnSite.push(newVehicle);
        showToast(`ANPR: ${plate} ENTRY registered at ${formatTime(now)}`);
        updateVehiclesTable();
        updateVehicleStats();
    }
}

function updateVehiclesTable() {
    const tbody = document.getElementById('vehiclesTable');
    if (!tbody) return;
    
    const onSite = vehiclesOnSite.filter(v => !v.exited);
    
    if (onSite.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#737373;padding:40px;">No vehicles on site - ANPR camera scanning...</td></tr>';
        return;
    }
    
    tbody.innerHTML = onSite.map(v => `
        <tr style="background: ${v.exitTime ? '#e8f5e9' : ''}">
            <td><strong style="color:#000;font-family:monospace;">${v.plate}</strong></td>
            <td>${v.driver}</td>
            <td><span class="badge badge-gray">${v.purpose}</span></td>
            <td><span style="color:#00a000;">${formatTime(v.entryTime)}</span></td>
            <td><span style="color:#666;">${v.exitTime ? formatTime(v.exitTime) : '—'}</span></td>
            <td>${getDuration(v.entryTime)}</td>
            <td>
                ${v.exitTime 
                    ? '<span class="badge badge-black">EXITED</span>' 
                    : '<span class="badge badge-gray">ON SITE</span>'}
            </td>
        </tr>
    `).join('');
}

function updateVehicleStats() {
    const onSite = vehiclesOnSite.filter(v => !v.exited);
    const countEl = document.getElementById('vehiclesInside');
    if (countEl) countEl.textContent = onSite.length;
    
    const exitedEl = document.getElementById('vehiclesExited');
    if (exitedEl) exitedEl.textContent = vehiclesOnSite.filter(v => v.exited).length;
}

function initPage() {
    const isLoggedIn = sessionStorage.getItem('updms_logged_in');
    const userData = JSON.parse(sessionStorage.getItem('updms_user') || 'null');

    const loginForm = document.getElementById('loginForm');
    const dashboard = document.getElementById('staffDashboard');

    if (isLoggedIn && userData) {
        if (loginForm) loginForm.style.display = 'none';
        if (dashboard) dashboard.style.display = 'flex';
        const userNameEl = document.getElementById('userName');
        const userRoleEl = document.getElementById('userRole');
        if (userNameEl) userNameEl.textContent = userData.name || 'Staff';
        if (userRoleEl) userRoleEl.textContent = userData.role || 'GATE OFFICER';
        loadQueue();
    } else {
        if (loginForm) loginForm.style.display = 'flex';
        if (dashboard) dashboard.style.display = 'none';
    }
}

function handleLogin(e) {
    e.preventDefault();
    const username = document.getElementById('loginUsername').value;
    const password = document.getElementById('loginPassword').value;

    if (username === 'admin' && password === 'admin123') {
        sessionStorage.setItem('updms_logged_in', 'true');
        sessionStorage.setItem('updms_user', JSON.stringify({
            name: 'Admin User',
            role: 'ADMIN',
            username: username
        }));
        initPage();
        loadQueue();
    } else {
        alert('Invalid credentials. Try: admin / admin123');
    }
    return false;
}

function handleLogout() {
    sessionStorage.removeItem('updms_logged_in');
    sessionStorage.removeItem('updms_user');
    initPage();
}

function showPage(page, el) {
    document.querySelectorAll('.page-section').forEach(p => p.style.display = 'none');
    document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));

    const pageEl = document.getElementById('page-' + page);
    if (pageEl) pageEl.style.display = 'block';
    if (el) el.classList.add('active');

    loadPageData(page);
}

function showToast(message) {
    const toast = document.getElementById('toast');
    if (toast) {
        toast.textContent = message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 4000);
    }
}

function formatTime(dateStr) {
    if (!dateStr) return '—';
    const date = dateStr instanceof Date ? dateStr : new Date(dateStr);
    return date.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
}

function getDuration(entryTime) {
    if (!entryTime) return '—';
    const date = entryTime instanceof Date ? entryTime : new Date(entryTime);
    const mins = Math.floor((Date.now() - date) / 60000);
    if (mins < 60) return mins + 'm';
    const hours = Math.floor(mins / 60);
    return hours + 'h ' + (mins % 60) + 'm';
}

async function loadPageData(page) {
    switch(page) {
        case 'queue': await loadQueue(); break;
        case 'vehicles': updateVehiclesTable(); updateVehicleStats(); break;
        case 'bookings': await loadBookings(); break;
        case 'visitors': await loadVisitors(); break;
        case 'alerts': await loadAlerts(); break;
        case 'reports': await loadReports(); break;
    }
}

async function loadQueue() {
    try {
        const res = await fetch('../api/visitors.php?action=list_visitors');
        const data = await res.json();
        const pending = Array.isArray(data) ? data.filter(v => v.status === 'inside' || v.status === 'pending') : [];
        const countEl = document.getElementById('queueCount');
        if (countEl) countEl.textContent = pending.length;

        const tbody = document.getElementById('queueTable');
        if (!tbody) return;

        if (pending.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#737373;padding:40px;">No visitors waiting</td></tr>';
            return;
        }

        tbody.innerHTML = pending.map(v => `
            <tr>
                <td><strong>${v.ref_number || '—'}</strong></td>
                <td>${v.full_name || v.driver_name || 'Visitor'}</td>
                <td><span class="badge badge-gray">${v.visitor_type || 'Visit'}</span></td>
                <td>${v.destination || '—'}</td>
                <td>${formatTime(v.entry_time)}</td>
                <td>
                    <button class="btn" style="padding:4px 10px;font-size:10px;" onclick="approveVisitor(${v.id})">Approve</button>
                    <button class="btn-outline" style="padding:4px 10px;font-size:10px;" onclick="rejectVisitor(${v.id})">Reject</button>
                </td>
            </tr>
        `).join('');
    } catch (e) {
        const tbody = document.getElementById('queueTable');
        if (tbody) tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#737373;padding:40px;">Connect database</td></tr>';
    }
}

async function loadVehicles() {
    try {
        const res = await fetch('../api/dashboard.php?action=current_vehicles');
        const data = await res.json();
        const vehicles = Array.isArray(data) ? data : [];
        const countEl = document.getElementById('vehiclesInside');
        if (countEl) countEl.textContent = vehicles.length;

        const tbody = document.getElementById('vehiclesTable');
        if (!tbody) return;

        if (vehicles.length === 0 && vehiclesOnSite.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#737373;padding:40px;">No vehicles on site</td></tr>';
            return;
        }

        if (vehicles.length > 0) {
            vehicles.forEach(v => {
                if (!vehiclesOnSite.find(x => x.plate === v.plate_number)) {
                    vehiclesOnSite.push({
                        plate: v.plate_number,
                        driver: v.driver_name || 'Unknown',
                        purpose: v.visitor_type || 'General',
                        entryTime: new Date(v.entry_time),
                        exitTime: null,
                        exited: false
                    });
                }
            });
        }
        updateVehiclesTable();
        updateVehicleStats();
    } catch (e) {
        updateVehiclesTable();
        updateVehicleStats();
    }
}

async function loadBookings() {
    try {
        const res = await fetch('../api/visitors.php?action=list_bookings');
        const data = await res.json();
        const tbody = document.getElementById('bookingsTable');
        if (!tbody) return;

        if (!Array.isArray(data) || data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#737373;padding:40px;">No bookings</td></tr>';
            return;
        }

        tbody.innerHTML = data.map(b => `
            <tr>
                <td><strong>${b.ref_number}</strong></td>
                <td>${b.full_name || '—'}</td>
                <td>${b.visit_type || '—'}</td>
                <td>${b.booking_date || '—'}</td>
                <td>${b.time_slot || '—'}</td>
                <td><span class="badge badge-${b.status === 'approved' ? 'black' : 'gray'}">${b.status}</span></td>
                <td><button class="btn" style="padding:4px 10px;font-size:10px;">View</button></td>
            </tr>
        `).join('');
    } catch (e) {}
}

async function loadAlerts() {
    try {
        const res = await fetch('../api/dashboard.php?action=overstay');
        const data = await res.json();
        const list = document.getElementById('alertsList');
        if (!list) return;

        if (!Array.isArray(data) || data.length === 0) {
            list.innerHTML = '<div class="alert alert-info" style="text-align:center;padding:40px;">No active alerts</div>';
            return;
        }

        list.innerHTML = data.map(a => `
            <div class="alert alert-error">
                <strong>Overstay:</strong> ${a.plate_number || 'Unknown'} - ${a.hours_inside || 0}h
            </div>
        `).join('');
    } catch (e) {}
}

async function loadReports() {
    try {
        const res = await fetch('../api/dashboard.php?action=stats');
        const data = await res.json();
        const visitorsEl = document.getElementById('reportVisitors');
        const vehiclesEl = document.getElementById('reportVehicles');
        const alertsEl = document.getElementById('reportAlerts');
        if (visitorsEl) visitorsEl.textContent = data.today_visitors || 0;
        if (vehiclesEl) vehiclesEl.textContent = vehiclesOnSite.filter(v => !v.exited).length;
        if (alertsEl) alertsEl.textContent = data.overstay_alerts || 0;
    } catch (e) {}
}

async function approveVisitor(id) {
    if (confirm('Approve this visitor?')) {
        try {
            await fetch('../api/visitors.php?action=approve_visitor&id=' + id, { method: 'POST' });
        } catch (e) {}
        showToast('Visitor approved');
        loadQueue();
    }
}

async function rejectVisitor(id) {
    if (confirm('Reject this visitor?')) {
        try {
            await fetch('../api/visitors.php?action=reject_visitor&id=' + id, { method: 'POST' });
        } catch (e) {}
        showToast('Visitor rejected');
        loadQueue();
    }
}

async function recordExit(id) {
    if (confirm('Record exit?')) {
        try {
            await fetch('../api/visitors.php?action=record_exit&id=' + id, { method: 'POST' });
        } catch (e) {}
        showToast('Exit recorded');
        loadVehicles();
    }
}

// Auto-refresh queue
setInterval(loadQueue, 10000);
