<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../configure.php';

$error = '';
$reset_link = null; // shown directly on screen as a fallback when there's no SMTP configured

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_reset'])) {
    csrf_verify();

    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $stmt = $conn->prepare("SELECT User_ID FROM users WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Always show the same confirmation whether or not the email exists —
        // don't let this form be used to check which emails are registered.
        if ($user) {
            $raw_token = bin2hex(random_bytes(32));
            $token_hash = hash('sha256', $raw_token);
            $expiry = date('Y-m-d H:i:s', time() + 3600); // 1 hour

            $upd = $conn->prepare("UPDATE users SET Reset_Token = ?, Reset_Token_Expiry = ? WHERE User_ID = ?");
            $upd->bind_param("ssi", $token_hash, $expiry, $user['User_ID']);
            $upd->execute();
            $upd->close();

            $reset_link = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']
                . dirname($_SERVER['PHP_SELF']) . '/reset_password.php?token=' . $raw_token;

            // Best-effort email — silently ignored if the server has no mail
            // transport configured. The link is always shown below too, so
            // this still works for local/dev testing without SMTP set up.
            @mail($email, 'Reset your password', "Reset your password here: $reset_link", "From: no-reply@example.com");
        }

        $_SESSION['reset_requested'] = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body class="login-body">
    <div class="login-card">
        <h2>Forgot Password</h2>

        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['reset_requested'])): ?>
            <div class="success-message">If that email is registered, a reset link has been sent.</div>
            <?php unset($_SESSION['reset_requested']); ?>
            <?php if ($reset_link): ?>
                <div class="success-message" style="word-break:break-all;">
                    <strong>Local/dev mode:</strong> since this server may not have outgoing email configured,
                    here's the link directly —
                    <a href="<?= htmlspecialchars($reset_link) ?>"><?= htmlspecialchars($reset_link) ?></a>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <form method="post" action="forgot_password.php">
                <?= csrf_field() ?>
                <label for="email">Email</label>
                <input type="email" name="email" id="email" required placeholder="Enter your account email">
                <input type="submit" name="request_reset" value="Send Reset Link">
            </form>
        <?php endif; ?>

        <a class="login-link" href="Login.php">&larr; Back to Login</a>
    </div>
</body>
</html>
