// Quarters Module JavaScript
const quartersData = {
    'A': { name: 'Senior Staff', houses: 20, occupied: 18, category: 'senior' },
    'B': { name: 'Senior Staff', houses: 20, occupied: 19, category: 'senior' },
    'C': { name: 'Officers', houses: 30, occupied: 25, category: 'officer' },
    'D': { name: 'Officers', houses: 30, occupied: 28, category: 'officer' },
    'E': { name: 'Warders', houses: 40, occupied: 35, category: 'warder' },
    'F': { name: 'Warders', houses: 40, occupied: 38, category: 'warder' },
    'J': { name: 'Warders', houses: 35, occupied: 30, category: 'warder' },
    'M': { name: 'Support Staff', houses: 25, occupied: 20, category: 'support' },
    'O': { name: 'Support Staff', houses: 25, occupied: 22, category: 'support' }
};

let staffDatabase = [
    { name: 'ASP Katamba Moses', serviceNo: 'UPS/1987/045', quarter: 'A', house: '01', phone: '0772123456', rank: 'ASP', since: '2018-03-15' },
    { name: 'DSP Akello Faith', serviceNo: 'UPS/1990/123', quarter: 'A', house: '03', phone: '0773456789', rank: 'DSP', since: '2019-07-22' },
    { name: 'Inspector Wanyama Robert', serviceNo: 'UPS/1995/234', quarter: 'B', house: '05', phone: '0774567890', rank: 'IP', since: '2020-01-10' },
    { name: 'Sgt Maj. Namuli Josephine', serviceNo: 'UPS/1998/156', quarter: 'C', house: '08', phone: '0775678901', rank: 'Sgt Maj', since: '2017-08-05' },
    { name: 'CPL Ssemakula John', serviceNo: 'UPS/2001/289', quarter: 'D', house: '12', phone: '0776789012', rank: 'CPL', since: '2019-11-20' },
    { name: 'PC Tumusiime Alex', serviceNo: 'UPS/2005/345', quarter: 'E', house: '15', phone: '0777890123', rank: 'PC', since: '2018-05-14' },
    { name: 'WPC Nakanwagi Ruth', serviceNo: 'UPS/2008/412', quarter: 'F', house: '22', phone: '0778901234', rank: 'WPC', since: '2020-09-01' },
    { name: 'PC Kasule Hassan', serviceNo: 'UPS/2010/523', quarter: 'J', house: '10', phone: '0779012345', rank: 'PC', since: '2019-02-28' },
    { name: 'Driver Okello Peter', serviceNo: 'UPS/2012/634', quarter: 'M', house: '05', phone: '0770123456', rank: 'Driver', since: '2018-12-15' },
    { name: 'Askari Opolot Sam', serviceNo: 'UPS/2015/745', quarter: 'O', house: '08', phone: '0701234567', rank: 'Askari', since: '2020-06-20' }
];

document.addEventListener('DOMContentLoaded', function() {
    updateClock();
    setInterval(updateClock, 1000);
    loadStaffFromStorage();
});

function updateClock() {
    const clockEl = document.getElementById('clock');
    if (clockEl) {
        clockEl.textContent = new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
    }
}

function loadStaffFromStorage() {
    const saved = localStorage.getItem('updms_staff');
    if (saved) {
        staffDatabase = JSON.parse(saved);
    }
    updateQuarterStats();
}

function updateQuarterStats() {
    Object.keys(quartersData).forEach(quarter => {
        const count = staffDatabase.filter(s => s.quarter === quarter).length;
        quartersData[quarter].occupied = count;
    });
}

function openQuarter(letter) {
    const quarter = quartersData[letter];
    document.getElementById('modalTitle').textContent = `Quarter ${letter} - ${quarter.name}`;
    
    const grid = document.getElementById('housesGrid');
    grid.innerHTML = '';
    
    for (let i = 1; i <= quarter.houses; i++) {
        const houseNum = String(i).padStart(2, '0');
        const houseId = `${letter}-${houseNum}`;
        const occupant = staffDatabase.find(s => s.quarter === letter && s.house === houseNum);
        
        const tile = document.createElement('div');
        tile.className = 'house-tile ' + (occupant ? 'occupied' : 'vacant');
        tile.textContent = houseId;
        tile.onclick = () => openHouse(letter, houseNum, occupant);
        grid.appendChild(tile);
    }
    
    document.getElementById('quarterModal').classList.add('show');
}

function closeQuarter() {
    document.getElementById('quarterModal').classList.remove('show');
}

