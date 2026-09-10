<?php
// Include after configure.php on any dashboard page. Expects $conn and
// $_SESSION['user_id'] to already be available.
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT Email_Verified FROM users WHERE User_ID = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row && (int)$row['Email_Verified'] === 0) {
        echo '<div class="error-message" style="text-align:center;">Please verify your email address. '
           . '<a href="../auth/resend_verification.php">Resend verification link</a></div>';
    }
}
