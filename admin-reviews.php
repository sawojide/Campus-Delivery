<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: admin-login.php");
    exit;
}

$success = "";
$error = "";

// Handle Bulk Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_action'])) {
    $review_ids = $_POST['review_ids'] ?? [];
    if (!empty($review_ids)) {
        try {
            $placeholders = implode(',', array_fill(0, count($review_ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM reviews WHERE id IN ($placeholders)");
            $stmt->execute($review_ids);
            $success = count($review_ids) . " review(s) deleted successfully.";
        } catch (PDOException $e) {
            $error = "Error deleting reviews.";
        }
    }
}

// Handle Single Delete
if (isset($_GET['delete'])) {
    $review_id = intval($_GET['delete']);
    try {
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->execute([$review_id]);
        $success = "Review deleted successfully.";
    } catch (PDOException $e) {
        $error = "Error deleting review.";
    }
}

// Get Filters
$search = $_GET['search'] ?? '';
$rating_filter = $_GET['rating'] ?? '';
$vendor_filter = $_GET['vendor'] ?? '';

// Build Query
$sql = "SELECT r.*, u.full_name, u.email, v.shop_name 
        FROM reviews r 
        JOIN users u ON r.user_id = u.id 
        JOIN vendors v ON r.vendor_id = v.id 
        WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (u.full_name LIKE ? OR v.shop_name LIKE ? OR r.comment LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($rating_filter) {
    $sql .= " AND r.rating = ?";
    $params[] = $rating_filter;
}

if ($vendor_filter) {
    $sql .= " AND r.vendor_id = ?";
    $params[] = $vendor_filter;
}

$sql .= " ORDER BY r.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll();

// Get Statistics
$stats = [];
$stats['total'] = $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
$stats['avg_rating'] = $pdo->query("SELECT AVG(rating) FROM reviews")->fetchColumn() ?: 0;
$stats['five_star'] = $pdo->query("SELECT COUNT(*) FROM reviews WHERE rating = 5")->fetchColumn();
$stats['one_star'] = $pdo->query("SELECT COUNT(*) FROM reviews WHERE rating = 1")->fetchColumn();

// Get Vendors for Filter
$vendors = $pdo->query("SELECT id, shop_name FROM vendors ORDER BY shop_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Review Management - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .sidebar { min-height: 100vh; background: linear-gradient(180deg, #dc3545 0%, #c82333 100%); }
        .sidebar a { color: white; text-decoration: none; padding: 12px 20px; display: block; border-left: 4px solid transparent; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.1); border-left-color: white; }
        .stat-box { background: white; border-radius: 10px; padding: 20px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .stat-box h3 { margin: 0; font-size: 2rem; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'admin-sidebar.php'; ?>
        
        <div class="col-md-10 p-4 bg-light">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-star-fill text-warning"></i> Review Management</h2>
                <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#bulkActionModal" id="bulkBtn" disabled>
                    <i class="bi bi-trash"></i> Bulk Delete (<span id="selectedCount">0</span>)
                </button>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            
            <!-- Statistics Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-box border-start border-4 border-primary">
                        <h3><?= number_format($stats['total']) ?></h3>
                        <p class="mb-0 text-muted">Total Reviews</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box border-start border-4 border-warning">
                        <h3 class="text-warning"><?= number_format($stats['avg_rating'], 1) ?> <i class="bi bi-star-fill" style="font-size: 1.5rem;"></i></h3>
                        <p class="mb-0 text-muted">Platform Avg Rating</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box border-start border-4 border-success">
                        <h3 class="text-success"><?= number_format($stats['five_star']) ?></h3>
                        <p class="mb-0 text-muted">5-Star Reviews</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box border-start border-4 border-danger">
                        <h3 class="text-danger"><?= number_format($stats['one_star']) ?></h3>
                        <p class="mb-0 text-muted">1-Star Reviews (Check These!)</p>
                    </div>
                </div>
            </div>
            
            <!-- Advanced Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Student, Vendor, or Comment..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Rating</label>
                            <select name="rating" class="form-select">
                                <option value="">All Ratings</option>
                                <option value="5" <?= $rating_filter == '5' ? 'selected' : '' ?>>⭐⭐⭐⭐⭐ 5 Stars</option>
                                <option value="4" <?= $rating_filter == '4' ? 'selected' : '' ?>>⭐⭐⭐⭐ 4 Stars</option>
                                <option value="3" <?= $rating_filter == '3' ? 'selected' : '' ?>>⭐⭐⭐ 3 Stars</option>
                                <option value="2" <?= $rating_filter == '2' ? 'selected' : '' ?>>⭐⭐ 2 Stars</option>
                                <option value="1" <?= $rating_filter == '1' ? 'selected' : '' ?>>⭐ 1 Star</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Vendor</label>
                            <select name="vendor" class="form-select">
                                <option value="">All Vendors</option>
                                <?php foreach ($vendors as $v): ?>
                                    <option value="<?= $v['id'] ?>" <?= $vendor_filter == $v['id'] ? 'selected' : '' ?>><?= htmlspecialchars($v['shop_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-danger w-100"><i class="bi bi-search"></i> Filter</button>
                        </div>
                        <div class="col-12">
                            <a href="admin-reviews.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-circle"></i> Clear All Filters</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Reviews Table -->
            <div class="card">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-list-ul"></i> All Reviews (<?= count($reviews) ?>)</h6>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="selectAll">
                            <label class="form-check-label small" for="selectAll">Select All</label>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($reviews)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-star display-4"></i>
                            <p class="mt-3">No reviews found matching your filters.</p>
                        </div>
                    <?php else: ?>
                        <form method="POST" id="bulkForm">
                            <input type="hidden" name="bulk_action" value="delete">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="40"><input type="checkbox" class="form-check-input select-all"></th>
                                            <th>Date</th>
                                            <th>Student</th>
                                            <th>Vendor</th>
                                            <th>Rating</th>
                                            <th>Comment</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reviews as $review): ?>
                                            <tr>
                                                <td><input type="checkbox" class="form-check-input review-checkbox" name="review_ids[]" value="<?= $review['id'] ?>"></td>
                                                <td><small><?= date('M d, Y', strtotime($review['created_at'])) ?><br><span class="text-muted"><?= date('h:i A', strtotime($review['created_at'])) ?></span></small></td>
                                                <td>
                                                    <strong><?= htmlspecialchars($review['full_name']) ?></strong><br>
                                                    <small class="text-muted"><?= htmlspecialchars($review['email']) ?></small>
                                                </td>
                                                <td><?= htmlspecialchars($review['shop_name']) ?></td>
                                                <td>
                                                    <span class="text-warning">
                                                        <?php for($i=1; $i<=5; $i++): ?>
                                                            <i class="bi bi-star<?= $i <= $review['rating'] ? '-fill' : '' ?>"></i>
                                                        <?php endfor; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="text-truncate d-inline-block" style="max-width: 200px;" title="<?= htmlspecialchars($review['comment']) ?>">
                                                        <?= htmlspecialchars($review['comment'] ?: '<em class="text-muted">No comment</em>') ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-info" onclick="viewComment('<?= htmlspecialchars($review['comment']) ?>', '<?= htmlspecialchars($review['full_name']) ?>', '<?= htmlspecialchars($review['shop_name']) ?>')" data-bs-toggle="modal" data-bs-target="#commentModal">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <a href="admin-reviews.php?delete=<?= $review['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this review permanently?')">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Comment Modal -->
<div class="modal fade" id="commentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Full Review Comment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Student:</strong> <span id="modalStudent"></span></p>
                <p><strong>Vendor:</strong> <span id="modalVendor"></span></p>
                <hr>
                <p class="fst-italic" id="modalComment"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Select All functionality
document.querySelectorAll('.select-all').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        document.querySelectorAll('.review-checkbox').forEach(cb => {
            cb.checked = this.checked;
        });
        updateBulkButton();
    });
});

document.querySelectorAll('.review-checkbox').forEach(cb => {
    cb.addEventListener('change', updateBulkButton);
});

function updateBulkButton() {
    const selected = document.querySelectorAll('.review-checkbox:checked');
    document.getElementById('selectedCount').textContent = selected.length;
    document.getElementById('bulkBtn').disabled = selected.length === 0;
}

function viewComment(comment, student, vendor) {
    document.getElementById('modalStudent').textContent = student;
    document.getElementById('modalVendor').textContent = vendor;
    document.getElementById('modalComment').textContent = comment || 'No comment provided.';
}
</script>
</body>
</html>