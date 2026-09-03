<?php
// setup_database.php - Run this ONCE to create all tables
require_once 'includes/db.php';

echo "<h2>Setting Up Campus Delivery Database...</h2>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;} .success{color:green;} .error{color:red;}</style>";

$queries = [
    // 1. Users Table (Includes location columns for distance calculation)
    "CREATE TABLE IF NOT EXISTS users (
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
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (referred_by) REFERENCES users(id)
    )",
    
    // 2. Wallets Table (Required by your register.php)
    "CREATE TABLE IF NOT EXISTS wallets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER UNIQUE NOT NULL,
        balance REAL DEFAULT 0.00,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",

    // 3. Vendors Table (Includes location columns for distance calculation)
    "CREATE TABLE IF NOT EXISTS vendors (
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
    
    // 4. Products Table
    "CREATE TABLE IF NOT EXISTS products (
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
    
    // 5. Orders Table
    "CREATE TABLE IF NOT EXISTS orders (
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
    
    // 6. Order Items Table
    "CREATE TABLE IF NOT EXISTS order_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER,
        product_id INTEGER,
        quantity INTEGER NOT NULL,
        price REAL NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id)
    )",

    // 7. Promo Codes Table (For checkout discounts)
    "CREATE TABLE IF NOT EXISTS promo_codes (
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

$success = 0;
$error = 0;

foreach ($queries as $index => $query) {
    try {
        $pdo->exec($query);
        echo "<p class='success'>✅ Table " . ($index + 1) . " created successfully</p>";
        $success++;
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Error creating table " . ($index + 1) . ": " . $e->getMessage() . "</p>";
        $error++;
    }
}

echo "<hr>";
echo "<h3>Summary: $success successful, $error errors</h3>";

if ($error == 0) {
    echo "<p class='success'><strong>🎉 Database setup complete!</strong></p>";
    echo "<p><a href='index.php'>Go to Homepage</a> | <a href='register.php'>Test Registration</a></p>";
    echo "<p style='color:red;'><strong>⚠️ IMPORTANT:</strong> Delete or rename this file after running it once for security.</p>";
}
?>