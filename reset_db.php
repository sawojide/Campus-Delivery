<?php
// reset_db.php - Run this ONCE to wipe the old database and start fresh
// SECURITY: In a real app, you would password-protect this file!

echo "<h2>Database Reset Tool</h2>";

// Close any active connections first
if (isset($pdo)) {
    $pdo = null;
}

$db_file = __DIR__ . '/campus_delivery.db';

if (file_exists($db_file)) {
    // Try to delete the file
    if (unlink($db_file)) {
        echo "<div style='color: green; font-family: Arial; padding: 20px;'>";
        echo "<h3>✅ Old database deleted successfully!</h3>";
        echo "<p>The old database file has been removed from the server.</p>";
        echo "<p><strong>Next Step:</strong> Run the setup script to create the new database with the correct columns.</p>";
        echo "<a href='setup_database.php' style='display: inline-block; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin-top: 10px;'>Create New Database</a>";
        echo "</div>";
    } else {
        echo "<div style='color: red; font-family: Arial; padding: 20px;'>";
        echo "<h3>❌ Failed to delete database</h3>";
        echo "<p>Permission denied or file is locked.</p>";
        echo "</div>";
    }
} else {
    echo "<div style='color: orange; font-family: Arial; padding: 20px;'>";
    echo "<h3>⚠️ No database file found</h3>";
    echo "<p>The database file doesn't exist yet.</p>";
    echo "<a href='setup_database.php' style='display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-top: 10px;'>Create Database Now</a>";
    echo "</div>";
}
?>