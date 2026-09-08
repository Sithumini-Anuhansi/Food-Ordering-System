<?php
// Expects $staff_role_label (e.g. "Kitchen") and $staff_home (e.g. "kitchen.php")
// to be set by the including page.
$is_admin_viewing = isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin';
?>
<header class="header">
    <div style="display: flex; align-items: center; justify-content: space-between; max-width: 1100px; margin: 0 auto; padding: 12px 24px;">
        <div>
            <a href="../pages/<?= htmlspecialchars($staff_home) ?>">
                <img src="../image/logo.png" alt="Logo" class="header-logo" style="height:48px;">
            </a>
        </div>
        <nav>
            <span style="margin-right:20px; font-weight:bold;"><?= htmlspecialchars($staff_role_label) ?> Dashboard</span>
            <?php if ($is_admin_viewing): ?>
                <a href="../pages/admin.php" style="margin-right:20px; color:black; font-weight:bold; text-decoration:none;">
                    <i class="fa fa-arrow-left"></i> Back to Admin
                </a>
            <?php endif; ?>
            <a href="../account/profile.php" style="margin-right:20px; color:black; font-weight:bold; text-decoration:none;">
                <i class="fa fa-user"></i> Profile
            </a>
            <a href="../auth/logout.php" style="color:#e74c3c; font-weight:bold; text-decoration:none;">
                <i class="fa fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>
</header>
