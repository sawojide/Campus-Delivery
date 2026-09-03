<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: admin-login.php");
    exit;
}

$success = "";
$error = "";

// Handle Add/Edit Vendor
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_vendor'])) {
    $vendor_id = isset($_POST['vendor_id']) ? intval($_POST['vendor_id']) : 0;
    $shop_name = trim($_POST['shop_name']);
    $owner_name = trim($_POST['owner_name']);
    $category = trim($_POST['category']);
    $location = trim($_POST['location']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    try {
        if ($vendor_id > 0) {
            // Update existing
            $stmt = $pdo->prepare("UPDATE vendors SET shop_name=?, owner_name=?, category=?, location=?, is_active=? WHERE id=?");
            $stmt->execute([$shop_name, $owner_name, $category, $location, $is_active, $vendor_id]);
            $success = "Vendor updated successfully!";
        } else {
            // Add new
            $stmt = $pdo->prepare("INSERT INTO vendors (shop_name, owner_name, category, location, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$shop_name, $owner_name, $category, $location, $is_active]);
            $success = "Vendor added successfully!";
        }
    } catch (PDOException $e) {
        $error = "Error saving vendor: " . $e->getMessage();
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $vendor_id = intval($_GET['delete']);
    try {
        $stmt = $pdo->prepare("DELETE FROM vendors WHERE id = ?");
        $stmt->execute([$vendor_id]);
        header("Location: admin-vendors.php?success=deleted");
        exit;
    } catch (PDOException $e) {
        $error = "Cannot delete vendor with existing orders.";
    }
}

// Get vendor for edit
$edit_vendor = null;
if (isset($_GET['edit'])) {
    $vendor_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM vendors WHERE id = ?");
    $stmt->execute([$vendor_id]);
    $edit_vendor = $stmt->fetch();
}

// Get all vendors
$vendors = $pdo->query("SELECT v.*, COUNT(p.id) as product_count FROM vendors v LEFT JOIN products p ON v.id = p.vendor_id GROUP BY v.id ORDER BY v.shop_name")->fetchAll();

$categories = ['Food', 'Perfumes', 'Cosmetics', 'Provisions', 'Snacks', 'Electronics', 'Books', 'Stationery', 'Health'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendors Management - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .sidebar { min-height: 100vh; background: linear-gradient(180deg, #dc3545 0%, #c82333 100%); }
        .sidebar a { color: white; text-decoration: none; padding: 12px 20px; display: block; border-left: 4px solid transparent; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.1); border-left-color: white; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'admin-sidebar.php'; ?>
        
        <div class="col-md-10 p-4 bg-light">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-shop text-danger"></i> Vendors Management</h2>
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#vendorModal" onclick="clearVendorForm()">
                    <i class="bi bi-plus-circle"></i> Add New Vendor
                </button>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Shop Name</th>
                                    <th>Owner</th>
                                    <th>Category</th>
                                    <th>Location</th>
                                    <th>Products</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vendors as $vendor): ?>
                                    <tr>
                                        <td><?= $vendor['id'] ?></td>
                                        <td><strong><?= htmlspecialchars($vendor['shop_name']) ?></strong></td>
                                        <td><?= htmlspecialchars($vendor['owner_name']) ?></td>
                                        <td><span class="badge bg-info"><?= htmlspecialchars($vendor['category']) ?></span></td>
                                        <td><?= htmlspecialchars($vendor['location']) ?></td>
                                        <td><span class="badge bg-secondary"><?= $vendor['product_count'] ?> products</span></td>
                                        <td>
                                            <?php if ($vendor['is_active']): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="editVendor(<?= htmlspecialchars(json_encode($vendor)) ?>)" data-bs-toggle="modal" data-bs-target="#vendorModal" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <a href="admin-vendors.php?delete=<?= $vendor['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this vendor?')" title="Delete">
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

<!-- Vendor Modal -->
<div class="modal fade" id="vendorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Vendor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="vendor_id" id="vendor_id">
                    <input type="hidden" name="save_vendor" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">Shop Name</label>
                        <input type="text" name="shop_name" id="shop_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Owner Name</label>
                        <input type="text" name="owner_name" id="owner_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category" id="category" class="form-select" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat ?>"><?= $cat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" id="location" class="form-control" required>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_active" id="is_active" class="form-check-input" checked>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Save Vendor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function clearVendorForm() {
    document.getElementById('vendor_id').value = '';
    document.getElementById('shop_name').value = '';
    document.getElementById('owner_name').value = '';
    document.getElementById('category').value = 'Food';
    document.getElementById('location').value = '';
    document.getElementById('is_active').checked = true;
    document.getElementById('modalTitle').textContent = 'Add Vendor';
}

function editVendor(vendor) {
    document.getElementById('vendor_id').value = vendor.id;
    document.getElementById('shop_name').value = vendor.shop_name;
    document.getElementById('owner_name').value = vendor.owner_name;
    document.getElementById('category').value = vendor.category;
    document.getElementById('location').value = vendor.location;
    document.getElementById('is_active').checked = vendor.is_active == 1;
    document.getElementById('modalTitle').textContent = 'Edit Vendor';
}
</script>
</body>
</html>