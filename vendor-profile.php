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

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $shop_name = trim($_POST['shop_name']);
    $owner_name = trim($_POST['owner_name']);
    $phone = trim($_POST['phone']);
    $location = trim($_POST['location']);
    $account_name = trim($_POST['account_name']);
    $account_number = trim($_POST['account_number']);
    $bank_name = trim($_POST['bank_name']);
    $new_password = trim($_POST['new_password']);
    
    try {
        if (!empty($new_password)) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE vendors SET shop_name=?, owner_name=?, phone=?, location=?, account_name=?, account_number=?, bank_name=?, password=? WHERE id=?");
            $stmt->execute([$shop_name, $owner_name, $phone, $location, $account_name, $account_number, $bank_name, $hashed, $vendor_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE vendors SET shop_name=?, owner_name=?, phone=?, location=?, account_name=?, account_number=?, bank_name=? WHERE id=?");
            $stmt->execute([$shop_name, $owner_name, $phone, $location, $account_name, $account_number, $bank_name, $vendor_id]);
        }
        $success = "Profile updated successfully!";
        $_SESSION['vendor_name'] = $shop_name;
        
        // Refresh vendor data
        $stmt = $pdo->prepare("SELECT * FROM vendors WHERE id = ?");
        $stmt->execute([$vendor_id]);
        $vendor = $stmt->fetch();
    } catch (PDOException $e) {
        $error = "Error updating profile: " . $e->getMessage();
    }
}

// Get vendor info
$stmt = $pdo->prepare("SELECT * FROM vendors WHERE id = ?");
$stmt->execute([$vendor_id]);
$vendor = $stmt->fetch();

// Get current balance for display
$current_balance = $vendor['wallet_balance'] ?? 0.00;

// --- Calculate Statistics at the top ---
$stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE vendor_id = ?");
$stmt->execute([$vendor_id]);
$total_products = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE vendor_id = ? AND status = 'completed'");
$stmt->execute([$vendor_id]);
$completed_orders = $stmt->fetchColumn();
// ----------------------------------------
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Vendor Panel</title>
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
                <a href="vendor-orders.php"><i class="bi bi-cart3"></i> Orders</a>
                <a href="vendor-products.php"><i class="bi bi-box-seam"></i> Products</a>
                <a href="vendor-profile.php" class="active"><i class="bi bi-person"></i> Profile</a>
                <hr class="text-white my-3">
                <a href="vendor-logout.php" class="logout-link"><i class="bi bi-box-arrow-left"></i> Logout</a>
            </div>
        </div>
        
        <div class="col-md-10 p-4 bg-light">
            <h2 class="mb-4"><i class="bi bi-person text-primary"></i> Shop Profile</h2>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <!-- Available Balance Display -->
                    <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-light rounded border">
                        <div>
                            <h6 class="text-muted mb-1 fw-bold">Available Balance for Withdrawal</h6>
                            <h2 class="text-success mb-0">₦<?= number_format($current_balance, 2) ?></h2>
                        </div>
                        <a href="vendor-withdraw.php" class="btn btn-primary">
                            <i class="bi bi-cash-stack"></i> Request Withdrawal
                        </a>
                    </div>

                    <form method="POST">
                        <h5 class="mb-3"><i class="bi bi-shop"></i> Basic Information</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Shop Name</label>
                                <input type="text" name="shop_name" class="form-control" value="<?= htmlspecialchars($vendor['shop_name']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Owner Name</label>
                                <input type="text" name="owner_name" class="form-control" value="<?= htmlspecialchars($vendor['owner_name']) ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="<?= htmlspecialchars($vendor['email']) ?>" disabled>
                                <small class="text-muted">Email cannot be changed</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($vendor['phone'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($vendor['location'] ?? '') ?>" required>
                        </div>
                        
                        <hr class="my-4">
                        <h5 class="mb-3"><i class="bi bi-bank"></i> Bank Details for Payouts</h5>
                        <div class="mb-3">
                            <label class="form-label">Bank Name</label>
                            <input type="text" name="bank_name" class="form-control" value="<?= htmlspecialchars($vendor['bank_name'] ?? '') ?>" placeholder="e.g., First Bank">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Account Number</label>
                                <input type="text" name="account_number" class="form-control" value="<?= htmlspecialchars($vendor['account_number'] ?? '') ?>" placeholder="0123456789">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Account Name</label>
                                <input type="text" name="account_name" class="form-control" value="<?= htmlspecialchars($vendor['account_name'] ?? '') ?>" placeholder="John Doe">
                            </div>
                        </div>

                        <hr class="my-4">
                        <h5 class="mb-3"><i class="bi bi-shield-lock"></i> Security</h5>
                        <div class="mb-4">
                            <label class="form-label">New Password (leave blank to keep current)</label>
                            <input type="password" name="new_password" class="form-control" placeholder="Enter new password">
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-check-circle"></i> Update Profile
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Shop Statistics -->
            <div class="row mt-4 g-3">
                <div class="col-md-4">
                    <div class="card bg-primary text-white border-0 shadow-sm">
                        <div class="card-body text-center">
                            <h3><?= $total_products ?></h3>
                            <p class="mb-0">Total Products</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white border-0 shadow-sm">
                        <div class="card-body text-center">
                            <h3><?= $completed_orders ?></h3>
                            <p class="mb-0">Completed Orders</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-info text-white border-0 shadow-sm">
                        <div class="card-body text-center">
                            <h3><?= htmlspecialchars($vendor['category'] ?? 'N/A') ?></h3>
                            <p class="mb-0">Category</p>
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