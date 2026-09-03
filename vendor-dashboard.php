<?php
session_start();
require 'includes/db.php';

// Check vendor access
if (!isset($_SESSION['vendor_id']) || $_SESSION['vendor_role'] != 'vendor') {
    header("Location: vendor-login.php");
    exit;
}

$vendor_id = $_SESSION['vendor_id'];

// Get statistics
$stats = [];
$stats['total_orders'] = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE vendor_id = ?");
$stats['total_orders']->execute([$vendor_id]);
$stats['total_orders'] = $stats['total_orders']->fetchColumn();

$stats['pending_orders'] = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE vendor_id = ? AND status = 'pending'");
$stats['pending_orders']->execute([$vendor_id]);
$stats['pending_orders'] = $stats['pending_orders']->fetchColumn();

$stats['preparing_orders'] = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE vendor_id = ? AND status = 'preparing'");
$stats['preparing_orders']->execute([$vendor_id]);
$stats['preparing_orders'] = $stats['preparing_orders']->fetchColumn();

$stats['total_revenue'] = $pdo->prepare("SELECT SUM(total_amount) FROM orders WHERE vendor_id = ? AND status != 'cancelled'");
$stats['total_revenue']->execute([$vendor_id]);
$stats['total_revenue'] = $stats['total_revenue']->fetchColumn() ?: 0;

$stats['total_products'] = $pdo->prepare("SELECT COUNT(*) FROM products WHERE vendor_id = ?");
$stats['total_products']->execute([$vendor_id]);
$stats['total_products'] = $stats['total_products']->fetchColumn();

// Get recent orders
$stmt = $pdo->prepare("
    SELECT o.*, u.full_name, u.phone
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.vendor_id = ?
    ORDER BY o.created_at DESC 
    LIMIT 10
");
$stmt->execute([$vendor_id]);
$recent_orders = $stmt->fetchAll();

// Get vendor info
$stmt = $pdo->prepare("SELECT * FROM vendors WHERE id = ?");
$stmt->execute([$vendor_id]);
$vendor = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Dashboard - <?= htmlspecialchars($vendor['shop_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
    .sidebar { 
        min-height: 100vh; 
        background: linear-gradient(180deg, #4e54c8 0%, #3b40a8 100%);
        box-shadow: 4px 0 10px rgba(0,0,0,0.1);
    }
    .sidebar a { 
        color: white; 
        text-decoration: none; 
        padding: 15px 25px; 
        display: block; 
        border-left: 4px solid transparent;
        transition: all 0.3s ease;
        font-weight: 500;
    }
    .sidebar a:hover, .sidebar a.active { 
        background: rgba(255,255,255,0.15); 
        border-left-color: #fff;
        padding-left: 30px;
    }
    .sidebar h4 {
        font-weight: 700;
        font-size: 1.4rem;
        border-bottom: 2px solid rgba(255,255,255,0.2);
        padding-bottom: 20px;
    }
    .sidebar hr {
        border-color: rgba(255,255,255,0.3);
        margin: 20px 0;
    }
    .sidebar .logout-link {
        color: #fff !important;
        background: rgba(255, 193, 7, 0.9);
        border-radius: 8px;
        margin: 0 15px;
        padding: 12px 20px !important;
        font-weight: 600;
        border: 2px solid rgba(255,255,255,0.3);
        transition: all 0.3s ease;
    }
    .sidebar .logout-link:hover {
        background: #ffc107;
        color: #000 !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(255, 193, 7, 0.4);
    }
    .sidebar .logout-link i {
        margin-right: 8px;
    }
</style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 sidebar p-0">
            <h4 class="text-white p-4 mb-0">
                <i class="bi bi-shop-window"></i> <?= htmlspecialchars($vendor['shop_name']) ?>
            </h4>
            <div class="mt-4">
                <a href="vendor-dashboard.php" class="active">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a href="vendor-orders.php">
                    <i class="bi bi-cart3"></i> Orders
                </a>
                <a href="vendor-products.php">
                    <i class="bi bi-box-seam"></i> Products
                </a>
                <a href="vendor-profile.php">
                    <i class="bi bi-person"></i> Profile
                </a>
                <hr class="text-white my-3">
                <a href="index.php">
                    <i class="bi bi-house"></i> Public Site
                </a>
                <a href="vendor-logout.php" style="color: #ffc107;">
                    <i class="bi bi-box-arrow-left"></i> Logout
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-10 p-4 bg-light">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-speedometer2 text-primary"></i> Dashboard Overview</h2>
                <div>
                    <span class="text-muted">Welcome, </span>
                    <strong><?= htmlspecialchars($vendor['shop_name']) ?></strong>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm" style="border-left-color: #667eea;">
                        <div class="card-body">
                            <h6 class="text-muted small mb-2">Total Orders</h6>
                            <h3 class="mb-0"><?= number_format($stats['total_orders']) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm" style="border-left-color: #ffc107;">
                        <div class="card-body">
                            <h6 class="text-muted small mb-2">Pending Orders</h6>
                            <h3 class="mb-0"><?= number_format($stats['pending_orders']) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm" style="border-left-color: #17a2b8;">
                        <div class="card-body">
                            <h6 class="text-muted small mb-2">Preparing</h6>
                            <h3 class="mb-0"><?= number_format($stats['preparing_orders']) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm" style="border-left-color: #28a745;">
                        <div class="card-body">
                            <h6 class="text-muted small mb-2">Total Revenue</h6>
                            <h3 class="mb-0">₦<?= number_format($stats['total_revenue']) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-clock-history text-primary"></i> Recent Orders</h5>
                        <a href="vendor-orders.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recent_orders)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox display-4"></i>
                            <p class="mt-3">No orders yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">Order #</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th class="text-end pe-4">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold">#<?= $order['id'] ?></td>
                                            <td>
                                                <?= htmlspecialchars($order['full_name']) ?><br>
                                                <small class="text-muted"><?= htmlspecialchars($order['phone']) ?></small>
                                            </td>
                                            <td><strong>₦<?= number_format($order['total_amount']) ?></strong></td>
                                            <td>
                                                <?php
                                                $badges = [
                                                    'pending' => 'warning',
                                                    'preparing' => 'info',
                                                    'delivering' => 'primary',
                                                    'completed' => 'success',
                                                    'cancelled' => 'danger'
                                                ];
                                                $badge = $badges[$order['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?= $badge ?>"><?= strtoupper($order['status']) ?></span>
                                            </td>
                                            <td><?= date('M d, h:i A', strtotime($order['created_at'])) ?></td>
                                            <td class="text-end pe-4">
                                                <a href="vendor-view-order.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i> View
                                                </a>
                                            </td>
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