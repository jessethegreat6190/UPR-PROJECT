<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Main Registration - Uganda Prisons Service</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="topbar">
    <div class="topbar-logo">
        <svg viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10"/>
            <path d="M12 6v6l4 2"/>
        </svg>
    </div>
    <div>
        <div class="topbar-title">UGANDA PRISONS SERVICE</div>
        <div class="topbar-sub">Main Registration - Select Your Visit Type</div>
    </div>
    <div class="topbar-right">
        <span class="topbar-time" id="clock">--:--</span>
        <a href="../staff/" class="btn" style="padding: 6px 14px; font-size: 11px;">Staff</a>
    </div>
</header>

<div class="kiosk-wrap">
    <div class="kiosk-card">
        <div class="kiosk-hdr">
            <h1>VISITOR REGISTRATION</h1>
            <p>Select the type of your visit below</p>
            <div class="kiosk-clock" id="kioskTime">--:--</div>
        </div>

        <div class="kiosk-body">
            <h2 style="font-size: 16px; margin-bottom: 20px; text-align: center;">What is the purpose of your visit?</h2>

            <div class="purpose-grid">
                <a href="../visitor/" class="purpose-card">
                    <div class="purpose-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </div>
                    <h3>REGULAR VISITOR</h3>
                    <p>Simple visit registration for family, friends, or personal business.</p>
                </a>

                <a href="../inmate/" class="purpose-card">
                    <div class="purpose-icon">
                        <svg viewBox="0 0 24 24">
                            <rect x="3" y="11" width="18" height="11" rx="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </div>
                    <h3>VISIT AN INMATE</h3>
                    <p>Visiting a remand or convicted prisoner. Destination: Visiting Hall.</p>
                </a>

                <a href="../hospital/" class="purpose-card">
                    <div class="purpose-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                        </svg>
                    </div>
                    <h3>HOSPITAL / MEDICAL</h3>
                    <p>Visiting a patient in the prison hospital. Destination: Hospital.</p>
                </a>

                <a href="../vehicle/" class="purpose-card">
                    <div class="purpose-icon">
                        <svg viewBox="0 0 24 24">
                            <rect x="1" y="3" width="15" height="13"/>
                            <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                            <circle cx="5.5" cy="18.5" r="2.5"/>
                            <circle cx="18.5" cy="18.5" r="2.5"/>
                        </svg>
                    </div>
                    <h3>DELIVERY</h3>
                    <p>Couriers, supplies, equipment. Quick registration with ANPR gate.</p>
                </a>

                <a href="../general/" class="purpose-card">
                    <div class="purpose-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </div>
                    <h3>GENERAL / BUSINESS</h3>
                    <p>Staff visit, delivery, suppliers, official business. Choose destination.</p>
                </a>
            </div>

            <div style="text-align: center; padding: 24px; background: #f5f5f5; border-radius: 10px; margin-top: 20px;">
                <div style="font-weight: 600; font-size: 12px; margin-bottom: 8px;">Have a booking reference?</div>
                <input type="text" id="bookingRef" placeholder="Enter reference number" style="padding: 10px 14px; border: 1px solid #ccc; border-radius: 6px; width: 220px; font-size: 13px;">
                <button class="btn" onclick="loadBooking()" style="margin-left: 8px;">Load</button>
            </div>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script src="app.js"></script>

</body>
</html>
