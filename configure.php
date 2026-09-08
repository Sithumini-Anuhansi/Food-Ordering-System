<?php
    // Load environment config, secure session handling, and CSRF helpers.
    require_once __DIR__ . '/includes/bootstrap.php';

    // Database credentials now come from .env (see .env.example) instead of
    // being hardcoded here.
    $host     = env('DB_HOST', 'localhost');
    $username = env('DB_USER', 'root');
    $password = env('DB_PASS', '');
    $database = env('DB_NAME', 'food-ordering-system');

    $conn = mysqli_connect($host, $username, $password, $database);

    if (!$conn) {
        if (env('APP_ENV', 'local') === 'production') {
            // Don't leak connection details to visitors in production.
            error_log('Database connection failed: ' . mysqli_connect_error());
            die('Sorry, something went wrong. Please try again shortly.');
        }
        die("Connection Failed: " . mysqli_connect_error());
    }

    mysqli_set_charset($conn, 'utf8mb4');
?>
