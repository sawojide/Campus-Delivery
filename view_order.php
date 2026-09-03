<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['order_id'])) {
    header("Location: order_history.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$order_id = intval($_GET['order_id']);

// Fetch order details with Vendor and Rider info (Enhanced query)
$stmt = $pdo->prepare("
    SELECT o.*, v.shop_name, v.location as vendor_location, v.phone as vendor_phone,
           r.full_name as rider_name, r.phone as rider_phone
    FROM orders o 
    JOIN vendors v ON o.vendor_id = v.id 
    LEFT JOIN users r ON o.rider_id = r.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: order_history.php");
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

// Determine current step (1 to 4) for the visual tracker
$step = 1;
if ($order['status'] == 'preparing' || $order['status'] == 'ready') $step = 2;
elseif ($order['status'] == 'delivering') $step = 3;
elseif ($order['status'] == 'completed') $step = 4;
elseif ($order['status'] == 'cancelled') $step = 0;

$status_colors = [
    'pending' => 'warning', 
    'preparing' => 'info', 
    'ready' => 'primary',
    'delivering' => 'primary', 
    'completed' => 'success', 
    'cancelled' => 'danger'
];
$badge_color = $status_colors[$order['status']] ?? 'secondary';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order #<?= $order_id ?> - Campus Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .tracker-step { position: relative; padding-left: 45px; margin-bottom: 30px; }
        .tracker-step::before {
            content: ''; position: absolute; left: 15px; top: 35px; bottom: -35px;
            width: 2px; background: #e9ecef; z-index: 0;
        }
        .tracker-step:last-child::before { display: none; }
        .tracker-step.completed::before { background: #198754; }
        .step-icon {
            position: absolute; left: 0; top: 0; width: 32px; height: 32px;
            border-radius: 50%; background: #e9ecef; color: #6c757d;
            display: flex; align-items: center; justify-content: center; z-index: 1;
            font-size: 1rem;
        }
        .tracker-step.completed .step-icon { background: #198754; color: white; }
        .tracker-step.active .step-icon { background: #0d6efd; color: white; animation: pulse 2s infinite; }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(13, 110, 253, 0); }
            100% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0); }
        }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-danger">
    <div class="container">
        <a href="order_history.php" class="navbar-brand mb-0 h1"><i class="bi bi-arrow-left"></i> Back to Orders</a>
    </div>
</nav>

<div class="container mt-4">
    <div class="row">
        <!-- Left Column: Live Tracking & Contacts -->
        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-<?= $badge_color ?> text-white py-3">
                    <h5 class="mb-0"><i class="bi bi-geo-alt-fill"></i> Live Order Tracking #<?= $order_id ?></h5>
                </div>
                <div class="card-body p-4">
                    <?php if ($order['status'] == 'cancelled'): ?>
                        <div class="alert alert-danger text-center">
                            <i class="bi bi-x-circle display-4"></i>
                            <h4 class="mt-2">Order Cancelled</h4>
                            <p>This order was cancelled. If you were charged, your wallet has been refunded.</p>
                        </div>
                    <?php else: ?>
                        <!-- Step 1: Order Placed -->
                        <div class="tracker-step <?= $step >= 1 ? 'completed' : '' ?> <?= $step == 1 ? 'active' : '' ?>">
                            <div class="step-icon"><i class="bi bi-check-lg"></i></div>
                            <h6 class="fw-bold mb-1">Order Placed</h6>
                            <p class="text-muted small mb-0">We've received your order. <?= date('M d, h:i A', strtotime($order['created_at'])) ?></p>
                        </div>

                        <!-- Step 2: Preparing -->
                        <div class="tracker-step <?= $step >= 2 ? 'completed' : '' ?> <?= $step == 2 ? 'active' : '' ?>">
                            <div class="step-icon"><i class="bi bi-fire"></i></div>
                            <h6 class="fw-bold mb-1">Vendor is Preparing</h6>
                            <p class="text-muted small mb-0">
                                <?= htmlspecialchars($order['shop_name']) ?> is getting your order ready.
                                <?php if ($order['status'] == 'ready'): ?><br><strong class="text-primary">It's ready for pickup!</strong><?php endif; ?>
                            </p>
                        </div>

                        <!-- Step 3: Out for Delivery -->
                        <div class="tracker-step <?= $step >= 3 ? 'completed' : '' ?> <?= $step == 3 ? 'active' : '' ?>">
                            <div class="step-icon"><i class="bi bi-bicycle"></i></div>
                            <h6 class="fw-bold mb-1">Out for Delivery</h6>
                            <p class="text-muted small mb-0">
                                Your order is on the way to <strong><?= htmlspecialchars($order['hostel_address'] ?? 'your hostel') ?></strong>.
                            </p>
                        </div>

                        <!-- Step 4: Delivered -->
                        <div class="tracker-step <?= $step >= 4 ? 'completed' : '' ?> <?= $step == 4 ? 'active' : '' ?>">
                            <div class="step-icon"><i class="bi bi-house-check"></i></div>
                            <h6 class="fw-bold mb-1">Delivered</h6>
                            <p class="text-muted small mb-0">Enjoy your order! Don't forget to leave a review.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Contact Cards -->
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <h6 class="fw-bold text-muted mb-3"><i class="bi bi-shop"></i> Vendor</h6>
                            <p class="mb-1 fw-bold"><?= htmlspecialchars($order['shop_name']) ?></p>
                            <p class="mb-2 small text-muted"><?= htmlspecialchars($order['vendor_location']) ?></p>
                            <a href="tel:<?= htmlspecialchars($order['vendor_phone']) ?>" class="btn btn-sm btn-outline-secondary w-100">
                                <i class="bi bi-telephone"></i> Call Vendor
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow-sm border-0 h-100 <?= $order['rider_name'] ? 'border-success' : 'opacity-50' ?>">
                        <div class="card-body">
                            <h6 class="fw-bold text-muted mb-3"><i class="bi bi-bicycle"></i> Rider</h6>
                            <?php if ($order['rider_name']): ?>
                                <p class="mb-1 fw-bold"><?= htmlspecialchars($order['rider_name']) ?></p>
                                <p class="mb-2 small text-muted">Assigned to your order</p>
                                <a href="tel:<?= htmlspecialchars($order['rider_phone']) ?>" class="btn btn-sm btn-success w-100">
                                    <i class="bi bi-telephone"></i> Call Rider
                                </a>
                            <?php else: ?>
                                <p class="mb-1 fw-bold">Waiting for Assignment</p>
                                <p class="mb-2 small text-muted">A rider will be assigned once the vendor marks it ready.</p>
                                <button class="btn btn-sm btn-secondary w-100" disabled>
                                    <i class="bi bi-clock"></i> Pending
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Order Summary & Review -->
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-receipt"></i> Order Summary</h6>
                </div>
                <div class="card-body">
                    <!-- Itemized Table (Preserved from your existing code) -->
                    <h6 class="mb-3 text-muted">Items Ordered</h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Price</th>
                                    <th>Qty</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['name']) ?></td>
                                        <td>₦<?= number_format($item['price']) ?></td>
                                        <td><?= $item['quantity'] ?></td>
                                        <td class="text-end"><strong>₦<?= number_format($item['price'] * $item['quantity']) ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <hr>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Items Total</span>
                        <span>₦<?= number_format($order['total_amount'], 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Delivery Fee</span>
                        <span>₦<?= number_format($order['delivery_fee'], 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between pt-2 border-top">
                        <span class="fw-bold fs-5">Total Paid</span>
                        <span class="fw-bold fs-5 text-danger">₦<?= number_format($order['total_amount'] + $order['delivery_fee'], 2) ?></span>
                    </div>
                </div>
            </div>

            <!-- Review Prompt (if completed) -->
            <?php if ($order['status'] == 'completed'): ?>
                <?php 
                $stmt_review = $pdo->prepare("SELECT id FROM reviews WHERE order_id = ?");
                $stmt_review->execute([$order_id]);
                $has_reviewed = $stmt_review->fetch();
                ?>
                <?php if (!$has_reviewed): ?>
                    <div class="card shadow-sm border-warning bg-warning bg-opacity-10">
                        <div class="card-body text-center p-4">
                            <i class="bi bi-star-fill text-warning display-6"></i>
                            <h6 class="mt-2 fw-bold">How was your order?</h6>
                            <p class="small text-muted mb-3">Help other students by reviewing <?= htmlspecialchars($order['shop_name']) ?></p>
                            <a href="submit_review.php?order_id=<?= $order_id ?>" class="btn btn-warning w-100 fw-bold">Leave a Review</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card shadow-sm border-success bg-success bg-opacity-10">
                        <div class="card-body text-center p-4">
                            <i class="bi bi-check-circle-fill text-success display-6"></i>
                            <h6 class="mt-2 fw-bold text-success">Thank you for your review!</h6>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Auto-refresh every 30 seconds to show live status updates -->
<script>
    setTimeout(function(){
        window.location.reload();
    }, 30000);
</script>
</body>
</html>