<?php
session_start();
require 'includes/db.php';
require 'includes/auth.php';

// Security Check: Must be logged in
requireLogin();

// Initialize cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Get category from URL if exists
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';

// Resilient SQL Query: Maps 'business_name' to 'shop_name' and 'image' to 'image_url' 
// to perfectly match our setup_database.php schema without crashing.
$base_sql = "SELECT p.id, p.name, p.description, p.price, p.category, p.image AS image_url, p.stock, 
                    v.business_name AS shop_name, 0 AS average_rating, 0 AS total_reviews 
             FROM products p 
             JOIN vendors v ON p.vendor_id = v.id 
             WHERE v.is_approved = 1 AND p.stock > 0";

if (!empty($category_filter)) {
    switch($category_filter) {
        case 'food':
            $sql = $base_sql . " AND p.category = 'Food' ORDER BY p.id DESC";
            break;
        case 'perfumes':
            $sql = $base_sql . " AND p.category IN ('Perfumes', 'Cosmetics') ORDER BY p.id DESC";
            break;
        case 'provisions':
            $sql = $base_sql . " AND p.category = 'Provisions' ORDER BY p.id DESC";
            break;
        case 'snacks':
            $sql = $base_sql . " AND p.category = 'Snacks' ORDER BY p.id DESC";
            break;
        case 'electronics':
            $sql = $base_sql . " AND p.category = 'Electronics' ORDER BY p.id DESC";
            break;
        case 'books':
            $sql = $base_sql . " AND p.category IN ('Books', 'Stationery') ORDER BY p.id DESC";
            break;
        default:
            $sql = $base_sql . " ORDER BY p.id DESC";
            break;
    }
    $stmt = $pdo->query($sql);
} else {
    $stmt = $pdo->query($base_sql . " ORDER BY p.id DESC");
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <meta name="theme-color" content="#dc3545">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-danger">
    <div class="container">
        <a href="dashboard.php" class="navbar-brand mb-0 h1 text-decoration-none text-white">
            <i class="bi bi-arrow-left"></i> Dashboard
        </a>
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
            <i class="bi bi-exclamation-triangle"></i> No products available in this category.
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($products as $product): ?>
                <div class="col-md-4 col-lg-3 mb-4">
                    <div class="card h-100 shadow-sm border-0">
                        <img src="<?= htmlspecialchars($product['image_url'] ?: 'https://via.placeholder.com/300x200?text=No+Image') ?>" 
                             class="card-img-top" alt="<?= htmlspecialchars($product['name']) ?>" style="height: 200px; object-fit: cover;">
                        <div class="card-body d-flex flex-column">
                            <span class="badge bg-secondary w-fit mb-2"><?= htmlspecialchars($product['category']) ?></span>
                            <h6 class="card-title fw-bold"><?= htmlspecialchars($product['name']) ?></h6>
                            <p class="card-text text-muted small flex-grow-1"><?= htmlspecialchars($product['description']) ?></p>
                            
                            <div class="d-flex align-items-center mb-2">
                                <small class="text-muted">Sold by: <?= htmlspecialchars($product['shop_name']) ?></small>
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
            <i class="bi bi-info-circle"></i> Showing <?= count($products) ?> product(s) in <?= htmlspecialchars($current_category_name) ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>