function openHouse(quarter, houseNum, occupant) {
    const houseId = `${quarter}-${houseNum}`;
    document.getElementById('houseModalTitle').textContent = `House ${houseId}`;
    
    const details = document.getElementById('houseDetails');
    
    if (occupant) {
        const history = getOccupancyHistory(occupant);
        details.innerHTML = `
            <div class="house-detail-section">
                <h4>Current Occupant</h4>
                <div class="detail-row">
                    <span class="detail-label">Name</span>
                    <span class="detail-value">${occupant.name}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Service No.</span>
                    <span class="detail-value">${occupant.serviceNo}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Rank</span>
                    <span class="detail-value">${occupant.rank}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Phone</span>
                    <span class="detail-value">${occupant.phone}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Occupied Since</span>
                    <span class="detail-value">${formatDate(occupant.since)}</span>
                </div>
            </div>
            
            <div class="house-detail-section">
                <h4>Occupancy History</h4>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Staff Member</th>
                            <th>From</th>
                            <th>To</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${history.map(h => `
                            <tr>
                                <td>${h.name}</td>
                                <td>${h.from}</td>
                                <td>${h.to}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
            
            <div class="form-actions">
                <button class="btn btn-outline" onclick="closeHouse()">Close</button>
                <button class="btn" onclick="initTransfer('${occupant.serviceNo}', '${houseId}')">Transfer Staff</button>
            </div>
        `;
    } else {
        details.innerHTML = `
            <div class="house-detail-section">
                <h4>House Status</h4>
                <div class="detail-row">
                    <span class="detail-label">Status</span>
                    <span class="detail-value" style="color: green;">VACANT</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Quarter</span>
                    <span class="detail-value">${quarter}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">House</span>
                    <span class="detail-value">${houseId}</span>
                </div>
            </div>
            
            <div class="form-actions">
                <button class="btn btn-outline" onclick="closeHouse()">Close</button>
            </div>
        `;
    }
    
    document.getElementById('houseModal').classList.add('show');
}

function getOccupancyHistory(occupant) {
    return [
        { name: occupant.name, from: formatDate(occupant.since), to: 'Present' }
    ];
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-GB', { year: 'numeric', month: 'short', day: 'numeric' });
}

function closeHouse() {
    document.getElementById('houseModal').classList.remove('show');
}

function initTransfer(serviceNo, currentHouse) {
    document.getElementById('transferFrom').value = currentHouse;
    document.getElementById('newQuarter').value = '';
    document.getElementById('newHouse').value = '';
    document.getElementById('transferDate').value = new Date().toISOString().split('T')[0];
    
    closeHouse();
    document.getElementById('transferModal').classList.add('show');
    
    window.transferStaffNo = serviceNo;
    window.transferFromHouse = currentHouse;
}

function loadHousesInQuarter() {
    const quarter = document.getElementById('newQuarter').value;
    const select = document.getElementById('newHouse');
    select.innerHTML = '<option value="">Select house...</option>';
    
    if (!quarter || !quartersData[quarter]) return;
    
    const data = quartersData[quarter];
    for (let i = 1; i <= data.houses; i++) {
        const houseNum = String(i).padStart(2, '0');
        const houseId = `${quarter}-${houseNum}`;
        const occupant = staffDatabase.find(s => s.quarter === quarter && s.house === houseNum);
        
        if (!occupant) {
            select.innerHTML += `<option value="${houseNum}">${houseId}</option>`;
        }
    }
}

function closeTransfer() {
    document.getElementById('transferModal').classList.remove('show');
}

document.getElementById('transferForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const newQuarter = document.getElementById('newQuarter').value;
    const newHouse = document.getElementById('newHouse').value;
    const reason = document.getElementById('transferReason').value;
    const date = document.getElementById('transferDate').value;
    
    if (!newQuarter || !newHouse) {
        showToast('Please select new quarter and house');
        return;
    }
    
    const staff = staffDatabase.find(s => s.serviceNo === window.transferStaffNo);
    if (staff) {
        staff.quarter = newQuarter;
        staff.house = newHouse;
        staff.since = date;
        localStorage.setItem('updms_staff', JSON.stringify(staffDatabase));
        
        updateQuarterStats();
        closeTransfer();
        openQuarter(newQuarter);
        showToast('Transfer completed successfully');
    }
});

function searchStaff() {
    const query = document.getElementById('searchInput').value.toLowerCase().trim();
    const resultsDiv = document.getElementById('searchResults');
    
    if (!query) {
        resultsDiv.style.display = 'none';
        return;
    }
    
    const results = staffDatabase.filter(s => 
        s.name.toLowerCase().includes(query) || 
        s.serviceNo.toLowerCase().includes(query)
    );
    
    if (results.length === 0) {
        resultsDiv.innerHTML = '<p style="text-align: center; color: #737373; padding: 20px;">No staff members found</p>';
    } else {
        resultsDiv.innerHTML = results.map(s => `
            <div class="search-result-item" onclick="viewStaff('${s.serviceNo}')">
                <div class="result-avatar">${s.name.split(' ').map(n => n[0]).join('').substring(0,2)}</div>
                <div class="result-info">
                    <h4>${s.name}</h4>
                    <p>${s.rank} - ${s.serviceNo}</p>
                    <span>Quarter ${s.quarter}, House ${s.quarter}-${s.house}</span>
                </div>
            </div>
        `).join('');
    }
    
    resultsDiv.style.display = 'block';
}

function viewStaff(serviceNo) {
    const staff = staffDatabase.find(s => s.serviceNo === serviceNo);
    if (staff) {
        document.getElementById('searchInput').value = staff.name;
        document.getElementById('searchResults').style.display = 'none';
        openHouse(staff.quarter, staff.house, staff);
    }
}

function showToast(message) {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
}

// Close modals on outside click
window.onclick = function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
    }
};
