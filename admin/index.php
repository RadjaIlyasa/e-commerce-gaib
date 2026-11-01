<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';

requireAdmin();

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function rupiah(mixed $amount): string
{
    return 'Rp ' . number_format((float) $amount, 0, ',', '.');
}

// Statistik pesanan.
$totalOrders = (int) $pdo->query(
    'SELECT COUNT(*) FROM pesanan'
)->fetchColumn();

$pendingOrders = (int) $pdo->query(
    "SELECT COUNT(*) FROM pesanan
     WHERE status_pesanan = 'menunggu_konfirmasi'"
)->fetchColumn();

// Ini total nilai seluruh pesanan, bukan omzet bersih.
$totalOrderValue = $pdo->query(
    'SELECT COALESCE(SUM(total_harga), 0) FROM pesanan'
)->fetchColumn();

// Statistik produk.
$activeProducts = (int) $pdo->query(
    "SELECT COUNT(*) FROM produk WHERE status_produk = 'aktif'"
)->fetchColumn();

$lowStock = (int) $pdo->query(
    "SELECT COUNT(*) FROM produk
     WHERE status_produk = 'aktif' AND stok <= 5"
)->fetchColumn();

// Lima pesanan terbaru.
$stmt = $pdo->query(
    'SELECT p.id_pesanan, p.kode_pesanan, p.total_harga,
            p.status_pesanan, p.created_at,
            pl.nama_pelanggan
     FROM pesanan p
     INNER JOIN pelanggan pl
        ON pl.id_pelanggan = p.id_pelanggan
     ORDER BY p.created_at DESC, p.id_pesanan DESC
     LIMIT 5'
);

$recentOrders = $stmt->fetchAll();

