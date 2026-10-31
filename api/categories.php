
<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

try {
    $sql = "
        SELECT
            k.id_kategori,
            k.nama_kategori,
            COUNT(p.id_produk) AS jumlah_produk,
            MIN(p.gambar) AS gambar
        FROM kategori AS k
        LEFT JOIN produk AS p
            ON p.id_kategori = k.id_kategori
            AND p.status_produk = 'aktif'
        GROUP BY
            k.id_kategori,
            k.nama_kategori
        ORDER BY k.id_kategori ASC
    ";

    $stmt = $pdo->query($sql);
    $kategori = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $kategori
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    error_log($e->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Gagal mengambil data kategori.'
    ], JSON_UNESCAPED_UNICODE);
}
