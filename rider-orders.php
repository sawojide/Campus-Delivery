<?php
session_start();
require 'includes/db.php';
require 'Notification.php';
require 'email.php'; // ✅ Correct path to Email Helper

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'rider') {
    header("Location: rider-login.php");
    exit;
}

$rider_id = $_SESSION['user_id'];
$success = "";

// Handle Claiming an Order
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['claim_order'])) {
    $order_id = intval($_POST['order_id']);
    $stmt = $pdo->prepare("SELECT user_id FROM orders WHERE id = ? AND status = 'ready'");
    $stmt->execute([$order_id]);
    $order_info = $stmt->fetch();
    
    if ($order_info) {
        $update_stmt = $pdo->prepare("UPDATE orders SET status = 'delivering', rider_id = ? WHERE id = ? AND status = 'ready'");
        $update_stmt->execute([$rider_id, $order_id]);
        
        if ($update_stmt->rowCount() > 0) {
            $success = "Order claimed successfully! Head to the pickup location.";
            $notification = new Notification($pdo);
            $notification->create($order_info['user_id'], "Rider Assigned", "A rider is on the way to pick up your order #{$order_id}", 'delivery', "view_order.php?order_id={$order_id}");
        } else {
            $success = "Sorry, this order was just claimed by another rider.";
        }
    } else {
        $success = "Sorry, this order is no longer available.";
    }
}

// Handle Completing an Order
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complete_order'])) {
    $order_id = intval($_POST['order_id']);
    $stmt = $pdo->prepare("UPDATE orders SET status = 'completed' WHERE id = ? AND rider_id = ? AND status = 'delivering'");
    $stmt->execute([$order_id, $rider_id]);
    
    if ($stmt->rowCount() > 0) {
        $success = "Delivery completed! Great job.";
        $stmt_order = $pdo->prepare("SELECT vendor_id, total_amount, user_id FROM orders WHERE id = ?");
        $stmt_order->execute([$order_id]);
        $order = $stmt_order->fetch();
        
        if ($order) {
            // 1. Vendor Payout
            $stmt_update = $pdo->prepare("UPDATE vendors SET wallet_balance = wallet_balance + ? WHERE id = ?");
            $stmt_update->execute([$order['total_amount'], $order['vendor_id']]);
            
            // 2. ✅ Referral Reward Logic + Email
            $stmt_referee = $pdo->prepare("SELECT referred_by FROM users WHERE id = ?");
            $stmt_referee->execute([$order['user_id']]);
            $referee = $stmt_referee->fetch();

            if ($referee && $referee['referred_by']) {
                $stmt_check = $pdo->prepare("SELECT id FROM referral_rewards WHERE referee_id = ?");
                $stmt_check->execute([$order['user_id']]);
                
                if (!$stmt_check->fetch()) {
                    $REFERRAL_BONUS = 500.00;
                    try {
                        $stmt_credit = $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ?");
                        $stmt_credit->execute([$REFERRAL_BONUS, $referee['referred_by']]);
                        
                        $stmt_record = $pdo->prepare("INSERT INTO referral_rewards (referrer_id, referee_id, reward_amount, order_id) VALUES (?, ?, ?, ?)");
                        $stmt_record->execute([$referee['referred_by'], $order['user_id'], $REFERRAL_BONUS, $order_id]);
                        
                        // ✅ EMAIL: Notify Referrer (with inline CSS for email client compatibility)
                        $stmt_referrer = $pdo->prepare("SELECT email, full_name FROM users WHERE id = ?");
                        $stmt_referrer->execute([$referee['referred_by']]);
                        $referrer = $stmt_referrer->fetch();
                        
                        if ($referrer) {
                            $site_url = defined('SITE_URL') ? SITE_URL : 'http://localhost/campus_delivery';
                            $emailHtml = "
                                <h2>You Earned a Referral Bonus! 🎉</h2>
                                <p>Hi " . htmlspecialchars($referrer['full_name']) . ",</p>
                                <p>Your friend just completed their first order! As a thank you, we've added <strong>₦" . number_format($REFERRAL_BONUS) . "</strong> to your Campus Delivery wallet.</p>
                                <a href='{$site_url}/dashboard.php' style='display:inline-block; padding:12px 24px; background-color:#dc3545; color:#ffffff; text-decoration:none; border-radius:5px; font-weight:bold; margin-top:15px;'>View My Wallet</a>
                            ";
                            sendCampusEmail($referrer['email'], $referrer['full_name'], "🎉 You Earned a Referral Bonus!", $emailHtml);
                        }
                        
                        // In-app notification
                        $notification = new Notification($pdo);
                        $notification->create($referee['referred_by'], "Referral Reward Earned! 🎉", "Your friend just completed their first order! We've added ₦" . number_format($REFERRAL_BONUS) . " to your wallet.", 'system', "dashboard.php");
                    } catch (Exception $e) {
                        error_log("Referral reward failed: " . $e->getMessage());
                    }
                }
            }
            
            // 3. Notify Customer
            $notification = new Notification($pdo);
            $notification->create($order['user_id'], "Order Delivered!", "Your order #{$order_id} has been successfully delivered. Enjoy!", 'delivery', "view_order.php?order_id={$order_id}");
        }
    }
}

