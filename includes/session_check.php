<?php
require_once __DIR__ . '/bootstrap.php';

function checkRole($role)
{
    if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== strtolower($role))
    {
        header("Location: ../auth/Login.php");
        exit();
    }
}

/** For pages more than one role may access, e.g. admin also viewing the kitchen/delivery dashboards. */
function checkAnyRole(array $roles)
{
    if (!isset($_SESSION['user_id']))
    {
        header("Location: ../auth/Login.php");
        exit();
    }
    $roles = array_map('strtolower', $roles);
    if (!in_array(strtolower($_SESSION['role']), $roles, true))
    {
        header("Location: ../auth/Login.php");
        exit();
    }
}

/** For pages any logged-in user (any role) may access, e.g. change password. */
function requireLogin()
{
    if (!isset($_SESSION['user_id']))
    {
        header("Location: ../auth/Login.php");
        exit();
    }
}
