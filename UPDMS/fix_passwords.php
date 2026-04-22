<?php
// Run this once to fix passwords, then delete it
$host = 'localhost';
$dbname = 'updms_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    
    // Update all passwords to 'admin123'
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ?");
    $stmt->execute([$hash]);
    
    echo "Passwords updated successfully!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