// Get Available & Active Orders
$stmt = $pdo->query("SELECT o.*, u.full_name as customer_name, u.phone as customer_phone, u.hostel_address, v.shop_name FROM orders o JOIN users u ON o.user_id = u.id JOIN vendors v ON o.vendor_id = v.id WHERE o.status = 'ready' ORDER BY o.created_at ASC");
$available_orders = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT o.*, u.full_name as customer_name, u.phone as customer_phone, u.hostel_address, v.shop_name FROM orders o JOIN users u ON o.user_id = u.id JOIN vendors v ON o.vendor_id = v.id WHERE o.status = 'delivering' AND o.rider_id = ? ORDER BY o.created_at DESC");
$stmt->execute([$rider_id]);
$active_orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deliveries - Rider Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .sidebar { min-height: 100vh; background: linear-gradient(180deg, #0d8568 0%, #0a6b54 100%); box-shadow: 4px 0 10px rgba(0,0,0,0.1); }
        .sidebar a { color: white; text-decoration: none; padding: 15px 25px; display: block; border-left: 4px solid transparent; transition: all 0.3s ease; font-weight: 500; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.15); border-left-color: #fff; padding-left: 30px; }
        .sidebar h4 { font-weight: 700; font-size: 1.4rem; border-bottom: 2px solid rgba(255,255,255,0.2); padding-bottom: 20px; }
        .sidebar hr { border-color: rgba(255,255,255,0.3); margin: 20px 0; }
        .sidebar .logout-link { color: #fff !important; background: rgba(220, 53, 69, 0.9); border-radius: 8px; margin: 0 15px; padding: 12px 20px !important; font-weight: 600; border: 2px solid rgba(255,255,255,0.3); transition: all 0.3s ease; }
        .sidebar .logout-link:hover { background: #dc3545; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4); }
        .sidebar .logout-link i { margin-right: 8px; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 sidebar p-0">
            <h4 class="text-white p-4 mb-0"><i class="bi bi-bicycle"></i> Rider Panel</h4>
            <div class="mt-4">
                <a href="rider-dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a href="rider-orders.php" class="active"><i class="bi bi-geo-alt"></i> Deliveries</a>
                <hr class="text-white my-3">
                <a href="rider-logout.php" class="logout-link"><i class="bi bi-box-arrow-left"></i> Logout</a>
            </div>
        </div>
        <div class="col-md-10 p-4 bg-light">
            <h2 class="mb-4"><i class="bi bi-geo-alt text-success"></i> Delivery Management</h2>
            <?php if ($success): ?><div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
            
            <div class="card mb-4 border-success">
                <div class="card-header bg-success text-white"><h5 class="mb-0"><i class="bi bi-bicycle"></i> My Active Deliveries (<?= count($active_orders) ?>)</h5></div>
                <div class="card-body">
                    <?php if (empty($active_orders)): ?><p class="text-muted text-center py-3">You have no active deliveries right now.</p>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($active_orders as $order): ?>
                                <div class="col-md-6">
                                    <div class="card h-100 shadow-sm">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between mb-2"><h6 class="fw-bold">Order #<?= $order['id'] ?></h6><span class="badge bg-primary">Delivering</span></div>
                                            <p class="mb-1"><strong>Pickup:</strong> <?= htmlspecialchars($order['shop_name']) ?></p>
                                            <p class="mb-1"><strong>Dropoff:</strong> <?= htmlspecialchars($order['hostel_address'] ?: 'Campus Hostel') ?></p>
                                            <p class="mb-1"><strong>Customer:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
                                            <p class="mb-3"><i class="bi bi-telephone"></i> <?= htmlspecialchars($order['customer_phone']) ?></p>
                                            <form method="POST"><input type="hidden" name="order_id" value="<?= $order['id'] ?>"><button type="submit" name="complete_order" class="btn btn-success w-100" onclick="return confirm('Have you delivered this order?')"><i class="bi bi-check-circle"></i> Mark as Delivered</button></form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-warning text-dark"><h5 class="mb-0"><i class="bi bi-bag-plus"></i> Available Orders to Claim (<?= count($available_orders) ?>)</h5></div>
                <div class="card-body">
                    <?php if (empty($available_orders)): ?><p class="text-muted text-center py-3">No orders are ready for pickup right now.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light"><tr><th>Order #</th><th>Vendor</th><th>Customer</th><th>Address</th><th>Total</th><th>Action</th></tr></thead>
                                <tbody>
                                    <?php foreach ($available_orders as $order): ?>
                                        <tr>
                                            <td><strong>#<?= $order['id'] ?></strong></td>
                                            <td><?= htmlspecialchars($order['shop_name']) ?></td>
                                            <td><?= htmlspecialchars($order['customer_name']) ?><br><small class="text-muted"><?= htmlspecialchars($order['customer_phone']) ?></small></td>
                                            <td><?= htmlspecialchars($order['hostel_address'] ?: 'Campus Hostel') ?></td>
                                            <td><strong>₦<?= number_format($order['total_amount'] + $order['delivery_fee']) ?></strong></td>
                                            <td><form method="POST"><input type="hidden" name="order_id" value="<?= $order['id'] ?>"><button type="submit" name="claim_order" class="btn btn-sm btn-warning" onclick="return confirm('Claim this order?')"><i class="bi bi-plus-circle"></i> Claim</button></form></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>