<?php
session_start();
require 'includes/db.php';
require 'paystack.php';
require 'Notification.php';
require 'email.php'; // ✅ Correct path to Email Helper

$reference = $_GET['reference'] ?? '';
if (empty($reference)) { 
    header("Location: index.php"); 
    exit; 
}

$paystack = new PaystackPayment();
$response = $paystack->verifyPayment($reference);

if ($response['status'] && $response['data']['status'] == 'success') {
    $payment_data = $response['data'];
    $metadata = json_decode($payment_data['metadata'], true);
    
    $user_id = $metadata['user_id'];
    $cart = $metadata['cart'];
    $delivery_fee = $metadata['delivery_fee'];
    $subtotal = $metadata['subtotal'];
    $promo_code = $metadata['promo'] ?? null;
    $discount_amount = $metadata['discount'] ?? 0.00;
    $grand_total = $subtotal + $delivery_fee - $discount_amount;
    
    try {
        $pdo->beginTransaction();
        
        // Determine vendor (using first item's vendor)
        $stmt = $pdo->prepare("SELECT vendor_id FROM products WHERE id = ?");
        $stmt->execute([$cart[0]['product_id']]);
        $vendor_id = $stmt->fetchColumn();
        
        // Create order WITH promo_code and discount_amount
        $stmt = $pdo->prepare("
            INSERT INTO orders (user_id, vendor_id, total_amount, delivery_fee, promo_code, discount_amount, status, payment_reference, payment_method)
            VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, 'paystack')
        ");
        $stmt->execute([$user_id, $vendor_id, $subtotal, $delivery_fee, $promo_code, $discount_amount, $reference]);
        $order_id = $pdo->lastInsertId();
        
        // Create order items & reduce stock
        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        foreach ($cart as $item) {
            $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
            
            $stmt_stock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stmt_stock->execute([$item['quantity'], $item['product_id']]);
        }
        
        // Increment promo usage if a promo was used
        if ($promo_code) {
            $stmt = $pdo->prepare("UPDATE promo_codes SET current_uses = current_uses + 1 WHERE code = ?");
            $stmt->execute([$promo_code]);
        }
        
        $pdo->commit();
        
        // ✅ EMAIL: Send to Student (with inline CSS for email client compatibility)
        $stmt_user = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
        $stmt_user->execute([$user_id]);
        $user = $stmt_user->fetch();
        
        if ($user) {
            $site_url = defined('SITE_URL') ? SITE_URL : 'http://localhost/campus_delivery';
            $studentEmailHtml = "
                <h2>Hi " . htmlspecialchars($user['full_name']) . ",</h2>
                <p>Payment successful! We've received your order and the vendor is preparing it.</p>
                <p><strong>Order #:</strong> {$order_id}<br><strong>Total Paid:</strong> ₦" . number_format($grand_total, 2) . "</p>
                <a href='{$site_url}/view_order.php?order_id={$order_id}' style='display:inline-block; padding:12px 24px; background-color:#dc3545; color:#ffffff; text-decoration:none; border-radius:5px; font-weight:bold; margin-top:15px;'>Track Your Order</a>
            ";
            sendCampusEmail($user['email'], $user['full_name'], "Payment Successful! Order #{$order_id}", $studentEmailHtml);
        }

        // ✅ EMAIL: Send to Vendor
        $stmt_vendor = $pdo->prepare("SELECT email, owner_name, shop_name FROM vendors WHERE id = ?");
        $stmt_vendor->execute([$vendor_id]);
        $vendor_info = $stmt_vendor->fetch();
        
        if ($vendor_info && $user) {
            $vendorEmailHtml = "
                <h2>New Order Alert! 🛎️</h2>
                <p>Hi " . htmlspecialchars($vendor_info['owner_name']) . ",</p>
                <p>You have a new paid order at <strong>" . htmlspecialchars($vendor_info['shop_name']) . "</strong>.</p>
                <p><strong>Order #:</strong> {$order_id}<br><strong>Customer:</strong> " . htmlspecialchars($user['full_name']) . "<br><strong>Amount:</strong> ₦" . number_format($subtotal, 2) . "</p>
                <p>Please log in to your vendor dashboard to prepare this order.</p>
            ";
            sendCampusEmail($vendor_info['email'], $vendor_info['owner_name'], "🔔 New Paid Order Received! #{$order_id}", $vendorEmailHtml);
        }
        
        // In-app notification
        $notification = new Notification($pdo);
        $notification->create($user_id, "Order Placed Successfully!", "Your order #{$order_id} has been placed and is being processed.", 'order', "view_order.php?order_id={$order_id}");
        
        // ✅ Clear cart, promo, and distance sessions
        unset($_SESSION['cart'], $_SESSION['applied_promo'], $_SESSION['delivery_distance']);
        
        // Redirect to success page
        header("Location: order_success.php?order_id=" . $order_id);
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        // In production, you should log $e->getMessage() here
        error_log("Paystack Callback Error: " . $e->getMessage());
        header("Location: checkout.php?error=order_creation_failed");
        exit;
    }
} else {
    // Payment failed
    header("Location: checkout.php?error=payment_failed");
    exit;
}
?>