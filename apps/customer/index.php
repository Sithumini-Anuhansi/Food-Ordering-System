<?php
require_once __DIR__ . '/../../includes/auth.php';
ensure_role('customer');

$page = $_GET['page'] ?? 'browse_menu';
$valid = [
    'browse_menu',
    'checkout',
    'order_history',
    'order_status_check',
    'profile',
    'payment_success',
    'cancel_order',
    'rate_order',
    'place_order',
];

if (!in_array($page, $valid, true)) {
    $page = 'browse_menu';
}

$target = __DIR__ . '/../../customer/' . $page . '.php';
if (file_exists($target)) {
    require $target;
    exit;
}

header('Location: ../../customer/browse_menu.php');
exit;
