<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: admin-login.php");
    exit;
}

// --- 1. Comprehensive Statistics (Existing + Enhanced) ---
$stats = [];
$stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
$stats['total_vendors'] = $pdo->query("SELECT COUNT(*) FROM vendors")->fetchColumn();
$stats['total_riders'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role='rider'")->fetchColumn();
$stats['total_products'] = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

$stats['total_orders'] = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$stats['pending_orders'] = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
$stats['preparing_orders'] = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='preparing'")->fetchColumn();
$stats['delivering_orders'] = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='delivering'")->fetchColumn();
$stats['completed_orders'] = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='completed'")->fetchColumn();

$stats['total_revenue'] = $pdo->query("SELECT SUM(total_amount + delivery_fee) FROM orders WHERE status != 'cancelled'")->fetchColumn() ?: 0;
$stats['today_revenue'] = $pdo->query("SELECT SUM(total_amount + delivery_fee) FROM orders WHERE DATE(created_at) = CURDATE() AND status != 'cancelled'")->fetchColumn() ?: 0;
$stats['today_orders'] = $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();

// ✅ Referral Stats Added
$stats['total_referrals'] = $pdo->query("SELECT COUNT(*) FROM referral_rewards")->fetchColumn();
$stats['total_referral_payouts'] = $pdo->query("SELECT SUM(reward_amount) FROM referral_rewards")->fetchColumn() ?: 0;

// --- 2. Analytics: Last 7 Days Revenue ---
$revenue_labels = [];
$revenue_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $revenue_labels[] = date('M d', strtotime($date));
    
    $stmt = $pdo->prepare("SELECT SUM(total_amount + delivery_fee) FROM orders WHERE DATE(created_at) = ? AND status != 'cancelled'");
    $stmt->execute([$date]);
    $revenue_data[] = (float)($stmt->fetchColumn() ?: 0);
}

// --- 3. Analytics: Order Status Distribution ---
$status_counts = [
    'Pending' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn(),
    'Preparing' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('preparing', 'ready')")->fetchColumn(),
    'Delivering' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status='delivering'")->fetchColumn(),
    'Completed' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status='completed'")->fetchColumn(),
    'Cancelled' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status='cancelled'")->fetchColumn()
];