$statusLabels = [
    'menunggu_konfirmasi' => 'Menunggu konfirmasi',
    'diproses' => 'Diproses',
    'dikirim' => 'Dikirim',
    'selesai' => 'Selesai',
    'dibatalkan' => 'Dibatalkan'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Admin | Pasar Gaib</title>
    <style>
        :root {
            color-scheme: dark;
            --bg: #0c111b;
            --panel: #151d2b;
            --border: #293548;
            --text: #f2f5fa;
            --muted: #9aa8bd;
            --accent: #629fad;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Inter, "Segoe UI", sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        a { color: inherit; text-decoration: none; }

        .layout {
            display: grid;
            grid-template-columns: 230px minmax(0, 1fr);
            min-height: 100vh;
        }

        .sidebar {
            padding: 28px 18px;
            background: #101724;
            border-right: 1px solid var(--border);
        }

        .brand {
            font-size: 21px;
            font-weight: 800;
            margin: 0 0 8px;
        }

        .brand span { color: var(--accent); }

        .side-caption {
            font-size: 12px;
            color: var(--muted);
            margin-bottom: 32px;
        }

        .nav-link {
            display: block;
            padding: 12px 14px;
            border-radius: 9px;
            margin-bottom: 7px;
            color: var(--muted);
        }

        .nav-link.active,
        .nav-link:hover {
            background: #213343;
            color: #fff;
        }

        .main { min-width: 0; padding: 32px; }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }

        h1 { font-size: 27px; margin: 0 0 8px; }

        .muted { color: var(--muted); font-size: 14px; }

        .admin-name { font-size: 14px; text-align: right; }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }

        .card, .section {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 14px;
        }

        .card { padding: 20px; min-width: 0; }

        .card-label {
            color: var(--muted);
            font-size: 13px;
            margin-bottom: 14px;
        }

        .card-value {
            font-size: clamp(21px, 2vw, 29px);
            font-weight: 750;
            overflow-wrap: anywhere;
        }

        .card-note {
            color: var(--muted);
            font-size: 12px;
            margin-top: 10px;
        }

        .section { overflow: hidden; }

        .section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 20px;
            border-bottom: 1px solid var(--border);
        }

        h2 { font-size: 17px; margin: 0; }

        .table-wrap { overflow-x: auto; }

        table {
            border-collapse: collapse;
            width: 100%;
            min-width: 680px;
            text-align: left;
        }

        th, td {
            padding: 15px 20px;
            border-bottom: 1px solid var(--border);
            font-size: 13px;
            white-space: nowrap;
        }

        th {
            color: var(--muted);
            font-weight: 500;
        }

        tbody tr:last-child td { border-bottom: 0; }

        .status {
            display: inline-block;
            padding: 6px 9px;
            border-radius: 7px;
            background: #263445;
            font-size: 12px;
        }

        .empty {
            padding: 35px 20px;
            text-align: center;
            color: var(--muted);
        }

        .logout button {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 9px;
            background: transparent;
            color: var(--text);
            padding: 12px;
            cursor: pointer;
            font: inherit;
        }

        .logout { margin-top: 30px; }

        @media (max-width: 1100px) {
            .stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 700px) {
            .layout { grid-template-columns: 1fr; }

            .sidebar {
                padding: 18px;
                border-right: 0;
                border-bottom: 1px solid var(--border);
            }

            .side-caption { margin-bottom: 16px; }

            .nav-list {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
            }

            .nav-link { margin: 0; padding: 10px; }

            .logout { margin-top: 14px; }

            .main { padding: 22px 16px; }

            .stats { gap: 10px; }

            .card { padding: 15px; }

            .card-label { font-size: 12px; }

            h1 { font-size: 23px; }
        }
    </style>
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="brand">PASAR <span>GAIB</span></div>
        <div class="side-caption">ADMINISTRATION PANEL</div>

        <nav class="nav-list">
            <a class="nav-link active"
               href="<?= APP_BASE_PATH ?>/admin/index.php">
                Dashboard
            </a>
            <a class="nav-link"
               href="<?= APP_BASE_PATH ?>/admin/orders.php">
                Pesanan
            </a>
            <a class="nav-link"
               href="<?= APP_BASE_PATH ?>/admin/products.php">
                Produk
            </a>
        </nav>

        <form class="logout"
              action="<?= APP_BASE_PATH ?>/admin/logout.php"
              method="POST">
            <button type="submit">Logout</button>
        </form>
    </aside>

    <main class="main">
        <header class="topbar">
            <div>
                <h1>Dashboard</h1>
                <div class="muted">
                    Ringkasan aktivitas toko Pasar Gaib.
                </div>
            </div>

            <div class="admin-name">
                <div class="muted">Login sebagai</div>
                <strong><?= e($_SESSION['admin_name'] ?? 'Admin') ?></strong>
            </div>
        </header>

        <section class="stats">
            <article class="card">
                <div class="card-label">Total Pesanan</div>
                <div class="card-value">
                    <?= number_format($totalOrders, 0, ',', '.') ?>
                </div>
                <div class="card-note">Semua pesanan tersimpan</div>
            </article>

            <article class="card">
                <div class="card-label">Menunggu Konfirmasi</div>
                <div class="card-value">
                    <?= number_format($pendingOrders, 0, ',', '.') ?>
                </div>
                <div class="card-note">Perlu ditinjau admin</div>
            </article>

            <article class="card">
                <div class="card-label">Total Nilai Pesanan</div>
                <div class="card-value">
                    <?= e(rupiah($totalOrderValue)) ?>
                </div>
                <div class="card-note">Nilai seluruh pesanan, bukan omzet bersih</div>
            </article>

            <article class="card">
                <div class="card-label">Produk Aktif</div>
                <div class="card-value">
                    <?= number_format($activeProducts, 0, ',', '.') ?>
                </div>
                <div class="card-note">
                    <?= number_format($lowStock, 0, ',', '.') ?>
                    produk dengan stok 5 atau kurang
                </div>
            </article>
        </section>

        <section class="section" id="pesanan">
            <div class="section-head">
                <h2>Pesanan Terbaru</h2>
                <span class="muted">5 pesanan terakhir</span>
            </div>

            <?php if (!$recentOrders): ?>
                <div class="empty">Belum ada pesanan.</div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>Kode Pesanan</th>
                            <th>Pelanggan</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td>
                                    <strong><?= e($order['kode_pesanan']) ?></strong>
                                </td>
                                <td><?= e($order['nama_pelanggan']) ?></td>
                                <td><?= e(rupiah($order['total_harga'])) ?></td>
                                <td>
                                    <span class="status">
                                        <?= e(
                                            $statusLabels[$order['status_pesanan']]
                                            ?? $order['status_pesanan']
                                        ) ?>
                                    </span>
                                </td>
                                <td>
                                    <?= e(date(
                                        'd-m-Y H:i',
                                        strtotime($order['created_at'])
                                    )) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <p class="muted" style="margin-top: 22px;">
            Data ditampilkan langsung dari database Pasar Gaib.
        </p>
    </main>
</div>
</body>
</html>
