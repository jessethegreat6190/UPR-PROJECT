<?php
require_once __DIR__ . '/config/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UPDMS - Uganda Prisons Digital Management System</title>
    <link rel="stylesheet" href="assets/css/black-white.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --maroon: #800000;
            --maroon-dark: #5C0000;
            --gold: #DAA520;
            --cream: #FFF8DC;
            --white: #fff;
            --text: #1a1a1a;
            --gray: #6b7280;
            --border: #e5e7eb;
            --success: #22c55e;
            --shadow: 0 4px 20px rgba(0,0,0,0.1);
            --radius: 12px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--cream);
            color: var(--text);
            min-height: 100vh;
        }
        
        /* HEADER */
        .header {
            background: linear-gradient(135deg, var(--maroon) 0%, var(--maroon-dark) 100%);
            color: var(--white);
            padding: 20px 5%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 20px rgba(128,0,0,0.3);
        }
        .header-logo {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .logo-icon {
            width: 50px;
            height: 50px;
            background: var(--gold);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 800;
            color: var(--maroon);
        }
        .header-title {
            font-size: 20px;
            font-weight: 700;
        }
        .header-sub {
            font-size: 11px;
            color: var(--gold);
            letter-spacing: 1px;
        }
        .header-clock {
            font-size: 14px;
            font-weight: 600;
            padding: 8px 16px;
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
        }
        
        /* MARQUEE */
        .marquee {
            background: var(--gold);
            color: var(--maroon);
            padding: 8px;
            text-align: center;
            font-weight: 700;
            font-size: 13px;
            animation: marquee 20s linear infinite;
        }
        @keyframes marquee {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }
        
        /* HERO */
        .hero {
            background: linear-gradient(135deg, var(--maroon) 0%, var(--maroon-dark) 40%, #3D0000 100%);
            padding: 80px 5%;
            text-align: center;
            color: var(--white);
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 80%, rgba(218,165,32,0.1) 0%, transparent 50%);
        }
        .hero h1 {
            font-size: clamp(28px, 5vw, 48px);
            font-weight: 800;
            margin-bottom: 16px;
        }
        .hero h1 em {
            color: var(--gold);
            font-style: normal;
        }
        .hero p {
            font-size: 16px;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto 30px;
            line-height: 1.6;
        }
        .hero-btns {
            display: flex;
            gap: 14px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            padding: 14px 28px;
            border-radius: 30px;
            font-weight: 700;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }
        .btn-gold {
            background: var(--gold);
            color: var(--maroon);
        }
        .btn-gold:hover {
            background: #FFD700;
            transform: translateY(-3px);
        }
        .btn-outline {
            background: transparent;
            border: 2px solid var(--white);
            color: var(--white);
        }
        .btn-outline:hover {
            background: rgba(255,255,255,0.1);
        }
        
        /* STATS */
        .stats-bar {
            background: var(--white);
            padding: 30px 5%;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            border-bottom: 4px solid var(--gold);
        }
        .stat-card {
            text-align: center;
            padding: 20px;
            border-radius: var(--radius);
            transition: all 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow);
        }
        .stat-num {
            font-size: 32px;
            font-weight: 800;
            color: var(--maroon);
        }
        .stat-label {
            font-size: 12px;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* MODULES GRID */
        .modules-section {
            padding: 60px 5%;
        }
        .section-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--maroon);
            text-align: center;
            margin-bottom: 40px;
        }
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .module-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: var(--shadow);
            text-decoration: none;
            color: var(--text);
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        .module-card:hover {
            transform: translateY(-5px);
            border-color: var(--gold);
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }
        .module-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--maroon) 0%, var(--maroon-dark) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }
        .module-icon svg {
            width: 30px;
            height: 30px;
            stroke: var(--gold);
        }
        .module-card h3 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--maroon);
        }
        .module-card p {
            font-size: 13px;
            color: var(--gray);
            line-height: 1.5;
        }
        .module-badge {
            display: inline-block;
            padding: 4px 10px;
            background: var(--gold);
            color: var(--maroon);
            font-size: 11px;
            font-weight: 700;
            border-radius: 20px;
            margin-top: 12px;
        }
        
        /* FOOTER */
        .footer {
            background: var(--maroon);
            color: var(--white);
            padding: 30px 5%;
            text-align: center;
        }
        .footer p {
            font-size: 13px;
            opacity: 0.8;
        }
        .footer a {
            color: var(--gold);
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }
            .hero h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>

<!-- HEADER -->
<header class="header">
    <div class="header-logo">
        <div class="logo-icon">UP</div>
        <div>
            <div class="header-title">UGANDA PRISONS SERVICE</div>
            <div class="header-sub">DIGITAL MANAGEMENT SYSTEM</div>
        </div>
    </div>
    <div class="header-clock" id="clock"></div>
</header>

<!-- MARQUEE -->
<div class="marquee">
    Welcome to Uganda Prisons Digital Management System - An Encounter with Excellence
</div>

