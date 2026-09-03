<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: admin-login.php");
    exit;
}

$success = "";
$error = "";

// Handle Add/Edit Category
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_category'])) {
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $icon = trim($_POST['icon']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    try {
        if ($category_id > 0) {
            // Update existing
            $stmt = $pdo->prepare("UPDATE categories SET name=?, description=?, icon=?, is_active=? WHERE id=?");
            $stmt->execute([$name, $description, $icon, $is_active, $category_id]);
            $success = "Category updated successfully!";
        } else {
            // Add new
            $stmt = $pdo->prepare("INSERT INTO categories (name, description, icon, is_active) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $description, $icon, $is_active]);
            $success = "Category created successfully!";
        }
    } catch (PDOException $e) {
        $error = "Error saving category: " . $e->getMessage();
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $category_id = intval($_GET['delete']);
    try {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);
        header("Location: admin-categories.php?success=deleted");
        exit;
    } catch (PDOException $e) {
        $error = "Cannot delete category with existing products.";
    }
}

// Get category for edit
$edit_category = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([intval($_GET['edit'])]);
    $edit_category = $stmt->fetch();
}

// Get all categories with product counts
$categories = $pdo->query("
    SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    GROUP BY c.id 
    ORDER BY c.name
")->fetchAll();

// Available Bootstrap Icons
$icons = [
    'bi-box', 'bi-cup-hot', 'bi-droplet', 'bi-box-seam', 'bi-cup-straw',
    'bi-phone', 'bi-book', 'bi-capsule', 'bi-activity', 'bi-fire',
    'bi-bag', 'bi-cart', 'bi-bicycle', 'bi-shop', 'bi-briefcase'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .sidebar { min-height: 100vh; background: linear-gradient(180deg, #dc3545 0%, #c82333 100%); }
        .sidebar a { color: white; text-decoration: none; padding: 12px 20px; display: block; border-left: 4px solid transparent; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.1); border-left-color: white; }
        .category-card { transition: all 0.3s; }
        .category-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'admin-sidebar.php'; ?>
        
        <div class="col-md-10 p-4 bg-light">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-tags text-danger"></i> Category Management</h2>
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#categoryModal" onclick="clearCategoryForm()">
                    <i class="bi bi-plus-circle"></i> Add New Category
                </button>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">Category deleted successfully!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            
            <!-- Statistics -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h3><?= count($categories) ?></h3>
                            <p class="mb-0">Total Categories</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h3><?= $pdo->query("SELECT COUNT(*) FROM categories WHERE is_active=1")->fetchColumn() ?></h3>
                            <p class="mb-0">Active Categories</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h3><?= $pdo->query("SELECT COUNT(*) FROM products WHERE category_id IS NOT NULL")->fetchColumn() ?></h3>
                            <p class="mb-0">Categorized Products</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-dark">
                        <div class="card-body">
                            <h3><?= $pdo->query("SELECT COUNT(*) FROM products WHERE category_id IS NULL")->fetchColumn() ?></h3>
                            <p class="mb-0">Uncategorized Products</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Categories Grid -->
            <div class="row g-4">
                <?php foreach ($categories as $category): ?>
                    <div class="col-md-4 col-lg-3">
                        <div class="card category-card h-100 <?= $category['is_active'] ? '' : 'opacity-50' ?>">
                            <div class="card-body text-center p-4">
                                <i class="bi <?= htmlspecialchars($category['icon']) ?> display-4 text-danger mb-3"></i>
                                <h5 class="card-title"><?= htmlspecialchars($category['name']) ?></h5>
                                <p class="card-text text-muted small"><?= htmlspecialchars($category['description']) ?></p>
                                <div class="mb-3">
                                    <span class="badge bg-<?= $category['is_active'] ? 'success' : 'secondary' ?>">
                                        <?= $category['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                    <span class="badge bg-primary"><?= $category['product_count'] ?> Products</span>
                                </div>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary" onclick='editCategory(<?= htmlspecialchars(json_encode($category)) ?>)' data-bs-toggle="modal" data-bs-target="#categoryModal">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                    <?php if ($category['product_count'] == 0): ?>
                                        <a href="admin-categories.php?delete=<?= $category['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this category?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Category Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="category_id" id="category_id">
                    <input type="hidden" name="save_category" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">Category Name *</label>
                        <input type="text" name="name" id="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Icon</label>
                        <select name="icon" id="icon" class="form-select">
                            <?php foreach ($icons as $icon): ?>
                                <option value="<?= $icon ?>"><?= $icon ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Bootstrap Icon name (e.g., bi-box, bi-fire)</small>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_active" id="is_active" class="form-check-input" checked>
                        <label class="form-check-label" for="is_active">Active Category</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function clearCategoryForm() {
    document.getElementById('category_id').value = '';
    document.getElementById('name').value = '';
    document.getElementById('description').value = '';
    document.getElementById('icon').value = 'bi-box';
    document.getElementById('is_active').checked = true;
    document.getElementById('modalTitle').textContent = 'Add Category';
}

function editCategory(category) {
    document.getElementById('category_id').value = category.id;
    document.getElementById('name').value = category.name;
    document.getElementById('description').value = category.description || '';
    document.getElementById('icon').value = category.icon;
    document.getElementById('is_active').checked = category.is_active == 1;
    document.getElementById('modalTitle').textContent = 'Edit Category';
}
</script>
</body>
</html>