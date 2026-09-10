<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../configure.php';

$token = $_GET['token'] ?? '';
$message = '';
$success = false;

if ($token === '') {
    $message = "Missing verification link.";
} else {
    $stmt = $conn->prepare("SELECT User_ID, Verification_Token_Expiry FROM users WHERE Verification_Token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        $message = "This verification link is invalid or has already been used.";
    } elseif (strtotime($user['Verification_Token_Expiry']) < time()) {
        $message = "This verification link has expired. Please request a new one.";
    } else {
        $upd = $conn->prepare("UPDATE users SET Email_Verified = 1, Verification_Token = NULL, Verification_Token_Expiry = NULL WHERE User_ID = ?");
        $upd->bind_param("i", $user['User_ID']);
        $upd->execute();
        $upd->close();
        $success = true;
        $message = "Your email has been verified. You can log in now.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Email</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body class="login-body">
    <div class="login-card">
        <h2>Email Verification</h2>
        <div class="<?= $success ? 'success-message' : 'error-message' ?>"><?= htmlspecialchars($message) ?></div>
        <a class="login-link" href="Login.php">Go to Login</a>
        <?php if (!$success): ?>
            <a class="login-link" href="resend_verification.php">Resend verification email</a>
        <?php endif; ?>
    </div>
</body>
</html>
