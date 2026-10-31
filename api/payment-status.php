<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

function respond(int $statusCode, array $data): never
{
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');

    respond(405, [
        'success' => false,
        'message' => 'Metode permintaan tidak diizinkan.'
    ]);
}

try {
    $input = json_decode(
        file_get_contents('php://input'),
        true,
        512,
        JSON_THROW_ON_ERROR
    );
} catch (JsonException $e) {
    respond(400, [
        'success' => false,
        'message' => 'Format JSON tidak valid.'
    ]);
}

if (!is_array($input)) {
    respond(400, [
        'success' => false,
        'message' => 'Data tidak valid.'
    ]);
}

$orderCode = trim((string) ($input['kode_pesanan'] ?? ''));

if ($orderCode === '' || strlen($orderCode) > 30) {
    respond(400, [
        'success' => false,
        'message' => 'Kode pesanan tidak valid.'
    ]);
}

try {
    $pdo->beginTransaction();

    // Kunci pesanan selama pemeriksaan.
    $stmt = $pdo->prepare("
        SELECT
            id_pesanan,
            kode_pesanan,
            metode_pembayaran,
            status_pembayaran,
            status_pesanan,
            batas_pembayaran,
            total_harga,
            GREATEST(
                TIMESTAMPDIFF(SECOND, NOW(), batas_pembayaran),
                0
            ) AS sisa_detik,
            NOW() AS waktu_sekarang
        FROM pesanan
        WHERE kode_pesanan = :kode
        LIMIT 1
        FOR UPDATE
    ");

    $stmt->execute(['kode' => $orderCode]);
    $order = $stmt->fetch();
        

    if (!$order) {
        $pdo->rollBack();

        respond(404, [
            'success' => false,
            'message' => 'Pesanan tidak ditemukan.'
        ]);
    }

    // COD tidak menggunakan sistem kedaluwarsa pembayaran.
    if (!in_array(
        $order['metode_pembayaran'],
        ['QRIS', 'TRANSFER'],
        true
    )) {
        $pdo->commit();

        respond(200, [
            'success' => true,
            'status_pembayaran' => $order['status_pembayaran'],
            'status_pesanan' => $order['status_pesanan'],
            'message' => 'Pesanan menggunakan metode COD.'
        ]);
    }

    // Jika pembayaran masih menunggu dan waktunya habis.
    if (
        $order['status_pembayaran'] === 'menunggu_pembayaran' &&
        (
            empty($order['batas_pembayaran']) ||
            strtotime($order['batas_pembayaran']) <=
            strtotime($order['waktu_sekarang'])
        )
    ) {
        // Kembalikan stok seluruh produk pesanan.
        $stmt = $pdo->prepare("
            UPDATE produk pr
            INNER JOIN detail_pesanan dp
                ON dp.id_produk = pr.id_produk
            SET pr.stok = pr.stok + dp.jumlah
            WHERE dp.id_pesanan = :id
        ");

        $stmt->execute([
            'id' => $order['id_pesanan']
        ]);

        // Ubah status pembayaran dan pesanan.
        $stmt = $pdo->prepare("
            UPDATE pesanan
            SET
                status_pembayaran = 'kedaluwarsa',
                status_pesanan = 'dibatalkan'
            WHERE id_pesanan = :id
              AND status_pembayaran = 'menunggu_pembayaran'
        ");

        $stmt->execute([
            'id' => $order['id_pesanan']
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException(
                'Gagal memperbarui status kedaluwarsa.'
            );
        }

        $pdo->commit();

        respond(200, [
            'success' => true,
            'status_pembayaran' => 'kedaluwarsa',
            'status_pesanan' => 'dibatalkan',
            'message' => 'Pembayaran kedaluwarsa. Stok telah dikembalikan.'
        ]);
    }

    $pdo->commit();

    respond(200, [
        'success' => true,
        'kode_pesanan' => $order['kode_pesanan'],
        'metode_pembayaran' => $order['metode_pembayaran'],
        'status_pembayaran' => $order['status_pembayaran'],
        'status_pesanan' => $order['status_pesanan'],
        'batas_pembayaran' => $order['batas_pembayaran'],
        'total_harga' => $order['total_harga'],
        'sisa_detik' => (int) $order['sisa_detik'],
        'message' => 'Status pembayaran berhasil diperiksa.'
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    respond(500, [
        'success' => false,
        'message' => 'Terjadi kesalahan pada server.'
    ]);
}
