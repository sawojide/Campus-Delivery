<?php
// includes/auth.php - Authentication Helper Functions

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Require login - redirect to login.php if not logged in
 */
function requireLogin($redirect = 'login.php') {
    if (!isLoggedIn()) {
        header("Location: " . $redirect);
        exit;
    }
}

/**
 * Check if user has a specific role
 * Matches $_SESSION['role'] set in login.php
 */
function hasRole($required_role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $required_role;
}

/**
 * Require specific role - redirect if user doesn't have it
 */
function requireRole($required_role, $redirect = 'dashboard.php') {
    requireLogin($redirect);
    if (!hasRole($required_role)) {
        header("Location: " . $redirect);
        exit;
    }
}

/**
 * Get current user data from database safely
 */
function getCurrentUser($pdo) {
    if (!isLoggedIn()) {
        return null;
    }

    try {
        // Only select necessary columns, matching your schema
        $stmt = $pdo->prepare("SELECT id, full_name, email, phone, role, hostel_address, referral_code FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}
?>