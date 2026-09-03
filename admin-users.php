<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: admin-login.php");
    exit;
}

$success = "";
$error = "";

// Handle Add New User
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $hostel_address = trim($_POST['hostel_address'] ?? '');
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password, role, hostel_address) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$full_name, $email, $phone, $password, $role, $hostel_address]);
        $user_id = $pdo->lastInsertId();
        
        // Create wallet for new user
        $stmt = $pdo->prepare("INSERT INTO wallets (user_id, balance) VALUES (?, 0)");
        $stmt->execute([$user_id]);
        
        $success = "User created successfully!";
    } catch (PDOException $e) {
        $error = "Error: Email might already exist. " . $e->getMessage();
    }
}

// Handle Update User
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_user'])) {
    $user_id = intval($_POST['user_id']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role = $_POST['role'];
    $hostel_address = trim($_POST['hostel_address'] ?? '');
    $new_password = trim($_POST['new_password']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    try {
        if (!empty($new_password)) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET full_name=?, email=?, phone=?, role=?, hostel_address=?, is_active=? WHERE id=?");
            $stmt->execute([$full_name, $email, $phone, $role, $hostel_address, $is_active, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET full_name=?, email=?, phone=?, role=?, hostel_address=?, is_active=? WHERE id=?");
            $stmt->execute([$full_name, $email, $phone, $role, $hostel_address, $is_active, $user_id]);
        }
        $success = "User updated successfully!";
    } catch (PDOException $e) {
        $error = "Error updating user: " . $e->getMessage();
    }
}

// Handle Delete User
if (isset($_GET['delete'])) {
    $user_id = intval($_GET['delete']);
    if ($user_id != $_SESSION['user_id']) { // Prevent deleting yourself
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $success = "User deleted successfully!";
        } catch (PDOException $e) {
            $error = "Cannot delete user with existing orders.";
        }
    } else {
        $error = "You cannot delete your own account!";
    }
}

// Get filters
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if ($role_filter) {
    $sql .= " AND role = ?";
    $params[] = $role_filter;
}

if ($status_filter !== '') {
    $sql .= " AND is_active = ?";
    $params[] = $status_filter;
}

if ($search) {
    $sql .= " AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Get statistics
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_students = $pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
$total_riders = $pdo->query("SELECT COUNT(*) FROM users WHERE role='rider'")->fetchColumn();
$total_admins = $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super User Management - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .sidebar { min-height: 100vh; background: linear-gradient(180deg, #dc3545 0%, #c82333 100%); }
        .sidebar a { color: white; text-decoration: none; padding: 12px 20px; display: block; border-left: 4px solid transparent; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.1); border-left-color: white; }
        .stat-box { background: white; border-radius: 10px; padding: 20px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .stat-box h3 { margin: 0; font-size: 2rem; }
        .stat-box.students { border-left: 5px solid #dc3545; }
        .stat-box.riders { border-left: 5px solid #28a745; }
        .stat-box.admins { border-left: 5px solid #ffc107; }
        .stat-box.total { border-left: 5px solid #17a2b8; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'admin-sidebar.php'; ?>
        
        <div class="col-md-10 p-4 bg-light">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-people-fill text-danger"></i> Super User Management</h2>
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="bi bi-person-plus"></i> Add New User
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
                    <div class="stat-box total">
                        <h3><?= $total_users ?></h3>
                        <p class="mb-0 text-muted">Total Users</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box students">
                        <h3><?= $total_students ?></h3>
                        <p class="mb-0 text-muted">Students</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box riders">
                        <h3><?= $total_riders ?></h3>
                        <p class="mb-0 text-muted">Riders</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box admins">
                        <h3><?= $total_admins ?></h3>
                        <p class="mb-0 text-muted">Admins</p>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <select name="role" class="form-select">
                                <option value="">All Roles</option>
                                <option value="student" <?= $role_filter == 'student' ? 'selected' : '' ?>>Students</option>
                                <option value="rider" <?= $role_filter == 'rider' ? 'selected' : '' ?>>Riders</option>
                                <option value="admin" <?= $role_filter == 'admin' ? 'selected' : '' ?>>Admins</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="1" <?= $status_filter === '1' ? 'selected' : '' ?>>Active</option>
                                <option value="0" <?= $status_filter === '0' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Search by name, email, or phone..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-danger w-100"><i class="bi bi-search"></i> Filter</button>
                        </div>
                    </form>
                    <a href="admin-users.php" class="btn btn-sm btn-outline-secondary mt-2"><i class="bi bi-x-circle"></i> Clear Filters</a>
                </div>
            </div>
            
            <!-- Users Table -->
            <div class="card">
                <div class="card-body">
                    <?php if (empty($users)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-people display-1"></i>
                            <p class="mt-3">No users found.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Wallet</th>
                                        <th>Registered</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?= $user['id'] ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($user['full_name']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($user['hostel_address'] ?? 'No address') ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td><?= htmlspecialchars($user['phone']) ?></td>
                                            <td>
                                                <?php
                                                $role_badges = [
                                                    'student' => 'secondary',
                                                    'rider' => 'success',
                                                    'admin' => 'danger'
                                                ];
                                                $badge = $role_badges[$user['role']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?= $badge ?>"><?= strtoupper($user['role']) ?></span>
                                            </td>
                                            <td>
                                                <?php if ($user['is_active'] ?? 1): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ?");
                                                $stmt->execute([$user['id']]);
                                                $wallet = $stmt->fetch();
                                                ?>
                                                <strong class="text-success">₦<?= number_format($wallet['balance'] ?? 0) ?></strong>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <button class="btn btn-sm btn-outline-primary" onclick='editUser(<?= htmlspecialchars(json_encode($user)) ?>)' data-bs-toggle="modal" data-bs-target="#editUserModal" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <a href="admin-wallets.php?user=<?= $user['id'] ?>" class="btn btn-sm btn-outline-info" title="Manage Wallet">
                                                        <i class="bi bi-wallet2"></i>
                                                    </a>
                                                    <a href="admin-orders.php?user=<?= $user['id'] ?>" class="btn btn-sm btn-outline-warning" title="View Orders">
                                                        <i class="bi bi-cart3"></i>
                                                    </a>
                                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                        <a href="admin-users.php?delete=<?= $user['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this user?')" title="Delete">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
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

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus"></i> Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="add_user" value="1">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email Address *</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone Number *</label>
                            <input type="text" name="phone" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password *</label>
                            <input type="password" name="password" class="form-control" required minlength="6">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Role *</label>
                            <select name="role" class="form-select" required>
                                <option value="student">Student</option>
                                <option value="rider">Rider</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Hostel/Address</label>
                            <input type="text" name="hostel_address" class="form-control" placeholder="e.g., Male Hostel Block A">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <input type="hidden" name="update_user" value="1">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email Address *</label>
                            <input type="email" name="email" id="edit_email" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone Number *</label>
                            <input type="text" name="phone" id="edit_phone" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">New Password (leave blank to keep current)</label>
                            <input type="password" name="new_password" id="edit_password" class="form-control" placeholder="Enter new password">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Role *</label>
                            <select name="role" id="edit_role" class="form-select" required>
                                <option value="student">Student</option>
                                <option value="rider">Rider</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Hostel/Address</label>
                            <input type="text" name="hostel_address" id="edit_hostel_address" class="form-control">
                        </div>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="is_active" id="edit_is_active" class="form-check-input" checked>
                        <label class="form-check-label" for="edit_is_active">Active User</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editUser(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_full_name').value = user.full_name;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_phone').value = user.phone;
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_hostel_address').value = user.hostel_address || '';
    document.getElementById('edit_is_active').checked = (user.is_active ?? 1) == 1;
    document.getElementById('edit_password').value = '';
}
</script>
</body>
</html>