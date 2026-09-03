<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: admin-login.php");
    exit;
}

$success = "";
$error = "";

// Handle Bulk Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_action'])) {
    $action = $_POST['bulk_action'];
    $order_ids = $_POST['order_ids'] ?? [];
    
    if (!empty($order_ids)) {
        try {
            $placeholders = implode(',', array_fill(0, count($order_ids), '?'));
            
            if ($action == 'update_status') {
                $new_status = $_POST['bulk_status'];
                $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id IN ($placeholders)");
                $stmt->execute(array_merge([$new_status], $order_ids));
                $success = count($order_ids) . " orders updated to " . ucfirst($new_status);
            } elseif ($action == 'delete') {
                $stmt = $pdo->prepare("DELETE FROM orders WHERE id IN ($placeholders)");
                $stmt->execute($order_ids);
                $success = count($order_ids) . " orders deleted successfully";
            }
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Handle single status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$new_status, $order_id]);
    
    // Log status change
    $stmt = $pdo->prepare("INSERT INTO order_status_history (order_id, old_status, new_status, changed_by) VALUES (?, ?, ?, ?)");
    $stmt->execute([$order_id, $_POST['old_status'], $new_status, $_SESSION['user_id']]);
    
    $success = "Order #$order_id updated to " . ucfirst($new_status);
}

// Handle delete
if (isset($_GET['delete'])) {
    $order_id = intval($_GET['delete']);
    try {
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $success = "Order deleted successfully";
    } catch (PDOException $e) {
        $error = "Cannot delete order with related records";
    }
}

// Get filters
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$vendor_filter = $_GET['vendor'] ?? '';
$search = $_GET['search'] ?? '';
$payment_filter = $_GET['payment'] ?? '';

// Build query
$sql = "SELECT o.*, u.full_name, u.email, u.phone, v.shop_name, r.full_name as rider_name
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        JOIN vendors v ON o.vendor_id = v.id 
        LEFT JOIN users r ON o.rider_id = r.id
        WHERE o.created_at BETWEEN ? AND ?";

$params = [$date_from . ' 00:00:00', $date_to . ' 23:59:59'];

if ($status_filter) {
    $sql .= " AND o.status = ?";
    $params[] = $status_filter;
}

if ($vendor_filter) {
    $sql .= " AND o.vendor_id = ?";
    $params[] = $vendor_filter;
}

