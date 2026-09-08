<?php
$current_page = basename($_SERVER['PHP_SELF']);
function nav_active($page, $current) {
    return $page === $current ? 'border-bottom: 2px solid #3498db;' : '';
}
?>

<header class="header">
    <div style="display: flex; align-items: center; justify-content: space-between; max-width: 1100px; margin: 0 auto; padding: 12px 24px; flex-wrap: wrap;">
        <div>
            <a href="../Home.php">
                <img src="../image/logo.png" alt="Logo" class="header-logo" style="height:48px;">
            </a>
        </div>
        <nav style="display:flex; flex-wrap:wrap; align-items:center;">
            <a href="../pages/customer.php"
               style="margin-right:20px; color:#000; font-weight:bold; text-decoration:none; <?= nav_active('customer.php', $current_page) ?>">
               <i class="fa fa-gauge"></i> Dashboard
            </a>
            <a href="../Home.php"
               style="margin-right:20px; color:#000; font-weight:bold; text-decoration:none; <?= nav_active('Home.php', $current_page) ?>">
               <i class="fa fa-house"></i> Home
            </a>
            <a href="../Menu.php"
               style="margin-right:20px; color:#000; font-weight:bold; text-decoration:none; <?= nav_active('Menu.php', $current_page) ?>">
               <i class="fa fa-bars"></i> Menu
            </a>
            <a href="../About.php"
               style="margin-right:20px; color:#000; font-weight:bold; text-decoration:none; <?= nav_active('About.php', $current_page) ?>">
               <i class="fa fa-circle-info"></i> About
            </a>
            <a href="../Reviews.php"
               style="margin-right:20px; color:#000; font-weight:bold; text-decoration:none; <?= nav_active('Reviews.php', $current_page) ?>">
               <i class="fa fa-star"></i> Reviews
            </a>
            <a href="../customer/browse_menu.php"
               style="margin-right:20px; color:#000; font-weight:bold; text-decoration:none; <?= nav_active('browse_menu.php', $current_page) ?>">
               <i class="fa fa-cart-plus"></i> Order Now
            </a>
            <a href="../customer/order_history.php"
               style="margin-right:20px; color:#000; font-weight:bold; text-decoration:none; <?= nav_active('order_history.php', $current_page) ?>">
               <i class="fa-solid fa-cart-shopping"></i> My Orders
            </a>
            <a href="../account/profile.php"
               style="margin-right:20px; color:#000; font-weight:bold; text-decoration:none; <?= nav_active('profile.php', $current_page) ?>">
               <i class="fa fa-user"></i> Profile
            </a>
            <a href="../auth/logout.php"
               style="color:#e74c3c; font-weight:bold; text-decoration:none;">
               <i class="fa fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>
</header>
