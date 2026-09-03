<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Initialize cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Get category from URL if exists
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';

// Fetch products with category filter
if (!empty($category_filter)) {
    // Define which vendor categories belong to each filter
    switch($category_filter) {
        case 'food':
            $sql = "SELECT p.*, v.shop_name, v.category, v.average_rating, v.total_reviews 
                    FROM products p 
                    JOIN vendors v ON p.vendor_id = v.id 
                    WHERE v.is_active = 1 AND p.stock > 0 AND p.is_available = 1 AND v.category = 'Food'
                    ORDER BY p.id DESC";
            $stmt = $pdo->query($sql);
            break;
            
        case 'perfumes':
            $sql = "SELECT p.*, v.shop_name, v.category, v.average_rating, v.total_reviews 
                    FROM products p 
                    JOIN vendors v ON p.vendor_id = v.id 
                    WHERE v.is_active = 1 AND p.stock > 0 AND p.is_available = 1 AND v.category IN ('Perfumes', 'Cosmetics')
                    ORDER BY p.id DESC";
            $stmt = $pdo->query($sql);
            break;
            
        case 'provisions':
            $sql = "SELECT p.*, v.shop_name, v.category, v.average_rating, v.total_reviews 
                    FROM products p 
                    JOIN vendors v ON p.vendor_id = v.id 
                    WHERE v.is_active = 1 AND p.stock > 0 AND p.is_available = 1 AND v.category = 'Provisions'
                    ORDER BY p.id DESC";
            $stmt = $pdo->query($sql);
            break;
            
        case 'snacks':
            $sql = "SELECT p.*, v.shop_name, v.category, v.average_rating, v.total_reviews 
                    FROM products p 
                    JOIN vendors v ON p.vendor_id = v.id 
                    WHERE v.is_active = 1 AND p.stock > 0 AND p.is_available = 1 AND v.category = 'Snacks'
                    ORDER BY p.id DESC";
            $stmt = $pdo->query($sql);
            break;
            
        case 'electronics':
            $sql = "SELECT p.*, v.shop_name, v.category, v.average_rating, v.total_reviews 
                    FROM products p 
                    JOIN vendors v ON p.vendor_id = v.id 
                    WHERE v.is_active = 1 AND p.stock > 0 AND p.is_available = 1 AND v.category = 'Electronics'
                    ORDER BY p.id DESC";
            $stmt = $pdo->query($sql);
            break;
            
        case 'books':
            $sql = "SELECT p.*, v.shop_name, v.category, v.average_rating, v.total_reviews 
                    FROM products p 
                    JOIN vendors v ON p.vendor_id = v.id 
                    WHERE v.is_active = 1 AND p.stock > 0 AND p.is_available = 1 AND v.category IN ('Books', 'Stationery')
                    ORDER BY p.id DESC";
            $stmt = $pdo->query($sql);
            break;
            
        default:
            $stmt = $pdo->query("
                SELECT p.*, v.shop_name, v.category, v.average_rating, v.total_reviews 
                FROM products p 
                JOIN vendors v ON p.vendor_id = v.id 
                WHERE v.is_active = 1 AND p.stock > 0 AND p.is_available = 1 
                ORDER BY p.id DESC
            ");
            break;
    }
} else {
    // No filter - show all products
    $stmt = $pdo->query("
        SELECT p.*, v.shop_name, v.category, v.average_rating, v.total_reviews 
        FROM products p 
        JOIN vendors v ON p.vendor_id = v.id 
        WHERE v.is_active = 1 AND p.stock > 0 AND p.is_available = 1 
        ORDER BY p.id DESC
    ");
}

$products = $stmt->fetchAll();

// Count cart items
$cart_count = array_sum(array_column($_SESSION['cart'], 'quantity'));

// Category names for display
$category_names = [
    'food' => 'Suya, BBQ & Hot Meals',
    'perfumes' => 'Perfumes & Cosmetics',
    'provisions' => 'Provisions & Groceries',
    'snacks' => 'Snacks & Drinks',
    'electronics' => 'Electronics & Gadgets',
    'books' => 'Books & Stationery'
];

$current_category_name = $category_names[$category_filter] ?? 'All Products';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse - Campus Delivery</title>
    <!-- Bootstrap 5 CSS (For beautiful, responsive layout) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons (For shopping carts, users, etc.) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#dc3545">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Campus Delivery">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="icons/icon-192x192.png">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-danger">
    <div class="container">
        <a href="dashboard.php" class="navbar-brand mb-0 h1"><i class="bi bi-arrow-left"></i> Dashboard</a>
        <div class="d-flex align-items-center">
            <a href="cart.php" class="btn btn-light position-relative me-3">
                <i class="bi bi-cart3"></i>
                <?php if ($cart_count > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?= $cart_count ?>
                    </span>
                <?php endif; ?>
            </a>
        </div>
    </div>
</nav>

<!-- Category Filter Header -->
<div class="container mt-4">
    <?php if (!empty($category_filter)): ?>
        <div class="alert bg-light border">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="bi bi-funnel-fill text-danger"></i> 
                    <?= htmlspecialchars($current_category_name) ?>
                </h4>
                <a href="browse.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x-circle"></i> Clear Filter - Show All
                </a>
            </div>
        </div>
    <?php else: ?>
        <h4 class="mb-4"><i class="bi bi-shop"></i> Available Items</h4>
    <?php endif; ?>
    
    <?php if (empty($products)): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i> 
            No products available in this category.
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($products as $product): ?>
                <div class="col-md-4 col-lg-3 mb-4">
                    <div class="card h-100 shadow-sm border-0">
                        <img src="<?= htmlspecialchars($product['image_url']) ?>" class="card-img-top" alt="<?= htmlspecialchars($product['name']) ?>" style="height: 200px; object-fit: cover;">
                        <div class="card-body d-flex flex-column">
                            <span class="badge bg-secondary w-fit mb-2"><?= htmlspecialchars($product['category']) ?></span>
                            <h6 class="card-title fw-bold"><?= htmlspecialchars($product['name']) ?></h6>
                            <p class="card-text text-muted small flex-grow-1"><?= htmlspecialchars($product['description']) ?></p>
                            
                            <!-- ✅ ADDED: Vendor Rating Display -->
                            <div class="d-flex align-items-center mb-2">
                                <div class="text-warning me-2">
                                    <i class="bi bi-star-fill"></i> 
                                    <span class="fw-bold text-dark"><?= number_format($product['average_rating'], 1) ?></span>
                                </div>
                                <small class="text-muted">(<?= $product['total_reviews'] ?> reviews)</small>
                                <small class="text-muted ms-2">| Sold by: <?= htmlspecialchars($product['shop_name']) ?></small>
                            </div>
                            
                            <form method="POST" action="cart.php">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                <input type="hidden" name="product_name" value="<?= htmlspecialchars($product['name']) ?>">
                                <input type="hidden" name="product_price" value="<?= $product['price'] ?>">
                                
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <span class="fs-5 fw-bold text-danger">₦<?= number_format($product['price']) ?></span>
                                    <div class="input-group" style="width: 120px;">
                                        <input type="number" name="quantity" class="form-control form-control-sm" value="1" min="1" max="10">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-cart-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="alert alert-info mt-3">
            <i class="bi bi-info-circle"></i> 
            Showing <?= count($products) ?> product(s) in <?= htmlspecialchars($current_category_name) ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- PWA Service Worker Registration -->
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('Service Worker registered successfully:', registration.scope);
            })
            .catch(error => {
                console.log('Service Worker registration failed:', error);
            });
    });
}
</script>
</body>
</html>