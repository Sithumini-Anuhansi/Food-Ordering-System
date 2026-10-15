<?php
require_once __DIR__ . '/../../includes/auth.php';
ensure_role('admin');

$page = $_GET['page'] ?? 'manage_food_items';
$valid = [
    'manage_food_items',
    'manage_orders',
    'manage_users',
    'manage_reviews',
    'analytics',
    'index',
];

if (!in_array($page, $valid, true)) {
    $page = 'manage_food_items';
}

$target = __DIR__ . '/../../admin/' . $page . '.php';
if (file_exists($target)) {
    require $target;
    exit;
}

header('Location: ../../admin/manage_food_items.php');
exit;
