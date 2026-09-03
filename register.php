<?php
session_start();
require 'includes/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$message = "";
$message_type = ""; 
$referral_error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $hostel_address = trim($_POST['hostel_address'] ?? '');
    $referral_code_input = strtoupper(trim($_POST['referral_code'] ?? ''));

    // Basic validation
    if (empty($full_name) || empty($email) || empty($phone) || empty($password)) {
        $message = "All required fields must be filled!";
        $message_type = "danger";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format!";
        $message_type = "danger";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters!";
        $message_type = "danger";
    } else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $message = "Email is already registered!";
                $message_type = "danger";
            } else {
                $referred_by = null;
                
                // Validate referral code if provided
                if (!empty($referral_code_input)) {
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE referral_code = ?");
                    $stmt->execute([$referral_code_input]);
                    $referrer = $stmt->fetch();
                    
                    if ($referrer) {
                        $referred_by = $referrer['id'];
                    } else {
                        $referral_error = "Invalid referral code.";
                    }
                }
                
                // Only proceed if there are no referral errors
                if (empty($referral_error)) {
                    // Start Transaction
                    $pdo->beginTransaction();

                    // Generate a unique 8-character referral code for the NEW user
                    $new_referral_code = strtoupper(substr(md5($email . time()), 0, 8));

                    // 1. Insert User
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password, role, hostel_address, referral_code, referred_by) VALUES (?, ?, ?, ?, 'student', ?, ?, ?)");
                    $stmt->execute([$full_name, $email, $phone, $hashed_password, $hostel_address, $new_referral_code, $referred_by]);
                    $user_id = $pdo->lastInsertId();

                    // 2. Create Wallet for the new user
                    $stmt = $pdo->prepare("INSERT INTO wallets (user_id, balance) VALUES (?, 0.00)");
                    $stmt->execute([$user_id]);

                    // Commit Transaction
                    $pdo->commit();
                    
                    $message = "Registration successful! You can now log in.";
                    $message_type = "success";
                }
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $message = "Registration failed: " . $e->getMessage();
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
    <title>Register - Campus Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 40px 0;
        }
        .register-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card register-card border-0">
                <div class="card-body p-4">
                    <h3 class="text-center mb-2"><i class="bi bi-bag-heart-fill text-danger"></i> Campus Delivery</h3>
                    <h5 class="text-center text-muted mb-4">Create Student Account</h5>

                    <?php if ($message): ?>
                        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
                            <?= htmlspecialchars($message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="register.php">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required pattern="[0-9]{11}" title="Please enter an 11-digit phone number">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Hostel / Address</label>
                            <input type="text" name="hostel_address" class="form-control" placeholder="e.g., Male Hostel Block A" value="<?= htmlspecialchars($_POST['hostel_address'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required minlength="6">
                        </div>
                        
                        <!-- Referral Code Field -->
                        <div class="mb-4">
                            <label class="form-label">Referral Code (Optional)</label>
                            <input type="text" name="referral_code" class="form-control text-uppercase" placeholder="Enter friend's code" value="<?= htmlspecialchars($_POST['referral_code'] ?? (isset($_GET['ref']) ? strtoupper($_GET['ref']) : '')) ?>">
                            <?php if (!empty($referral_error)): ?>
                                <small class="text-danger"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($referral_error) ?></small>
                            <?php else: ?>
                                <small class="text-muted"><i class="bi bi-gift"></i> Have a friend who uses Campus Delivery? Enter their code!</small>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn btn-danger w-100 mb-3 btn-lg fw-bold">Register</button>
                    </form>
                    
                    <div class="text-center">
                        <small>Already have an account? <a href="login.php" class="text-danger fw-bold">Login here</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>