<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: admin-login.php");
    exit;
}

$success = "";
$error = "";

// Handle Fund Wallet
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['fund_wallet'])) {
    $user_id = intval($_POST['user_id']);
    $amount = floatval($_POST['amount']);
    $description = trim($_POST['description'] ?? 'Manual funding by admin');
    
    if ($amount > 0) {
        try {
            $pdo->beginTransaction();
            
            // Add to wallet
            $stmt = $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ?");
            $stmt->execute([$amount, $user_id]);
            
            // Log transaction
            $stmt = $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, description, admin_id) VALUES (?, 'credit', ?, ?, ?)");
            $stmt->execute([$user_id, $amount, $description, $_SESSION['user_id']]);
            
            $pdo->commit();
            $success = "Wallet funded with ₦" . number_format($amount);
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error funding wallet: " . $e->getMessage();
        }
    }
}

// Handle Deduct from Wallet
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['deduct_wallet'])) {
    $user_id = intval($_POST['user_id']);
    $amount = floatval($_POST['amount']);
    $description = trim($_POST['deduct_description'] ?? 'Manual deduction by admin');
    
    if ($amount > 0) {
        try {
            // Check current balance
            $stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $current_balance = $stmt->fetchColumn();
            
            if ($current_balance >= $amount) {
                $pdo->beginTransaction();
                
                // Deduct from wallet
                $stmt = $pdo->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ?");
                $stmt->execute([$amount, $user_id]);
                
                // Log transaction
                $stmt = $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, description, admin_id) VALUES (?, 'debit', ?, ?, ?)");
                $stmt->execute([$user_id, $amount, $description, $_SESSION['user_id']]);
                
                $pdo->commit();
                $success = "₦" . number_format($amount) . " deducted successfully";
            } else {
                $error = "Insufficient balance. Current balance: ₦" . number_format($current_balance);
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error deducting: " . $e->getMessage();
        }
    }
}

