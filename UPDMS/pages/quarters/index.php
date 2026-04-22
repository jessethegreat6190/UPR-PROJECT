<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Quarters - Uganda Prisons Service</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="topbar">
    <div class="topbar-logo">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
    </div>
    <div>
        <div class="topbar-title">UGANDA PRISONS SERVICE</div>
        <div class="topbar-sub">Staff Quarters Management</div>
    </div>
    <nav class="nav-links">
        <a href="../main/" class="nav-link">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Main
        </a>
        <a href="#" class="nav-link active">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
            Quarters
        </a>
        <a href="../staff/" class="nav-link">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Staff Portal
        </a>
    </nav>
    <div class="topbar-right">
        <span class="topbar-time" id="clock">--:--</span>
        <a href="../login.php" class="btn" style="padding: 6px 14px; font-size: 11px;">Login</a>
    </div>
</header>

<main class="main-content">
    <div class="page-header">
        <h1>Staff Quarters</h1>
        <p>Browse quarters and manage staff housing assignments</p>
    </div>

    <div class="search-bar">
        <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="text" id="searchInput" placeholder="Search by staff name or service number..." onkeyup="searchStaff()">
    </div>

    <div id="searchResults" class="search-results" style="display: none;"></div>

    <h2 class="section-title">All Quarters</h2>
    <div class="quarters-grid" id="quartersGrid">
        <div class="quarter-tile" onclick="openQuarter('A')">
            <div class="quarter-letter">A</div>
            <div class="quarter-info">
                <h3>Senior Staff</h3>
                <p>20 Houses</p>
            </div>
            <div class="quarter-stats">
                <span class="stat-occupied">18</span>/<span class="stat-total">20</span> occupied
            </div>
        </div>

        <div class="quarter-tile" onclick="openQuarter('B')">
            <div class="quarter-letter">B</div>
            <div class="quarter-info">
                <h3>Senior Staff</h3>
                <p>20 Houses</p>
            </div>
            <div class="quarter-stats">
                <span class="stat-occupied">19</span>/<span class="stat-total">20</span> occupied
            </div>
        </div>

        <div class="quarter-tile" onclick="openQuarter('C')">
            <div class="quarter-letter">C</div>
            <div class="quarter-info">
                <h3>Officers</h3>
                <p>30 Houses</p>
            </div>
            <div class="quarter-stats">
                <span class="stat-occupied">25</span>/<span class="stat-total">30</span> occupied
            </div>
        </div>

        <div class="quarter-tile" onclick="openQuarter('D')">
            <div class="quarter-letter">D</div>
            <div class="quarter-info">
                <h3>Officers</h3>
                <p>30 Houses</p>
            </div>
            <div class="quarter-stats">
                <span class="stat-occupied">28</span>/<span class="stat-total">30</span> occupied
            </div>
        </div>

        <div class="quarter-tile" onclick="openQuarter('E')">
            <div class="quarter-letter">E</div>
            <div class="quarter-info">
                <h3>Warders</h3>
                <p>40 Houses</p>
            </div>
            <div class="quarter-stats">
                <span class="stat-occupied">35</span>/<span class="stat-total">40</span> occupied
            </div>
        </div>

        <div class="quarter-tile" onclick="openQuarter('F')">
            <div class="quarter-letter">F</div>
            <div class="quarter-info">
                <h3>Warders</h3>
                <p>40 Houses</p>
            </div>
            <div class="quarter-stats">
                <span class="stat-occupied">38</span>/<span class="stat-total">40</span> occupied
            </div>
        </div>

        <div class="quarter-tile" onclick="openQuarter('J')">
            <div class="quarter-letter">J</div>
            <div class="quarter-info">
                <h3>Warders</h3>
                <p>35 Houses</p>
            </div>
            <div class="quarter-stats">
                <span class="stat-occupied">30</span>/<span class="stat-total">35</span> occupied
            </div>
        </div>

        <div class="quarter-tile" onclick="openQuarter('M')">
            <div class="quarter-letter">M</div>
            <div class="quarter-info">
                <h3>Support Staff</h3>
                <p>25 Houses</p>
            </div>
            <div class="quarter-stats">
                <span class="stat-occupied">20</span>/<span class="stat-total">25</span> occupied
            </div>
        </div>

        <div class="quarter-tile" onclick="openQuarter('O')">
            <div class="quarter-letter">O</div>
            <div class="quarter-info">
                <h3>Support Staff</h3>
                <p>25 Houses</p>
            </div>
            <div class="quarter-stats">
                <span class="stat-occupied">22</span>/<span class="stat-total">25</span> occupied
            </div>
        </div>
    </div>
</main>

<!-- Quarter Detail Modal -->
<div id="quarterModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Quarter A - Senior Staff</h2>
            <button class="close-btn" onclick="closeQuarter()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="legend">
                <span class="legend-item"><span class="dot occupied"></span> Occupied</span>
                <span class="legend-item"><span class="dot vacant"></span> Vacant</span>
                <span class="legend-item"><span class="dot maintenance"></span> Maintenance</span>
            </div>
            <div class="houses-grid" id="housesGrid"></div>
        </div>
    </div>
</div>

<!-- House Detail Modal -->
<div id="houseModal" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h2 id="houseModalTitle">House A-01</h2>
            <button class="close-btn" onclick="closeHouse()">&times;</button>
        </div>
        <div class="modal-body" id="houseDetails"></div>
    </div>
</div>

<!-- Transfer Modal -->
<div id="transferModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Transfer Staff</h2>
            <button class="close-btn" onclick="closeTransfer()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="transferForm">
                <div class="form-group">
                    <label>Current Assignment</label>
                    <input type="text" id="transferFrom" readonly>
                </div>
                <div class="form-group">
                    <label>Select New Quarter</label>
                    <select id="newQuarter" onchange="loadHousesInQuarter()">
                        <option value="">Select quarter...</option>
                        <option value="A">A - Senior Staff</option>
                        <option value="B">B - Senior Staff</option>
                        <option value="C">C - Officers</option>
                        <option value="D">D - Officers</option>
                        <option value="E">E - Warders</option>
                        <option value="F">F - Warders</option>
                        <option value="J">J - Warders</option>
                        <option value="M">M - Support Staff</option>
                        <option value="O">O - Support Staff</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Select House</label>
                    <select id="newHouse" required>
                        <option value="">Select house...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Reason for Transfer</label>
                    <select id="transferReason">
                        <option value="promotion">Promotion / Demotion</option>
                        <option value="proximity">Proximity to Duty Station</option>
                        <option value="family">Family Reasons</option>
                        <option value="vacancy">Vacancy Filling</option>
                        <option value="request">Staff Request</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Effective Date</label>
                    <input type="date" id="transferDate" required>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeTransfer()">Cancel</button>
                    <button type="submit" class="btn">Confirm Transfer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script src="app.js"></script>

</body>
</html>
