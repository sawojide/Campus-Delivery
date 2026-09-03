<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['order_id'])) {
    header("Location: dashboard.php");
    exit;
}

$order_id = intval($_GET['order_id']);
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: dashboard.php");
    exit;
}

// Fetch order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.name 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();

// Format payment method for display
$payment_method_display = isset($order['payment_method']) && $order['payment_method'] == 'paystack' 
    ? 'Paystack (Card/Bank)' 
    : 'Wallet Balance';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success - Campus Delivery</title>
    <!-- Bootstrap 5 CSS (For beautiful, responsive layout) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons (For shopping carts, users, etc.) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .success-icon {
            animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        @keyframes popIn {
            0% { transform: scale(0); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card shadow border-0">
                <div class="card-body p-5 text-center">
                    <!-- Success Animation -->
                    <div class="mb-4 success-icon">
                        <i class="bi bi-check-circle-fill text-success display-1"></i>
                    </div>
                    
                    <h2 class="mb-2 fw-bold">Order Placed Successfully!</h2>
                    <p class="lead text-muted">Thank you! Your order #<?= $order_id ?> has been confirmed.</p>
                    
                    <!-- Order Summary Box -->
                    <div class="alert alert-light border text-start mt-4 p-4">
                        <div class="row">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <p class="mb-1"><strong>Order Status:</strong> <span class="badge bg-warning text-dark"><?= strtoupper($order['status']) ?></span></p>
                                <p class="mb-0"><strong>Payment Method:</strong> <?= htmlspecialchars($payment_method_display) ?></p>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <p class="mb-1 text-muted">Total Amount Paid</p>
                                <h4 class="text-success fw-bold mb-0">₦<?= number_format($order['total_amount'] + $order['delivery_fee'], 2) ?></h4>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Items List -->
                    <h6 class="text-start mt-4 mb-3 fw-bold"><i class="bi bi-bag-check"></i> Items Ordered:</h6>
                    <ul class="list-group list-group-flush text-start mb-4 rounded border">
                        <?php foreach ($items as $item): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>
                                    <?= htmlspecialchars($item['name']) ?> 
                                    <span class="badge bg-secondary rounded-pill">x<?= $item['quantity'] ?></span>
                                </span>
                                <span class="fw-bold">₦<?= number_format($item['price'] * $item['quantity']) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <!-- Action Buttons -->
                    <div class="d-flex flex-wrap justify-content-center gap-3 mt-4">
                        <a href="dashboard.php" class="btn btn-outline-secondary px-4">
                            <i class="bi bi-house"></i> Dashboard
                        </a>
                        <a href="order_history.php" class="btn btn-primary px-4">
                            <i class="bi bi-clock-history"></i> Track Order
                        </a>
                        <a href="browse.php" class="btn btn-danger px-4">
                            <i class="bi bi-shop"></i> Shop Again
                        </a>
                    </div>
                    
                    <p class="text-muted small mt-4 mb-0">
                        <i class="bi bi-envelope"></i> A confirmation has been noted. You can track your order status in your dashboard.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>