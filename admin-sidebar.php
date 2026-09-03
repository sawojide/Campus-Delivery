<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="col-md-2 sidebar p-0">
    <h4 class="text-white p-4 mb-0">
        <i class="bi bi-shield-lock"></i> Admin Panel
    </h4>
    <div class="mt-4">
        <a href="admin-dashboard.php" class="<?= $current_page == 'admin-dashboard.php' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="admin-orders.php" class="<?= $current_page == 'admin-orders.php' ? 'active' : '' ?>">
            <i class="bi bi-cart3"></i> Orders
        </a>
        <a href="admin-vendors.php" class="<?= $current_page == 'admin-vendors.php' ? 'active' : '' ?>">
            <i class="bi bi-shop"></i> Vendors
        </a>
        <a href="admin-products.php" class="<?= $current_page == 'admin-products.php' ? 'active' : '' ?>">
            <i class="bi bi-box-seam"></i> Products
        </a>
        <a href="admin-categories.php" class="<?= $current_page == 'admin-categories.php' ? 'active' : '' ?>">
            <i class="bi bi-tags"></i> Categories
        </a>
        <a href="admin-users.php" class="<?= $current_page == 'admin-users.php' ? 'active' : '' ?>">
            <i class="bi bi-people"></i> Customers
        </a>
        <a href="admin-wallets.php" class="<?= $current_page == 'admin-wallets.php' ? 'active' : '' ?>">
            <i class="bi bi-wallet2"></i> Wallets
        </a>
        <a href="admin-reviews.php" class="<?= $current_page == 'admin-reviews.php' ? 'active' : '' ?>">
     <i class="bi bi-star"></i> Reviews
        </a>
        <a href="admin-promos.php" class="<?= $current_page == 'admin-promos.php' ? 'active' : '' ?>">
    <i class="bi bi-tag"></i> Promo Codes
    </a>
    <a href="admin-withdrawals.php" class="<?= $current_page == 'admin-withdrawals.php' ? 'active' : '' ?>">
    <i class="bi bi-cash-stack"></i> Withdrawals
</a>
        <hr class="text-white my-3">
        <a href="index.php">
            <i class="bi bi-house"></i> Public Site
        </a>
        <a href="admin-logout.php" class="logout-link">
    <i class="bi bi-box-arrow-left"></i> Logout
</a>
    </div>
</div>

<style>
.logout-link {
    color: #ffc107 !important;
    font-weight: bold;
    padding: 12px 20px;
    display: block;
    border-left: 4px solid transparent;
    transition: all 0.3s;
}
.logout-link:hover {
    background: rgba(255, 193, 7, 0.2);
    border-left-color: #ffc107;
    color: #fff !important;
    text-decoration: none;
}
</style>