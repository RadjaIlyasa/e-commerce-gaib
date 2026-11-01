<?php
declare(strict_types=1);

// Path otomatis untuk localhost dan hosting.
$isLocal = in_array(
    $_SERVER['HTTP_HOST'] ?? '',
    ['localhost', '127.0.0.1']
);

if (!defined('APP_BASE_PATH')) {
    define('APP_BASE_PATH', $isLocal ? '/toko-dukun' : '');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = !empty($_SERVER['HTTPS'])
        && $_SERVER['HTTPS'] !== 'off';

    session_set_cookie_params([
        'httponly' => true,
        'secure' => $isHttps,
        'samesite' => 'Lax',
        'path' => APP_BASE_PATH . '/'
    ]);

    session_start();
}

function requireAdmin(): void
{
    if (empty($_SESSION['admin_id'])) {
        header(
            'Location: ' . APP_BASE_PATH . '/admin/login.php'
        );
        exit;
    }
}
