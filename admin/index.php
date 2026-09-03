<?php
session_start();
require '../includes/db.php';

// Simple admin authentication (In production, use proper login)
// For now, we'll just check if user_id = 1 (first registered user is admin)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    // Make the first user an admin automatically for testing
    $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = 1");
    $stmt->execute();
    
    if ($_SESSION['user_id'] != 1) {
        die("Access Denied. Admin access required.");
    }
}

// Get statistics
$stats = [];
$stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$stats['total_vendors'] = $pdo->query("SELECT COUNT(*) FROM vendors")->fetchColumn();
$stats['total_products'] = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$stats['total_orders'] = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$stats['pending_orders'] = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$stats['total_revenue'] = $pdo->query("SELECT SUM(total_amount + delivery_fee) FROM orders WHERE status != 'cancelled'")->fetchColumn() ?: 0;

// Get recent orders
$recent_orders = $pdo->query("
    SELECT o.*, u.full_name, v.shop_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    JOIN vendors v ON o.vendor_id = v.id 
    ORDER BY o.created_at DESC 
    LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Campus Delivery</title>
    <!-- Bootstrap 5 CSS (For beautiful, responsive layout) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons (For shopping carts, users, etc.) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .sidebar { min-height: 100vh; background: #343a40; }
        .sidebar a { color: white; text-decoration: none; padding: 10px 20px; display: block; }
        .sidebar a:hover { background: #495057; }
        .sidebar a.active { background: #dc3545; }
        .stat-card { border-left: 4px solid #dc3545; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 sidebar p-0">
            <h4 class="text-white p-3 bg-danger">Admin Panel</h4>
            <a href="index.php" class="active"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="orders.php"><i class="bi bi-cart3"></i> Orders</a>
            <a href="vendors.php"><i class="bi bi-shop"></i> Vendors</a>
            <a href="products.php"><i class="bi bi-box-seam"></i> Products</a>
            <a href="users.php"><i class="bi bi-people"></i> Users</a>
            <a href="../dashboard.php"><i class="bi bi-arrow-left"></i> Back to Site</a>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-10 p-4">
            <h2 class="mb-4"><i class="bi bi-speedometer2"></i> Dashboard Overview</h2>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted">Total Students</h6>
                            <h3><?= number_format($stats['total_users']) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm" style="border-left-color: #0d6efd;">
                        <div class="card-body">
                            <h6 class="text-muted">Total Vendors</h6>
                            <h3><?= number_format($stats['total_vendors']) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm" style="border-left-color: #198754;">
                        <div class="card-body">
                            <h6 class="text-muted">Total Products</h6>
                            <h3><?= number_format($stats['total_products']) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm" style="border-left-color: #ffc107;">
                        <div class="card-body">
                            <h6 class="text-muted">Total Orders</h6>
                            <h3><?= number_format($stats['total_orders']) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm" style="border-left-color: #6f42c1;">
                        <div class="card-body">
                            <h6 class="text-muted">Pending Orders</h6>
                            <h3><?= number_format($stats['pending_orders']) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm" style="border-left-color: #20c997;">
                        <div class="card-body">
                            <h6 class="text-muted">Total Revenue</h6>
                            <h3>₦<?= number_format($stats['total_revenue']) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Orders</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Vendor</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td>#<?= $order['id'] ?></td>
                                        <td><?= htmlspecialchars($order['full_name']) ?></td>
                                        <td><?= htmlspecialchars($order['shop_name']) ?></td>
                                        <td>₦<?= number_format($order['total_amount'] + $order['delivery_fee']) ?></td>
                                        <td><span class="badge bg-<?= $order['status'] == 'completed' ? 'success' : ($order['status'] == 'pending' ? 'warning' : 'info') ?>"><?= ucfirst($order['status']) ?></span></td>
                                        <td><?= date('M d, Y h:i A', strtotime($order['created_at'])) ?></td>
                                        <td>
                                            <a href="order_details.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>