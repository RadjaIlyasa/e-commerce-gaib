<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

function respond(int $status, array $data): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');

    respond(405, [
        'success' => false,
        'message' => 'Metode request tidak diizinkan.'
    ]);
}

$pdo = null;

try {
    require_once __DIR__ . '/../config/database.php';

    if (!$pdo instanceof PDO) {
        throw new RuntimeException('Koneksi PDO tidak tersedia.');
    }

    $input = json_decode(
        file_get_contents('php://input'),
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    if (!is_array($input)) {
        respond(400, [
            'success' => false,
            'message' => 'Format data tidak valid.'
        ]);
    }

    $customer = $input['customer'] ?? null;
    $items = $input['items'] ?? null;

    // Metode pembayaran default COD untuk menjaga kompatibilitas
    // selama frontend belum mengirim payment_method.
    $paymentMethod = strtoupper(trim(
        (string) ($input['payment_method'] ?? 'COD')
    ));

    if (!in_array($paymentMethod, ['COD', 'QRIS', 'TRANSFER'], true)) {
        respond(422, [
            'success' => false,
            'message' => 'Metode pembayaran tidak valid.'
        ]);
    }

    if (!is_array($customer) || !is_array($items) || count($items) < 1) {
        respond(422, [
            'success' => false,
            'message' => 'Data pelanggan dan keranjang wajib diisi.'
        ]);
    }

    // 1. Validasi data pelanggan.
    $nama = trim((string) ($customer['nama'] ?? ''));
    $email = trim((string) ($customer['email'] ?? ''));
    $telepon = trim((string) ($customer['telepon'] ?? ''));
    $alamat = trim((string) ($customer['alamat'] ?? ''));
    $catatan = trim((string) ($customer['catatan'] ?? ''));

    if (
        $nama === '' ||
        mb_strlen($nama) > 100 ||
        $email === '' ||
        strlen($email) > 150 ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        $telepon === '' ||
        strlen($telepon) > 20 ||
        $alamat === ''
    ) {
        respond(422, [
            'success' => false,
            'message' => 'Periksa kembali nama, email, nomor telepon, dan alamat.'
        ]);
    }

    // 2. Validasi dan gabungkan produk yang sama.
    if (count($items) > 50) {
        respond(422, [
            'success' => false,
            'message' => 'Jumlah jenis produk dalam pesanan terlalu banyak.'
        ]);
    }

    $quantities = [];

    foreach ($items as $item) {
        if (!is_array($item)) {
            respond(422, [
                'success' => false,
                'message' => 'Format item keranjang tidak valid.'
            ]);
        }

        $rawId = $item['product_id'] ?? null;
        $rawQuantity = $item['quantity'] ?? null;

        if (
            !(is_int($rawId) || (is_string($rawId) && ctype_digit($rawId))) ||
            !(is_int($rawQuantity) || (is_string($rawQuantity) && ctype_digit($rawQuantity)))
        ) {
            respond(422, [
                'success' => false,
                'message' => 'ID produk atau jumlah tidak valid.'
            ]);
        }

        $productId = (int) $rawId;
        $quantity = (int) $rawQuantity;

        if ($productId < 1 || $quantity < 1) {
            respond(422, [
                'success' => false,
                'message' => 'ID produk dan jumlah harus lebih dari nol.'
            ]);
        }

        $quantities[$productId] = ($quantities[$productId] ?? 0) + $quantity;

        if ($quantities[$productId] > 2147483647) {
            respond(422, [
                'success' => false,
                'message' => 'Jumlah produk terlalu besar.'
            ]);
        }
    }

    if (count($quantities) < 1) {
        respond(422, [
            'success' => false,
            'message' => 'Keranjang belanja kosong.'
        ]);
    }

    // 3. Mulai transaksi database.
    $pdo->beginTransaction();

    $findProduct = $pdo->prepare(
        "SELECT id_produk, nama_produk, harga, stok
         FROM produk
         WHERE id_produk = ? AND status_produk = 'aktif'
         FOR UPDATE"
    );

    $validatedProducts = [];
    $subtotalCents = 0;

    foreach ($quantities as $productId => $quantity) {
        $findProduct->execute([$productId]);
        $product = $findProduct->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new DomainException(
                'Salah satu produk tidak ditemukan atau sudah tidak aktif.'
            );
        }

        if ((int) $product['stok'] < $quantity) {
            throw new DomainException(
                'Stok produk "' . $product['nama_produk'] .
                '" tidak mencukupi. Silakan perbarui keranjang.'
            );
        }

        // Hitung dalam satuan sen.
        $priceCents = (int) round((float) $product['harga'] * 100);

        if ($priceCents < 0) {
            throw new RuntimeException('Harga produk tidak valid.');
        }

        $subtotalCents += $priceCents * $quantity;

        $validatedProducts[] = [
            'id_produk' => (int) $product['id_produk'],
            'harga' => $product['harga'],
            'jumlah' => $quantity
        ];
    }

    // 4. Hitung ongkir mengikuti aturan cart.js.
    $shippingCents = 0;

    if ($subtotalCents > 0 && $subtotalCents < 100000000) {
        $shippingCents = 5000000;
    } elseif ($subtotalCents >= 100000000 && $subtotalCents < 500000000) {
        $shippingCents = 2500000;
    }

    $totalCents = $subtotalCents + $shippingCents;

    // 5. Siapkan data pembayaran.
    $paymentCode = null;
    $paymentStatus = 'menunggu_pembayaran';

    if ($paymentMethod === 'QRIS') {
        $paymentCode = 'PGQR-' . strtoupper(bin2hex(random_bytes(5)));
    } elseif ($paymentMethod === 'TRANSFER') {
        $paymentCode = 'PGTR-' . strtoupper(bin2hex(random_bytes(5)));
    }

    // COD tidak memiliki batas waktu pembayaran.
    // QRIS dan transfer memiliki waktu pembayaran 15 menit.
    $paymentDeadlineSql = in_array(
        $paymentMethod,
        ['QRIS', 'TRANSFER'],
        true
    )
        ? 'DATE_ADD(NOW(), INTERVAL 15 MINUTE)'
        : 'NULL';

    // 6. Simpan pelanggan.
    $insertCustomer = $pdo->prepare(
        'INSERT INTO pelanggan (nama_pelanggan, no_telepon, email)
         VALUES (?, ?, ?)'
    );

    $insertCustomer->execute([$nama, $telepon, $email]);
    $customerId = (int) $pdo->lastInsertId();

    // 7. Buat kode pesanan.
    $orderCode = 'PG-' . date('Ymd') . '-' .
        strtoupper(bin2hex(random_bytes(4)));

    // 8. Simpan pesanan beserta informasi pembayaran.
    $insertOrder = $pdo->prepare(
        "INSERT INTO pesanan
            (
                kode_pesanan,
                id_pelanggan,
                alamat_pengiriman,
                catatan,
                metode_pembayaran,
                status_pembayaran,
                kode_pembayaran,
                batas_pembayaran,
                status_pesanan,
                total_harga
            )
         VALUES (
            ?, ?, ?, ?, ?, ?, ?, {$paymentDeadlineSql},
            'menunggu_konfirmasi', ?
         )"
    );

    $insertOrder->execute([
        $orderCode,
        $customerId,
        $alamat,
        $catatan !== '' ? $catatan : null,
        $paymentMethod,
        $paymentStatus,
        $paymentCode,
        number_format($totalCents / 100, 2, '.', '')
    ]);

    $orderId = (int) $pdo->lastInsertId();

    // 9. Simpan detail pesanan dan kurangi stok.
    $insertDetail = $pdo->prepare(
        'INSERT INTO detail_pesanan
            (id_pesanan, id_produk, jumlah, harga_satuan)
         VALUES (?, ?, ?, ?)'
    );

    $updateStock = $pdo->prepare(
        "UPDATE produk
         SET stok = stok - ?
         WHERE id_produk = ?
           AND status_produk = 'aktif'
           AND stok >= ?"
    );

    foreach ($validatedProducts as $product) {
        $updateStock->execute([
            $product['jumlah'],
            $product['id_produk'],
            $product['jumlah']
        ]);

        if ($updateStock->rowCount() !== 1) {
            throw new DomainException(
                'Stok produk berubah saat checkout. Silakan coba lagi.'
            );
        }

        $insertDetail->execute([
            $orderId,
            $product['id_produk'],
            $product['jumlah'],
            $product['harga']
        ]);
    }

    // 10. Ambil batas pembayaran yang dibuat oleh MySQL.
    $findDeadline = $pdo->prepare(
        'SELECT batas_pembayaran FROM pesanan WHERE id_pesanan = ?'
    );

    $findDeadline->execute([$orderId]);
    $paymentDeadline = $findDeadline->fetchColumn();

    // 11. Simpan seluruh perubahan.
    $pdo->commit();

    respond(201, [
        'success' => true,
        'message' => 'Pesanan berhasil dibuat.',
        'data' => [
            'id_pesanan' => $orderId,
            'order_number' => $orderCode,
            'subtotal' => $subtotalCents / 100,
            'shipping' => $shippingCents / 100,
            'total' => $totalCents / 100,
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'payment_code' => $paymentCode,
            'payment_deadline' => $paymentDeadline ?: null,
            'status' => 'menunggu_konfirmasi'
        ]
    ]);

} catch (JsonException $e) {
    respond(400, [
        'success' => false,
        'message' => 'Format JSON tidak valid.'
    ]);

} catch (DomainException $e) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    respond(422, [
        'success' => false,
        'message' => $e->getMessage()
    ]);

} catch (Throwable $e) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Checkout error: ' . $e->getMessage());

    respond(500, [
        'success' => false,
        'message' => 'Pesanan gagal diproses. Silakan coba kembali.'
    ]);
}
