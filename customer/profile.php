<?php
// Superseded by the unified profile page usable by every role.
include '../includes/session_check.php';
checkRole('customer');
header("Location: ../account/profile.php");
exit();
