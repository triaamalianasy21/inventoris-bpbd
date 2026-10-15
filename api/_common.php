<?php
header('Content-Type: application/json');

// Prevent PHP warnings/notices from breaking JSON responses
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../app/config/auth_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function api_response($ok, $data = null, $error = null)
{
    echo json_encode([
        'ok' => (bool)$ok,
        'data' => $data,
        'error' => $error
    ]);
    exit;
}

function api_input()
{
    $raw = file_get_contents('php://input');
    $ct = strtolower($_SERVER['CONTENT_TYPE'] ?? '');
    if ($raw && strpos($ct, 'application/json') !== false) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) return $decoded;
    }
    return $_POST;
}

function require_login_api()
{
    if (!currentUserId()) {
        api_response(false, null, 'Belum login.');
    }
}

function require_role_api($roles)
{
    if (!hasRole($roles)) {
        api_response(false, null, 'Akses ditolak.');
    }
}
