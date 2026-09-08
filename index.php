<?php

// Load environment config and start a properly-hardened session
require_once __DIR__ . '/includes/bootstrap.php';

// Check if the user is logged in by verifying the existence of 'user_id' in the session
if (isset($_SESSION['user_id'])) 
{
    // Redirect user based on their role
    switch ($_SESSION['role']) 
    {
        case 'admin':
            header("Location: pages/admin.php");
            exit();
        case 'customer':
            header("Location: pages/customer.php");
            exit();
        case 'kitchen':
            header("Location: pages/kitchen.php");
            exit();
        case 'delivery':
            header("Location: pages/delivery.php");
            exit();

        default:
            // If the role is unrecognized, destroy the session and redirect to login
            session_destroy();
            header("Location: auth/Login.php");
            exit();
            break;
    }
} 
else 
{
    // If user is not logged in, redirect to the login page
    header("Location: auth/Login.php");
    exit();
}
?>

