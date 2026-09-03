<?php
// includes/db.php - SQLite Version (Works on XAMPP AND Render)

try {
    // SQLite database file
    $db_file = __DIR__ . '/../campus_delivery.db';
    
    // Create PDO connection to SQLite
    $pdo = new PDO("sqlite:$db_file");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
?>