<!-- HERO -->
<section class="hero">
    <h1>Welcome to <em>UPDMS</em></h1>
    <p>Uganda Prisons Digital Management System - Streamlining prison operations through digital innovation. Select a module below to get started.</p>
    <div class="hero-btns">
        <a href="pages/visitors/checkin.php" class="btn btn-gold">Start Registration</a>
        <a href="pages/gate/" class="btn btn-outline">Gate Control</a>
    </div>
</section>

<!-- STATS -->
<div class="stats-bar">
    <div class="stat-card">
        <div class="stat-num">1,234</div>
        <div class="stat-label">Registered Visitors</div>
    </div>
    <div class="stat-card">
        <div class="stat-num">567</div>
        <div class="stat-label">Inmates</div>
    </div>
    <div class="stat-card">
        <div class="stat-num">89</div>
        <div class="stat-label">Vehicles Today</div>
    </div>
    <div class="stat-card">
        <div class="stat-num">12</div>
        <div class="stat-label">Staff On Duty</div>
    </div>
</div>

<!-- MODULES -->
<section class="modules-section">
    <h2 class="section-title">Select Module</h2>
    
    <div class="modules-grid">
        <!-- MAIN REGISTRATION -->
        <a href="pages/main/" class="module-card">
            <div class="module-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <h3>Main Registration</h3>
            <p>Register visitors, inmates and manage all check-in/check-out operations.</p>
            <span class="module-badge">Core</span>
        </a>
        
        <!-- VISITORS -->
        <a href="pages/visitors/checkin.php" class="module-card">
            <div class="module-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <h3>Visitor Management</h3>
            <p>Check-in, check-out, booking, and visitor log management.</p>
            <span class="module-badge">Popular</span>
        </a>
        
        <!-- INMATE MANAGEMENT -->
        <a href="pages/inmate/" class="module-card">
            <div class="module-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <rect x="1" y="3" width="22" height="14" rx="2"/>
                </svg>
            </div>
            <h3>Inmate Management</h3>
            <p>Register inmates with NIN validation, OCR, and face matching. (Node.js Backend)</p>
            <span class="module-badge">NEW</span>
        </a>
        
        <!-- PRISONERS -->
        <a href="pages/prisoners/list.php" class="module-card">
            <div class="module-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <path d="M9 3v18"/>
                    <path d="M15 3v18"/>
                </svg>
            </div>
            <h3>Prisoners Records</h3>
            <p>View court dates, warrants, sentences, and remand alerts.</p>
        </a>
        
        <!-- GATE / ANPR -->
        <a href="pages/gate/" class="module-card">
            <div class="module-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="1" y="3" width="15" height="13"/>
                    <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                    <circle cx="5.5" cy="18.5" r="2.5"/>
                    <circle cx="18.5" cy="18.5" r="2.5"/>
                </svg>
            </div>
            <h3>Gate Control & ANPR</h3>
            <p>Vehicle number plate recognition, parking management, and OCR capture.</p>
            <span class="module-badge">ANPR</span>
        </a>
        
        <!-- HOSPITAL -->
        <a href="pages/hospital/" class="module-card">
            <div class="module-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                </svg>
            </div>
            <h3>Hospital / Medical</h3>
            <p>Medical services, inmate health records, and hospital admissions.</p>
            <span class="module-badge">Medical</span>
        </a>
        
        <!-- GENERAL VISITS -->
        <a href="pages/general/" class="module-card">
            <div class="module-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                </svg>
            </div>
            <h3>General Visits</h3>
            <p>Manage general visitor records with database layer integration.</p>
        </a>
        
        <!-- STAFF -->
        <a href="pages/staff/" class="module-card">
            <div class="module-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <h3>Staff Portal</h3>
            <p>Staff management, schedules, and duty rosters.</p>
        </a>
        
        <!-- VEHICLE -->
        <a href="pages/vehicle/" class="module-card">
            <div class="module-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="1" y="1" width="15" height="10"/>
                    <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                    <circle cx="5.5" cy="18" r="2.5"/>
                    <circle cx="18.5" cy="18" r="2.5"/>
                </svg>
            </div>
            <h3>Vehicle Tracking</h3>
            <p>Track all vehicles entering and exiting the facility.</p>
        </a>
        
        <!-- HQ / REPORTS -->
        <a href="pages/hq/dashboard.php" class="module-card">
            <div class="module-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 20V10"/>
                    <path d="M12 20V4"/>
                    <path d="M6 20v-6"/>
                </svg>
            </div>
            <h3>HQ & Reports</h3>
            <p>Headquarters dashboard, analytics, and reports.</p>
        </a>
        
        <!-- ADMIN -->
        <a href="pages/admin/" class="module-card">
            <div class="module-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                </svg>
            </div>
            <h3>Administration</h3>
            <p>System settings, user management, and audit logs.</p>
        </a>
    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    <p>&copy; <?php echo date('Y'); ?> Uganda Prisons Service - Digital Management System</p>
    <p style="margin-top: 8px;">
        <a href="pages/login.php">Staff Login</a> | 
        <a href="setup.php">Setup Database</a>
    </p>
</footer>

<script>
function updateClock() {
    const now = new Date();
    document.getElementById('clock').textContent = now.toLocaleString('en-US', {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
setInterval(updateClock, 1000);
updateClock();
</script>

</body>
</html>