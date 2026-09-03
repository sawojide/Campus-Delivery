<?php
// reset_password.php - TEMPORARY DEV TOOL ONLY
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $new_password = $_POST['new_password'];
    
    if (!empty($email) && !empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        if ($stmt->execute([$hashed_password, $email])) {
            if ($stmt->rowCount() > 0) {
                echo "<h3 style='color:green;'>✅ Password reset successfully!</h3>";
                echo "<p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>";
                echo "<p><strong>New Password:</strong> " . htmlspecialchars($new_password) . "</p>";
                echo "<a href='login.php' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Go to Login</a>";
            } else {
                echo "<h3 style='color:red;'>❌ Email not found in database.</h3>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Reset Password (Dev)</title></head>
<body style="font-family: Arial, sans-serif; padding: 40px; background: #f8f9fa;">
    <div style="background: white; padding: 30px; border-radius: 10px; max-width: 500px; margin: 0 auto; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
        <h2>🔑 Temporary Password Reset</h2>
        <form method="POST">
            <label style="font-weight: bold;">Email Address:</label><br>
            <input type="email" name="email" required style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 5px;"><br>
            
            <label style="font-weight: bold;">New Password:</label><br>
            <input type="text" name="new_password" required style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 5px;"><br>
            
            <button type="submit" style="width: 100%; padding: 12px; background: #dc3545; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; margin-top: 10px;">Reset Password</button>
        </form>
        <p style="color: red; font-size: 0.9em; margin-top: 20px; text-align: center;">⚠️ WARNING: Delete this file immediately after use!</p>
    </div>
</body>
</html>