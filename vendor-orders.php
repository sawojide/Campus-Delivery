<?php
session_start();
require 'includes/db.php';
require 'Notification.php'; // ✅ ADDED: Include the Notification class

if (!isset($_SESSION['vendor_id']) || $_SESSION['vendor_role'] != 'vendor') {
    header("Location: vendor-login.php");
    exit;
}

$vendor_id = $_SESSION['vendor_id'];
$success = "";

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['status'];
    
    // Verify order belongs to this vendor and get user_id for notification
    $stmt = $pdo->prepare("SELECT vendor_id, status, user_id FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    
    // Vendor can only update if it's not already with a rider or completed
    if ($order && $order['vendor_id'] == $vendor_id && !in_array($order['status'], ['delivering', 'completed', 'cancelled'])) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $order_id]);
        $success = "Order status updated to " . ucfirst($new_status);
        
        // ✅ ADDED: Create in-app notification for the student
        $notification = new Notification($pdo);
        $notification->create(
            $order['user_id'],
            "Order Status Updated",
            "Your order #{$order_id} is now " . ucfirst($new_status),
            'order',
            "view_order.php?order_id={$order_id}"
        );
    } else {
        $success = "Cannot update this order. It may already be with a rider or completed.";
    }
}

// Get filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query
$sql = "SELECT o.*, u.full_name, u.phone 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.vendor_id = ?";
$params = [$vendor_id];

if ($status_filter) {
    $sql .= " AND o.status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Status counts (Added 'ready')
$status_counts = [];
foreach (['pending', 'preparing', 'ready', 'delivering', 'completed'] as $status) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE vendor_id = ? AND status = ?");
    $stmt->execute([$vendor_id, $status]);
    $status_counts[$status] = $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Vendor Panel</title>
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
        <div class="col-md-2 sidebar p-0">
            <h4 class="text-white p-4 mb-0"><i class="bi bi-shop-window"></i> Vendor Panel</h4>
            <div class="mt-4">
                <a href="vendor-dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a href="vendor-orders.php" class="active"><i class="bi bi-cart3"></i> Orders</a>
                <a href="vendor-products.php"><i class="bi bi-box-seam"></i> Products</a>
                <a href="vendor-profile.php"><i class="bi bi-person"></i> Profile</a>
                <hr class="text-white my-3">
                <a href="vendor-logout.php" class="logout-link"><i class="bi bi-box-arrow-left"></i> Logout</a>
            </div>
        </div>
        
        <div class="col-md-10 p-4 bg-light">
            <h2 class="mb-4"><i class="bi bi-cart3 text-primary"></i> Order Management</h2>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            
            <!-- Status Filter Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <a href="vendor-orders.php" class="text-decoration-none">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h3><?= array_sum($status_counts) ?></h3>
                                <p class="mb-0">All Orders</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="vendor-orders.php?status=pending" class="text-decoration-none">
                        <div class="card bg-warning bg-opacity-25">
                            <div class="card-body text-center">
                                <h3><?= $status_counts['pending'] ?></h3>
                                <p class="mb-0">Pending</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="vendor-orders.php?status=preparing" class="text-decoration-none">
                        <div class="card bg-info bg-opacity-25">
                            <div class="card-body text-center">
                                <h3><?= $status_counts['preparing'] ?></h3>
                                <p class="mb-0">Preparing</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="vendor-orders.php?status=ready" class="text-decoration-none">
                        <div class="card bg-primary bg-opacity-25 text-white">
                            <div class="card-body text-center">
                                <h3><?= $status_counts['ready'] ?></h3>
                                <p class="mb-0">Ready for Rider</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
            
            <!-- Orders Table -->
            <div class="card">
                <div class="card-body">
                    <?php if (empty($orders)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox display-4"></i>
                            <p class="mt-3">No orders found.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Order #</th>
                                        <th>Customer</th>
                                        <th>Phone</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Update Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td><strong>#<?= $order['id'] ?></strong></td>
                                            <td><?= htmlspecialchars($order['full_name']) ?></td>
                                            <td><?= htmlspecialchars($order['phone']) ?></td>
                                            <td><strong>₦<?= number_format($order['total_amount']) ?></strong></td>
                                            <td>
                                                <?php
                                                $badge_colors = [
                                                    'pending' => 'warning',
                                                    'preparing' => 'info',
                                                    'ready' => 'primary',
                                                    'delivering' => 'success',
                                                    'completed' => 'dark',
                                                    'cancelled' => 'danger'
                                                ];
                                                $color = $badge_colors[$order['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?= $color ?>">
                                                    <?= strtoupper($order['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= date('M d, h:i A', strtotime($order['created_at'])) ?></td>
                                            <td>
                                                <?php if (in_array($order['status'], ['delivering', 'completed', 'cancelled'])): ?>
                                                    <span class="text-muted small"><i class="bi bi-lock"></i> With Rider/Done</span>
                                                <?php else: ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                            <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                                            <option value="preparing" <?= $order['status'] == 'preparing' ? 'selected' : '' ?>>Preparing</option>
                                                            <option value="ready" <?= $order['status'] == 'ready' ? 'selected' : '' ?>>Ready for Pickup</option>
                                                        </select>
                                                        <input type="hidden" name="update_status" value="1">
                                                    </form>
                                                <?php endif; ?>
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