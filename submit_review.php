<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$message = "";
$message_type = "";

// Verify order belongs to user and is completed
$stmt = $pdo->prepare("
    SELECT o.id, o.status, o.vendor_id, v.shop_name 
    FROM orders o 
    JOIN vendors v ON o.vendor_id = v.id 
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: order_history.php");
    exit;
}

if ($order['status'] != 'completed') {
    $message = "You can only review completed orders.";
    $message_type = "warning";
}

// Check if already reviewed
$stmt = $pdo->prepare("SELECT id FROM reviews WHERE order_id = ?");
$stmt->execute([$order_id]);
if ($stmt->fetch()) {
    $message = "You have already reviewed this order.";
    $message_type = "info";
}

// Handle Review Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $order['status'] == 'completed') {
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);
    
    if ($rating >= 1 && $rating <= 5) {
        try {
            $pdo->beginTransaction();
            
            // 1. Insert review
            $stmt = $pdo->prepare("INSERT INTO reviews (order_id, user_id, vendor_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$order_id, $user_id, $order['vendor_id'], $rating, $comment]);
            
            // 2. Update vendor's average rating and total reviews
            $stmt = $pdo->prepare("
                UPDATE vendors 
                SET total_reviews = total_reviews + 1,
                    average_rating = (
                        SELECT AVG(rating) FROM reviews WHERE vendor_id = ?
                    )
                WHERE id = ?
            ");
            $stmt->execute([$order['vendor_id'], $order['vendor_id']]);
            
            $pdo->commit();
            $message = "Thank you! Your review has been submitted.";
            $message_type = "success";
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Error submitting review: " . $e->getMessage();
            $message_type = "danger";
        }
    } else {
        $message = "Please select a valid rating (1-5 stars).";
        $message_type = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave a Review - Campus Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .star-rating { direction: rtl; display: inline-flex; font-size: 2rem; }
        .star-rating input { display: none; }
        .star-rating label { color: #ccc; cursor: pointer; transition: color 0.2s; }
        .star-rating input:checked ~ label, .star-rating label:hover, .star-rating label:hover ~ label { color: #ffc107; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-danger">
    <div class="container">
        <a href="order_history.php" class="navbar-brand mb-0 h1"><i class="bi bi-arrow-left"></i> Back to Orders</a>
    </div>
</nav>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow border-0">
                <div class="card-body p-5 text-center">
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $message_type ?> mb-4"><?= htmlspecialchars($message) ?></div>
                        <?php if ($message_type == 'success' || $message_type == 'info'): ?>
                            <a href="order_history.php" class="btn btn-primary">Back to Order History</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <i class="bi bi-star-fill text-warning display-1 mb-3"></i>
                        <h3 class="mb-2">How was your order?</h3>
                        <p class="text-muted mb-4">You ordered from <strong><?= htmlspecialchars($order['shop_name']) ?></strong></p>
                        
                        <form method="POST">
                            <div class="mb-4">
                                <label class="form-label fw-bold d-block">Your Rating</label>
                                <div class="star-rating justify-content-center">
                                    <input type="radio" id="star5" name="rating" value="5" required><label for="star5" title="5 stars"><i class="bi bi-star-fill"></i></label>
                                    <input type="radio" id="star4" name="rating" value="4"><label for="star4" title="4 stars"><i class="bi bi-star-fill"></i></label>
                                    <input type="radio" id="star3" name="rating" value="3"><label for="star3" title="3 stars"><i class="bi bi-star-fill"></i></label>
                                    <input type="radio" id="star2" name="rating" value="2"><label for="star2" title="2 stars"><i class="bi bi-star-fill"></i></label>
                                    <input type="radio" id="star1" name="rating" value="1"><label for="star1" title="1 star"><i class="bi bi-star-fill"></i></label>
                                </div>
                            </div>
                            
                            <div class="mb-4 text-start">
                                <label class="form-label fw-bold">Your Review (Optional)</label>
                                <textarea name="comment" class="form-control" rows="4" placeholder="Tell us about your experience with the food and delivery..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-danger btn-lg w-100">
                                <i class="bi bi-send"></i> Submit Review
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>