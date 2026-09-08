<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// Clear session data, then remove the session cookie itself, then destroy it.
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Ordering System</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>


<div class="logout-message" style="max-width:400px;margin:60px auto;padding:32px 24px;background:#c4e0e8;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.08);text-align:center;">
    <h2>You have been logged out.</h2>
    <p>Redirecting to <a href="Login.php">Login</a> page...</p>
</div>
<script>
    setTimeout(function() {
        window.location.href = 'Login.php';
    }, 1800);
</script>
</body>
</html>
