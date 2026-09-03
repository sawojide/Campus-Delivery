<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['vendor_id']) || $_SESSION['vendor_role'] != 'vendor') {
    header("Location: vendor-login.php");
    exit;
}

$vendor_id = $_SESSION['vendor_id'];
$success = "";
$error = "";

// Get vendor details
$stmt = $pdo->prepare("SELECT * FROM vendors WHERE id = ?");
$stmt->execute([$vendor_id]);
$vendor = $stmt->fetch();
$balance = $vendor['wallet_balance'] ?? 0.00;

// Handle Withdrawal Request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_withdrawal'])) {
    $amount = floatval($_POST['amount']);
    
    if ($amount <= 0) {
        $error = "Invalid amount.";
    } elseif ($amount > $balance) {
        $error = "Insufficient balance. Your available balance is ₦" . number_format($balance);
    } elseif (empty($vendor['account_number']) || empty($vendor['bank_name'])) {
        $error = "Please update your bank details in your Profile first.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Deduct from vendor balance immediately (held in escrow until approved)
            $stmt = $pdo->prepare("UPDATE vendors SET wallet_balance = wallet_balance - ? WHERE id = ?");
            $stmt->execute([$amount, $vendor_id]);
            
            // Create withdrawal request
            $stmt = $pdo->prepare("INSERT INTO withdrawal_requests (vendor_id, amount, status) VALUES (?, ?, 'pending')");
            $stmt->execute([$vendor_id, $amount]);
            
            $pdo->commit();
            $success = "Withdrawal request of ₦" . number_format($amount) . " submitted successfully!";
            
            // Refresh balance
            $vendor['wallet_balance'] -= $amount;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error processing request: " . $e->getMessage();
        }
    }
}

// Get withdrawal history
$stmt = $pdo->prepare("SELECT * FROM withdrawal_requests WHERE vendor_id = ? ORDER BY requested_at DESC");
$stmt->execute([$vendor_id]);
$withdrawals = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdrawals - Vendor Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .sidebar { min-height: 100vh; background: linear-gradient(180deg, #667eea 0%, #764ba2 100%); }
        .sidebar a { color: white; text-decoration: none; padding: 12px 20px; display: block; }
        .sidebar a.active { background: rgba(255,255,255,0.1); border-left: 4px solid white; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 sidebar p-0">
            <h4 class="text-white p-4 mb-0"><i class="bi bi-shop-window"></i> Vendor Panel</h4>
            <div class="mt-4">
                <a href="vendor-dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a href="vendor-orders.php"><i class="bi bi-cart3"></i> Orders</a>
                <a href="vendor-products.php"><i class="bi bi-box-seam"></i> Products</a>
                <a href="vendor-profile.php"><i class="bi bi-person"></i> Profile</a>
                <a href="vendor-withdraw.php" class="active"><i class="bi bi-cash-stack"></i> Withdrawals</a>
                <hr class="text-white my-3">
                <a href="vendor-logout.php" class="logout-link"><i class="bi bi-box-arrow-left"></i> Logout</a>
            </div>
        </div>
        
        <div class="col-md-10 p-4 bg-light">
            <h2 class="mb-4"><i class="bi bi-cash-stack text-primary"></i> Withdrawals</h2>
            
            <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            
            <div class="row">
                <!-- Request Form -->
                <div class="col-lg-4 mb-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4">
                            <h5 class="mb-3">Available Balance</h5>
                            <h2 class="text-success mb-4">₦<?= number_format($balance, 2) ?></h2>
                            
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Withdrawal Amount (₦)</label>
                                    <input type="number" name="amount" class="form-control" step="0.01" min="100" required>
                                    <small class="text-muted">Minimum withdrawal: ₦100</small>
                                </div>
                                <div class="mb-3 p-3 bg-light rounded">
                                    <small class="text-muted d-block">Payout to:</small>
                                    <strong><?= htmlspecialchars($vendor['bank_name'] ?: 'Not Set') ?></strong><br>
                                    <strong><?= htmlspecialchars($vendor['account_number'] ?: 'Not Set') ?></strong><br>
                                    <small><?= htmlspecialchars($vendor['account_name'] ?: 'Not Set') ?></small>
                                </div>
                                <button type="submit" name="request_withdrawal" class="btn btn-primary w-100">Request Withdrawal</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- History -->
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3">
                            <h6 class="mb-0 fw-bold">Withdrawal History</h6>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($withdrawals)): ?>
                                <p class="text-muted text-center py-4">No withdrawal requests yet.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Date</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Admin Note</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($withdrawals as $req): ?>
                                                <tr>
                                                    <td><?= date('M d, Y', strtotime($req['requested_at'])) ?></td>
                                                    <td><strong>₦<?= number_format($req['amount']) ?></strong></td>
                                                    <td>
                                                        <?php
                                                        $badges = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
                                                        $badge = $badges[$req['status']] ?? 'secondary';
                                                        ?>
                                                        <span class="badge bg-<?= $badge ?>"><?= ucfirst($req['status']) ?></span>
                                                    </td>
                                                    <td class="text-muted small"><?= htmlspecialchars($req['admin_note'] ?: '-') ?></td>
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
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>