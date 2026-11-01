<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
requireAdmin();

require_once __DIR__ . '/../config/database.php';

// Daftar status yang tersedia pada sistem.
$statuses = [
    'menunggu_konfirmasi',
    'diproses',
    'dikirim',
    'selesai',
    'dibatalkan',
];

// Ambil input pencarian dan filter dari URL.
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

// Tolak status filter yang tidak dikenal.
if ($status !== '' && !in_array($status, $statuses, true)) {
    $status = '';
}

// Susun query dengan prepared statement.
$sql = "
    SELECT
        p.id_pesanan,
        p.kode_pesanan,
        p.status_pesanan,
        p.total_harga,
        p.created_at,
        pl.nama_pelanggan,
        pl.no_telepon
    FROM pesanan p
    INNER JOIN pelanggan pl
        ON pl.id_pelanggan = p.id_pelanggan
    WHERE 1 = 1
";

$params = [];

if ($search !== '') {
    $sql .= "
        AND (
            p.kode_pesanan LIKE :kode
            OR pl.nama_pelanggan LIKE :nama
        )
    ";

    $params['kode'] = '%' . $search . '%';
    $params['nama'] = '%' . $search . '%';
}

if ($status !== '') {
    $sql .= " AND p.status_pesanan = :status";
    $params['status'] = $status;
}

$sql .= " ORDER BY p.created_at DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

function e(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function rupiah(string|int|float $value): string
{
    return 'Rp' . number_format(
        (float) $value,
        0,
        ',',
        '.'
    );
}

function statusLabel(string $status): string
{
    return ucwords(str_replace('_', ' ', $status));
}

function statusClass(string $status): string
{
    return match ($status) {
        'menunggu_konfirmasi' => 'pending',
        'diproses' => 'processing',
        'dikirim' => 'shipped',
        'selesai' => 'completed',
        'dibatalkan' => 'cancelled',
        default => 'unknown',
    };
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manajemen Pesanan | Pasar Gaib</title>

    <style>
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #0b1120;
            color: #e5e7eb;
        }
        a {
            color: inherit;
            text-decoration: none;
        }
        .layout {
            max-width: 1400px;
            margin: auto;
            padding: 32px 24px;
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        .brand {
            font-size: 22px;
            font-weight: 800;
            color: #c4b5fd;
        }
        .back-link {
            color: #c4b5fd;
            font-size: 14px;
        }
        h1 {
            margin-bottom: 8px;
            font-size: clamp(25px, 4vw, 34px);
        }
        .subtitle {
            margin-top: 0;
            margin-bottom: 28px;
            color: #94a3b8;
        }
        .panel {
            background: #111827;
            border: 1px solid #263247;
            border-radius: 16px;
            padding: 22px;
        }
        .filters {
            display: flex;
            gap: 12px;
            margin-bottom: 22px;
            flex-wrap: wrap;
        }
        input, select, button {
            font: inherit;
            border-radius: 9px;
            padding: 12px 14px;
        }
        input,
        select {
            color: #e5e7eb;
            background: #0b1120;
            border: 1px solid #334155;
        }
        input {
            flex: 1;
            min-width: 200px;
        }
        select {
            min-width: 190px;
        }
        button {
            border: 0;
            background: #a78bfa;
            color: #111827;
            font-weight: 700;
            cursor: pointer;
        }
        .reset {
            display: inline-flex;
            align-items: center;
            color: #cbd5e1;
            padding: 12px;
            font-size: 14px;
        }
        .table-wrap {
            width: 100%;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }
        th, td {
            padding: 16px 12px;
            text-align: left;
            border-bottom: 1px solid #263247;
            font-size: 14px;
        }
        th {
            color: #94a3b8;
            font-weight: 600;
            white-space: nowrap;
        }
        td {
            color: #e5e7eb;
        }
        .order-code {
            font-weight: 700;
            color: #c4b5fd;
        }
        .customer-phone, .date {
            color: #94a3b8;
            font-size: 12px;
            margin-top: 5px;
        }
        .status {
            display: inline-block;
            padding: 6px 9px;
            border-radius: 20px;
            font-size: 12px;
            white-space: nowrap;
        }
        .pending {
            background: #422006;
            color: #fcd34d;
        }
        .processing {
            background: #172554;
            color: #93c5fd;
        }
        .shipped {
            background: #312e81;
            color: #c4b5fd;
        }
        .completed {
            background: #064e3b;
            color: #6ee7b7;
        }
        .cancelled {
            background: #450a0a;
            color: #fca5a5;
        }
        .unknown {
            background: #374151;
            color: #e5e7eb;
        }
        .empty {
            padding: 36px 12px;
            text-align: center;
            color: #94a3b8;
        }
        .result-count {
            margin-bottom: 18px;
            color: #94a3b8;
            font-size: 14px;
        }
        @media (max-width: 600px) {
            .layout {
                padding: 22px 14px;
            }
            .panel {
                padding: 14px;
            }
            .filters {
                flex-direction: column;
            }
            input, select, button {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <main class="layout">
        <header class="topbar">
            <a class="brand" href="<?= APP_BASE_PATH ?>/admin/index.php">
                PASAR GAIB
            </a>
            <a
                class="back-link"
                href="<?= APP_BASE_PATH ?>/admin/index.php"
            >
                ← Kembali ke Dashboard
            </a>
        </header>

        <h1>Manajemen Pesanan</h1>

        <p class="subtitle">
            Pantau dan cari pesanan pelanggan yang masuk.
        </p>

        <section class="panel">
            <form class="filters" method="GET">
                <input
                    type="search"
                    name="search"
                    placeholder="Cari kode pesanan atau nama pelanggan..."
                    value="<?= e($search) ?>"
                >
                <select name="status">
                    <option value="">Semua Status</option>

                    <?php foreach ($statuses as $item): ?>
                        <option
                            value="<?= e($item) ?>"
                            <?= $status === $item ? 'selected' : '' ?>
                        >
                            <?= e(statusLabel($item)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit">Cari Pesanan</button>

                <a class="reset" href="<?= APP_BASE_PATH ?>/admin/orders.php">Reset</a>
            </form>

            <p class="result-count">
                Menampilkan <?= count($orders) ?> pesanan
                <?php if (count($orders) === 100): ?>
                    (maksimal 100 data terbaru)
                <?php endif; ?>
            </p>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Kode Pesanan</th>
                            <th>Pelanggan</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Tanggal Pesanan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!$orders): ?>
                            <tr>
                                <td colspan="6" class="empty">
                                    Belum ada pesanan atau data tidak ditemukan.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>
                                        <span class="order-code">
                                            <?= e($order['kode_pesanan']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= e($order['nama_pelanggan']) ?>

                                        <div class="customer-phone">
                                            <?= e($order['no_telepon']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?= rupiah($order['total_harga']) ?>
                                    </td>
                                    <td>
                                        <span class="status <?= e(statusClass($order['status_pesanan'])) ?>">
                                            <?= e(statusLabel($order['status_pesanan'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= e(date(
                                            'd-m-Y H:i',
                                            strtotime($order['created_at'])
                                        )) ?>
                                    </td>
                                    <td>
                                        <a
                                        href="<?= APP_BASE_PATH ?>/admin/order-detail.php?id=<?= (int) $order['id_pesanan'] ?>"
                                        style="color: #c4b5fd; font-weight: 700;"
                                        >
                                        Lihat Detail →
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
