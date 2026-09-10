<?php
/**
 * Central application bootstrap.
 *
 * Every entry-point file should load this (directly, or indirectly via
 * configure.php / includes/session_check.php) before producing any output.
 * It is safe to include multiple times per request.
 *
 * Responsibilities:
 *  - load .env into getenv()/$_ENV
 *  - set error reporting based on APP_ENV
 *  - redirect HTTP -> HTTPS when APP_FORCE_HTTPS=true (safe to leave off
 *    for local dev — `php -S` doesn't speak HTTPS at all)
 *  - configure secure session cookie params and start the session
 *  - pull in the CSRF helper so csrf_field()/csrf_verify() are always available
 */

if (!defined('APP_BOOTSTRAPPED')) {
    define('APP_BOOTSTRAPPED', true);

    require_once __DIR__ . '/env.php';
    loadEnv(__DIR__ . '/../.env');

    $appEnv = env('APP_ENV', 'local');

    if ($appEnv === 'production') {
        error_reporting(0);
        ini_set('display_errors', '0');
    } else {
        error_reporting(E_ALL);
        ini_set('display_errors', '1');
    }

    if (env('APP_FORCE_HTTPS', 'false') === 'true') {
        // Check the proxy header too — most real deployments sit behind a
        // load balancer/reverse proxy that terminates TLS, so $_SERVER['HTTPS']
        // alone would never be set even when the original request was HTTPS.
        $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        if (!$is_https) {
            $redirect_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header("Location: $redirect_url", true, 301);
            exit();
        }

        // Already on HTTPS — tell browsers to remember that for a year and
        // always use HTTPS for this host from now on.
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            // Flip this on once the site is served over HTTPS:
            'secure'   => (env('APP_FORCE_SECURE_COOKIES', 'false') === 'true'),
        ]);
        session_start();
    }

    require_once __DIR__ . '/csrf.php';
}
