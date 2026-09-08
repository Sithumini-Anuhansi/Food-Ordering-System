<?php
// Change password now lives inside the unified profile page.
include '../includes/session_check.php';
requireLogin();
header("Location: profile.php#change-password");
exit();
