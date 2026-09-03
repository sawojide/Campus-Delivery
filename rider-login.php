<?php
session_start();
require 'includes/db.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'rider'");
        $stmt->execute([$email]);
        $rider = $stmt->fetch();
        
        if ($rider && password_verify($password, $rider['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $rider['id'];
            $_SESSION['user_name'] = $rider['full_name'];
            $_SESSION['role'] = 'rider';
            
            header("Location: rider-dashboard.php");
            exit;
        } else {
            $error = "Invalid email/password or not a registered rider.";
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
    <title>Rider Login - Campus Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); min-height: 100vh; display: flex; align-items: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="bi bi-bicycle text-success display-4"></i>
                            <h3 class="mt-3 fw-bold">Rider Portal</h3>
                            <p class="text-muted">Manage your deliveries</p>
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
                            <button type="submit" class="btn btn-success w-100 btn-lg">
                                <i class="bi bi-box-arrow-in-right"></i> Login to Rider Panel
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
    </div>
</body>
</html>