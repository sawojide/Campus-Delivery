<?php
session_start();
require 'includes/db.php';
require 'includes/auth.php';

// Security Check: Must be logged in to have a cart
requireLogin();

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$message = "";
$message_type = "";

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $product_id = intval($_POST['product_id']);
        
        // Update quantity
        if ($_POST['action'] == 'update') {
            $quantity = intval($_POST['quantity']);
            if ($quantity > 0) {
                foreach ($_SESSION['cart'] as &$item) {
                    if ($item['product_id'] == $product_id) {
                        $item['quantity'] = $quantity;
                        $message = "Cart updated!";
                        $message_type = "success";
                        break;
                    }
                }
            }
        }
        
        // Remove item
        if ($_POST['action'] == 'remove') {
            $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($product_id) {
                return $item['product_id'] != $product_id;
            });
            $_SESSION['cart'] = array_values($_SESSION['cart']);
            $message = "Item removed from cart!";
            $message_type = "info";
        }
        
        // Clear cart
        if ($_POST['action'] == 'clear') {
            $_SESSION['cart'] = [];
            $message = "Cart cleared!";
            $message_type = "info";
        }
    }
}

// Calculate totals
$total_items = 0;
$subtotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $total_items += $item['quantity'];
    $subtotal += $item['price'] * $item['quantity'];
}

// Estimated delivery fee for cart view (exact fee calculated at checkout)
$estimated_delivery_fee = 100.00; 
$grand_total = $subtotal + $estimated_delivery_fee;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Campus Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .quantity-btn { width: 35px; height: 35px; font-weight: bold; }
        .quantity-input { width: 60px; text-align: center; font-weight: bold; }
        .cart-item { transition: all 0.3s; }
        .cart-item:hover { background-color: #f8f9fa; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-danger">
    <div class="container">
        <a href="browse.php" class="navbar-brand mb-0 h1 text-decoration-none text-white">
            <i class="bi bi-arrow-left"></i> Continue Shopping
        </a>
        <div class="d-flex align-items-center">
            <span class="text-white me-3"><i class="bi bi-cart3"></i> <?= $total_items ?> Items</span>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h3 class="mb-4"><i class="bi bi-cart3"></i> Your Shopping Cart</h3>
    
    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (empty($_SESSION['cart'])): ?>
        <div class="alert alert-info text-center py-5">
            <i class="bi bi-cart-x display-4"></i>
            <p class="mt-3 mb-0">Your cart is empty!</p>
            <a href="browse.php" class="btn btn-danger mt-3">Start Shopping</a>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <?php foreach ($_SESSION['cart'] as $item): ?>
                            <div class="cart-item border-bottom py-3">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h6 class="mb-1"><?= htmlspecialchars($item['name']) ?></h6>
                                        <p class="text-muted mb-2">₦<?= number_format($item['price']) ?> per item</p>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                            <input type="hidden" name="action" value="update">
                                            <div class="input-group" style="width: 150px;">
                                                <button type="button" class="btn btn-outline-secondary quantity-btn" onclick="updateQuantity(<?= $item['product_id'] ?>, <?= $item['quantity'] - 1 ?>)"><i class="bi bi-dash"></i></button>
                                                <input type="number" name="quantity" class="form-control quantity-input" value="<?= $item['quantity'] ?>" min="1" max="10" onchange="updateQuantity(<?= $item['product_id'] ?>, this.value)">
                                                <button type="button" class="btn btn-outline-secondary quantity-btn" onclick="updateQuantity(<?= $item['product_id'] ?>, <?= $item['quantity'] + 1 ?>)"><i class="bi bi-plus"></i></button>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <h5 class="text-danger mb-2">₦<?= number_format($item['price'] * $item['quantity']) ?></h5>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                            <input type="hidden" name="action" value="remove">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this item?')"><i class="bi bi-trash"></i> Remove</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <form method="POST" class="mt-3">
                            <input type="hidden" name="action" value="clear">
                            <button type="submit" class="btn btn-outline-secondary btn-sm" onclick="return confirm('Clear all items?')"><i class="bi bi-trash"></i> Clear Cart</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Order Summary</h5>
                        <hr>
                        <div class="d-flex justify-content-between mb-2"><span>Subtotal (<?= $total_items ?> items):</span><strong>₦<?= number_format($subtotal, 2) ?></strong></div>
                        <div class="d-flex justify-content-between mb-2"><span>Estimated Delivery:</span><strong>₦<?= number_format($estimated_delivery_fee, 2) ?></strong></div>
                        <hr>
                        <div class="d-flex justify-content-between mb-3"><span class="fs-5 fw-bold">Total:</span><span class="fs-4 text-danger fw-bold">₦<?= number_format($grand_total, 2) ?></span></div>
                        <a href="checkout.php" class="btn btn-success w-100 btn-lg"><i class="bi bi-credit-card"></i> Proceed to Checkout</a>
                        <a href="browse.php" class="btn btn-outline-secondary w-100 mt-2"><i class="bi bi-arrow-left"></i> Continue Shopping</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function updateQuantity(productId, quantity) {
    if (quantity < 1) { alert('Minimum quantity is 1'); return; }
    if (quantity > 10) { alert('Maximum quantity is 10 per item'); return; }
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `<input type="hidden" name="product_id" value="${productId}"><input type="hidden" name="action" value="update"><input type="hidden" name="quantity" value="${quantity}">`;
    document.body.appendChild(form);
    form.submit();
}
</script>
</body>
</html>