<?php
echo "<h1>Testing Database Connection</h1>";

$host = getenv('DB_HOST');
$port = getenv('DB_PORT');
$dbname = getenv('DB_NAME');
$username = getenv('DB_USER');
$password = getenv('DB_PASS');

echo "<p>Host: $host</p>";
echo "<p>Port: $port</p>";
echo "<p>Database: $dbname</p>";
echo "<p>User: $username</p>";

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<h2 style='color:green;'>✅ Connection Successful!</h2>";
    
    // Test query
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Tables found: " . count($tables) . "</p>";
    echo "<pre>" . print_r($tables, true) . "</pre>";
    
} catch(PDOException $e) {
    echo "<h2 style='color:red;'>❌ Connection Failed!</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>