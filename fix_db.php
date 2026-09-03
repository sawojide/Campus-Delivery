<?php
// fix_db.php - Forces a complete database rebuild
require_once 'includes/db.php';

echo "<h2>🔧 Fixing Database Schema...</h2>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;} .success{color:green;} .error{color:red;}</style>";

// 1. Turn off foreign keys so we can drop tables in any order
$pdo->exec("PRAGMA foreign_keys = OFF;");

// 2. Drop all existing tables
$tables = ['promo_codes', 'order_items', 'orders', 'products', 'vendors', 'wallets', 'users'];
echo "<h3>Step 1: Dropping old tables...</h3>";
foreach ($tables as $table) {
    try {
        $pdo->exec("DROP TABLE IF EXISTS $table");
        echo "<p class='success'>✅ Dropped table: $table</p>";
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Error dropping $table: " . $e->getMessage() . "</p>";
    }
}

// 3. Create new tables with the CORRECT schema (full_name)
echo "<h3>Step 2: Creating new tables...</h3>";

$queries = [
    "CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        full_name TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        phone TEXT NOT NULL,
        password TEXT NOT NULL,
        role TEXT DEFAULT 'student',
        hostel_address TEXT,
        latitude REAL,
        longitude REAL,
        referral_code TEXT UNIQUE,
        referred_by INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE wallets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER UNIQUE NOT NULL,
        balance REAL DEFAULT 0.00,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",

    "CREATE TABLE vendors (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        business_name TEXT NOT NULL,
        description TEXT,
        logo TEXT,
        latitude REAL,
        longitude REAL,
        is_approved INTEGER DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    
    "CREATE TABLE products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        vendor_id INTEGER,
        name TEXT NOT NULL,
        description TEXT,
        price REAL NOT NULL,
        category TEXT,
        image TEXT,
        stock INTEGER DEFAULT 0,
        FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE
    )",
    
    "CREATE TABLE orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        vendor_id INTEGER,
        rider_id INTEGER,
        total_amount REAL NOT NULL,
        delivery_fee REAL DEFAULT 0.00,
        promo_code TEXT,
        discount_amount REAL DEFAULT 0.00,
        status TEXT DEFAULT 'pending',
        payment_method TEXT DEFAULT 'wallet',
        delivery_address TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (vendor_id) REFERENCES vendors(id),
        FOREIGN KEY (rider_id) REFERENCES users(id)
    )",
    
    "CREATE TABLE order_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER,
        product_id INTEGER,
        quantity INTEGER NOT NULL,
        price REAL NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id)
    )",

    "CREATE TABLE promo_codes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        code TEXT UNIQUE NOT NULL,
        type TEXT DEFAULT 'percentage',
        value REAL NOT NULL,
        min_order REAL DEFAULT 0,
        max_uses INTEGER DEFAULT 0,
        current_uses INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1,
        expires_at DATETIME
    )"
];

foreach ($queries as $i => $query) {
    try {
        $pdo->exec($query);
        echo "<p class='success'>✅ Created table " . ($i + 1) . "</p>";
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Error creating table: " . $e->getMessage() . "</p>";
    }
}

// 4. Turn foreign keys back on
$pdo->exec("PRAGMA foreign_keys = ON;");

echo "<hr>";
echo "<h3 style='color:green;'>🎉 Database Fixed!</h3>";
echo "<p><a href='register.php' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Test Registration Now</a></p>";
?>