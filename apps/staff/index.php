<?php
require_once __DIR__ . '/../../includes/auth.php';
ensure_any_role(['kitchen', 'delivery']);

$role = strtolower($_SESSION['role'] ?? '');
if ($role === 'kitchen') {
    require __DIR__ . '/../../pages/kitchen.php';
    exit;
}
if ($role === 'delivery') {
    require __DIR__ . '/../../pages/delivery.php';
    exit;
}

header('Location: ../../auth/Login.php');
exit;
