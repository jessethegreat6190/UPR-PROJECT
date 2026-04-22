<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Portal - Uganda Prisons Service</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="topbar">
    <div class="topbar-logo">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <path d="M12 6v6l4 2"/>
        </svg>
    </div>
    <div>
        <div class="topbar-title">UGANDA PRISONS SERVICE</div>
        <div class="topbar-sub">Staff Portal - Login Required</div>
    </div>
    <nav class="nav-links">
        <a href="../main/" class="nav-link">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Main
        </a>
        <a href="#" class="nav-link active">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Dashboard
        </a>
    </nav>
    <div class="topbar-right">
        <span class="topbar-time" id="clock">--:--</span>
        <a href="../login.php" class="btn" style="padding: 8px 16px; font-size: 12px;">Logout</a>
    </div>
</header>

<!-- LOGIN FORM (shown when not logged in) -->
<div id="loginForm" class="login-page" style="display: none;">
    <div class="login-card">
        <div class="login-header">
            <h1>Staff Login</h1>
            <p>Enter your credentials to access the portal</p>
        </div>
        <div class="login-body">
            <form onsubmit="return handleLogin(event)">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" id="loginUsername" required placeholder="Enter username">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" id="loginPassword" required placeholder="Enter password">
                </div>
                <button type="submit" class="btn" style="width: 100%;">Login</button>
            </form>
            <div style="margin-top: 20px; padding: 16px; background: #f5f5f5; border-radius: 8px; font-size: 13px; color: #666;">
                <strong>Demo:</strong> admin / admin123
            </div>
        </div>
    </div>
</div>

