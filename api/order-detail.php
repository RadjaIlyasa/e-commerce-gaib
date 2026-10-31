
<?php

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

function respond($status, $data)
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(405, [
        'success' => false,
        'message' => 'Method tidak diizinkan.'
    ]);
}

$kodePesanan = trim($_GET['kode'] ?? '');

if ($kodePesanan === '') {
    respond(400, [
        'success' => false,
        'message' => 'Kode pesanan wajib diisi.'
    ]);
}

try {
    // Ambil informasi utama pesanan dan pelanggan.
    $stmt = $pdo->prepare("
        SELECT
            p.id_pesanan,
            p.kode_pesanan,
            p.alamat_pengiriman,
            p.catatan,
            p.metode_pembayaran,
            p.status_pembayaran,
            p.kode_pembayaran,
            p.batas_pembayaran,
            p.status_pesanan,
            p.total_harga,
            p.created_at,
            pl.nama_pelanggan,
            pl.no_telepon,
            pl.email
        FROM pesanan p
        JOIN pelanggan pl
            ON p.id_pelanggan = pl.id_pelanggan
        WHERE p.kode_pesanan = :kode
        LIMIT 1
    ");

    $stmt->execute([
        ':kode' => $kodePesanan
    ]);

    $pesanan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pesanan) {
        respond(404, [
            'success' => false,
            'message' => 'Pesanan tidak ditemukan.'
        ]);
    }

    // Ambil daftar produk dalam pesanan.
    $stmtDetail = $pdo->prepare("
        SELECT
            dp.id_produk,
            pr.nama_produk,
            pr.gambar,
            dp.harga_satuan,
            dp.jumlah,
            dp.subtotal
        FROM detail_pesanan dp
        JOIN produk pr
            ON dp.id_produk = pr.id_produk
        WHERE dp.id_pesanan = :id_pesanan
    ");

    $stmtDetail->execute([
        ':id_pesanan' => $pesanan['id_pesanan']
    ]);

    $produk = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);

    respond(200, [
        'success' => true,
        'message' => 'Detail pesanan berhasil diambil.',
        'data' => [
            'pesanan' => $pesanan,
            'produk' => $produk
        ]
    ]);

} catch (PDOException $e) {
    respond(500, [
        'success' => false,
        'message' => 'Terjadi kesalahan saat mengambil detail pesanan.'
    ]);
}
