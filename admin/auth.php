<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = !empty($_SERVER['HTTPS'])
        && $_SERVER['HTTPS'] !== 'off';

    session_set_cookie_params([
        'httponly' => true,
        'secure' => $isHttps,
        'samesite' => 'Lax',
        'path' => '/toko-dukun/'
    ]);

    session_start();
}

function requireAdmin(): void
{
    if (empty($_SESSION['admin_id'])) {
        header('Location: /toko-dukun/admin/login.php');
        exit;
    }
}
