<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../configure.php';

$error = '';
$success = false;
$raw_token = $_GET['token'] ?? ($_POST['token'] ?? '');

if ($raw_token === '') {
    $error = "Missing or invalid reset link.";
} else {
    $token_hash = hash('sha256', $raw_token);
    $stmt = $conn->prepare("SELECT User_ID, Reset_Token_Expiry FROM users WHERE Reset_Token = ?");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user || strtotime($user['Reset_Token_Expiry']) < time()) {
        $error = "This reset link is invalid or has expired. Please request a new one.";
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
        csrf_verify();

        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (strlen($password) < 6) {
            $error = "Password must be at least 6 characters.";
        } elseif ($password !== $confirm) {
            $error = "Passwords don't match.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE users SET Password = ?, Reset_Token = NULL, Reset_Token_Expiry = NULL, Failed_Login_Attempts = 0, Lockout_Until = NULL WHERE User_ID = ?");
            $upd->bind_param("si", $hash, $user['User_ID']);
            $upd->execute();
            $upd->close();
            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body class="login-body">
    <div class="login-card">
        <h2>Reset Password</h2>

        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <a class="login-link" href="forgot_password.php">Request a new reset link</a>
        <?php elseif ($success): ?>
            <div class="success-message">Password updated. You can log in now.</div>
            <a class="login-link" href="Login.php">Go to Login</a>
        <?php else: ?>
            <form method="post" action="reset_password.php?token=<?= htmlspecialchars($raw_token) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= htmlspecialchars($raw_token) ?>">
                <label for="password">New Password</label>
                <input type="password" name="password" id="password" required minlength="6">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" name="confirm_password" id="confirm_password" required minlength="6">
                <input type="submit" name="reset_password" value="Reset Password">
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
