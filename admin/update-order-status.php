<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
requireAdmin();

require_once __DIR__ . '/../config/database.php';

function redirectToDetail(int $id, string $message): never
{
    header(
        'Location: /toko-dukun/admin/order-detail.php?id='
        . $id . '&' . $message
    );
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Metode permintaan tidak diizinkan.');
}

// Validasi ID pesanan.
$orderId = filter_input(INPUT_POST, 'id_pesanan', FILTER_VALIDATE_INT);

if (!$orderId || $orderId < 1) {
    http_response_code(400);
    exit('ID pesanan tidak valid.');
}

// Validasi token CSRF.
$sessionToken = $_SESSION['csrf_token'] ?? '';
$submittedToken = $_POST['csrf_token'] ?? '';

if (
    !is_string($sessionToken) ||
    !is_string($submittedToken) ||
    $sessionToken === '' ||
    !hash_equals($sessionToken, $submittedToken)
) {
    http_response_code(403);
    exit('Token keamanan tidak valid. Muat ulang halaman lalu coba lagi.');
}

$newStatus = $_POST['status_pesanan'] ?? '';

$allowedStatuses = [
    'diproses',
    'dikirim',
    'selesai',
    'dibatalkan',
];

if (
    !is_string($newStatus) ||
    !in_array($newStatus, $allowedStatuses, true)
) {
    redirectToDetail($orderId, 'error=status_tidak_valid');
}

try {
    $pdo->beginTransaction();

    // Kunci baris pesanan agar dua permintaan tidak membatalkan
    // pesanan yang sama secara bersamaan.
    $stmt = $pdo->prepare("
        SELECT
        status_pesanan,
        metode_pembayaran,
        status_pembayaran
    FROM pesanan
    WHERE id_pesanan = :id
    FOR UPDATE
    ");

    $stmt->execute(['id' => $orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        $pdo->rollBack();
        redirectToDetail($orderId, 'error=pesanan_tidak_ditemukan');
    }

    $currentStatus = $order['status_pesanan'];

    if (
        $newStatus === 'dibatalkan' &&
        in_array($order['metode_pembayaran'], ['QRIS', 'TRANSFER'], true)
    ) {
    $pdo->rollBack();

    redirectToDetail(
        $orderId,
        'error=pembatalan_pembayaran_digital_tidak_diizinkan'
        );
    }

    // Aturan perpindahan status yang diperbolehkan.
    $transitions = [
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

    if (!in_array(
        $newStatus,
        $transitions[$currentStatus] ?? [],
        true
    )) {
        $pdo->rollBack();
        redirectToDetail($orderId, 'error=transisi_tidak_diizinkan');
    }

    // Kembalikan stok hanya ketika pembatalan pertama berhasil.
    // Status pesanan masih terkunci sampai transaksi selesai.
    if ($newStatus === 'dibatalkan') {
        $stmt = $pdo->prepare("
            UPDATE produk pr
            INNER JOIN detail_pesanan dp
                ON dp.id_produk = pr.id_produk
            SET pr.stok = pr.stok + dp.jumlah
            WHERE dp.id_pesanan = :id
        ");

        $stmt->execute(['id' => $orderId]);
    }

    $stmt = $pdo->prepare("
        UPDATE pesanan
        SET status_pesanan = :status
        WHERE id_pesanan = :id
    ");

    $stmt->execute([
        'status' => $newStatus,
        'id' => $orderId,
    ]);

    $pdo->commit();

    redirectToDetail($orderId, 'updated=1');

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    redirectToDetail($orderId, 'error=gagal_memperbarui');
}