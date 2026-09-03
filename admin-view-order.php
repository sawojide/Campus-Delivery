<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: admin-login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: admin-orders.php");
    exit;
}

$order_id = intval($_GET['id']);

// Get order details
$stmt = $pdo->prepare("
    SELECT o.*, u.full_name, u.email, u.phone, v.shop_name, v.location as vendor_location
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    JOIN vendors v ON o.vendor_id = v.id 
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: admin-orders.php");
    exit;
}

// Get order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.name 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $order_id]);
    $order['status'] = $new_status;
    $success = "Order status updated!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?= $order_id ?> - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .sidebar { min-height: 100vh; background: linear-gradient(180deg, #dc3545 0%, #c82333 100%); }
        .sidebar a { color: white; text-decoration: none; padding: 12px 20px; display: block; border-left: 4px solid transparent; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.1); border-left-color: white; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'admin-sidebar.php'; ?>
        
        <div class="col-md-10 p-4 bg-light">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-receipt text-danger"></i> Order #<?= $order_id ?></h2>
                <a href="admin-orders.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to Orders</a>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-8">
                    <!-- Order Items -->
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Order Items</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Price</th>
                                            <th>Qty</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($items as $item): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($item['name']) ?></td>
                                                <td>₦<?= number_format($item['price']) ?></td>
                                                <td><?= $item['quantity'] ?></td>
                                                <td><strong>₦<?= number_format($item['price'] * $item['quantity']) ?></strong></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                            <td>₦<?= number_format($order['total_amount']) ?></td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Delivery:</strong></td>
                                            <td>₦<?= number_format($order['delivery_fee']) ?></td>
                                        </tr>
                                        <tr class="table-danger">
                                            <td colspan="3" class="text-end"><strong>TOTAL:</strong></td>
                                            <td><strong>₦<?= number_format($order['total_amount'] + $order['delivery_fee']) ?></strong></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <!-- Customer Info -->
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Customer Details</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Name:</strong><br><?= htmlspecialchars($order['full_name']) ?></p>
                            <p><strong>Email:</strong><br><?= htmlspecialchars($order['email']) ?></p>
                            <p><strong>Phone:</strong><br><?= htmlspecialchars($order['phone']) ?></p>
                        </div>
                    </div>
                    
                    <!-- Vendor Info -->
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Vendor Details</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Shop:</strong><br><?= htmlspecialchars($order['shop_name']) ?></p>
                            <p><strong>Location:</strong><br><?= htmlspecialchars($order['vendor_location']) ?></p>
                        </div>
                    </div>
                    
                    <!-- Order Status -->
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Order Status</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Update Status</label>
                                    <select name="status" class="form-select" onchange="this.form.submit()">
                                        <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="preparing" <?= $order['status'] == 'preparing' ? 'selected' : '' ?>>Preparing</option>
                                        <option value="delivering" <?= $order['status'] == 'delivering' ? 'selected' : '' ?>>Delivering</option>
                                        <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                                        <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                </div>
                                <input type="hidden" name="update_status" value="1">
                            </form>
                            
                            <hr>
                            <p><strong>Date:</strong><br><?= date('M d, Y h:i A', strtotime($order['created_at'])) ?></p>
                            <p><strong>Status:</strong><br><span class="badge bg-<?= $order['status'] == 'completed' ? 'success' : ($order['status'] == 'pending' ? 'warning' : 'info') ?>"><?= strtoupper($order['status']) ?></span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>