if ($search) {
    $sql .= " AND (o.id LIKE ? OR u.full_name LIKE ? OR v.shop_name LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$sql .= " ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Get statistics
$stats = [];
$stats['total_orders'] = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$stats['pending'] = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
$stats['preparing'] = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='preparing'")->fetchColumn();
$stats['delivering'] = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='delivering'")->fetchColumn();
$stats['completed'] = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='completed'")->fetchColumn();
$stats['cancelled'] = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='cancelled'")->fetchColumn();

$stats['today_orders'] = $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$stats['today_revenue'] = $pdo->query("SELECT SUM(total_amount + delivery_fee) FROM orders WHERE DATE(created_at) = CURDATE() AND status != 'cancelled'")->fetchColumn() ?: 0;
$stats['total_revenue'] = $pdo->query("SELECT SUM(total_amount + delivery_fee) FROM orders WHERE status != 'cancelled'")->fetchColumn() ?: 0;
$stats['avg_order_value'] = $pdo->query("SELECT AVG(total_amount + delivery_fee) FROM orders WHERE status != 'cancelled'")->fetchColumn() ?: 0;

// Get vendors for filter
$vendors = $pdo->query("SELECT id, shop_name FROM vendors ORDER BY shop_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Order Management - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .sidebar { min-height: 100vh; background: linear-gradient(180deg, #dc3545 0%, #c82333 100%); }
        .sidebar a { color: white; text-decoration: none; padding: 12px 20px; display: block; border-left: 4px solid transparent; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.1); border-left-color: white; }
        .stat-card { transition: transform 0.3s; cursor: pointer; }
        .stat-card:hover { transform: translateY(-5px); }
        .order-row:hover { background-color: #f8f9fa; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'admin-sidebar.php'; ?>
        
        <div class="col-md-10 p-4 bg-light">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-cart3 text-danger"></i> Super Order Management</h2>
                <div>
                    <button class="btn btn-outline-success" onclick="exportToCSV()">
                        <i class="bi bi-download"></i> Export CSV
                    </button>
                    <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#bulkActionModal">
                        <i class="bi bi-broadcast"></i> Bulk Actions
                    </button>
                </div>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            
            <!-- Statistics Dashboard -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card stat-card bg-danger text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Total Orders</h6>
                                    <h3 class="mb-0"><?= number_format($stats['total_orders']) ?></h3>
                                </div>
                                <i class="bi bi-cart3 display-4 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Today's Orders</h6>
                                    <h3 class="mb-0"><?= number_format($stats['today_orders']) ?></h3>
                                </div>
                                <i class="bi bi-calendar-check display-4 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card bg-primary text-white">
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
                    <div class="card stat-card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Avg Order Value</h6>
                                    <h3 class="mb-0">₦<?= number_format($stats['avg_order_value'], 2) ?></h3>
                                </div>
                                <i class="bi bi-graph-up display-4 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Status Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-2">
                    <div class="card bg-warning text-center">
                        <div class="card-body p-3">
                            <h4 class="mb-1"><?= $stats['pending'] ?></h4>
                            <small>Pending</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-info text-center">
                        <div class="card-body p-3">
                            <h4 class="mb-1"><?= $stats['preparing'] ?></h4>
                            <small>Preparing</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-primary text-center">
                        <div class="card-body p-3">
                            <h4 class="mb-1"><?= $stats['delivering'] ?></h4>
                            <small>Delivering</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-success text-center">
                        <div class="card-body p-3">
                            <h4 class="mb-1"><?= $stats['completed'] ?></h4>
                            <small>Completed</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-secondary text-center">
                        <div class="card-body p-3">
                            <h4 class="mb-1"><?= $stats['cancelled'] ?></h4>
                            <small>Cancelled</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-dark text-white text-center">
                        <div class="card-body p-3">
                            <h4 class="mb-1">₦<?= number_format($stats['total_revenue']) ?></h4>
                            <small>Total Revenue</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Advanced Filters -->
            <div class="card mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0"><i class="bi bi-funnel"></i> Advanced Filters</h6>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="preparing" <?= $status_filter == 'preparing' ? 'selected' : '' ?>>Preparing</option>
                                <option value="delivering" <?= $status_filter == 'delivering' ? 'selected' : '' ?>>Delivering</option>
                                <option value="completed" <?= $status_filter == 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="cancelled" <?= $status_filter == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">Vendor</label>
                            <select name="vendor" class="form-select">
                                <option value="">All Vendors</option>
                                <?php foreach ($vendors as $v): ?>
                                    <option value="<?= $v['id'] ?>" <?= $vendor_filter == $v['id'] ? 'selected' : '' ?>><?= htmlspecialchars($v['shop_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Order ID, Customer..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-danger"><i class="bi bi-search"></i> Apply Filters</button>
                            <a href="admin-orders.php" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> Clear All</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Orders Table -->
            <div class="card">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-list-ul"></i> Orders (<?= count($orders) ?>)</h6>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="selectAll">
                            <label class="form-check-label" for="selectAll">Select All</label>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($orders)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox display-4"></i>
                            <p class="mt-3">No orders found with current filters.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="40"><input type="checkbox" class="form-check-input select-all"></th>
                                        <th>Order #</th>
                                        <th>Customer</th>
                                        <th>Vendor</th>
                                        <th>Items</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Rider</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr class="order-row">
                                            <td><input type="checkbox" class="form-check-input order-checkbox" value="<?= $order['id'] ?>"></td>
                                            <td><strong>#<?= $order['id'] ?></strong></td>
                                            <td>
                                                <div><?= htmlspecialchars($order['full_name']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($order['phone']) ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($order['shop_name']) ?></td>
                                            <td>
                                                <?php
                                                $stmt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE order_id = ?");
                                                $stmt->execute([$order['id']]);
                                                $item_count = $stmt->fetchColumn();
                                                ?>
                                                <span class="badge bg-secondary"><?= $item_count ?> items</span>
                                            </td>
                                            <td>
                                                <strong>₦<?= number_format($order['total_amount']) ?></strong><br>
                                                <small class="text-muted">+₦<?= number_format($order['delivery_fee']) ?> delivery</small>
                                            </td>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                    <input type="hidden" name="old_status" value="<?= $order['status'] ?>">
                                                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 120px;">
                                                        <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                                        <option value="preparing" <?= $order['status'] == 'preparing' ? 'selected' : '' ?>>Preparing</option>
                                                        <option value="ready" <?= $order['status'] == 'ready' ? 'selected' : '' ?>>Ready</option>
                                                        <option value="delivering" <?= $order['status'] == 'delivering' ? 'selected' : '' ?>>Delivering</option>
                                                        <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                                                        <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                    </select>
                                                    <input type="hidden" name="update_status" value="1">
                                                </form>
                                            </td>
                                            <td>
                                                <?php if ($order['rider_name']): ?>
                                                    <span class="badge bg-success"><?= htmlspecialchars($order['rider_name']) ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Unassigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div><?= date('M d, Y', strtotime($order['created_at'])) ?></div>
                                                <small class="text-muted"><?= date('h:i A', strtotime($order['created_at'])) ?></small>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <button class="btn btn-sm btn-outline-primary" onclick="viewOrderDetails(<?= $order['id'] ?>)" title="View Details">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-info" onclick="printInvoice(<?= $order['id'] ?>)" title="Print Invoice">
                                                        <i class="bi bi-printer"></i>
                                                    </button>
                                                    <a href="admin-view-order.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-success" title="Full Details">
                                                        <i class="bi bi-box-arrow-up-right"></i>
                                                    </a>
                                                    <a href="admin-orders.php?delete=<?= $order['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this order?')" title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
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

<!-- Bulk Action Modal -->
<div class="modal fade" id="bulkActionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Bulk Actions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="bulk_action" value="update_status">
                    <div class="mb-3">
                        <label class="form-label">Selected Orders: <span id="selectedCount">0</span></label>
                        <div id="selectedOrders" class="small text-muted"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Change Status To:</label>
                        <select name="bulk_status" class="form-select" required>
                            <option value="pending">Pending</option>
                            <option value="preparing">Preparing</option>
                            <option value="delivering">Delivering</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Apply to Selected</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Details #<span id="detailOrderId"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                Loading...
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Select All functionality
document.querySelectorAll('.select-all').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        document.querySelectorAll('.order-checkbox').forEach(cb => {
            cb.checked = this.checked;
        });
    });
});

// Update selected count
function updateSelectedCount() {
    const selected = document.querySelectorAll('.order-checkbox:checked');
    document.getElementById('selectedCount').textContent = selected.length;
    let ids = Array.from(selected).map(cb => '#' + cb.value).join(', ');
    document.getElementById('selectedOrders').textContent = ids;
}

document.querySelectorAll('.order-checkbox').forEach(cb => {
    cb.addEventListener('change', updateSelectedCount);
});

// View Order Details
function viewOrderDetails(orderId) {
    document.getElementById('detailOrderId').textContent = orderId;
    document.getElementById('orderDetailsContent').innerHTML = 'Loading...';
    
    const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
    modal.show();
    
    // Fetch order details via AJAX
    fetch('admin-view-order.php?id=' + orderId + '&modal=1')
        .then(response => response.text())
        .then(data => {
            document.getElementById('orderDetailsContent').innerHTML = data;
        })
        .catch(error => {
            document.getElementById('orderDetailsContent').innerHTML = 'Error loading details';
        });
}

// Print Invoice
function printInvoice(orderId) {
    window.open('print-invoice.php?order=' + orderId, '_blank');
}

// Export to CSV
function exportToCSV() {
    const params = new URLSearchParams(window.location.search);
    window.location.href = 'export-orders.php?' + params.toString();
}
</script>
</body>
</html>