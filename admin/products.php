<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

requireAdmin();

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';

$allowedStatuses = ['aktif', 'nonaktif'];

if (!in_array($status, $allowedStatuses, true)) {
    $status = '';
}

$sql = "
    SELECT
        p.id_produk,
        p.nama_produk,
        p.harga,
        p.stok,
        p.gambar,
        p.status_produk,
        p.created_at,
        k.nama_kategori
    FROM produk p
    LEFT JOIN kategori k
        ON p.id_kategori = k.id_kategori
    WHERE 1 = 1
";

$params = [];

if ($search !== '') {
    $sql .= " AND p.nama_produk LIKE :search";
    $params['search'] = '%' . $search . '%';
}

if ($status !== '') {
    $sql .= " AND p.status_produk = :status";
    $params['status'] = $status;
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$totalProducts = (int) $pdo
    ->query("SELECT COUNT(*) FROM produk")
    ->fetchColumn();

$activeProducts = (int) $pdo
    ->query("SELECT COUNT(*) FROM produk WHERE status_produk = 'aktif'")
    ->fetchColumn();

$inactiveProducts = $totalProducts - $activeProducts;

function rupiah(float|string $amount): string
{
    return 'Rp ' . number_format((float) $amount, 0, ',', '.');
}

function e(string|null $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Produk | Pasar Gaib</title>

    <style>
        :root {
            --bg: #0f172a;
            --surface: #1e293b;
            --border: #334155;
            --text: #f1f5f9;
            --muted: #94a3b8;
            --accent: #38bdf8;
            --green: #4ade80;
            --red: #f87171;
            --yellow: #facc15;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 28px;
            background: var(--bg);
            color: var(--text);
            font-family: Arial, sans-serif;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .container {
            max-width: 1400px;
            margin: auto;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 28px;
        }

        .topbar h1 {
            margin: 0 0 8px;
            font-size: 28px;
        }

        .muted {
            color: var(--muted);
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 11px 16px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--surface);
            color: var(--text);
            font-size: 14px;
            cursor: pointer;
        }

        .button-primary {
            border-color: var(--accent);
            background: var(--accent);
            color: #082f49;
            font-weight: bold;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat, .panel {
            border: 1px solid var(--border);
            border-radius: 12px;
            background: var(--surface);
        }

        .stat {
            padding: 20px;
        }

        .stat p {
            margin: 0 0 10px;
            color: var(--muted);
            font-size: 14px;
        }

        .stat strong {
            font-size: 27px;
        }

        .panel {
            overflow: hidden;
        }

        .filters {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            padding: 20px;
            border-bottom: 1px solid var(--border);
        }

        input, select {
            min-height: 42px;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--bg);
            color: var(--text);
            font-size: 14px;
        }

        input {
            flex: 1;
            min-width: 200px;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            white-space: nowrap;
        }

        th, td {
            padding: 16px 18px;
            border-bottom: 1px solid var(--border);
            text-align: left;
            font-size: 14px;
        }

        th {
            color: var(--muted);
            font-weight: 600;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .product {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 220px;
        }

        .product img, .image-placeholder {
            width: 52px;
            height: 52px;
            flex-shrink: 0;
            border-radius: 8px;
            object-fit: cover;
            background: var(--bg);
        }

        .image-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--muted);
            font-size: 11px;
        }

        .product-name {
            font-weight: bold;
            white-space: normal;
        }

        .category {
            margin-top: 5px;
            color: var(--muted);
            font-size: 12px;
        }

        .badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-active {
            background: #14532d;
            color: var(--green);
        }

        .badge-inactive {
            background: #7f1d1d;
            color: #fecaca;
        }

        .stock-low {
            color: var(--yellow);
        }

        .stock-empty {
            color: var(--red);
        }

        .empty {
            padding: 40px 20px;
            color: var(--muted);
            text-align: center;
        }

        .footer {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            padding: 16px 20px;
            border-top: 1px solid var(--border);
            color: var(--muted);
            font-size: 13px;
        }

        @media (max-width: 700px) {
            body {
                padding: 16px;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .topbar h1 {
                font-size: 23px;
            }

            .filters {
                flex-direction: column;
            }

            .filters input,
            .filters select,
            .filters button {
                width: 100%;
            }
        }
    </style>
</head>

<body>
<div class="container">
    <header class="topbar">
        <div>
            <h1>Manajemen Produk</h1>
            <div class="muted">
                Kelola katalog produk Pasar Gaib.
            </div>
        </div>

        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <a class="button" href="<?= APP_BASE_PATH ?>/admin/index.php">
                Kembali ke Dashboard
            </a>

            <a class="button button-primary" href="<?= APP_BASE_PATH ?>/admin/product-form.php">
                + Tambah Produk
            </a>
        </div>
    </header>

    <section class="stats">
        <div class="stat">
            <p>Total Produk</p>
            <strong><?= number_format($totalProducts, 0, ',', '.') ?></strong>
        </div>

        <div class="stat">
            <p>Produk Aktif</p>
            <strong style="color:var(--green)">
                <?= number_format($activeProducts, 0, ',', '.') ?>
            </strong>
        </div>

        <div class="stat">
            <p>Produk Nonaktif</p>
            <strong style="color:var(--red)">
                <?= number_format($inactiveProducts, 0, ',', '.') ?>
            </strong>
        </div>
    </section>

    <section class="panel">
        <form class="filters" method="GET">
            <input
                type="search"
                name="search"
                placeholder="Cari nama produk..."
                value="<?= e($search) ?>"
            >

            <select name="status">
                <option value="">Semua Status</option>
                <option value="aktif" <?= $status === 'aktif' ? 'selected' : '' ?>>
                    Aktif
                </option>
                <option value="nonaktif" <?= $status === 'nonaktif' ? 'selected' : '' ?>>
                    Nonaktif
                </option>
            </select>

            <button class="button button-primary" type="submit">
                Cari Produk
            </button>

            <a class="button" href="<?= APP_BASE_PATH ?>/admin/products.php">
                Reset
            </a>
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Produk</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Status</th>
                    <th>Dibuat</th>
                    <th>Aksi</th>
                </tr>
                </thead>

                <tbody>
                <?php if (!$products): ?>
                    <tr>
                        <td colspan="6" class="empty">
                            Produk tidak ditemukan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <div class="product">
                                    <?php if (!empty($product['gambar'])): ?>
                                        <img
                                            src="../asset/<?= e($product['gambar']) ?>"
                                            alt="<?= e($product['nama_produk']) ?>"
                                            loading="lazy"
                                            onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
                                        >
                                        <div class="image-placeholder" style="display:none">
                                            No Image
                                        </div>
                                    <?php else: ?>
                                        <div class="image-placeholder">
                                            No Image
                                        </div>
                                    <?php endif; ?>

                                    <div>
                                        <div class="product-name">
                                            <?= e($product['nama_produk']) ?>
                                        </div>
                                        <div class="category">
                                            <?= e($product['nama_kategori'] ?? 'Tanpa kategori') ?>
                                        </div>
                                        <div class="category">
                                            ID: <?= (int) $product['id_produk'] ?>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td><?= rupiah($product['harga']) ?></td>

                            <td>
                                <?php
                                $stock = (int) $product['stok'];
                                $stockClass = $stock === 0
                                    ? 'stock-empty'
                                    : ($stock <= 5 ? 'stock-low' : '');
                                ?>
                                <span class="<?= $stockClass ?>">
                                    <?= number_format($stock, 0, ',', '.') ?> unit
                                </span>
                            </td>

                            <td>
                                <?php if ($product['status_produk'] === 'aktif'): ?>
                                    <span class="badge badge-active">Aktif</span>
                                <?php else: ?>
                                    <span class="badge badge-inactive">Nonaktif</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= e(date('d-m-Y', strtotime($product['created_at']))) ?>
                            </td>

                            <td>     
                            <a class="button"
                             href="<?= APP_BASE_PATH ?>/admin/product-form.php?id=<?= (int) $product['id_produk'] ?>">
                                 Edit
                            </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="footer">
            <span>Menampilkan <?= count($products) ?> produk</span>
            <span>Pasar Gaib · Admin Panel</span>
        </div>
    </section>
</div>
</body>
</html>
