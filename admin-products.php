<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: admin-login.php");
    exit;
}

$success = "";
$error = "";

// Handle Add/Edit Product
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_product'])) {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $vendor_id = intval($_POST['vendor_id']);
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $image_url = trim($_POST['image_url']);
    $current_image = trim($_POST['current_image'] ?? '');
    
    // Handle Image Upload
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/products/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Get file info
        $file = $_FILES['product_image'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
        
        // Validate file
        if (in_array($file_ext, $allowed_ext)) {
            if ($file['size'] <= 2000000) { // 2MB max
                // Generate unique filename
                $new_filename = uniqid('product_') . '.' . $file_ext;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $image_url = $upload_path;
                } else {
                    $error = "Failed to upload image.";
                }
            } else {
                $error = "Image size must be less than 2MB.";
            }
        } else {
            $error = "Only JPG, PNG, and WEBP images are allowed.";
        }
    } elseif (empty($image_url) && !empty($current_image)) {
        // Keep existing image if no new upload and no new URL
        $image_url = $current_image;
    }
    
    if (empty($error)) {
        try {
            if ($product_id > 0) {
                // Update existing product
                $stmt = $pdo->prepare("UPDATE products SET vendor_id=?, name=?, description=?, price=?, stock=?, image_url=? WHERE id=?");
                $stmt->execute([$vendor_id, $name, $description, $price, $stock, $image_url, $product_id]);
                $success = "Product updated successfully!";
            } else {
                // Insert new product
                $stmt = $pdo->prepare("INSERT INTO products (vendor_id, name, description, price, stock, image_url) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$vendor_id, $name, $description, $price, $stock, $image_url]);
                $success = "Product added successfully!";
            }
        } catch (PDOException $e) {
            $error = "Error saving product: " . $e->getMessage();
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $product_id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    header("Location: admin-products.php?success=deleted");
    exit;
}

// Get product for edit
$edit_product = null;
if (isset($_GET['edit'])) {
    $product_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $edit_product = $stmt->fetch();
}

// Get filters
$vendor_filter = isset($_GET['vendor']) ? intval($_GET['vendor']) : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query (p.* automatically fetches the new is_available column)
$sql = "SELECT p.*, v.shop_name FROM products p JOIN vendors v ON p.vendor_id = v.id WHERE 1=1";
$params = [];

if ($vendor_filter) {
    $sql .= " AND p.vendor_id = ?";
    $params[] = $vendor_filter;
}

if ($search) {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
}

$sql .= " ORDER BY p.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$vendors = $pdo->query("SELECT id, shop_name FROM vendors ORDER BY shop_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management - Admin Panel</title>
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
                <h2><i class="bi bi-box-seam text-danger"></i> Products Management</h2>
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#productModal" onclick="clearProductForm()">
                    <i class="bi bi-plus-circle"></i> Add New Product
                </button>
            </div>
            
            <?php if (isset($_GET['success']) && $_GET['success'] == 'deleted'): ?>
                <div class="alert alert-success alert-dismissible fade show">Product deleted successfully!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            
            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <select name="vendor" class="form-select">
                                <option value="0">All Vendors</option>
                                <?php foreach ($vendors as $v): ?>
                                    <option value="<?= $v['id'] ?>" <?= $vendor_filter == $v['id'] ? 'selected' : '' ?>><?= htmlspecialchars($v['shop_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Search products..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-danger"><i class="bi bi-search"></i> Filter</button>
                            <a href="admin-products.php" class="btn btn-outline-secondary">Clear</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Vendor</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Availability</th> <!-- ✅ New Column -->
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td><?= $product['id'] ?></td>
                                        <td> <img src="<?= htmlspecialchars($product['image_url']) ?>" alt=""  style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;" onerror="this.src='https://via.placeholder.com/50?text=No+Image'"></td>
                                        <td>
                                            <strong><?= htmlspecialchars($product['name']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars(substr($product['description'], 0, 50)) ?>...</small>
                                        </td>
                                        <td><?= htmlspecialchars($product['shop_name']) ?></td>
                                        <td><strong class="text-danger">₦<?= number_format($product['price']) ?></strong></td>
                                        <td>
                                            <?php if ($product['stock'] > 10): ?>
                                                <span class="badge bg-success"><?= $product['stock'] ?> in stock</span>
                                            <?php elseif ($product['stock'] > 0): ?>
                                                <span class="badge bg-warning">Low stock (<?= $product['stock'] ?>)</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Out of stock</span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <!-- ✅ Toggle Switch -->
                                        <td>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input toggle-availability" type="checkbox" 
                                                       role="switch" 
                                                       data-id="<?= $product['id'] ?>" 
                                                       <?= ($product['is_available'] ?? 1) ? 'checked' : '' ?>>
                                                <label class="form-check-label small">
                                                    <?= ($product['is_available'] ?? 1) ? '<span class="text-success fw-bold">Active</span>' : '<span class="text-danger fw-bold">Hidden</span>' ?>
                                                </label>
                                            </div>
                                        </td>
                                        
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="editProduct(<?= htmlspecialchars(json_encode($product)) ?>)" data-bs-toggle="modal" data-bs-target="#productModal" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <a href="admin-products.php?delete=<?= $product['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this product?')" title="Delete">
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

<!-- Product Modal -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="product_id" id="product_id">
                    <input type="hidden" name="save_product" value="1">
                    <input type="hidden" name="current_image" id="current_image">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Product Name</label>
                            <input type="text" name="name" id="name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Vendor</label>
                            <select name="vendor_id" id="vendor_id" class="form-select" required>
                                <?php foreach ($vendors as $v): ?>
                                    <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['shop_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Price (₦)</label>
                            <input type="number" name="price" id="price" class="form-control" step="0.01" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Stock Quantity</label>
                            <input type="number" name="stock" id="stock" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Current Image</label>
                            <div id="current_image_preview" class="mb-2">
                                <!-- Preview will be shown here -->
                            </div>
                        </div>
                    </div>
                    
                    <!-- Image Upload Section -->
                    <div class="mb-3">
                        <label class="form-label">Upload Product Image</label>
                        <input type="file" name="product_image" id="product_image" class="form-control" accept="image/*">
                        <small class="text-muted">Allowed: JPG, PNG, WEBP. Max size: 2MB</small>
                        <div id="image_preview" class="mt-2"></div>
                    </div>
                    
                    <!-- OR Use URL -->
                    <div class="mb-3">
                        <label class="form-label">OR Enter Image URL</label>
                        <input type="url" name="image_url" id="image_url" class="form-control" placeholder="https://example.com/image.jpg">
                        <small class="text-muted">Leave blank if uploading a file above</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Save Product</button>
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
    document.getElementById('vendor_id').value = '<?= $vendors[0]['id'] ?? '' ?>';
    document.getElementById('description').value = '';
    document.getElementById('price').value = '';
    document.getElementById('stock').value = '';
    document.getElementById('image_url').value = '';
    document.getElementById('current_image').value = '';
    document.getElementById('product_image').value = '';
    document.getElementById('current_image_preview').innerHTML = '';
    document.getElementById('image_preview').innerHTML = '';
    document.getElementById('modalTitle').textContent = 'Add Product';
}

function editProduct(product) {
    document.getElementById('product_id').value = product.id;
    document.getElementById('name').value = product.name;
    document.getElementById('vendor_id').value = product.vendor_id;
    document.getElementById('description').value = product.description;
    document.getElementById('price').value = product.price;
    document.getElementById('stock').value = product.stock;
    document.getElementById('image_url').value = '';
    document.getElementById('current_image').value = product.image_url;
    document.getElementById('product_image').value = '';
    
    // Show current image preview
    const previewDiv = document.getElementById('current_image_preview');
    if (product.image_url) {
        previewDiv.innerHTML = `
            <div class="position-relative d-inline-block">
                <img src="<?= htmlspecialchars($product['image_url']) ?>" 
                     alt="Current Image" 
                     class="img-thumbnail" 
                     style="max-width: 150px; max-height: 100px;">
                <span class="badge bg-secondary position-absolute top-0 start-0">Current</span>
            </div>
        `;
    } else {
        previewDiv.innerHTML = '<span class="text-muted">No image</span>';
    }
    
    document.getElementById('image_preview').innerHTML = '';
    document.getElementById('modalTitle').textContent = 'Edit Product';
}

// Image upload preview
document.getElementById('product_image')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    const previewDiv = document.getElementById('image_preview');
    
    if (file) {
        if (file.size > 2000000) {
            alert('File size must be less than 2MB');
            this.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            previewDiv.innerHTML = `
                <div class="position-relative d-inline-block">
                    <img src="${e.target.result}" 
                         alt="Preview" 
                         class="img-thumbnail" 
                         style="max-width: 150px; max-height: 100px;">
                    <span class="badge bg-success position-absolute top-0 start-0">New</span>
                </div>
            `;
            // Clear URL field when uploading file
            document.getElementById('image_url').value = '';
        };
        reader.readAsDataURL(file);
    } else {
        previewDiv.innerHTML = '';
    }
});

// AJAX Toggle Availability Logic
document.querySelectorAll('.toggle-availability').forEach(toggle => {
    toggle.addEventListener('change', function() {
        const productId = this.dataset.id;
        const label = this.nextElementSibling;
        const originalState = this.checked;
        
        // Optimistic UI update
        label.innerHTML = originalState ? '<span class="text-success fw-bold">Active</span>' : '<span class="text-danger fw-bold">Hidden</span>';
        this.disabled = true;

        fetch('toggle_product.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `product_id=${productId}`
        })
        .then(res => res.json())
        .then(data => {
            this.disabled = false;
            if (!data.success) {
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