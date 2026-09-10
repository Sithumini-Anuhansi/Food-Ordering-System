<?php
/**
 * Lightweight health check for external uptime monitoring (UptimeRobot,
 * Better Uptime, a cron+curl script, etc. — point any of them at this URL).
 * Deliberately outside session_check.php: monitoring services don't have a
 * login session, and this endpoint reveals nothing sensitive.
 */
require_once __DIR__ . '/configure.php';

header('Content-Type: application/json');

$db_ok = false;
if ($conn && mysqli_ping($conn)) {
    $db_ok = true;
}

$status = $db_ok ? 'ok' : 'degraded';
http_response_code($db_ok ? 200 : 503);

echo json_encode([
    'status' => $status,
    'database' => $db_ok ? 'connected' : 'unreachable',
    'timestamp' => date('c'),
]);
