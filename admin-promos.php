<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: admin-login.php");
    exit;
}

$success = "";
$error = "";

// Handle Add Promo
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_promo'])) {
    $code = strtoupper(trim($_POST['code']));
    $type = $_POST['type'];
    $value = floatval($_POST['value']);
    $min_order = floatval($_POST['min_order']);
    $max_uses = intval($_POST['max_uses']);
    $expires = !empty($_POST['expires']) ? $_POST['expires'] : null;
    $applicable_to = $_POST['applicable_to'];
    $product_ids = isset($_POST['product_ids']) ? $_POST['product_ids'] : [];

    try {
        $pdo->beginTransaction();
        
        // Insert promo code
        $stmt = $pdo->prepare("INSERT INTO promo_codes (code, type, value, min_order, max_uses, expires_at, applicable_to) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$code, $type, $value, $min_order, $max_uses, $expires, $applicable_to]);
        $promo_id = $pdo->lastInsertId();
        
        // Insert product relationships if specific products selected
        if ($applicable_to == 'specific' && !empty($product_ids)) {
            $stmt = $pdo->prepare("INSERT INTO promo_products (promo_id, product_id) VALUES (?, ?)");
            foreach ($product_ids as $product_id) {
                $stmt->execute([$promo_id, $product_id]);
            }
        }
        
        $pdo->commit();
        $success = "Promo code '$code' created successfully!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error: Promo code already exists or invalid data.";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $pdo->prepare("DELETE FROM promo_codes WHERE id = ?")->execute([$id]);
    header("Location: admin-promos.php?success=deleted");
    exit;
}

if (isset($_GET['success']) && $_GET['success'] == 'deleted') {
    $success = "Promo code deleted successfully.";
}

// Fetch all promos with product count
$promos = $pdo->query("
    SELECT p.*, 
    (SELECT COUNT(*) FROM promo_products pp WHERE pp.promo_id = p.id) as product_count
    FROM promo_codes p 
    ORDER BY p.created_at DESC
")->fetchAll();

// Fetch all products for the dropdown
$products = $pdo->query("SELECT id, name, price FROM products ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promo Codes - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .sidebar { min-height: 100vh; background: linear-gradient(180deg, #dc3545 0%, #c82333 100%); }
        .sidebar a { color: white; text-decoration: none; padding: 12px 20px; display: block; border-left: 4px solid transparent; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.1); border-left-color: white; }
        .product-select-box { max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 5px; }
        .product-checkbox { margin-bottom: 8px; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'admin-sidebar.php'; ?>
        
        <div class="col-md-10 p-4 bg-light">
            <h2 class="mb-4"><i class="bi bi-tag text-warning"></i> Promo Codes Management</h2>
            
            <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            
            <div class="row">
                <!-- Create Promo Form -->
                <div class="col-lg-5 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white"><h6 class="mb-0 fw-bold">Create New Promo</h6></div>
                        <div class="card-body">
                            <form method="POST" id="promoForm">
                                <div class="mb-3">
                                    <label class="form-label">Promo Code</label>
                                    <input type="text" name="code" class="form-control text-uppercase" required placeholder="e.g., CAMPUS20">
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <label class="form-label">Type</label>
                                        <select name="type" class="form-select" id="promoType">
                                            <option value="percentage">Percentage (%)</option>
                                            <option value="fixed">Fixed Amount (₦)</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Value</label>
                                        <input type="number" name="value" class="form-control" required placeholder="20">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Minimum Order (₦)</label>
                                    <input type="number" name="min_order" class="form-control" value="0">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Max Uses (0 = Unlimited)</label>
                                    <input type="number" name="max_uses" class="form-control" value="0">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Applicable To</label>
                                    <select name="applicable_to" class="form-select" id="applicableTo" onchange="toggleProductSelection()">
                                        <option value="all">All Products</option>
                                        <option value="specific">Specific Products</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3" id="productSelection" style="display: none;">
                                    <label class="form-label">Select Products</label>
                                    <div class="product-select-box">
                                        <?php foreach ($products as $product): ?>
                                            <div class="product-checkbox">
                                                <input type="checkbox" name="product_ids[]" value="<?= $product['id'] ?>" id="prod_<?= $product['id'] ?>">
                                                <label for="prod_<?= $product['id'] ?>" class="ms-2">
                                                    <?= htmlspecialchars($product['name']) ?> - ₦<?= number_format($product['price']) ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <small class="text-muted">Select products this promo applies to</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Expiry Date (Optional)</label>
                                    <input type="datetime-local" name="expires" class="form-control">
                                </div>
                                
                                <button type="submit" name="add_promo" class="btn btn-danger w-100">Create Promo</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Promos List -->
                <div class="col-lg-7">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white"><h6 class="mb-0 fw-bold">Active Promos</h6></div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Code</th>
                                            <th>Discount</th>
                                            <th>Applies To</th>
                                            <th>Min Order</th>
                                            <th>Uses</th>
                                            <th>Expires</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($promos as $promo): ?>
                                            <tr>
                                                <td><strong class="text-danger"><?= htmlspecialchars($promo['code']) ?></strong></td>
                                                <td>
                                                    <?php if ($promo['type'] == 'percentage'): ?>
                                                        <?= $promo['value'] ?>%
                                                    <?php else: ?>
                                                        ₦<?= number_format($promo['value']) ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($promo['applicable_to'] == 'all'): ?>
                                                        <span class="badge bg-success">All Products</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-info"><?= $promo['product_count'] ?> Products</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>₦<?= number_format($promo['min_order']) ?></td>
                                                <td>
                                                    <?= $promo['current_uses'] ?> 
                                                    <?php if ($promo['max_uses'] > 0): ?> / <?= $promo['max_uses'] ?><?php endif; ?>
                                                </td>
                                                <td>
                                                    <?= $promo['expires_at'] ? date('M d, Y', strtotime($promo['expires_at'])) : 'Never' ?>
                                                </td>
                                                <td>
                                                    <a href="admin-promos.php?delete=<?= $promo['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this promo?')">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleProductSelection() {
    const applicableTo = document.getElementById('applicableTo').value;
    const productSelection = document.getElementById('productSelection');
    productSelection.style.display = applicableTo === 'specific' ? 'block' : 'none';
}
</script>
</body>
</html>