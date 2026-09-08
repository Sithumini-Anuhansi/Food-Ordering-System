<?php require_once __DIR__ . '/bootstrap.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Food Ordering System</title>
    <link rel="stylesheet" href="/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
<section>
<nav>
    <div class="logo">
        <img src="image/logo.png" alt="Logo" id="navLogo">
    </div>
    <ul>
        <li><a href="Home.php" aria-current="page">Home</a></li>
        <li><a href="Menu.php">Menu</a></li>
        <li><a href="About.php">About</a></li>
        <li><a href="Reviews.php">Reviews</a></li>
    </ul>
    <form class="d-flex" role="search" style="display:inline-block;">
        <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search"/>
        <button class="btn btn-outline-success" type="submit">Search</button>
    </form>
    <div class="nav-auth">
        <?php if (!isset($_SESSION['user_id'])): ?>
            <a href="auth/Login.php">Login</a>
            <a href="auth/Register.php">Sign Up</a>
        <?php else: ?>
            <a href="auth/logout.php">Logout</a>
            <a href="account/profile.php" title="Profile"><i class="fa fa-user"></i></a>
        <?php endif; ?>
    </div>
</nav>
</section>
