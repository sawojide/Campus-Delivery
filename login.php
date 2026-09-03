<?php
session_start();
require 'includes/db.php';

$message = "";
$message_type = "";

// Only process if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate inputs
    if (empty($email) || empty($password)) {
        $message = "Please enter both email and password!";
        $message_type = "danger";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, full_name, password, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Regenerate session ID to prevent session fixation attacks
                session_regenerate_id(true);
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];

                // Redirect based on role
                               // Redirect based on role
                if ($user['role'] == 'admin') {
                    header("Location: admin-dashboard.php");
                } elseif ($user['role'] == 'vendor') {
                    header("Location: vendor-dashboard.php");
                } elseif ($user['role'] == 'rider') {
                    header("Location: rider-dashboard.php");
                } else {
                    header("Location: dashboard.php"); // Default to student
                }
                exit;
            } else {
                $message = "Invalid email or password!";
                $message_type = "danger";
            }
        } catch (PDOException $e) {
            $message = "Login error occurred.";
            $message_type = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Campus Delivery</title>
    <!-- Bootstrap 5 CSS (For beautiful, responsive layout) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons (For shopping carts, users, etc.) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- PWA Meta Tags -->
<meta name="theme-color" content="#dc3545">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Campus Delivery">
<link rel="manifest" href="manifest.json">
<link rel="apple-touch-icon" href="icons/icon-192x192.png">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h3 class="text-center mb-4">
                        <i class="bi bi-bag-heart-fill text-danger"></i> Campus Runs
                    </h3>
                    <h5 class="text-center mb-4">Welcome Back</h5>

                    <?php if ($message): ?>
                        <div class="alert alert-<?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
                    <?php endif; ?>

                    <form method="POST" action="login.php">
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-danger w-100 mb-3">Login</button>
                    </form>
                    
                    <div class="text-center">
                        <small>Don't have an account? <a href="register.php">Register here</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- PWA Service Worker Registration -->
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('Service Worker registered successfully:', registration.scope);
            })
            .catch(error => {
                console.log('Service Worker registration failed:', error);
            });
    });
}
</script>
</body>
</html>