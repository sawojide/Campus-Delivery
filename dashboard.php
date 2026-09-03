<?php
session_start();
require 'includes/db.php';

// Security Check: If not logged in, kick them back to login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Student';

// Fetch Wallet Balance
$stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ?");
$stmt->execute([$user_id]);
$wallet = $stmt->fetch();
$balance = $wallet ? $wallet['balance'] : 0.00;

// ✅ Fetch User Referral Code
$stmt_user = $pdo->prepare("SELECT referral_code FROM users WHERE id = ?");
$stmt_user->execute([$user_id]);
$user = $stmt_user->fetch();
$referral_code = $user['referral_code'] ?? 'N/A';

// ✅ Get total referral earnings for this user
$stmt_earnings = $pdo->prepare("SELECT SUM(reward_amount) as total FROM referral_rewards WHERE referrer_id = ?");
$stmt_earnings->execute([$user_id]);
$total_earned = $stmt_earnings->fetch()['total'] ?? 0;

// Define site URL for the referral link (adjust if your live domain is different)
$site_url = defined('SITE_URL') ? SITE_URL : 'http://localhost/campus_delivery';
$referral_link = $site_url . '/register.php?ref=' . urlencode($referral_code);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Campus Delivery</title>
    <!-- Bootstrap 5 CSS (For beautiful, responsive layout) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons (For shopping carts, users, etc.) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- PWA Meta Tags -->
<meta name="theme-color" content="#dc3545">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Campus Delivery">
<link rel="manifest" href="manifest.json">
<link rel="apple-touch-icon" href="icons/icon-192x192.png">
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-dark bg-danger">
    <div class="container">
        <span class="navbar-brand mb-0 h1"><i class="bi bi-bag-heart-fill"></i> Campus Delivery</span>
        <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
</nav>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Welcome, <?= htmlspecialchars($user_name) ?>! 👋</h4>
    </div>
    
    <!-- ✅ Referral Widget (Placed prominently at the top) -->
    <div class="card shadow-sm border-0 mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="card-body text-white p-4">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h4 class="mb-2"><i class="bi bi-gift"></i> Invite Friends, Earn ₦500!</h4>
                    <p class="mb-3 opacity-75">Share your unique code. When your friend places their first order, you get rewarded instantly.</p>
                    
                    <div class="d-flex gap-2 flex-wrap">
                        <div class="bg-white text-dark px-3 py-2 rounded fw-bold d-flex align-items-center" style="letter-spacing: 2px;">
                            <?= htmlspecialchars($referral_code) ?>
                        </div>
                        <button class="btn btn-light text-primary fw-bold" onclick="copyReferralCode('<?= htmlspecialchars($referral_link) ?>')">
                            <i class="bi bi-clipboard"></i> Copy Link
                        </button>
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                    <h2 class="mb-0">₦<?= number_format($total_earned) ?></h2>
                    <small class="opacity-75">Total Referral Earnings</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Wallet & Browse Categories Row -->
    <div class="row mt-2">
        <div class="col-md-4 mb-3">
            <div class="card bg-dark text-white shadow h-100">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-white-50">My Wallet Balance</h6>
                    <h2 class="card-title">₦<?= number_format($balance, 2) ?></h2>
                    <a href="fund_wallet.php" class="btn btn-success btn-sm mt-2">
                        <i class="bi bi-plus-circle"></i> Fund Wallet
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-8 mb-3">
            <div class="card shadow h-100">
                <div class="card-body">
                    <h5><i class="bi bi-shop"></i> Browse Categories</h5>
                    <p class="text-muted">Select what you want to order today.</p>
                    <div class="d-flex gap-2 flex-wrap mb-3">
                        <a href="browse.php?category=food" class="btn btn-outline-danger">
                            <i class="bi bi-fire"></i> Suya & BBQ
                        </a>
                        <a href="browse.php?category=food" class="btn btn-outline-primary">
                            <i class="bi bi-cup-hot"></i> Food & Drinks
                        </a>
                        <a href="browse.php?category=perfumes" class="btn btn-outline-warning">
                            <i class="bi bi-droplet"></i> Perfumes
                        </a>
                        <a href="browse.php?category=provisions" class="btn btn-outline-secondary">
                            <i class="bi bi-box-seam"></i> Provisions
                        </a>
                    </div>
                    <a href="browse.php" class="btn btn-danger">
                        <i class="bi bi-shop"></i> Browse All Products
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row mt-2">
        <div class="col-md-4 mb-3">
            <a href="browse.php" class="btn btn-outline-danger w-100 p-4 text-center">
                <i class="bi bi-shop display-6 d-block mb-2"></i>
                <h6 class="mb-0">Shop Now</h6>
            </a>
        </div>
        <div class="col-md-4 mb-3">
            <a href="order_history.php" class="btn btn-outline-primary w-100 p-4 text-center">
                <i class="bi bi-clock-history display-6 d-block mb-2"></i>
                <h6 class="mb-0">My Orders</h6>
            </a>
        </div>
        <div class="col-md-4 mb-3">
            <a href="fund_wallet.php" class="btn btn-outline-success w-100 p-4 text-center">
                <i class="bi bi-wallet2 display-6 d-block mb-2"></i>
                <h6 class="mb-0">Fund Wallet</h6>
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function copyReferralCode(link) {
    navigator.clipboard.writeText(link).then(() => {
        // Create a temporary toast/alert for better UX than a browser alert
        const toast = document.createElement('div');
        toast.className = 'position-fixed bottom-0 end-0 p-3';
        toast.style.zIndex = '11';
        toast.innerHTML = `
            <div class="toast show align-items-center text-white bg-success border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi bi-check-circle"></i> Referral link copied to clipboard!
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }).catch(err => {
        console.error('Failed to copy: ', err);
        alert('Failed to copy link. Please copy it manually: ' + link);
    });
}
</script>
<!-- PWA Service Worker Registration -->
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('Service Worker registered successfully:', registration.scope);
            })
            .catch(error => {
                console.log('Service Worker registration failed:', error);
            });
    });
}
</script>
</body>
</html>