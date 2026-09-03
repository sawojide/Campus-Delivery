<?php
require_once 'includes/db.php';
echo "<h2>Force Resetting Database...</h2>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;}</style>";

$drop_queries = [
    "DROP TABLE IF EXISTS promo_codes",
    "DROP TABLE IF EXISTS order_items",
    "DROP TABLE IF EXISTS orders",
    "DROP TABLE IF EXISTS products",
    "DROP TABLE IF EXISTS vendors",
    "DROP TABLE IF EXISTS wallets",
    "DROP TABLE IF EXISTS users"
];

echo "<h3>Step 1: Dropping old tables...</h3>";
foreach ($drop_queries as $i => $query) {
    try { $pdo->exec($query); echo "<p class='success'>✅ Dropped table ".($i+1)."</p>"; } 
    catch (PDOException $e) { echo "<p class='error'>❌ Error: ".$e->getMessage()."</p>"; }
}

echo "<h3>Step 2: Creating new tables...</h3>";
$create_queries = [
    "CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, full_name TEXT NOT NULL, email TEXT UNIQUE NOT NULL, phone TEXT NOT NULL, password TEXT NOT NULL, role TEXT DEFAULT 'student', hostel_address TEXT, latitude REAL, longitude REAL, referral_code TEXT UNIQUE, referred_by INTEGER, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)",
    "CREATE TABLE wallets (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER UNIQUE NOT NULL, balance REAL DEFAULT 0.00)",
    "CREATE TABLE vendors (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, business_name TEXT NOT NULL, description TEXT, logo TEXT, latitude REAL, longitude REAL, is_approved INTEGER DEFAULT 0)",
    "CREATE TABLE products (id INTEGER PRIMARY KEY AUTOINCREMENT, vendor_id INTEGER, name TEXT NOT NULL, description TEXT, price REAL NOT NULL, category TEXT, image TEXT, stock INTEGER DEFAULT 0)",
    "CREATE TABLE orders (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, vendor_id INTEGER, rider_id INTEGER, total_amount REAL NOT NULL, delivery_fee REAL DEFAULT 0.00, promo_code TEXT, discount_amount REAL DEFAULT 0.00, status TEXT DEFAULT 'pending', payment_method TEXT DEFAULT 'wallet', delivery_address TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)",
    "CREATE TABLE order_items (id INTEGER PRIMARY KEY AUTOINCREMENT, order_id INTEGER, product_id INTEGER, quantity INTEGER NOT NULL, price REAL NOT NULL)",
    "CREATE TABLE promo_codes (id INTEGER PRIMARY KEY AUTOINCREMENT, code TEXT UNIQUE NOT NULL, type TEXT DEFAULT 'percentage', value REAL NOT NULL, min_order REAL DEFAULT 0, max_uses INTEGER DEFAULT 0, current_uses INTEGER DEFAULT 0, is_active INTEGER DEFAULT 1, expires_at DATETIME)"
];

foreach ($create_queries as $i => $query) {
    try { $pdo->exec($query); echo "<p class='success'>✅ Created table ".($i+1)."</p>"; } 
    catch (PDOException $e) { echo "<p class='error'>❌ Error: ".$e->getMessage()."</p>"; }
}

echo "<h3 style='color:green;'>🎉 Database completely reset!</h3>";
echo "<p><a href='register.php' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Test Registration Now</a></p>";
?>
