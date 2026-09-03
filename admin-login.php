<?php
session_start();
require 'includes/db.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    try {
        $stmt = $pdo->prepare("SELECT id, full_name, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Check if admin or promote user #1
            if ($user['role'] == 'admin' || $user['id'] == 1) {
                if ($user['role'] != 'admin') {
                    $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
                    $stmt->execute([$user['id']]);
                }
                
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['role'] = 'admin';
                
                header("Location: admin-dashboard.php");
                exit;
            } else {
                $error = "Access denied. Admin privileges required.";
            }
        } else {
            $error = "Invalid email or password.";
        }
    } catch (PDOException $e) {
        $error = "Login error. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Campus Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); min-height: 100vh; display: flex; align-items: center; }
        .login-card { max-width: 400px; margin: auto; }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-card">
            <div class="card shadow-lg border-0">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="bi bi-shield-lock-fill text-danger display-4"></i>
                        <h3 class="mt-3 fw-bold">Admin Panel</h3>
                        <p class="text-muted">Sign in to continue</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" class="form-control" required autofocus>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-danger w-100 btn-lg">
                            <i class="bi bi-box-arrow-in-right"></i> Login to Admin Panel
                        </button>
                    </form>
                    
                    <div class="text-center mt-4">
                        <a href="index.php" class="text-muted text-decoration-none">
                            <i class="bi bi-arrow-left"></i> Back to Website
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>