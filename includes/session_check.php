<?php
require_once __DIR__ . '/auth.php';

function checkRole($role)
{
    ensure_role($role);
}

/** For pages more than one role may access, e.g. admin also viewing the kitchen/delivery dashboards. */
function checkAnyRole(array $roles)
{
    ensure_any_role($roles);
}

/** For pages any logged-in user (any role) may access, e.g. change password. */
function requireLogin()
{
    ensure_logged_in();
}
