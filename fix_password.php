<?php
require 'includes/db.php';

// --- CHANGE THESE TO MATCH YOUR DETAILS ---
$my_email = 'simple4real08@gmail.com'; // Put the email you are trying to login with
$my_new_password = '123456';          // The password you want to use
// ------------------------------------------

try {
    // 1. Hash the new password securely
    $hashed_password = password_hash($my_new_password, PASSWORD_DEFAULT);

    // 2. Update the database
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->execute([$hashed_password, $my_email]);

    if ($stmt->rowCount() > 0) {
        echo "<h3 style='color:green; text-align:center; margin-top:50px;'>✅ Password Fixed!</h3>";
        echo "<p style='text-align:center;'>You can now login with email: <strong>$my_email</strong> and password: <strong>$my_new_password</strong></p>";
        echo "<p style='text-align:center;'><a href='login.php'>Go to Login Page</a></p>";
    } else {
        echo "<h3 style='color:red; text-align:center; margin-top:50px;'> Email not found in database!</h3>";
        echo "<p style='text-align:center;'>Please check if you typed the email correctly, or register a new account.</p>";
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>