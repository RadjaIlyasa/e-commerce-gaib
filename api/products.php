
<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

try {
    $sql = "
        SELECT
            p.id_produk,
            p.nama_produk,
            p.deskripsi,
            p.harga,
            p.stok,
            p.gambar,
            k.nama_kategori
        FROM produk AS p
        INNER JOIN kategori AS k
            ON p.id_kategori = k.id_kategori
        WHERE p.status_produk = 'aktif'
        ORDER BY p.id_produk ASC
    ";

    $stmt = $pdo->query($sql);
    $produk = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $produk
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    error_log($e->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Gagal mengambil data produk.'
    ], JSON_UNESCAPED_UNICODE);
}
