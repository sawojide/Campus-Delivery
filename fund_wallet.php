<?php
session_start();
require 'includes/db.php';

// Security Check: Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";
$message_type = "";

// Fetch current balance
$stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ?");
$stmt->execute([$user_id]);
$wallet = $stmt->fetch();
$current_balance = $wallet ? $wallet['balance'] : 0.00;

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = floatval($_POST['amount']);

    if ($amount <= 0) {
        $message = "Please enter a valid amount greater than zero.";
        $message_type = "danger";
    } else {
        try {
            // Simulate bank transfer: Add to wallet balance
            $stmt = $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ?");
            $stmt->execute([$amount, $user_id]);
            
            $message = "✅ Successfully funded wallet with ₦" . number_format($amount, 2) . "!";
            $message_type = "success";
            
            // Update displayed balance
            $current_balance += $amount;
        } catch (PDOException $e) {
            $message = "❌ Funding failed. Please try again.";
            $message_type = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fund Wallet - Campus Delivery</title>
    <!-- Bootstrap 5 CSS (For beautiful, responsive layout) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons (For shopping carts, users, etc.) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-dark bg-danger">
    <div class="container">
        <a href="dashboard.php" class="navbar-brand mb-0 h1">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</nav>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h4 class="mb-3 text-center">
                        <i class="bi bi-wallet2 text-success"></i> Fund Your Wallet
                    </h4>
                    
                    <!-- Current Balance Alert -->
                    <div class="alert alert-info text-center">
                        <i class="bi bi-cash-coin"></i> 
                        <strong>Current Balance:</strong> 
                        <span class="fs-4">₦<?= number_format($current_balance, 2) ?></span>
                    </div>

                    <!-- Success/Error Messages -->
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Funding Form -->
                    <form method="POST" action="fund_wallet.php">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Amount to Fund (₦)</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text">₦</span>
                                <input type="number" 
                                       name="amount" 
                                       class="form-control" 
                                       placeholder="e.g., 5000" 
                                       min="100" 
                                       step="100" 
                                       required
                                       autofocus>
                            </div>
                            <div class="form-text text-muted">
                                <i class="bi bi-info-circle"></i> 
                                Minimum funding amount is ₦100
                            </div>
                        </div>

                        <!-- Quick Amount Buttons -->
                        <div class="mb-4">
                            <label class="form-label">Quick Amounts:</label>
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-outline-secondary" onclick="setAmount(1000)">₦1,000</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="setAmount(2000)">₦2,000</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="setAmount(5000)">₦5,000</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="setAmount(10000)">₦10,000</button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success w-100 btn-lg">
                            <i class="bi bi-check-circle"></i> Confirm Funding
                        </button>
                    </form>

                    <hr>

                    <div class="text-center text-muted small">
                        <i class="bi bi-shield-check text-success"></i> 
                        Secure wallet funding - No real money is charged in demo mode
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Quick amount selector
function setAmount(amount) {
    document.querySelector('input[name="amount"]').value = amount;
}
</script>

</body>
</html>