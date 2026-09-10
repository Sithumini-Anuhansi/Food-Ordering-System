<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../configure.php';
require_once __DIR__ . '/../includes/mailer.php';

$sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend'])) {
    csrf_verify();

    $email = trim($_POST['email'] ?? '');

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $conn->prepare("SELECT User_ID, Name, Email_Verified FROM users WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Same confirmation either way — don't reveal whether the email is registered.
        if ($user && (int)$user['Email_Verified'] === 0) {
            $verify_token = bin2hex(random_bytes(32));
            $verify_expiry = date('Y-m-d H:i:s', time() + 86400);

            $upd = $conn->prepare("UPDATE users SET Verification_Token = ?, Verification_Token_Expiry = ? WHERE User_ID = ?");
            $upd->bind_param("ssi", $verify_token, $verify_expiry, $user['User_ID']);
            $upd->execute();
            $upd->close();

            $base_url = rtrim(env('APP_BASE_URL', ''), '/');
            $verify_link = "$base_url/auth/verify_email.php?token=$verify_token";
            try {
                send_email($email, "Verify your email", "<p>Hi " . htmlspecialchars($user['Name']) . ",</p><p>Here's your new verification link:</p><p><a href=\"$verify_link\">$verify_link</a></p>");
            } catch (\Throwable $e) {
                error_log("Resend verification email failed: " . $e->getMessage());
            }
        }
    }
    $sent = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Resend Verification Email</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body class="login-body">
    <div class="login-card">
        <h2>Resend Verification Email</h2>
        <?php if ($sent): ?>
            <div class="success-message">If that email is registered and not yet verified, a new link has been sent.</div>
        <?php else: ?>
            <form method="post" action="resend_verification.php">
                <?= csrf_field() ?>
                <label for="email">Email</label>
                <input type="email" name="email" id="email" required placeholder="Enter your account email">
                <input type="submit" name="resend" value="Resend Link">
            </form>
        <?php endif; ?>
        <a class="login-link" href="Login.php">&larr; Back to Login</a>
    </div>
</body>
</html>
