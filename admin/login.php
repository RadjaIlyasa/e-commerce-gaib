<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: ' . APP_BASE_PATH . '/admin/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT id_admin, username, password_hash, nama_admin
             FROM admin
             WHERE username = ?
             LIMIT 1'
        );

        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if (
            $admin &&
            password_verify($password, $admin['password_hash'])
        ) {
            session_regenerate_id(true);

            $_SESSION['admin_id'] = (int) $admin['id_admin'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name'] = $admin['nama_admin'];

            header('Location: ' . APP_BASE_PATH . '/admin/index.php');
            exit;
        }

        $error = 'Username atau password salah.';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Admin - Pasar Gaib</title>
</head>
<body>
    <h1>Login Admin Pasar Gaib</h1>

    <?php if ($error !== ''): ?>
        <p style="color: red;">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <form method="POST">
        <label for="username">Username</label><br>
        <input
            id="username"
            name="username"
            maxlength="50"
            autocomplete="username"
            required
        ><br><br>

        <label for="password">Password</label><br>
        <input
            id="password"
            type="password"
            name="password"
            autocomplete="current-password"
            required
        ><br><br>

        <button type="submit">Masuk</button>
    </form>
</body>
</html>
