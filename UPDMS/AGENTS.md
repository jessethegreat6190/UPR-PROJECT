# UPDMS - Uganda Prisons Digital Management System

## Quick Start

1. **Setup Database**: Open browser and go to `http://localhost/UPDMS/setup.php`
2. **Login**: Username: `admin`, Password: `admin123`
3. **Access Modules**: Go to `http://localhost/UPDMS/pages/landing.php`

## Project Structure

```
UPDMS/
├── api/                    # AJAX endpoints
│   ├── dashboard.php      # Dashboard stats API
│   ├── visitors.php       # Visitor management API
│   ├── vehicles.php       # Vehicle management API
│   ├── gate-api.php       # Gate control API (housing module)
│   └── housing-api.php   # Housing quarters API
├── assets/css/
│   ├── black-white.css    # Main theme (black & white)
│   ├── ups-theme.css      # UPS branded theme (maroon/gold)
│   └── custom.css         # Custom overrides
├── pages/
│   ├── landing.php       # Main landing page
│   ├── gate/
│   │   ├── kiosk.php     # Public visitor kiosk
│   │   ├── staff.php     # Staff portal with sidebar
│   │   ├── gate-dashboard.php
│   │   └── ...
│   ├── hq/
│   │   ├── housing.html  # Housing quarters demo
│   │   └── vehicle-gate.html
│   └── ...
├── config/
│   ├── database.php      # Database connection
│   ├── constants.php     # Configuration constants
│   └── bootstrap.php      # Core functions
├── sql/
│   ├── setup.sql         # Main database schema
│   └── housing_module.sql # Housing quarters schema
└── uploads/              # File uploads
```

## Modules

### 1. Visitor Kiosk (`pages/gate/kiosk.php`)
- Public self-service registration
- Step-by-step wizard (Purpose → Details → Submit)
- Generates reference numbers (VIS-2026-XXXX)
- Offline-capable

### 2. Staff Portal (`pages/gate/staff.php`)
- Sidebar navigation
- Visitor queue management
- Vehicle tracking
- Booking management
- Alerts system

### 3. Gate Control
- Vehicle entry/exit tracking
- ANPR camera status display
- Overstay alerts
- Vehicle whitelist

## Database Setup

Run `setup.php` in browser OR import `sql/setup.sql` manually.

Default credentials: `admin` / `admin123`

## CSS Themes

- **black-white.css**: Clean black & white theme
- **ups-theme.css**: Official UPS maroon/gold branding

## API Endpoints

| Endpoint | Actions |
|----------|---------|
| `/api/dashboard.php` | stats, current_vehicles, overstay |
| `/api/visitors.php` | kiosk_register, list_visitors, approve_visitor, record_exit |
| `/api/vehicles.php` | check, check_inside, blacklist |
| `/api/gate-api.php` | detect, entries, approve, exit |

## Coding Standards

- Use `getDB()` for database operations
- Use `sanitize()` for user input
- Use `logAction()` for audit logging
- Use `requireLogin()` for protected pages
