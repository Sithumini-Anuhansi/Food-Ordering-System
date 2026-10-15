<?php
/**
 * Central authorization and request helpers.
 *
 * Provides safer, consistent behavior for pages and API endpoints:
 *  - ensures sessions are started via bootstrap
 *  - centralized role checks that return 401/403 for API/XHR requests
 *  - helpers for requiring POST + CSRF on state-changing endpoints
 *
 * Include this from other include files instead of duplicating logic.
 */

require_once __DIR__ . '/bootstrap.php';

if (!function_exists('is_ajax_request')) {
    function is_ajax_request()
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);
    }
}

if (!function_exists('is_api_request')) {
    function is_api_request()
    {
        // Basic heuristic: URL contains /api/ or the script is inside an api/ folder
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        if (stripos($uri, '/api/') !== false) return true;
        if (stripos($script, '/api/') !== false) return true;
        return false;
    }
}

if (!function_exists('redirect_to_login')) {
    function redirect_to_login()
    {
        // Keep the same relative path used elsewhere in the app for compatibility.
        header('Location: ../auth/Login.php');
        exit();
    }
}

if (!function_exists('json_unauthorized')) {
    function json_unauthorized()
    {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Authentication required']);
        exit();
    }
}

if (!function_exists('json_forbidden')) {
    function json_forbidden($message = 'Forbidden')
    {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
        exit();
    }
}

if (!function_exists('ensure_logged_in')) {
    function ensure_logged_in()
    {
        if (!isset($_SESSION['user_id'])) {
            if (is_api_request() || is_ajax_request()) {
                json_unauthorized();
            }
            redirect_to_login();
        }
    }
}

if (!function_exists('ensure_role')) {
    function ensure_role($role)
    {
        ensure_logged_in();
        if (strtolower($_SESSION['role'] ?? '') !== strtolower($role)) {
            if (is_api_request() || is_ajax_request()) {
                json_forbidden('Insufficient role');
            }
            // For UI pages, redirect to login to avoid revealing details.
            redirect_to_login();
        }
    }
}

if (!function_exists('ensure_any_role')) {
    function ensure_any_role(array $roles)
    {
        ensure_logged_in();
        $roles = array_map('strtolower', $roles);
        if (!in_array(strtolower($_SESSION['role'] ?? ''), $roles, true)) {
            if (is_api_request() || is_ajax_request()) {
                json_forbidden('Insufficient role');
            }
            redirect_to_login();
        }
    }
}

if (!function_exists('require_post')) {
    function require_post()
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            if (is_api_request() || is_ajax_request()) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Method Not Allowed']);
                exit();
            }
            die('Method Not Allowed');
        }
    }
}

if (!function_exists('require_csrf_for_post')) {
    function require_csrf_for_post()
    {
        require_post();
        // csrf_verify() is provided by includes/csrf.php (loaded via bootstrap)
        if (!function_exists('csrf_verify')) {
            // Something is wrong: CSRF helper missing
            error_log('CSRF helper missing when calling require_csrf_for_post()');
            http_response_code(500);
            die('Server configuration error');
        }
        csrf_verify();
    }
}