<!-- STAFF DASHBOARD (shown when logged in) -->
<div id="staffDashboard" class="layout">
    <aside class="sidebar">
        <div class="sidebar-section">
            <div class="sidebar-title">Gate Control</div>
            <a href="#" class="sidebar-link active" onclick="showPage('queue', this); return false;">
                <span class="icon-svg">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </span>
                Visitor Queue
            </a>
            <a href="#" class="sidebar-link" onclick="showPage('vehicles', this); return false;">
                <span class="icon-svg">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                </span>
                Vehicles
            </a>
            <a href="#" class="sidebar-link" onclick="showPage('bookings', this); return false;">
                <span class="icon-svg">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </span>
                Bookings
            </a>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-title">Management</div>
            <a href="#" class="sidebar-link" onclick="showPage('visitors', this); return false;">
                <span class="icon-svg">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </span>
                Visitor Log
            </a>
            <a href="#" class="sidebar-link" onclick="showPage('alerts', this); return false;">
                <span class="icon-svg">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </span>
                Alerts
            </a>
            <a href="#" class="sidebar-link" onclick="showPage('whitelist', this); return false;">
                <span class="icon-svg">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </span>
                Whitelist
            </a>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-title">Reports</div>
            <a href="#" class="sidebar-link" onclick="showPage('reports', this); return false;">
                <span class="icon-svg">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                </span>
                Reports
            </a>
        </div>
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-name" id="userName">Staff</div>
                <div class="user-role" id="userRole">GATE OFFICER</div>
            </div>
            <button class="logout-btn" onclick="handleLogout()">LOGOUT</button>
        </div>
    </aside>

    <main class="content">
        <!-- VISITOR QUEUE PAGE -->
        <div id="page-queue" class="page-section active">
            <div class="anpr-bar" style="background: #1a1a1a;">
                <div class="anpr-dot"></div>
                <span class="anpr-text" style="font-weight: 700; letter-spacing: 1px;">ANPR GATE 1</span>
                <div class="anpr-plate" id="detectedPlate" style="font-family: monospace; font-size: 20px; font-weight: 700; color: #00ff00; background: #000; padding: 4px 16px; border-radius: 4px; min-width: 180px; text-align: center;">SCANNING...</div>
                <span id="anprStatus" style="margin-left: 12px; color: #00ff00; font-size: 11px;">● LIVE</span>
                <span style="margin-left: auto; color: #737373; font-size: 11px;">Last capture: <span id="lastCapture">—</span></span>
            </div>

            <h2 style="font-size: 18px; margin-bottom: 16px;">Visitor Queue</h2>

            <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 24px;">
                <div class="stat-card">
                    <div class="stat-number" id="queueCount">0</div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="approvedCount">0</div>
                    <div class="stat-label">Approved Today</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="totalCount">0</div>
                    <div class="stat-label">Total Today</div>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Ref</th>
                            <th>Name</th>
                            <th>Purpose</th>
                            <th>Destination</th>
                            <th>Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="queueTable">
                        <tr>
                            <td colspan="6" style="text-align: center; color: #737373; padding: 40px;">
                                No visitors in queue
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- VEHICLES PAGE -->
        <div id="page-vehicles" class="page-section" style="display: none;">
            <div class="anpr-bar" style="background: #1a1a1a;">
                <div class="anpr-dot"></div>
                <span class="anpr-text" style="font-weight: 700; letter-spacing: 1px;">ANPR GATE 1 - VEHICLE TRACKING</span>
                <div class="anpr-plate" id="anprPlateDisplay" style="font-family: monospace; font-size: 20px; font-weight: 700; color: #00ff00; background: #000; padding: 4px 16px; border-radius: 4px; min-width: 180px; text-align: center;">SCANNING...</div>
                <span style="margin-left: auto; color: #00ff00; font-size: 11px;">● CAMERA ACTIVE</span>
            </div>

            <h2 style="font-size: 18px; margin-bottom: 16px;">Vehicles On Site</h2>

            <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 24px;">
                <div class="stat-card">
                    <div class="stat-number" id="vehiclesInside">0</div>
                    <div class="stat-label">On Site</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="vehiclesExited">0</div>
                    <div class="stat-label">Exited Today</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="vehiclesOverstay">0</div>
                    <div class="stat-label">Overstaying</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="vehiclesAutoExit">0</div>
                    <div class="stat-label">Auto-Exited</div>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Plate</th>
                            <th>Driver</th>
                            <th>Purpose</th>
                            <th>Entry (ANPR)</th>
                            <th>Exit (ANPR)</th>
                            <th>Duration</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="vehiclesTable">
                        <tr>
                            <td colspan="7" style="text-align: center; color: #737373; padding: 40px;">
                                No vehicles on site - ANPR camera scanning...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- BOOKINGS PAGE -->
        <div id="page-bookings" class="page-section" style="display: none;">
            <h2 style="font-size: 18px; margin-bottom: 16px;">Advance Bookings</h2>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Ref</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="bookingsTable">
                        <tr>
                            <td colspan="7" style="text-align: center; color: #737373; padding: 40px;">
                                No bookings for today
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- VISITORS LOG PAGE -->
        <div id="page-visitors" class="page-section" style="display: none;">
            <h2 style="font-size: 18px; margin-bottom: 16px;">Complete Visitor Log</h2>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Ref</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Entry</th>
                            <th>Exit</th>
                            <th>Duration</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="visitorsTable">
                        <tr>
                            <td colspan="7" style="text-align: center; color: #737373; padding: 40px;">
                                No visitor records
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ALERTS PAGE -->
        <div id="page-alerts" class="page-section" style="display: none;">
            <h2 style="font-size: 18px; margin-bottom: 16px;">Active Alerts</h2>

            <div id="alertsList">
                <div class="alert alert-info" style="text-align: center; padding: 40px;">
                    No active alerts
                </div>
            </div>
        </div>

        <!-- WHITELIST PAGE -->
        <div id="page-whitelist" class="page-section" style="display: none;">
            <h2 style="font-size: 18px; margin-bottom: 16px;">Vehicle Whitelist (Auto-Gate)</h2>

            <div class="alert alert-info" style="margin-bottom: 20px;">
                Whitelisted vehicles bypass manual registration. ANPR will automatically open the gate.
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Plate</th>
                            <th>Owner</th>
                            <th>Category</th>
                            <th>Access</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="whitelistTable">
                        <tr>
                            <td colspan="5" style="text-align: center; color: #737373; padding: 40px;">
                                No whitelisted vehicles
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- REPORTS PAGE -->
        <div id="page-reports" class="page-section" style="display: none;">
            <h2 style="font-size: 18px; margin-bottom: 16px;">Reports & Analytics</h2>

            <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 24px;">
                <div class="stat-card">
                    <div class="stat-number" id="reportVisitors">0</div>
                    <div class="stat-label">Visitors Today</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="reportVehicles">0</div>
                    <div class="stat-label">Vehicles Today</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="reportAlerts">0</div>
                    <div class="stat-label">Alerts</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="reportOverstay">0</div>
                    <div class="stat-label">Overstays</div>
                </div>
            </div>

            <button class="btn" style="margin-bottom: 20px;">Download Report</button>
        </div>
    </main>
</div>

<div class="toast" id="toast"></div>

<script src="app.js"></script>

</body>
</html>
