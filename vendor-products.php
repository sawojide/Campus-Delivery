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

// Handle Add/Edit Product
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_product'])) {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $image_url = trim($_POST['image_url']);
    
    try {
        if ($product_id > 0) {
            // Verify ownership
            $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND vendor_id = ?");
            $stmt->execute([$product_id, $vendor_id]);
            if ($stmt->fetch()) {
                $stmt = $pdo->prepare("UPDATE products SET name=?, description=?, price=?, stock=?, image_url=? WHERE id=?");
                $stmt->execute([$name, $description, $price, $stock, $image_url, $product_id]);
                $success = "Product updated successfully!";
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO products (vendor_id, name, description, price, stock, image_url) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$vendor_id, $name, $description, $price, $stock, $image_url]);
            $success = "Product added successfully!";
        }
    } catch (PDOException $e) {
        $error = "Error saving product.";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $product_id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND vendor_id = ?");
    $stmt->execute([$product_id, $vendor_id]);
    header("Location: vendor-products.php?success=deleted");
    exit;
}

// Get products
$stmt = $pdo->prepare("SELECT * FROM products WHERE vendor_id = ? ORDER BY id DESC");
$stmt->execute([$vendor_id]);
$products = $stmt->fetchAll();

// Get product for edit
$edit_product = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND vendor_id = ?");
    $stmt->execute([intval($_GET['edit']), $vendor_id]);
    $edit_product = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Vendor Panel</title>
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
                <a href="vendor-products.php" class="active"><i class="bi bi-box-seam"></i> Products</a>
                <a href="vendor-profile.php"><i class="bi bi-person"></i> Profile</a>
                <hr class="text-white my-3">
                <a href="vendor-logout.php" style="color: #ffc107;"><i class="bi bi-box-arrow-left"></i> Logout</a>
            </div>
        </div>
        
        <div class="col-md-10 p-4 bg-light">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-box-seam text-primary"></i> My Products</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal" onclick="clearProductForm()">
                    <i class="bi bi-plus-circle"></i> Add Product
                </button>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">Product deleted successfully!</div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <?php if (empty($products)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-box display-4"></i>
                            <p class="mt-3">No products yet. Click "Add Product" to get started!</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Image</th>
                                        <th>Name</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td><?= $product['id'] ?></td>
                                            <td><img src="<?= htmlspecialchars($product['image_url']) ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;"></td>
                                            <td>
                                                <strong><?= htmlspecialchars($product['name']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars(substr($product['description'], 0, 50)) ?>...</small>
                                            </td>
                                            <td><strong class="text-primary">₦<?= number_format($product['price']) ?></strong></td>
                                            <td>
                                                <?php if ($product['stock'] > 10): ?>
                                                    <span class="badge bg-success"><?= $product['stock'] ?></span>
                                                <?php elseif ($product['stock'] > 0): ?>
                                                    <span class="badge bg-warning"> <?= $product['stock'] ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Out of Stock</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $product['stock'] > 0 ? 'Available' : 'Unavailable' ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick='editProduct(<?= json_encode($product) ?>)' data-bs-toggle="modal" data-bs-target="#productModal">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <a href="vendor-products.php?delete=<?= $product['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this product?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
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

<!-- Product Modal -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="product_id" id="product_id">
                    <input type="hidden" name="save_product" value="1">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Product Name</label>
                            <input type="text" name="name" id="name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Price (₦)</label>
                            <input type="number" name="price" id="price" class="form-control" step="0.01" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Stock Quantity</label>
                            <input type="number" name="stock" id="stock" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Image URL</label>
                            <input type="url" name="image_url" id="image_url" class="form-control" placeholder="https://..." required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function clearProductForm() {
    document.getElementById('product_id').value = '';
    document.getElementById('name').value = '';
    document.getElementById('description').value = '';
    document.getElementById('price').value = '';
    document.getElementById('stock').value = '';
    document.getElementById('image_url').value = '';
    document.getElementById('modalTitle').textContent = 'Add Product';
}

function editProduct(product) {
    document.getElementById('product_id').value = product.id;
    document.getElementById('name').value = product.name;
    document.getElementById('description').value = product.description;
    document.getElementById('price').value = product.price;
    document.getElementById('stock').value = product.stock;
    document.getElementById('image_url').value = product.image_url;
    document.getElementById('modalTitle').textContent = 'Edit Product';
}
</script>
<script>
document.querySelectorAll('.toggle-availability').forEach(toggle => {
    toggle.addEventListener('change', function() {
        const productId = this.dataset.id;
        const label = this.nextElementSibling;
        const originalState = this.checked;
        
        // Optimistic UI update (makes it feel instant)
        label.innerHTML = originalState ? '<span class="text-success fw-bold">Active</span>' : '<span class="text-danger fw-bold">Hidden</span>';
        this.disabled = true; // Prevent double-clicking

        fetch('toggle_product.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `product_id=${productId}`
        })
        .then(res => res.json())
        .then(data => {
            this.disabled = false;
            if (!data.success) {
                // Revert if it failed
                this.checked = !originalState;
                label.innerHTML = originalState ? '<span class="text-danger fw-bold">Hidden</span>' : '<span class="text-success fw-bold">Active</span>';
                alert('Failed to update product status.');
            }
        })
        .catch(() => {
            this.disabled = false;
            this.checked = !originalState;
            label.innerHTML = originalState ? '<span class="text-danger fw-bold">Hidden</span>' : '<span class="text-success fw-bold">Active</span>';
            alert('Network error. Please try again.');
        });
    });
});
</script>
</body>
</html>