// Get filters
$role_filter = isset($_GET['role']) ? $_GET['role'] : 'all';
$balance_filter = isset($_GET['balance_filter']) ? $_GET['balance_filter'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$sql = "SELECT w.*, u.full_name, u.email, u.role, u.phone 
        FROM wallets w 
        JOIN users u ON w.user_id = u.id 
        WHERE 1=1";
$params = [];

if ($role_filter && $role_filter != 'all') {
    $sql .= " AND u.role = ?";
    $params[] = $role_filter;
}

if ($balance_filter) {
    switch($balance_filter) {
        case 'zero':
            $sql .= " AND w.balance = 0";
            break;
        case 'low':
            $sql .= " AND w.balance > 0 AND w.balance < 1000";
            break;
        case 'medium':
            $sql .= " AND w.balance >= 1000 AND w.balance < 10000";
            break;
        case 'high':
            $sql .= " AND w.balance >= 10000";
            break;
    }
}

if ($search) {
    $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$sql .= " ORDER BY w.balance DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$wallets = $stmt->fetchAll();

// Get Statistics
$total_wallets = $pdo->query("SELECT COUNT(*) FROM wallets")->fetchColumn();
$total_balance = $pdo->query("SELECT SUM(balance) FROM wallets")->fetchColumn() ?: 0;
$zero_balance = $pdo->query("SELECT COUNT(*) FROM wallets WHERE balance = 0")->fetchColumn();
$low_balance = $pdo->query("SELECT COUNT(*) FROM wallets WHERE balance > 0 AND balance < 1000")->fetchColumn();

// Get recent transactions
$stmt = $pdo->query("
    SELECT wt.*, u.full_name, u.email 
    FROM wallet_transactions wt 
    JOIN users u ON wt.user_id = u.id 
    ORDER BY wt.created_at DESC 
    LIMIT 10
");
$recent_transactions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Wallet Management - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .sidebar { min-height: 100vh; background: linear-gradient(180deg, #dc3545 0%, #c82333 100%); }
        .sidebar a { color: white; text-decoration: none; padding: 12px 20px; display: block; border-left: 4px solid transparent; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.1); border-left-color: white; }
        .stat-box { background: white; border-radius: 10px; padding: 20px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .stat-box h3 { margin: 0; font-size: 2rem; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'admin-sidebar.php'; ?>
        
        <div class="col-md-10 p-4 bg-light">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-wallet2 text-danger"></i> Super Wallet Management</h2>
                <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#transactionModal">
                    <i class="bi bi-clock-history"></i> View Transactions
                </button>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            
            <!-- Statistics Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-box border-start border-4 border-primary">
                        <h3><?= number_format($total_wallets) ?></h3>
                        <p class="mb-0 text-muted">Total Wallets</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box border-start border-4 border-success">
                        <h3>₦<?= number_format($total_balance) ?></h3>
                        <p class="mb-0 text-muted">Total Balance</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box border-start border-4 border-danger">
                        <h3><?= number_format($zero_balance) ?></h3>
                        <p class="mb-0 text-muted">Zero Balance</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box border-start border-4 border-warning">
                        <h3><?= number_format($low_balance) ?></h3>
                        <p class="mb-0 text-muted">Low Balance (<1000)</p>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <select name="role" class="form-select">
                                <option value="all">All Users</option>
                                <option value="student" <?= $role_filter == 'student' ? 'selected' : '' ?>>Students</option>
                                <option value="rider" <?= $role_filter == 'rider' ? 'selected' : '' ?>>Riders</option>
                                <option value="admin" <?= $role_filter == 'admin' ? 'selected' : '' ?>>Admins</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="balance_filter" class="form-select">
                                <option value="">All Balances</option>
                                <option value="zero" <?= $balance_filter == 'zero' ? 'selected' : '' ?>>Zero Balance</option>
                                <option value="low" <?= $balance_filter == 'low' ? 'selected' : '' ?>>Low (₦0 - ₦999)</option>
                                <option value="medium" <?= $balance_filter == 'medium' ? 'selected' : '' ?>>Medium (₦1k - ₦9,999)</option>
                                <option value="high" <?= $balance_filter == 'high' ? 'selected' : '' ?>>High (₦10k+)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Search by name, email, or phone..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-danger w-100"><i class="bi bi-search"></i> Filter</button>
                        </div>
                    </form>
                    <a href="admin-wallets.php" class="btn btn-sm btn-outline-secondary mt-2"><i class="bi bi-x-circle"></i> Clear Filters</a>
                </div>
            </div>
            
            <!-- Wallets Table -->
            <div class="card">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0"><i class="bi bi-wallet"></i> All Wallets</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($wallets)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-wallet2 display-4"></i>
                            <p class="mt-3">No wallets found.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>User</th>
                                        <th>Role</th>
                                        <th>Contact</th>
                                        <th>Balance</th>
                                        <th>Status</th>
                                        <th>Last Activity</th>
                                        <th>Quick Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($wallets as $wallet): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($wallet['full_name']) ?></strong><br>
                                                <small class="text-muted">ID: <?= $wallet['user_id'] ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $wallet['role'] == 'admin' ? 'danger' : ($wallet['role'] == 'rider' ? 'success' : 'secondary') ?>">
                                                    <?= strtoupper($wallet['role']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small><?= htmlspecialchars($wallet['email']) ?></small><br>
                                                <small class="text-muted"><?= htmlspecialchars($wallet['phone']) ?></small>
                                            </td>
                                            <td>
                                                <strong class="text-success fs-5">₦<?= number_format($wallet['balance'], 2) ?></strong>
                                            </td>
                                            <td>
                                                <?php if ($wallet['balance'] == 0): ?>
                                                    <span class="badge bg-secondary">Empty</span>
                                                <?php elseif ($wallet['balance'] < 1000): ?>
                                                    <span class="badge bg-warning text-dark">Low</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $stmt = $pdo->prepare("SELECT created_at FROM wallet_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
                                                $stmt->execute([$wallet['user_id']]);
                                                $last_activity = $stmt->fetchColumn();
                                                ?>
                                                <small><?= $last_activity ? date('M d, H:i', strtotime($last_activity)) : 'Never' ?></small>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <button class="btn btn-sm btn-outline-success" onclick="openFundModal(<?= $wallet['user_id'] ?>, '<?= htmlspecialchars($wallet['full_name']) ?>')" data-bs-toggle="modal" data-bs-target="#fundModal" title="Fund">
                                                        <i class="bi bi-plus-circle"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="openDeductModal(<?= $wallet['user_id'] ?>, '<?= htmlspecialchars($wallet['full_name']) ?>', <?= $wallet['balance'] ?>)" data-bs-toggle="modal" data-bs-target="#deductModal" title="Deduct" <?= $wallet['balance'] <= 0 ? 'disabled' : '' ?>>
                                                        <i class="bi bi-dash-circle"></i>
                                                    </button>
                                                    <a href="admin-wallets.php?view_transactions=<?= $wallet['user_id'] ?>" class="btn btn-sm btn-outline-info" title="View History">
                                                        <i class="bi bi-clock-history"></i>
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

<!-- Fund Wallet Modal -->
<div class="modal fade" id="fundModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Fund Wallet</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="fund_user_id">
                    <input type="hidden" name="fund_wallet" value="1">
                    
                    <div class="alert alert-info">
                        <strong>User:</strong> <span id="fund_user_name"></span>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Amount (₦) *</label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Reason for funding...">Manual funding by admin</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-circle"></i> Fund Wallet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Deduct Wallet Modal -->
<div class="modal fade" id="deductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-dash-circle"></i> Deduct from Wallet</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="deduct_user_id">
                    <input type="hidden" name="deduct_wallet" value="1">
                    
                    <div class="alert alert-warning">
                        <strong>User:</strong> <span id="deduct_user_name"></span><br>
                        <strong>Current Balance:</strong> ₦<span id="current_balance"></span>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Amount to Deduct (₦) *</label>
                        <input type="number" name="amount" id="deduct_amount" class="form-control" step="0.01" min="1" required>
                        <div class="form-text">Cannot exceed current balance</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason for Deduction *</label>
                        <textarea name="deduct_description" class="form-control" rows="2" placeholder="Why are you deducting?" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-dash-circle"></i> Deduct</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Transaction History Modal -->
<div class="modal fade" id="transactionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-clock-history"></i> Recent Wallet Transactions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>User</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_transactions as $txn): ?>
                                <tr>
                                    <td><?= date('M d, Y H:i', strtotime($txn['created_at'])) ?></td>
                                    <td><?= htmlspecialchars($txn['full_name']) ?></td>
                                    <td>
                                        <?php if ($txn['type'] == 'credit'): ?>
                                            <span class="badge bg-success">Credit</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Debit</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="<?= $txn['type'] == 'credit' ? 'text-success' : 'text-danger' ?>">
                                        <strong><?= $txn['type'] == 'credit' ? '+' : '-' ?> ₦<?= number_format($txn['amount']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($txn['description']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openFundModal(userId, userName) {
    document.getElementById('fund_user_id').value = userId;
    document.getElementById('fund_user_name').textContent = userName;
}

function openDeductModal(userId, userName, balance) {
    document.getElementById('deduct_user_id').value = userId;
    document.getElementById('deduct_user_name').textContent = userName;
    document.getElementById('current_balance').textContent = balance.toLocaleString();
    document.getElementById('deduct_amount').max = balance;
}
</script>
</body>
</html>