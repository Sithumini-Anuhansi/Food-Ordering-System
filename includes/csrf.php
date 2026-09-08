<?php
/**
 * Lightweight CSRF protection.
 * Requires an active session (started in includes/bootstrap.php).
 */

if (!function_exists('csrf_token')) {
    function csrf_token()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    /** Echo/return this inside every state-changing <form>. */
    function csrf_field()
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
    }
}

if (!function_exists('csrf_verify')) {
    /** Call at the top of every POST handler before touching the database. */
    function csrf_verify()
    {
        $submitted = $_POST['csrf_token'] ?? '';
        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $submitted)) {
            http_response_code(403);
            die('Your session has expired or the request could not be verified. Please go back, refresh the page, and try again.');
        }
    }
}
