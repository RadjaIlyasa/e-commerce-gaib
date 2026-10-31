<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

function respond(int $statusCode, array $data): never
{
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
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
    require_once __DIR__ . '/../config/database.php';

    $input = json_decode(
        file_get_contents('php://input'),
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    if (!is_array($input)) {
        respond(400, [
            'success' => false,
            'message' => 'Data pembayaran tidak valid.'
        ]);
    }

    $orderCode = trim((string) ($input['kode_pesanan'] ?? ''));

    if ($orderCode === '' || strlen($orderCode) > 30) {
        respond(400, [
            'success' => false,
            'message' => 'Kode pesanan tidak valid.'
        ]);
    }

    $pdo->beginTransaction();

    // Kunci pesanan agar tidak diproses bersamaan.
    $stmt = $pdo->prepare("
        SELECT
            id_pesanan,
            kode_pesanan,
            metode_pembayaran,
            status_pembayaran,
            status_pesanan,
            batas_pembayaran,
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

    if (!in_array(
        $order['metode_pembayaran'],
        ['QRIS', 'TRANSFER'],
        true
    )) {
        $pdo->rollBack();

        respond(400, [
            'success' => false,
            'message' => 'Pesanan ini tidak menggunakan pembayaran digital.'
        ]);
    }

    if ($order['status_pembayaran'] === 'berhasil') {
        $pdo->rollBack();

        respond(409, [
            'success' => false,
            'message' => 'Pembayaran sudah dikonfirmasi sebelumnya.'
        ]);
    }

    if ($order['status_pembayaran'] !== 'menunggu_pembayaran') {
        $pdo->rollBack();

        respond(409, [
            'success' => false,
            'message' => 'Pembayaran tidak lagi dapat dikonfirmasi.'
        ]);
    }

    // Periksa deadline menggunakan waktu database.
    if (
        empty($order['batas_pembayaran']) ||
        strtotime($order['batas_pembayaran']) <=
        strtotime($order['waktu_sekarang'])
    ) {
        // Kembalikan stok hanya ketika pesanan masih menunggu pembayaran.
        $restoreStock = $pdo->prepare("
            UPDATE produk AS p
            INNER JOIN detail_pesanan AS d
                ON d.id_produk = p.id_produk
            SET p.stok = p.stok + d.jumlah
            WHERE d.id_pesanan = :id_pesanan
        ");

        $restoreStock->execute([
            'id_pesanan' => $order['id_pesanan']
        ]);

        // Perubahan status dan pengembalian stok berada dalam transaksi sama.
        $expireOrder = $pdo->prepare("
            UPDATE pesanan
            SET
                status_pembayaran = 'kedaluwarsa',
                status_pesanan = 'dibatalkan'
            WHERE id_pesanan = :id_pesanan
              AND status_pembayaran = 'menunggu_pembayaran'
        ");

        $expireOrder->execute([
            'id_pesanan' => $order['id_pesanan']
        ]);

        if ($expireOrder->rowCount() !== 1) {
            throw new RuntimeException(
                'Status pesanan gagal diperbarui menjadi kedaluwarsa.'
            );
        }

        $pdo->commit();

        respond(409, [
            'success' => false,
            'message' => 'Waktu pembayaran habis. Pesanan dibatalkan dan stok dikembalikan.',
            'kode_pesanan' => $order['kode_pesanan'],
            'status_pembayaran' => 'kedaluwarsa',
            'status_pesanan' => 'dibatalkan'
        ]);
    }

    // Simulasi konfirmasi pembayaran, bukan verifikasi uang sungguhan.
    $stmt = $pdo->prepare("
        UPDATE pesanan
        SET status_pembayaran = 'berhasil'
        WHERE id_pesanan = :id
          AND status_pembayaran = 'menunggu_pembayaran'
    ");

    $stmt->execute([
        'id' => $order['id_pesanan']
    ]);

    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException(
            'Status pembayaran gagal diperbarui.'
        );
    }

    $pdo->commit();

    respond(200, [
        'success' => true,
        'message' => 'Pembayaran demo berhasil dikonfirmasi.',
        'kode_pesanan' => $order['kode_pesanan'],
        'status_pembayaran' => 'berhasil'
    ]);

} catch (JsonException $e) {
    respond(400, [
        'success' => false,
        'message' => 'Format JSON tidak valid.'
    ]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Payment confirmation error: ' . $e->getMessage());

    respond(500, [
        'success' => false,
        'message' => 'Terjadi kesalahan pada server.'
    ]);
}
