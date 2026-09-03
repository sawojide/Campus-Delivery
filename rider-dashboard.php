<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'rider') {
    header("Location: rider-login.php");
    exit;
}

$rider_id = $_SESSION['user_id'];

// Get statistics
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE rider_id = ? AND status = 'delivering'");
$stmt->execute([$rider_id]);
$active_deliveries = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE status = 'ready'");
$stmt->execute([]);
$available_orders = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE rider_id = ? AND status = 'completed'");
$stmt->execute([$rider_id]);
$completed_deliveries = $stmt->fetchColumn();

// Calculate earnings (e.g., ₦200 per completed delivery)
$earnings = $completed_deliveries * 200; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rider Dashboard - Campus Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
   <style>
    .sidebar { 
        min-height: 100vh; 
        background: linear-gradient(180deg, #0d8568 0%, #0a6b54 100%);
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
        background: rgba(220, 53, 69, 0.9);
        border-radius: 8px;
        margin: 0 15px;
        padding: 12px 20px !important;
        font-weight: 600;
        border: 2px solid rgba(255,255,255,0.3);
        transition: all 0.3s ease;
    }
    .sidebar .logout-link:hover {
        background: #dc3545;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
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
            <h4 class="text-white p-4 mb-0"><i class="bi bi-bicycle"></i> Rider Panel</h4>
            <div class="mt-4">
                <a href="rider-dashboard.php" class="active"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a href="rider-orders.php"><i class="bi bi-geo-alt"></i> Deliveries</a>
                <hr class="text-white my-3">
                <a href="index.php"><i class="bi bi-house"></i> Public Site</a>
                <a href="rider-logout.php" style="color: #ffc107;"><i class="bi bi-box-arrow-left"></i> Logout</a>
            </div>
        </div>
        
        <div class="col-md-10 p-4 bg-light">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-speedometer2 text-success"></i> Rider Dashboard</h2>
                <div><span class="text-muted">Welcome, </span><strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong></div>
            </div>
            
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 border-start border-4 border-warning">
                        <div class="card-body">
                            <h6 class="text-muted">Available Orders</h6>
                            <h3 class="mb-0"><?= $available_orders ?></h3>
                            <small class="text-muted">Ready for pickup</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 border-start border-4 border-primary">
                        <div class="card-body">
                            <h6 class="text-muted">Active Deliveries</h6>
                            <h3 class="mb-0"><?= $active_deliveries ?></h3>
                            <small class="text-muted">On the way</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 border-start border-4 border-success">
                        <div class="card-body">
                            <h6 class="text-muted">Completed</h6>
                            <h3 class="mb-0"><?= $completed_deliveries ?></h3>
                            <small class="text-muted">Total trips</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 border-start border-4 border-info">
                        <div class="card-body">
                            <h6 class="text-muted">Estimated Earnings</h6>
                            <h3 class="mb-0">₦<?= number_format($earnings) ?></h3>
                            <small class="text-muted">Based on completed trips</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> Go to the <strong>Deliveries</strong> page to claim available orders or complete your active trips!
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>