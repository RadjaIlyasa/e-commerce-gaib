<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

requireAdmin();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$isEdit = $id !== null && $id !== false && $id > 0;

$errors = [];
$categories = $pdo
    ->query("SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori")
    ->fetchAll();

$product = [
    'nama_produk' => '',
    'id_kategori' => '',
    'deskripsi' => '',
    'harga' => '',
    'stok' => '0',
    'status_produk' => 'aktif',
    'gambar' => '',
];

if ($isEdit) {
    $stmt = $pdo->prepare(
        "SELECT * FROM produk WHERE id_produk = :id LIMIT 1"
    );
    $stmt->execute(['id' => $id]);
    $existingProduct = $stmt->fetch();

    if (!$existingProduct) {
        http_response_code(404);
        exit('Produk tidak ditemukan.');
    }

    $product = $existingProduct;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($submittedToken) ||
        !hash_equals($csrfToken, $submittedToken)
    ) {
        $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.';
    }

    $nama = trim($_POST['nama_produk'] ?? '');
    $kategoriId = filter_var(
        $_POST['id_kategori'] ?? '',
        FILTER_VALIDATE_INT
    );
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $hargaInput = trim($_POST['harga'] ?? '');
    $stokInput = trim($_POST['stok'] ?? '');
    $statusInput = $_POST['status_produk'] ?? 'aktif';

    $product['nama_produk'] = $nama;
    $product['id_kategori'] = $kategoriId ?: '';
    $product['deskripsi'] = $deskripsi;
    $product['harga'] = $hargaInput;
    $product['stok'] = $stokInput;
    $product['status_produk'] = $statusInput;

    if ($nama === '' || mb_strlen($nama) > 150) {
        $errors[] = 'Nama produk wajib diisi dan maksimal 150 karakter.';
    }

    if (!$kategoriId || $kategoriId < 1) {
        $errors[] = 'Pilih kategori produk yang valid.';
    } else {
        $categoryCheck = $pdo->prepare(
            "SELECT COUNT(*) FROM kategori WHERE id_kategori = :id"
        );
        $categoryCheck->execute(['id' => $kategoriId]);

        if ((int) $categoryCheck->fetchColumn() === 0) {
            $errors[] = 'Kategori yang dipilih tidak ditemukan.';
        }
    }

    if (
        $hargaInput === '' ||
        !is_numeric($hargaInput) ||
        (float) $hargaInput < 0 ||
        (float) $hargaInput > 9999999999.99
    ) {
        $errors[] = 'Harga harus berupa angka yang valid dan tidak negatif.';
    }

    if (
        $stokInput === '' ||
        filter_var($stokInput, FILTER_VALIDATE_INT) === false ||
        (int) $stokInput < 0 ||
        (int) $stokInput > 4294967295
    ) {
        $errors[] = 'Stok harus berupa bilangan bulat nol atau lebih.';
    }

    if (!in_array($statusInput, ['aktif', 'nonaktif'], true)) {
        $errors[] = 'Status produk tidak valid.';
    }

    if (mb_strlen($deskripsi) > 50000) {
        $errors[] = 'Deskripsi terlalu panjang.';
    }

    $imageName = $product['gambar'] ?: null;
    $newImagePath = null;

    if (
        isset($_FILES['gambar']) &&
        $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE
    ) {
        $file = $_FILES['gambar'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Upload gambar gagal. Coba pilih gambar lagi.';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Ukuran gambar maksimal 5 MB.';
        } elseif (!is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'File upload tidak valid.';
        } else {
            $imageInfo = @getimagesize($file['tmp_name']);
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);

            $allowedTypes = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
            ];

            if (
                $imageInfo === false ||
                !isset($allowedTypes[$mimeType])
            ) {
                $errors[] = 'Format gambar harus JPG, PNG, atau WebP.';
            } else {
                $uploadDirectory = __DIR__ . '/../asset/';

                if (!is_dir($uploadDirectory) || !is_writable($uploadDirectory)) {
                    $errors[] = 'Folder asset tidak tersedia atau tidak bisa ditulisi.';
                } else {
                    $imageName = bin2hex(random_bytes(16))
                        . '.' . $allowedTypes[$mimeType];

                    $newImagePath = $uploadDirectory . $imageName;
                }
            }
        }
    }

    if (!$errors) {
        try {
            if ($newImagePath !== null) {
                if (!move_uploaded_file(
                    $_FILES['gambar']['tmp_name'],
                    $newImagePath
                )) {
                    throw new RuntimeException('Gagal menyimpan gambar.');
                }
            }

            $params = [
                'kategori' => $kategoriId,
                'nama' => $nama,
                'deskripsi' => $deskripsi !== '' ? $deskripsi : null,
                'harga' => number_format((float) $hargaInput, 2, '.', ''),
                'stok' => (int) $stokInput,
                'gambar' => $imageName,
                'status' => $statusInput,
            ];

            if ($isEdit) {
                $params['id'] = $id;

                $stmt = $pdo->prepare("
                    UPDATE produk
                    SET id_kategori = :kategori,
                        nama_produk = :nama,
                        deskripsi = :deskripsi,
                        harga = :harga,
                        stok = :stok,
                        gambar = :gambar,
                        status_produk = :status
                    WHERE id_produk = :id
                ");

                $stmt->execute($params);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO produk (
                        id_kategori,
                        nama_produk,
                        deskripsi,
                        harga,
                        stok,
                        gambar,
                        status_produk
                    ) VALUES (
                        :kategori,
                        :nama,
                        :deskripsi,
                        :harga,
                        :stok,
                        :gambar,
                        :status
                    )
                ");

                $stmt->execute($params);
            }

            header('Location: products.php?success=' . ($isEdit ? 'updated' : 'created'));
            exit;
        } catch (Throwable $exception) {
            if ($newImagePath !== null && is_file($newImagePath)) {
                unlink($newImagePath);
            }

            error_log($exception->getMessage());
            $errors[] = 'Produk gagal disimpan. Periksa database dan folder gambar.';
        }
    }
}

