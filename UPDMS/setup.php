<?php
/**
 * UPDMS Database Setup Script
 * Run this file in browser: http://localhost/UPDMS/setup.php
 */

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Connect without database first
    $host = $_POST['db_host'] ?? 'localhost';
    $user = $_POST['db_user'] ?? 'root';
    $pass = $_POST['db_pass'] ?? '';
    
    try {
        $pdo = new PDO("mysql:host=$host", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Create database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS updms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE updms_db");
        
        // Create tables
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS facilities (
                id INT PRIMARY KEY AUTO_INCREMENT,
                facility_code VARCHAR(10) UNIQUE NOT NULL,
                name VARCHAR(100) NOT NULL,
                region VARCHAR(50),
                type ENUM('maximum', 'medium', 'minimum', 'rehabilitation') DEFAULT 'medium',
                capacity INT DEFAULT 0,
                address VARCHAR(255),
                is_active TINYINT DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT PRIMARY KEY AUTO_INCREMENT,
                username VARCHAR(50) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                full_name VARCHAR(100) NOT NULL,
                badge_number VARCHAR(20) UNIQUE,
                role ENUM('admin', 'hq_command', 'supervisor', 'gate_officer') NOT NULL,
                facility_id INT,
                phone VARCHAR(20),
                is_active TINYINT DEFAULT 1,
                last_login TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE SET NULL
            )
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS visitors (
                id INT PRIMARY KEY AUTO_INCREMENT,
                visitor_type ENUM('inmate', 'hospital', 'staff', 'official', 'delivery') NOT NULL,
                national_id VARCHAR(20),
                first_name VARCHAR(50) NOT NULL,
                last_name VARCHAR(50),
                phone VARCHAR(20),
                vehicle_plate VARCHAR(20),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS visitor_logs (
                id INT PRIMARY KEY AUTO_INCREMENT,
                visitor_id INT NOT NULL,
                ref_number VARCHAR(20),
                facility_id INT NOT NULL,
                visitor_type ENUM('inmate', 'hospital', 'staff', 'official', 'delivery') NOT NULL,
                full_name VARCHAR(100),
                national_id VARCHAR(20),
                phone VARCHAR(20),
                purpose VARCHAR(100),
                destination VARCHAR(100),
                person_visited VARCHAR(100),
                entry_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                exit_time TIMESTAMP NULL,
                duration_minutes INT,
                status ENUM('pending', 'inside', 'exited', 'blocked') DEFAULT 'pending',
                gate_officer_entry_id INT,
                gate_officer_exit_id INT,
                notes TEXT,
                FOREIGN KEY (visitor_id) REFERENCES visitors(id),
                FOREIGN KEY (facility_id) REFERENCES facilities(id)
            )
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS vehicles (
                id INT PRIMARY KEY AUTO_INCREMENT,
                plate_number VARCHAR(20) NOT NULL,
                vehicle_type VARCHAR(30),
                color VARCHAR(30),
                make_model VARCHAR(50),
                owner_name VARCHAR(100),
                owner_phone VARCHAR(20),
                company VARCHAR(100),
                last_driver_name VARCHAR(100),
                last_visit TIMESTAMP NULL,
                total_visits INT DEFAULT 0,
                is_blacklisted TINYINT DEFAULT 0,
                blacklisted_reason TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_plate (plate_number)
            )
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS vehicle_logs (
                id INT PRIMARY KEY AUTO_INCREMENT,
                vehicle_id INT NOT NULL,
                facility_id INT NOT NULL,
                visitor_type ENUM('inmate', 'hospital', 'staff', 'official', 'delivery') NOT NULL,
                driver_name VARCHAR(100),
                driver_id VARCHAR(20),
                entry_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                exit_time TIMESTAMP NULL,
                duration_minutes INT,
                status ENUM('inside', 'exited') DEFAULT 'inside',
                gate_officer_entry_id INT NOT NULL,
                gate_officer_exit_id INT,
                notes TEXT,
                FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
                FOREIGN KEY (facility_id) REFERENCES facilities(id)
            )
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS visitor_bookings (
                id INT PRIMARY KEY AUTO_INCREMENT,
                visitor_id INT NOT NULL,
                ref_number VARCHAR(20) UNIQUE NOT NULL,
                facility_id INT,
                booking_date DATE NOT NULL,
                booking_time TIME NOT NULL,
                visit_purpose VARCHAR(200),
                status ENUM('pending', 'approved', 'rejected', 'completed', 'cancelled') DEFAULT 'pending',
                approved_by INT,
                approved_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (visitor_id) REFERENCES visitors(id),
                FOREIGN KEY (facility_id) REFERENCES facilities(id)
            )
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS action_logs (
                id BIGINT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                user_name VARCHAR(100),
                action_type VARCHAR(50) NOT NULL,
                table_name VARCHAR(50) NOT NULL,
                record_id INT,
                old_values JSON,
                new_values JSON,
                ip_address VARCHAR(45),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Insert default facility
        $pdo->exec("
            INSERT IGNORE INTO facilities (id, facility_code, name, region, type, capacity) 
            VALUES (1, 'LUZ', 'Luzira Upper Prison', 'Central', 'maximum', 3000)
        ");
        
        // Insert default admin user (password: admin123)
        $pdo->exec("
            INSERT IGNORE INTO users (id, username, password_hash, full_name, badge_number, role, facility_id) 
            VALUES (1, 'admin', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'ADMIN001', 'admin', 1)
        ");
        
        // Insert default gate officer
        $pdo->exec("
            INSERT IGNORE INTO users (id, username, password_hash, full_name, badge_number, role, facility_id) 
            VALUES (2, 'gate1', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Gate Officer One', 'LUZ101', 'gate_officer', 1)
        ");
        
        $message = 'Database setup completed successfully!';
        $success = true;
        
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UPDMS Setup</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: #f5f5f5; color: #000; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { background: #fff; border: 1px solid #e5e5e5; border-radius: 12px; width: 100%; max-width: 450px; overflow: hidden; }
        .header { background: #000; color: #fff; padding: 32px; text-align: center; }
        .header h1 { font-size: 20px; margin-bottom: 8px; }
        .header p { font-size: 12px; color: #999; }
        .body { padding: 32px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px; color: #666; }
        .form-group input { width: 100%; padding: 12px 16px; border: 1px solid #ccc; border-radius: 8px; font-size: 14px; }
        .form-group input:focus { outline: none; border-color: #000; }
        .btn { width: 100%; padding: 14px; background: #000; color: #fff; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; }
        .btn:hover { background: #333; }
        .alert { padding: 16px 20px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .info { background: #f5f5f5; padding: 16px; border-radius: 8px; font-size: 13px; color: #666; margin-bottom: 20px; }
        .info strong { color: #000; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>⚖ UPDMS Setup</h1>
            <p>Uganda Prisons Digital Management System</p>
        </div>
        <div class="body">
            <?php if ($message): ?>
                <div class="alert <?php echo $success ? 'alert-success' : 'alert-error'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="info">
                    <strong>Default Login:</strong><br>
                    Username: <code>admin</code><br>
                    Password: <code>admin123</code>
                </div>
                <a href="landing.php" class="btn" style="display: block; text-align: center; text-decoration: none;">Go to System →</a>
            <?php else: ?>
                <div class="info">
                    <strong>Database Settings:</strong><br>
                    Default values work for XAMPP. Update if needed.
                </div>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Database Host</label>
                        <input type="text" name="db_host" value="localhost">
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="db_user" value="root">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="db_pass" value="">
                    </div>
                    <button type="submit" class="btn">Setup Database</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
