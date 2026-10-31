<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
requireAdmin();

require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function rupiah(string|int|float $value): string
{
    return 'Rp' . number_format((float) $value, 0, ',', '.');
}

// Ambil ID pesanan dari URL.
$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$orderId || $orderId < 1) {
    http_response_code(400);
    exit('ID pesanan tidak valid.');
}

// Ambil informasi pesanan dan pelanggan.
$stmt = $pdo->prepare("
    SELECT
        p.id_pesanan,
        p.kode_pesanan,
        p.alamat_pengiriman,
        p.catatan,
        p.metode_pembayaran,
        p.status_pesanan,
        p.total_harga,
        p.created_at,
        pl.nama_pelanggan,
        pl.no_telepon,
        pl.email
    FROM pesanan p
    INNER JOIN pelanggan pl
        ON pl.id_pelanggan = p.id_pelanggan
    WHERE p.id_pesanan = :id
    LIMIT 1
");

$stmt->execute(['id' => $orderId]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    exit('Pesanan tidak ditemukan.');
}

// Ambil rincian produk dalam pesanan.
$stmtItems = $pdo->prepare("
    SELECT
        dp.id_produk,
        dp.jumlah,
        dp.harga_satuan,
        dp.subtotal,
        pr.nama_produk
    FROM detail_pesanan dp
    LEFT JOIN produk pr
        ON pr.id_produk = dp.id_produk
    WHERE dp.id_pesanan = :id
    ORDER BY dp.id_detail ASC
");

$stmtItems->execute(['id' => $orderId]);
$items = $stmtItems->fetchAll();

// Hitung subtotal dari detail transaksi yang tersimpan.
$subtotal = 0;

foreach ($items as $item) {
    $subtotal += (float) $item['harga_satuan'] * (int) $item['jumlah'];
}

// Total tersimpan mencakup subtotal dan ongkir.
// Ongkir dihitung sebagai selisih, bukan dihitung ulang.
$total = (float) $order['total_harga'];
$shipping = $total - $subtotal;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Detail Pesanan | Pasar Gaib</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #0b1120;
            color: #e5e7eb;
            font-family: Arial, sans-serif;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .container {
            max-width: 1100px;
            margin: auto;
            padding: 30px 20px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }

        .brand {
            font-size: 21px;
            font-weight: 800;
            color: #c4b5fd;
        }

        .back {
            color: #c4b5fd;
            font-size: 14px;
        }

        h1 {
            margin-bottom: 8px;
            font-size: 30px;
            overflow-wrap: anywhere;
        }

        .muted {
            color: #94a3b8;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 25px;
        }

        .panel {
            min-width: 0;
            padding: 22px;
            background: #111827;
            border: 1px solid #263247;
            border-radius: 15px;
            margin-bottom: 20px;
        }

        h2 {
            font-size: 18px;
            margin-top: 0;
            margin-bottom: 20px;
        }

        .info {
            display: grid;
            gap: 17px;
        }

        .info-item small {
            display: block;
            color: #94a3b8;
            margin-bottom: 6px;
        }

        .info-item div {
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .badge {
            display: inline-block;
            padding: 7px 11px;
            border-radius: 20px;
            background: #312e81;
            color: #c4b5fd;
            font-size: 12px;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 560px;
        }

        th, td {
            text-align: left;
            padding: 14px 10px;
            border-bottom: 1px solid #263247;
            font-size: 14px;
        }

        th {
            color: #94a3b8;
            font-weight: 600;
        }

        .number {
            text-align: right;
            white-space: nowrap;
        }

        .summary {
            max-width: 460px;
            margin-left: auto;
            margin-top: 22px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            padding: 11px 0;
            color: #cbd5e1;
        }

        .summary-row span:last-child {
            text-align: right;
        }

        .grand-total {
            margin-top: 8px;
            padding-top: 17px;
            border-top: 1px solid #334155;
            color: #c4b5fd;
            font-size: 19px;
            font-weight: 800;
        }

        .notice {
            margin-top: 18px;
            padding: 13px;
            border-radius: 9px;
            background: #1e293b;
            color: #cbd5e1;
            font-size: 13px;
            line-height: 1.6;
        }

        @media (max-width: 700px) {
            .container {
                padding: 22px 14px;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .panel {
                padding: 17px;
            }

            h1 {
                font-size: 25px;
            }
        }
    </style>
</head>

<body>
<main class="container">
    <header class="topbar">
        <a class="brand" href="/toko-dukun/admin/dashboard.php">
            PASAR GAIB
        </a>

        <a class="back" href="/toko-dukun/admin/orders.php">
            ← Kembali ke Pesanan
        </a>
    </header>

    <h1>Detail Pesanan</h1>

    <p class="muted">
        Kode transaksi:
        <strong><?= e($order['kode_pesanan']) ?></strong>
    </p>

    <?php if (($_GET['updated'] ?? '') === '1'): ?>
    <div class="notice">
        Status pesanan berhasil diperbarui.
    </div>
<?php elseif (isset($_GET['error'])): ?>
    <div class="notice">
        Status gagal diperbarui.
        Silakan periksa status pesanan dan coba kembali.
    </div>
<?php endif; ?>

    <div class="grid">
        <section class="panel">
            <h2>Informasi Pelanggan</h2>

            <div class="info">
                <div class="info-item">
                    <small>Nama pelanggan</small>
                    <div><?= e($order['nama_pelanggan']) ?></div>
                </div>

                <div class="info-item">
                    <small>Nomor telepon</small>
                    <div><?= e($order['no_telepon']) ?></div>
                </div>

                <div class="info-item">
                    <small>Email</small>
                    <div><?= e($order['email'] ?: '-') ?></div>
                </div>

                <div class="info-item">
                    <small>Alamat pengiriman</small>
                    <div><?= nl2br(e($order['alamat_pengiriman'])) ?></div>
                </div>

                <div class="info-item">
                    <small>Catatan pelanggan</small>
                    <div><?= nl2br(e($order['catatan'] ?: '-')) ?></div>
                </div>
            </div>
        </section>

        <section class="panel">
            <h2>Informasi Transaksi</h2>

            <div class="info">
                <div class="info-item">
                    <small>Kode pesanan</small>
                    <div><?= e($order['kode_pesanan']) ?></div>
                </div>

                <div class="info-item">
                    <small>Tanggal pesanan</small>
                    <div>
                        <?= e(date(
                            'd-m-Y H:i',
                            strtotime($order['created_at'])
                        )) ?>
                    </div>
                </div>

                <div class="info-item">
                    <small>Metode pembayaran</small>
                    <div><?= e($order['metode_pembayaran']) ?></div>
                </div>

                <div class="info-item">
                    <small>Status pesanan</small>
                    <div>
                        <span class="badge">
                            <?= e(ucwords(str_replace(
                                '_',
                                ' ',
                                $order['status_pesanan']
                            ))) ?>
                        </span>
                        <form
    action="/toko-dukun/admin/update-order-status.php"
    method="POST"
    style="margin-top: 18px;"
>
    <input
        type="hidden"
        name="csrf_token"
        value="<?= e($csrfToken) ?>"
    >

    <input
        type="hidden"
        name="id_pesanan"
        value="<?= (int) $order['id_pesanan'] ?>"
    >

    <?php
    $nextStatuses = [
        'menunggu_konfirmasi' => [
            'diproses',
            'dibatalkan',
        ],
        'diproses' => [
            'dikirim',
            'dibatalkan',
        ],
        'dikirim' => [
            'selesai',
        ],
        'selesai' => [],
        'dibatalkan' => [],
    ];

    $availableStatuses =
        $nextStatuses[$order['status_pesanan']] ?? [];
    ?>

    <?php if ($availableStatuses): ?>
        <label for="status_pesanan">Ubah status pesanan</label>

        <select
            name="status_pesanan"
            id="status_pesanan"
            required
            style="
                display: block;
                width: 100%;
                margin: 10px 0;
                padding: 12px;
                background: #0b1120;
                color: #e5e7eb;
                border: 1px solid #334155;
                border-radius: 9px;
            "
        >
            <?php foreach ($availableStatuses as $nextStatus): ?>
                <option value="<?= e($nextStatus) ?>">
                    <?= e(ucwords(str_replace(
                        '_',
                        ' ',
                        $nextStatus
                    ))) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button
            type="submit"
            onclick="return confirm('Yakin ingin mengubah status pesanan ini?')"
            style="
                padding: 12px 16px;
                border: 0;
                border-radius: 9px;
                background: #a78bfa;
                color: #111827;
                font-weight: bold;
                cursor: pointer;
            "
        >
            Simpan Status
        </button>
    <?php else: ?>
        <p class="muted">
            Pesanan ini sudah berstatus final dan tidak dapat diubah lagi.
        </p>
    <?php endif; ?>
</form>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <section class="panel">
        <h2>Produk yang Dipesan</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nama produk</th>
                        <th class="number">Harga satuan</th>
                        <th class="number">Jumlah</th>
                        <th class="number">Subtotal</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (!$items): ?>
                    <tr>
                        <td colspan="4" class="muted">
                            Tidak ada detail produk pada pesanan ini.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <?= e($item['nama_produk'] ?? (
                                    'Produk #' . $item['id_produk']
                                )) ?>
                            </td>

                            <td class="number">
                                <?= rupiah($item['harga_satuan']) ?>
                            </td>

                            <td class="number">
                                <?= (int) $item['jumlah'] ?>
                            </td>

                            <td class="number">
                                <?= rupiah(
                                    (float) $item['harga_satuan']
                                    * (int) $item['jumlah']
                                ) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="summary">
            <div class="summary-row">
                <span>Subtotal produk</span>
                <span><?= rupiah($subtotal) ?></span>
            </div>

            <div class="summary-row">
                <span>Ongkos kirim</span>
                <span><?= rupiah($shipping) ?></span>
            </div>

            <div class="summary-row grand-total">
                <span>Total pesanan</span>
                <span><?= rupiah($total) ?></span>
            </div>
        </div>

        <div class="notice">
            Ongkir ditampilkan dari selisih total pesanan dengan subtotal
            produk. Ini mengikuti total yang tersimpan saat checkout,
            bukan menghitung ulang ongkir menggunakan aturan terbaru.
        </div>
    </section>
</main>
</body>
</html>
