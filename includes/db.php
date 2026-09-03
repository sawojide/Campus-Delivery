<?php
// includes/db.php - SQLite Version (Works on XAMPP AND Render)

try {
    // SQLite database file in the root directory
    $db_file = __DIR__ . '/../campus_delivery.db';
    
    // Create PDO connection to SQLite
    $pdo = new PDO("sqlite:$db_file");
    
    // Set error modes
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // CRITICAL: Enable foreign key constraints in SQLite
    $pdo->exec("PRAGMA foreign_keys = ON;");
    
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
?>