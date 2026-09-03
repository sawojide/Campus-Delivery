<?php
session_start();
require 'includes/db.php';
require 'paystack.php';
require 'Notification.php';
require 'distance.php';
require 'email.php'; // ✅ Correct path to Email Helper

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (empty($_SESSION['cart'])) {
    header("Location: browse.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";
$message_type = "";

// Fetch user info and wallet balance
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ?");
$stmt->execute([$user_id]);
$wallet = $stmt->fetch();
$wallet_balance = $wallet ? $wallet['balance'] : 0.00;

// Calculate Base Totals
$subtotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

// ✅ DYNAMIC DELIVERY FEE CALCULATION
$first_item = $_SESSION['cart'][0];
$stmt_vendor = $pdo->prepare("SELECT latitude, longitude FROM vendors WHERE id = (SELECT vendor_id FROM products WHERE id = ?)");
$stmt_vendor->execute([$first_item['product_id']]);
$vendor_loc = $stmt_vendor->fetch();

$stmt_student = $pdo->prepare("SELECT latitude, longitude FROM users WHERE id = ?");
$stmt_student->execute([$user_id]);
$student_loc = $stmt_student->fetch();

// ⚠️ REPLACE THESE WITH YOUR ACTUAL CAMPUS GPS COORDINATES
$CAMPUS_CENTER_LAT = 9.0765;  
$CAMPUS_CENTER_LNG = 7.3986;  

$vendor_lat = $vendor_loc['latitude'] ?? $CAMPUS_CENTER_LAT;
$vendor_lng = $vendor_loc['longitude'] ?? $CAMPUS_CENTER_LNG;
$student_lat = $student_loc['latitude'] ?? $CAMPUS_CENTER_LAT;
$student_lng = $student_loc['longitude'] ?? $CAMPUS_CENTER_LNG;

$distance_km = calculateDistanceInKm($vendor_lat, $vendor_lng, $student_lat, $student_lng);
$delivery_fee = calculateDeliveryFee($distance_km);
$_SESSION['delivery_distance'] = $distance_km;

// --- Handle Promo Code Application ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['apply_promo'])) {
    $code = strtoupper(trim($_POST['promo_code']));
    $stmt = $pdo->prepare("SELECT * FROM promo_codes WHERE code = ? AND is_active = 1");
    $stmt->execute([$code]);
    $promo = $stmt->fetch();

    if ($promo) {
        if ($promo['expires_at'] && strtotime($promo['expires_at']) < time()) {
            $message = "This promo code has expired."; $message_type = "danger";
        } elseif ($promo['max_uses'] > 0 && $promo['current_uses'] >= $promo['max_uses']) {
            $message = "This promo code has reached its maximum uses."; $message_type = "danger";
        } elseif ($subtotal < $promo['min_order']) {
            $message = "Minimum order of ₦" . number_format($promo['min_order']) . " required."; $message_type = "danger";
        } else {
            if ($promo['applicable_to'] == 'specific') {
                $stmt = $pdo->prepare("SELECT product_id FROM promo_products WHERE promo_id = ?");
                $stmt->execute([$promo['id']]);
                $allowed_products = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                $cart_product_ids = array_column($_SESSION['cart'], 'product_id');
                $has_valid_product = false;
                foreach ($cart_product_ids as $cart_product_id) {
                    if (in_array($cart_product_id, $allowed_products)) {
                        $has_valid_product = true;
                        break;
                    }
                }
                
                if (!$has_valid_product) {
                    $message = "This promo code only applies to specific products."; $message_type = "danger";
                } else {
                    $discount = 0;
                    foreach ($_SESSION['cart'] as $item) {
                        if (in_array($item['product_id'], $allowed_products)) {
                            $item_total = $item['price'] * $item['quantity'];
                            $discount += ($promo['type'] == 'percentage') ? ($item_total * ($promo['value'] / 100)) : ($promo['value'] / count($allowed_products));
                        }
                    }
                    if ($discount > $subtotal) $discount = $subtotal;
                    $_SESSION['applied_promo'] = ['code' => $promo['code'], 'discount' => $discount, 'type' => $promo['type'], 'value' => $promo['value'], 'applicable_to' => $promo['applicable_to']];
                    $message = "Promo code applied! You saved ₦" . number_format($discount); $message_type = "success";
                }
            } else {
                $discount = ($promo['type'] == 'percentage') ? ($subtotal * ($promo['value'] / 100)) : $promo['value'];
                if ($discount > $subtotal) $discount = $subtotal;
                $_SESSION['applied_promo'] = ['code' => $promo['code'], 'discount' => $discount, 'type' => $promo['type'], 'value' => $promo['value'], 'applicable_to' => $promo['applicable_to']];
                $message = "Promo code applied! You saved ₦" . number_format($discount); $message_type = "success";
            }
        }
    } else {
        $message = "Invalid promo code."; $message_type = "danger";
    }
}

if (isset($_GET['remove_promo'])) {
    unset($_SESSION['applied_promo']);
    header("Location: checkout.php");
    exit;
}

$discount_amount = $_SESSION['applied_promo']['discount'] ?? 0.00;
$grand_total = $subtotal + $delivery_fee - $discount_amount;

// --- Handle Final Checkout ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complete_order'])) {
    $payment_method = $_POST['payment_method'] ?? '';

    if ($payment_method == 'wallet') {
        if ($wallet_balance < $grand_total) {
            $message = "Insufficient wallet balance!"; $message_type = "danger";
        } else {
            try {
                $pdo->beginTransaction();
                
                $stmt = $pdo->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ?");
                $stmt->execute([$grand_total, $user_id]);
                
                $first_item = $_SESSION['cart'][0];
                $stmt = $pdo->prepare("SELECT vendor_id FROM products WHERE id = ?");
                $stmt->execute([$first_item['product_id']]);
                $vendor = $stmt->fetch();
                $vendor_id = $vendor['vendor_id'];
                
                $promo_code = $_SESSION['applied_promo']['code'] ?? null;
                $stmt = $pdo->prepare("INSERT INTO orders (user_id, vendor_id, total_amount, delivery_fee, promo_code, discount_amount, status, payment_method) VALUES (?, ?, ?, ?, ?, ?, 'pending', 'wallet')");
                $stmt->execute([$user_id, $vendor_id, $subtotal, $delivery_fee, $promo_code, $discount_amount]);
                $order_id = $pdo->lastInsertId();
                
                foreach ($_SESSION['cart'] as $item) {
                    $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
                    $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                    $stmt->execute([$item['quantity'], $item['product_id']]);
                }
                
                if ($promo_code) {
                    $stmt = $pdo->prepare("UPDATE promo_codes SET current_uses = current_uses + 1 WHERE code = ?");
                    $stmt->execute([$promo_code]);
                }
                
                $pdo->commit();
                
                // ✅ EMAIL: Send to Student (with inline CSS for email client compatibility)
                $site_url = defined('SITE_URL') ? SITE_URL : 'http://localhost/campus_delivery';
                $studentEmailHtml = "
                    <h2>Hi " . htmlspecialchars($user['full_name']) . ",</h2>
                    <p>Thank you for your order! We've received it and the vendor is preparing your food.</p>
                    <p><strong>Order #:</strong> {$order_id}<br><strong>Total Paid:</strong> ₦" . number_format($grand_total, 2) . "</p>
                    <a href='{$site_url}/view_order.php?order_id={$order_id}' style='display:inline-block; padding:12px 24px; background-color:#dc3545; color:#ffffff; text-decoration:none; border-radius:5px; font-weight:bold; margin-top:15px;'>Track Your Order</a>
                ";
                sendCampusEmail($user['email'], $user['full_name'], "Order Confirmed! #{$order_id}", $studentEmailHtml);

                // ✅ EMAIL: Send to Vendor
                $stmt_vendor_email = $pdo->prepare("SELECT email, owner_name, shop_name FROM vendors WHERE id = ?");
                $stmt_vendor_email->execute([$vendor_id]);
                $vendor_info = $stmt_vendor_email->fetch();
                
                if ($vendor_info) {
                    $vendorEmailHtml = "
                        <h2>New Order Alert! 🛎️</h2>
                        <p>Hi " . htmlspecialchars($vendor_info['owner_name']) . ",</p>
                        <p>You have a new order at <strong>" . htmlspecialchars($vendor_info['shop_name']) . "</strong>.</p>
                        <p><strong>Order #:</strong> {$order_id}<br><strong>Customer:</strong> " . htmlspecialchars($user['full_name']) . "<br><strong>Amount:</strong> ₦" . number_format($subtotal, 2) . "</p>
                        <p>Please log in to your vendor dashboard to prepare this order.</p>
                    ";
                    sendCampusEmail($vendor_info['email'], $vendor_info['owner_name'], "🔔 New Order Received! #{$order_id}", $vendorEmailHtml);
                }
                
                // In-app notification
                $notification = new Notification($pdo);
                $notification->create($user_id, "Order Placed Successfully!", "Your order #{$order_id} has been placed and is being processed.", 'order', "view_order.php?order_id={$order_id}");
                
                unset($_SESSION['cart'], $_SESSION['applied_promo'], $_SESSION['delivery_distance']);
                header("Location: order_success.php?order_id=" . $order_id);
                exit;
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = "Checkout failed: " . $e->getMessage(); $message_type = "danger";
            }
        }
    } elseif ($payment_method == 'paystack') {
        $paystack = new PaystackPayment();
        $metadata = ['user_id' => $user_id, 'cart' => $_SESSION['cart'], 'delivery_fee' => $delivery_fee, 'subtotal' => $subtotal, 'discount' => $discount_amount, 'promo' => $_SESSION['applied_promo']['code'] ?? null];
        $response = $paystack->initializePayment($user['email'], $grand_total, $metadata);
        if ($response['status']) {
            header("Location: " . $response['data']['authorization_url']);
            exit;
        } else {
            $message = "Payment initialization failed."; $message_type = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Campus Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-danger">
    <div class="container">
        <a href="cart.php" class="navbar-brand mb-0 h1"><i class="bi bi-arrow-left"></i> Back to Cart</a>
    </div>
</nav>

<div class="container mt-4">
    <h3 class="mb-4"><i class="bi bi-credit-card"></i> Secure Checkout</h3>
    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-7">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3"><i class="bi bi-geo-alt"></i> Delivery Information</h5>
                    <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($user['full_name']) ?></p>
                    <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                    <p class="mb-1"><strong>Phone:</strong> <?= htmlspecialchars($user['phone']) ?></p>
                    <p class="mb-0"><strong>Address:</strong> <?= htmlspecialchars($user['hostel_address'] ?? 'Campus Hostel (Default)') ?></p>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3"><i class="bi bi-bag"></i> Order Summary</h5>
                    <?php foreach ($_SESSION['cart'] as $item): ?>
                        <div class="d-flex justify-content-between mb-2 border-bottom pb-2">
                            <span><?= htmlspecialchars($item['name']) ?> <span class="text-muted">x<?= $item['quantity'] ?></span></span>
                            <strong>₦<?= number_format($item['price'] * $item['quantity']) ?></strong>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="d-flex justify-content-between mb-2 mt-3">
                        <span>Subtotal:</span>
                        <strong>₦<?= number_format($subtotal, 2) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Delivery Fee <small class="text-muted">(<?= number_format($_SESSION['delivery_distance'] ?? 0, 1) ?> km)</small>:</span>
                        <strong>₦<?= number_format($delivery_fee, 2) ?></strong>
                    </div>
                    
                    <?php if ($discount_amount > 0): ?>
                        <div class="d-flex justify-content-between mb-2 text-success fw-bold">
                            <span>Discount (<?= htmlspecialchars($_SESSION['applied_promo']['code']) ?>):</span>
                            <span>- ₦<?= number_format($discount_amount, 2) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <hr>
                    <div class="d-flex justify-content-between fs-5">
                        <span class="fw-bold">Total Amount:</span>
                        <span class="text-danger fw-bold">₦<?= number_format($grand_total, 2) ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="bi bi-tag"></i> Have a Promo Code?</h6>
                    <?php if (isset($_SESSION['applied_promo'])): ?>
                        <div class="alert alert-success d-flex justify-content-between align-items-center mb-0">
                            <span><i class="bi bi-check-circle"></i> <?= htmlspecialchars($_SESSION['applied_promo']['code']) ?> applied!</span>
                            <a href="?remove_promo=1" class="btn btn-sm btn-outline-danger">Remove</a>
                        </div>
                    <?php else: ?>
                        <form method="POST" class="input-group">
                            <input type="text" name="promo_code" class="form-control text-uppercase" placeholder="Enter code" required>
                            <button type="submit" name="apply_promo" class="btn btn-outline-danger">Apply</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h5 class="mb-4">Select Payment Method</h5>
                    <form method="POST" id="checkoutForm">
                        <div class="form-check p-3 border rounded mb-3 <?= $wallet_balance < $grand_total ? 'bg-light opacity-75' : 'border-success' ?>">
                            <input class="form-check-input" type="radio" name="payment_method" id="payWallet" value="wallet" <?= $wallet_balance >= $grand_total ? 'checked' : 'disabled' ?>>
                            <label class="form-check-label w-100" for="payWallet">
                                <div class="d-flex justify-content-between">
                                    <strong class="text-success"><i class="bi bi-wallet2"></i> Pay with Wallet</strong>
                                    <span class="badge bg-success">₦<?= number_format($wallet_balance) ?></span>
                                </div>
                            </label>
                        </div>
                        <div class="form-check p-3 border rounded mb-4 border-primary">
                            <input class="form-check-input" type="radio" name="payment_method" id="payPaystack" value="paystack" <?= $wallet_balance < $grand_total ? 'checked' : '' ?>>
                            <label class="form-check-label w-100" for="payPaystack">
                                <div class="d-flex justify-content-between">
                                    <strong class="text-primary"><i class="bi bi-credit-card"></i> Pay with Paystack</strong>
                                    <span class="badge bg-primary">Recommended</span>
                                </div>
                            </label>
                        </div>
                        <button type="submit" name="complete_order" class="btn btn-success w-100 btn-lg shadow-sm">
                            <i class="bi bi-lock-fill"></i> Complete Order - ₦<?= number_format($grand_total, 2) ?>
                        </button>
                    </form>
                    <?php if ($wallet_balance < $grand_total): ?>
                        <div class="text-center mt-3">
                            <a href="fund_wallet.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-plus-circle"></i> Fund Wallet First</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('payWallet').disabled) {
        document.getElementById('payPaystack').checked = true;
    }
});
</script>
</body>
</html>