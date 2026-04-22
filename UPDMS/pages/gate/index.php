<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ANPR System - Number Plate Recognition</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root { --primary: #7B1C2E; --secondary: #B8860B; --white: #fff; --light: #f5f5f5; --text: #333; --gray: #666; }
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Inter', sans-serif; background: var(--light); color: var(--text); min-height: 100vh; }
.header { background: linear-gradient(135deg, var(--primary) 0%, #5a1422 100%); color: var(--white); padding: 20px 32px; display: flex; align-items: center; gap: 16px; }
.logo { width: 48px; height: 48px; background: var(--secondary); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 20px; color: var(--primary); }
.title { font-size: 24px; font-weight: 700; }
.subtitle { font-size: 12px; opacity: 0.85; }
.main { padding: 32px; max-width: 1200px; margin: 0 auto; }
.grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; }
.card { background: var(--white); border-radius: 12px; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
.card h3 { font-size: 18px; font-weight: 600; color: var(--primary); margin-bottom: 16px; display: flex; align-items: center; gap: 10px; }
.card p { color: var(--gray); font-size: 14px; line-height: 1.6; margin-bottom: 16px; }
.btn { display: inline-block; padding: 12px 24px; background: var(--primary); color: var(--white); text-decoration: none; border-radius: 8px; font-weight: 600; transition: transform 0.2s; }
.btn:hover { transform: translateY(-2px); }
.btn.secondary { background: var(--secondary); color: var(--primary); }
.stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 32px; }
.stat-box { background: var(--white); padding: 20px; border-radius: 12px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.stat-box .num { font-size: 32px; font-weight: 700; color: var(--primary); }
.stat-box .label { font-size: 13px; color: var(--gray); margin-top: 4px; }
</style>
</head>
<body>
<header class="header">
<div class="logo">ANPR</div>
<div>
<div class="title">Automatic Number Plate Recognition</div>
<div class="subtitle">Vehicle Detection & Tracking System</div>
</div>
</header>

<main class="main">
<div class="stats">
<div class="stat-box"><div class="num">1,234</div><div class="label">Total Vehicles</div></div>
<div class="stat-box"><div class="num">12</div><div class="label">Inside</div></div>
<div class="stat-box"><div class="num">5</div><div class="label">Overstayed</div></div>
<div class="stat-box"><div class="num">89</div><div class="label">Today</div></div>
</div>

<h2 style="margin-bottom: 20px; font-size: 20px;">Select Module</h2>
<div class="grid">
<div class="card">
<h3>📹 Entry Camera</h3>
<p>Capture vehicle number plates on entry using camera/OCR. Automatically registers vehicles entering the facility.</p>
<a href="parking-entry-capture.php" class="btn">Open</a>
</div>
<div class="card">
<h3>📹 Exit Camera</h3>
<p>Capture vehicle number plates on exit. Records exit time and updates vehicle status.</p>
<a href="parking-exit-capture.php" class="btn">Open</a>
</div>
<div class="card">
<h3>🤖 Auto Entry</h3>
<p>Automated continuous camera detection for entry. No manual intervention needed.</p>
<a href="auto-parking.php" class="btn secondary">Open</a>
</div>
<div class="card">
<h3>🤖 Auto Exit</h3>
<p>Automated continuous camera detection for exit. Monitors and records departures.</p>
<a href="auto-parking-exit.php" class="btn secondary">Open</a>
</div>
<div class="card">
<h3>📝 Manual Entry</h3>
<p>Manual entry form for vehicles without cameras. Enter plate number manually.</p>
<a href="parking-entry.php" class="btn">Open</a>
</div>
<div class="card">
<h3>📝 Manual Exit</h3>
<p>Manual exit form. Record vehicle exit without camera.</p>
<a href="parking-exit.php" class="btn">Open</a>
</div>
<div class="card">
<h3>🔍 Captured Plates</h3>
<p>View all captured number plates with timestamps and images.</p>
<a href="captured-plates.php" class="btn">View Gallery</a>
</div>
</div>
</main>
</body>
</html>