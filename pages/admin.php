<?php
    include '../includes/session_check.php';
    checkRole('admin');
    include '../configure.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Ordering System - Admin Dashboard</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="admin-body">
    <?php include '../includes/admin_header.php'; ?>

    <div class="admin-container">
        <h2>Welcome, <?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Admin'; ?> (Admin)</h2>
        <ul>
            <li><a href="../admin/manage_users.php"><i class="fa fa-users"></i> Manage Users</a></li>
            <li><a href="../admin/manage_orders.php"><i class="fa fa-list"></i> Manage Orders</a></li>
            <li><a href="../admin/manage_food_items.php"><i class="fa fa-utensils"></i> Manage Food Items</a></li>
            <li><a href="kitchen.php"><i class="fa fa-fire-burner"></i> Kitchen View</a></li>
            <li><a href="delivery.php"><i class="fa fa-truck"></i> Delivery View</a></li>
        </ul>
    </div>

    <?php include '../includes/footer.php'; ?>