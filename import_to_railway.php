<?php
// Temporary database import script
// DELETE THIS FILE AFTER USE!

// Railway MySQL credentials (GET THESE FROM RAILWAY DASHBOARD)
$host = 'sakura.proxy.rlwy.net';
$port = '52142';
$dbname = 'railway';
$username = 'root';
$password = 'kAbeSeoKYtaoHTFVuLUaZxXasFZvuIZS'; // ← Replace with actual password from Railway

// Connect to Railway
try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<h2>✅ Connected to Railway Database!</h2>";
    
    // Read the SQL file
    $sqlFile = __DIR__ . '/campus_backup.sql';
    if (!file_exists($sqlFile)) {
        die("❌ Error: campus_backup.sql not found!");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Execute the SQL
    echo "<h3>Importing database... please wait</h3>";
    $pdo->exec($sql);
    
    echo "<h3 style='color:green;'>🎉 Database imported successfully!</h3>";
    echo "<p><strong>⚠️ IMPORTANT: Delete this file now for security!</strong></p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>