// --- 4. Analytics: Top 5 Best-Selling Products ---
$top_products_stmt = $pdo->query("
    SELECT p.name, SUM(oi.quantity) as total_sold 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    JOIN orders o ON oi.order_id = o.id 
    WHERE o.status != 'cancelled'
    GROUP BY p.id 
    ORDER BY total_sold DESC 
    LIMIT 5
");
$top_products = $top_products_stmt->fetchAll();
$product_names = array_column($top_products, 'name');
$product_sales = array_column($top_products, 'total_sold');

// --- 5. Recent Orders & Top Vendors (Existing) ---
$recent_orders = $pdo->query("
    SELECT o.*, u.full_name, v.shop_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    JOIN vendors v ON o.vendor_id = v.id 
    ORDER BY o.created_at DESC 
    LIMIT 10
")->fetchAll();

$top_vendors = $pdo->query("
    SELECT v.shop_name, COUNT(o.id) as order_count, SUM(o.total_amount) as revenue
    FROM vendors v 
    LEFT JOIN orders o ON v.id = o.vendor_id AND o.status != 'cancelled'
    GROUP BY v.id 
    ORDER BY order_count DESC 
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Campus Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .sidebar { min-height: 100vh; background: linear-gradient(180deg, #dc3545 0%, #c82333 100%); }
        .sidebar a { color: white; text-decoration: none; padding: 12px 20px; display: block; border-left: 4px solid transparent; transition: all 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.1); border-left-color: white; }
        
        .stat-card { transition: transform 0.3s; border-left: 5px solid; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card.red { border-left-color: #dc3545; }
        .stat-card.blue { border-left-color: #0d6efd; }
        .stat-card.green { border-left-color: #198754; }
        .stat-card.yellow { border-left-color: #ffc107; }
        .stat-card.purple { border-left-color: #6f42c1; }
        .stat-card.teal { border-left-color: #20c997; }
        
        .chart-container { position: relative; height: 300px; width: 100%; }
        
        /* Logout Button - Make it stand out */
        .sidebar .logout-link {
            color: #fff !important;
            background: rgba(0,0,0,0.2);
            border: 2px solid rgba(255,255,255,0.5);
            border-radius: 6px;
            margin: 15px 10px;
            padding: 12px 20px !important;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s ease;
        }
        .sidebar .logout-link:hover {
            background: rgba(0,0,0,0.3);
            border-color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }
        .sidebar .logout-link i { margin-right: 8px; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'admin-sidebar.php'; ?>
        
        <div class="col-md-10 p-4 bg-light">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-speedometer2 text-danger"></i> Analytics Dashboard</h2>
                <div>
                    <span class="text-muted">Welcome, </span>
                    <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong>
                </div>
            </div>
            
            <!-- Row 1: Core Statistics Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-4 col-lg-2">
                    <div class="card stat-card red shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted small mb-2">Total Customers</h6>
                            <h3 class="mb-0"><?= number_format($stats['total_users']) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-lg-2">
                    <div class="card stat-card blue shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted small mb-2">Total Vendors</h6>
                            <h3 class="mb-0"><?= number_format($stats['total_vendors']) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-lg-2">
                    <div class="card stat-card green shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted small mb-2">Total Products</h6>
                            <h3 class="mb-0"><?= number_format($stats['total_products']) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-lg-2">
                    <div class="card stat-card yellow shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted small mb-2">Total Orders</h6>
                            <h3 class="mb-0"><?= number_format($stats['total_orders']) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-lg-2">
                    <div class="card stat-card purple shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted small mb-2">Pending Orders</h6>
                            <h3 class="mb-0"><?= number_format($stats['pending_orders']) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-lg-2">
                    <div class="card stat-card teal shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted small mb-2">Total Revenue</h6>
                            <h3 class="mb-0">₦<?= number_format($stats['total_revenue']) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Row 2: Today's Performance & Referral Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Today's Orders</h6>
                                    <h3 class="mb-0"><?= number_format($stats['today_orders']) ?></h3>
                                </div>
                                <i class="bi bi-cart-check display-4 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Today's Revenue</h6>
                                    <h3 class="mb-0">₦<?= number_format($stats['today_revenue']) ?></h3>
                                </div>
                                <i class="bi bi-cash-stack display-4 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Active Riders</h6>
                                    <h3 class="mb-0"><?= number_format($stats['total_riders']) ?></h3>
                                </div>
                                <i class="bi bi-bicycle display-4 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- ✅ NEW: Referral Payouts Card -->
                <div class="col-md-3">
                    <div class="card stat-card purple shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted small mb-2">Referral Payouts</h6>
                            <h3 class="mb-0 text-purple" style="color: #6f42c1 !important;">₦<?= number_format($stats['total_referral_payouts']) ?></h3>
                            <small class="text-muted"><?= number_format($stats['total_referrals']) ?> successful invites</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Row 3: Analytics Charts -->
            <div class="row g-4 mb-4">
                <div class="col-lg-8">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white py-3">
                            <h6 class="mb-0 fw-bold"><i class="bi bi-graph-up text-primary"></i> 7-Day Revenue Trend</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="revenueChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white py-3">
                            <h6 class="mb-0 fw-bold"><i class="bi bi-pie-chart text-warning"></i> Order Status</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="statusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 4: Top Products & Top Vendors -->
            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white py-3">
                            <h6 class="mb-0 fw-bold"><i class="bi bi-bar-chart text-success"></i> Top 5 Best-Selling Products</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="productsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white py-3">
                            <h6 class="mb-0 fw-bold"><i class="bi bi-trophy text-warning"></i> Top Vendors</h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($top_vendors)): ?>
                                <p class="text-muted text-center py-4">No vendor data yet</p>
                            <?php else: ?>
                                <?php foreach ($top_vendors as $index => $vendor): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 <?= $index < count($top_vendors) - 1 ? 'border-bottom' : '' ?>">
                                        <div>
                                            <span class="badge bg-<?= $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : 'secondary') ?> me-2">#<?= $index + 1 ?></span>
                                            <strong><?= htmlspecialchars($vendor['shop_name']) ?></strong>
                                        </div>
                                        <div class="text-end">
                                            <div class="small text-muted"><?= $vendor['order_count'] ?> orders</div>
                                            <strong class="text-success">₦<?= number_format($vendor['revenue']) ?></strong>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Row 5: Recent Orders Table -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-clock-history text-danger"></i> Recent Orders</h5>
                            <a href="admin-orders.php" class="btn btn-sm btn-outline-danger">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($recent_orders)): ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox display-4"></i>
                                    <p class="mt-3">No orders yet. When students start ordering, they'll appear here!</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0 align-middle">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="ps-4">Order #</th>
                                                <th>Customer</th>
                                                <th>Vendor</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_orders as $order): ?>
                                                <tr>
                                                    <td class="ps-4 fw-bold">#<?= $order['id'] ?></td>
                                                    <td><?= htmlspecialchars($order['full_name']) ?></td>
                                                    <td><?= htmlspecialchars($order['shop_name']) ?></td>
                                                    <td><strong>₦<?= number_format($order['total_amount'] + $order['delivery_fee']) ?></strong></td>
                                                    <td>
                                                        <?php
                                                        $status_badges = [
                                                            'pending' => 'warning',
                                                            'preparing' => 'info',
                                                            'ready' => 'primary',
                                                            'delivering' => 'primary',
                                                            'completed' => 'success',
                                                            'cancelled' => 'danger'
                                                        ];
                                                        $badge = $status_badges[$order['status']] ?? 'secondary';
                                                        ?>
                                                        <span class="badge bg-<?= $badge ?>">
                                                            <?= strtoupper($order['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= date('M d, h:i A', strtotime($order['created_at'])) ?></td>
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
            
            <!-- Row 6: Quick Actions -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0"><i class="bi bi-lightning-charge text-warning"></i> Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <a href="admin-vendors.php" class="btn btn-outline-danger w-100">
                                        <i class="bi bi-plus-circle"></i> Add Vendor
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="admin-products.php" class="btn btn-outline-primary w-100">
                                        <i class="bi bi-plus-circle"></i> Add Product
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="admin-users.php" class="btn btn-outline-success w-100">
                                        <i class="bi bi-people"></i> Manage Customers
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="admin-wallets.php" class="btn btn-outline-info w-100">
                                        <i class="bi bi-wallet2"></i> Manage Wallets
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // 1. Revenue Line Chart
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode($revenue_labels) ?>,
            datasets: [{
                label: 'Revenue (₦)',
                data: <?= json_encode($revenue_data) ?>,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    // 2. Status Doughnut Chart
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_keys($status_counts)) ?>,
            datasets: [{
                data: <?= json_encode(array_values($status_counts)) ?>,
                backgroundColor: ['#ffc107', '#17a2b8', '#0d6efd', '#198754', '#dc3545']
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });

    // 3. Top Products Bar Chart
    new Chart(document.getElementById('productsChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($product_names) ?>,
            datasets: [{
                label: 'Units Sold',
                data: <?= json_encode($product_sales) ?>,
                backgroundColor: '#198754',
                borderRadius: 5
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false, 
            indexAxis: 'y', // Horizontal bar chart
            plugins: { legend: { display: false } }
        }
    });
</script>
</body>
</html>