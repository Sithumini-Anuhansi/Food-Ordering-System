<?php
    include '../includes/session_check.php';
    checkRole('customer');
    include '../configure.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Ordering System - Customer Dashboard</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>


<body class="customer-body">
<?php include '../includes/customer_header.php'; ?>
<?php include '../includes/verification_banner.php'; ?>

    <!-- Sidebar menu -->
    <div class="sidebar-menu" id="sidebarMenu" role="navigation" aria-label="Customer menu">
        <span class="sidebar-close" id="sidebarClose"><i class="fa fa-times"></i></span>
        <ul>
            <li><a href="../Home.php"><i class="fa fa-house"></i> Home</a></li>
            <li><a href="../Menu.php"><i class="fa fa-bars"></i> Menu</a></li>
            <li><a href="../About.php"><i class="fa fa-circle-info"></i> About</a></li>
            <li><a href="../Reviews.php"><i class="fa fa-star"></i> Reviews</a></li>
            <li><a href="../customer/browse_menu.php"><i class="fa fa-cart-plus"></i> Order Now</a></li>
            <li><a href="../customer/order_history.php"><i class="fa-solid fa-cart-shopping"></i> My Orders</a></li>
            <li><a href="../account/profile.php"><i class="fa fa-user"></i> Profile</a></li>
            <li><a href="../auth/logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="customer-container">
        <h2>Welcome, <?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Customer'; ?>!</h2>
        <p style="text-align:center; color:#666;">Use the menu to browse our site, place an order, or check your order history.</p>
        
    </div>

    <script>
        const sidebar = document.getElementById('sidebarMenu');
        const closeBtn = document.getElementById('sidebarClose');
        const overlay = document.getElementById('sidebarOverlay');
        const navProfileIcon = document.getElementById('navProfileIcon');

        function openSidebar() {
            sidebar.classList.add('open');
            overlay.classList.add('active');
        }
        function closeSidebar() {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        }

        if (navProfileIcon) {
            navProfileIcon.onclick = openSidebar;
        }
        closeBtn.onclick = closeSidebar;
        overlay.onclick = closeSidebar;
    </script>

<?php include '../includes/footer.php'; ?>