<?php
// delete_db.php - Force delete the database file
echo "<h2>Deleting Old Database...</h2>";

$db_file = __DIR__ . '/campus_delivery.db';

// Close any PDO connections
if (isset($pdo)) {
    $pdo = null;
}

// Force delete
if (file_exists($db_file)) {
    chmod($db_file, 0777); // Make it writable
    unlink($db_file); // Delete it
    
    if (!file_exists($db_file)) {
        echo "<div style='color: green; font-family: Arial; padding: 20px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px;'>";
        echo "<h3>✅ SUCCESS!</h3>";
        echo "<p>Old database deleted.</p>";
        echo "<p><a href='setup_database.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;'>Click here to create new database</a></p>";
        echo "</div>";
    } else {
        echo "<div style='color: red;'>❌ Failed to delete. File still exists.</div>";
    }
} else {
    echo "<div style='color: orange;'>⚠️ Database file not found. <a href='setup_database.php'>Create it now</a></div>";
}
?>