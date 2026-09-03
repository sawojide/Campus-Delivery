<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch all orders for this user
$stmt = $pdo->prepare("
    SELECT o.*, v.shop_name 
    FROM orders o 
    JOIN vendors v ON o.vendor_id = v.id 
    WHERE o.user_id = ? 
    ORDER BY o.created_at DESC
");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

// Status color mapping
$status_colors = [
    'pending' => 'warning',
    'preparing' => 'info',
    'ready' => 'primary',
    'delivering' => 'primary',
    'completed' => 'success',
    'cancelled' => 'danger'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - Campus Delivery</title>
    <!-- Bootstrap 5 CSS (For beautiful, responsive layout) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons (For shopping carts, users, etc.) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-danger">
    <div class="container">
        <a href="dashboard.php" class="navbar-brand mb-0 h1"><i class="bi bi-arrow-left"></i> Dashboard</a>
        <span class="navbar-text text-white">My Orders</span>
    </div>
</nav>

<div class="container mt-4">
    <h3 class="mb-4"><i class="bi bi-clock-history"></i> My Order History</h3>
    
    <?php if (empty($orders)): ?>
        <div class="alert alert-info text-center py-5">
            <i class="bi bi-bag display-4"></i>
            <p class="mt-3 mb-0">You haven't placed any orders yet!</p>
            <a href="browse.php" class="btn btn-danger mt-3">Start Shopping</a>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($orders as $order): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card shadow-sm h-100 border-0">
                        <div class="card-header bg-<?= $status_colors[$order['status']] ?> text-white d-flex justify-content-between align-items-center">
                            <span class="fw-bold"><i class="bi bi-receipt"></i> Order #<?= $order['id'] ?></span>
                            <span class="badge bg-white text-<?= $status_colors[$order['status']] ?>">
                                <?= strtoupper($order['status']) ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <p class="mb-2"><strong>Vendor:</strong> <?= htmlspecialchars($order['shop_name']) ?></p>
                            <p class="mb-2"><strong>Date:</strong> <?= date('M d, Y h:i A', strtotime($order['created_at'])) ?></p>
                            <p class="mb-2"><strong>Items Total:</strong> ₦<?= number_format($order['total_amount']) ?></p>
                            <p class="mb-2"><strong>Delivery:</strong> ₦<?= number_format($order['delivery_fee']) ?></p>
                            <hr>
                            <h5 class="text-danger mb-0">Total: ₦<?= number_format($order['total_amount'] + $order['delivery_fee']) ?></h5>
                        </div>
                        
                        <!-- ✅ UPDATED CARD FOOTER WITH REVIEW LOGIC -->
                        <div class="card-footer bg-white border-top-0 pb-3">
                            <a href="view_order.php?order_id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary w-100 mb-2">
                                <i class="bi bi-eye"></i> View Details
                            </a>
                            
                            <?php if ($order['status'] == 'completed'): ?>
                                <?php 
                                // Check if already reviewed
                                $stmt_review = $pdo->prepare("SELECT id FROM reviews WHERE order_id = ?");
                                $stmt_review->execute([$order['id']]);
                                $has_reviewed = $stmt_review->fetch();
                                ?>
                                
                                <?php if (!$has_reviewed): ?>
                                    <a href="submit_review.php?order_id=<?= $order['id'] ?>" class="btn btn-sm btn-warning w-100 text-dark fw-bold">
                                        <i class="bi bi-star-fill"></i> Leave a Review
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-secondary w-100" disabled>
                                        <i class="bi bi-check-circle-fill"></i> Reviewed
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>