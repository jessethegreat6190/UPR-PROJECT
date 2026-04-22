<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regular Visit - Uganda Prisons Service</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="topbar">
    <div class="topbar-logo">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
    </div>
    <div>
        <div class="topbar-title">UGANDA PRISONS SERVICE</div>
        <div class="topbar-sub">REGULAR VISIT REGISTRATION</div>
    </div>
    <nav class="nav-links">
        <a href="../visitor/" class="nav-link active">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Visitor
        </a>
        <a href="../inmate/" class="nav-link">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            Inmate
        </a>
        <a href="../hospital/" class="nav-link">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
            Hospital
        </a>
        <a href="../general/" class="nav-link">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            General
        </a>
        <a href="../vehicle/" class="nav-link">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
            Vehicle
        </a>
        <a href="../quarters/" class="nav-link">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
            Quarters
        </a>
    </nav>
    <div class="topbar-right">
        <span class="topbar-time" id="clock">--:--</span>
        <a href="../staff/" class="btn" style="padding: 6px 14px; font-size: 11px;">Staff</a>
    </div>
</header>

<div class="kiosk-wrap">
    <div class="kiosk-card">
        <div class="kiosk-hdr">
            <h1>REGULAR VISIT</h1>
            <p>Complete the form below - Simple visitor registration</p>
            <div class="kiosk-clock" id="kioskTime">--:--</div>
        </div>

        <div class="kiosk-body">
            <div id="step1">
                <div class="steps">
                    <div class="step active">
                        <div class="step-num">1</div>
                        <span class="step-label">Details</span>
                    </div>
                    <div class="step-line"></div>
                    <div class="step">
                        <div class="step-num">2</div>
                        <span class="step-label">Done</span>
                    </div>
                </div>

                <form id="visitorForm">
                    <input type="hidden" name="purpose" value="visitor">
                    <input type="hidden" name="destination" value="general">

                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="full_name" required placeholder="Enter your full name">
                    </div>

                    <div class="form-group">
                        <label>National ID *</label>
                        <input type="text" name="national_id" required placeholder="Enter your National ID">
                    </div>

                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" placeholder="+256...">
                    </div>

                    <div class="form-group">
                        <label>Purpose of Visit *</label>
                        <select name="visit_reason" required>
                            <option value="">Select purpose...</option>
                            <option value="family">Family Visit</option>
                            <option value="friend">Friend Visit</option>
                            <option value="personal">Personal Business</option>
                            <option value="welfare">Welfare Check</option>
                            <option value="inquiry">General Inquiry</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Vehicle Number Plate (if driving)</label>
                        <input type="text" name="plate_number" placeholder="e.g., UAR 123X" style="text-transform: uppercase;">
                    </div>

                    <div class="form-group">
                        <label>Your Signature</label>
                        <canvas id="signatureCanvas" width="400" height="100" style="border: 1px solid #ccc; border-radius: 8px; background: #fff; cursor: crosshair;"></canvas>
                        <input type="hidden" name="signature" id="signatureData">
                        <button type="button" onclick="clearSignature()" style="margin-top: 6px; padding: 4px 12px; font-size: 11px; background: #f5f5f5; border: 1px solid #ccc; border-radius: 4px; cursor: pointer;">Clear</button>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <a href="../" class="btn btn-outline" style="flex: 1; text-align: center; text-decoration: none;">Back</a>
                        <button type="submit" class="btn" style="flex: 2;">Submit Registration</button>
                    </div>
                </form>
            </div>

            <div id="step2" style="display: none;">
                <div class="steps">
                    <div class="step done">
                        <div class="step-num"><svg viewBox="0 0 24 24" width="12" height="12"><polyline points="20 6 9 17 4 12"/></svg></div>
                        <span class="step-label">Details</span>
                    </div>
                    <div class="step-line done"></div>
                    <div class="step done">
                        <div class="step-num"><svg viewBox="0 0 24 24" width="12" height="12"><polyline points="20 6 9 17 4 12"/></svg></div>
                        <span class="step-label">Done</span>
                    </div>
                </div>

                <div style="text-align: center; padding: 40px 0;">
                    <div class="success-check">
                        <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <h2 style="font-size: 20px; margin-bottom: 8px;">Registration Submitted!</h2>
                    <p style="color: #737373; margin-bottom: 20px;">Your details have been sent to the guard station.</p>

                    <div class="ref-box">
                        <div class="lbl">YOUR REFERENCE NUMBER</div>
                        <div class="num" id="refNumber">VIS-2026-0001</div>
                    </div>

                    <div class="time-box">
                        <span class="time-label">REGISTRATION TIME</span>
                        <span class="time-value" id="regTime">--:--:--</span>
                    </div>

                    <p style="font-size: 13px; color: #525252; margin-bottom: 24px;">Please wait here. The guard will review and assist you.</p>

                    <a href="../visitor/" class="btn">New Registration</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script src="app.js"></script>

</body>
</html>