function e(string|int|null $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Edit' : 'Tambah' ?> Produk | Pasar Gaib</title>

    <style>
        :root {
            --bg: #0f172a;
            --surface: #1e293b;
            --border: #334155;
            --text: #f1f5f9;
            --muted: #94a3b8;
            --accent: #38bdf8;
            --red: #f87171;
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

        .container {
            max-width: 850px;
            margin: auto;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 14px;
            margin-bottom: 24px;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 27px;
        }

        .muted {
            color: var(--muted);
        }

        .panel {
            padding: 24px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: var(--surface);
        }

        .field {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: bold;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--bg);
            color: var(--text);
            font: inherit;
        }

        textarea {
            min-height: 130px;
            resize: vertical;
        }

        input[type="file"] {
            padding: 10px;
        }

        .hint {
            margin-top: 7px;
            color: var(--muted);
            font-size: 12px;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .preview {
            display: block;
            max-width: 200px;
            max-height: 200px;
            margin-top: 12px;
            border-radius: 8px;
            object-fit: contain;
            background: var(--bg);
        }

        .errors {
            margin-bottom: 20px;
            padding: 14px 18px;
            border: 1px solid #7f1d1d;
            border-radius: 8px;
            background: #450a0a;
            color: #fecaca;
        }

        .errors ul {
            margin-bottom: 0;
            padding-left: 20px;
        }

        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 24px;
        }

        .button {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            padding: 12px 16px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--bg);
            color: var(--text);
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
        }

        .primary {
            border-color: var(--accent);
            background: var(--accent);
            color: #082f49;
            font-weight: bold;
        }

        @media (max-width: 600px) {
            body {
                padding: 16px;
            }

            .panel {
                padding: 18px;
            }

            .grid {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <header class="topbar">
        <div>
            <h1><?= $isEdit ? 'Edit Produk' : 'Tambah Produk' ?></h1>
            <div class="muted">Lengkapi informasi produk Pasar Gaib.</div>
        </div>

        <a class="button" href="products.php">Kembali</a>
    </header>

    <main class="panel">
        <?php if ($errors): ?>
            <div class="errors">
                <strong>Periksa kembali data lu:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

            <div class="field">
                <label for="nama_produk">Nama Produk *</label>
                <input
                    id="nama_produk"
                    name="nama_produk"
                    type="text"
                    maxlength="150"
                    required
                    value="<?= e($product['nama_produk']) ?>"
                    placeholder="Contoh: Minyak Penglaris"
                >
            </div>

            <div class="field">
                <label for="id_kategori">Kategori *</label>
                <select id="id_kategori" name="id_kategori" required>
                    <option value="">-- Pilih kategori --</option>
                    <?php foreach ($categories as $category): ?>
                        <option
                            value="<?= (int) $category['id_kategori'] ?>"
                            <?= (string) $product['id_kategori'] === (string) $category['id_kategori'] ? 'selected' : '' ?>
                        >
                            <?= e($category['nama_kategori']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <?php if (!$categories): ?>
                    <div class="hint">
                        Belum ada kategori. Tambahkan kategori terlebih dahulu.
                    </div>
                <?php endif; ?>
            </div>

            <div class="field">
                <label for="deskripsi">Deskripsi</label>
                <textarea
                    id="deskripsi"
                    name="deskripsi"
                    maxlength="50000"
                    placeholder="Jelaskan detail produk..."
                ><?= e($product['deskripsi']) ?></textarea>
            </div>

            <div class="grid">
                <div class="field">
                    <label for="harga">Harga (Rp) *</label>
                    <input
                        id="harga"
                        name="harga"
                        type="number"
                        min="0"
                        max="9999999999.99"
                        step="0.01"
                        required
                        value="<?= e($product['harga']) ?>"
                        placeholder="50000"
                    >
                </div>

                <div class="field">
                    <label for="stok">Stok *</label>
                    <input
                        id="stok"
                        name="stok"
                        type="number"
                        min="0"
                        max="4294967295"
                        step="1"
                        required
                        value="<?= e($product['stok']) ?>"
                        placeholder="10"
                    >
                </div>
            </div>

            <div class="field">
                <label for="status_produk">Status Produk *</label>
                <select id="status_produk" name="status_produk" required>
                    <option
                        value="aktif"
                        <?= $product['status_produk'] === 'aktif' ? 'selected' : '' ?>
                    >
                        Aktif
                    </option>
                    <option
                        value="nonaktif"
                        <?= $product['status_produk'] === 'nonaktif' ? 'selected' : '' ?>
                    >
                        Nonaktif
                    </option>
                </select>
                <div class="hint">
                    Produk nonaktif tidak tersedia untuk pembelian jika API katalog memfilter status dengan benar.
                </div>
            </div>

            <div class="field">
                <label for="gambar">Gambar Produk</label>
                <input
                    id="gambar"
                    name="gambar"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                >
                <div class="hint">
                    JPG, PNG, atau WebP. Maksimal 5 MB.
                    <?= $isEdit ? 'Kosongkan jika ingin mempertahankan gambar lama.' : '' ?>
                </div>

                <?php if (!empty($product['gambar'])): ?>
                    <img
                        class="preview"
                        src="../asset/<?= e($product['gambar']) ?>"
                        alt="Gambar produk saat ini"
                    >
                <?php endif; ?>
            </div>

            <div class="actions">
                <button
                    class="button primary"
                    type="submit"
                    <?= !$categories ? 'disabled' : '' ?>
                >
                    <?= $isEdit ? 'Simpan Perubahan' : 'Simpan Produk' ?>
                </button>

                <a class="button" href="products.php">Batal</a>
            </div>
        </form>
    </main>
</div>
</body>
</html>
