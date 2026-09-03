<?php
// Run this file ONCE to create all your tables
require_once 'includes/db.php';

$queries = [
    "CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        role TEXT DEFAULT 'student',
        phone TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS vendors (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        business_name TEXT NOT NULL,
        description TEXT,
        logo TEXT,
        is_approved INTEGER DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )",
    
    "CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        vendor_id INTEGER,
        name TEXT NOT NULL,
        description TEXT,
        price REAL NOT NULL,
        category TEXT,
        image TEXT,
        stock INTEGER DEFAULT 0,
        FOREIGN KEY (vendor_id) REFERENCES vendors(id)
    )",
    
    "CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        vendor_id INTEGER,
        rider_id INTEGER,
        total_amount REAL NOT NULL,
        status TEXT DEFAULT 'pending',
        delivery_address TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (vendor_id) REFERENCES vendors(id),
        FOREIGN KEY (rider_id) REFERENCES users(id)
    )",
    
    "CREATE TABLE IF NOT EXISTS order_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER,
        product_id INTEGER,
        quantity INTEGER NOT NULL,
        price REAL NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id),
        FOREIGN KEY (product_id) REFERENCES products(id)
    )"
];

foreach ($queries as $query) {
    try {
        $pdo->exec($query);
        echo "✅ Table created successfully<br>";
    } catch (PDOException $e) {
        echo "❌ Error: " . $e->getMessage() . "<br>";
    }
}

echo "<br><strong>Database setup complete!</strong>";
?>