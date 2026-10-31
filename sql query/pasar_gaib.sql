-- MySQL dump 10.13  Distrib 8.0.46, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: pasar_gaib
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin` (
  `id_admin` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nama_admin` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_admin`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
INSERT INTO `admin` VALUES (1,'Ilyas','$2y$10$tBxgMJNz5l/H4yvTA4I/sOGnhH7kIGPkZM/71JIUpZD/Mr1cedI96','Yas','2026-10-29 10:05:52');
/*!40000 ALTER TABLE `admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detail_pesanan`
--

DROP TABLE IF EXISTS `detail_pesanan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detail_pesanan` (
  `id_detail` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_pesanan` bigint(20) unsigned NOT NULL,
  `id_produk` int(10) unsigned NOT NULL,
  `jumlah` int(10) unsigned NOT NULL,
  `harga_satuan` decimal(12,2) unsigned NOT NULL,
  `subtotal` decimal(14,2) unsigned GENERATED ALWAYS AS (`jumlah` * `harga_satuan`) STORED,
  PRIMARY KEY (`id_detail`),
  KEY `fk_detail_pesanan` (`id_pesanan`),
  KEY `idx_detail_produk` (`id_produk`),
  CONSTRAINT `fk_detail_pesanan` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`) ON UPDATE CASCADE,
  CONSTRAINT `fk_detail_produk` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`) ON UPDATE CASCADE,
  CONSTRAINT `chk_jumlah_positif` CHECK (`jumlah` > 0)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detail_pesanan`
--

LOCK TABLES `detail_pesanan` WRITE;
/*!40000 ALTER TABLE `detail_pesanan` DISABLE KEYS */;
INSERT INTO `detail_pesanan` (`id_detail`, `id_pesanan`, `id_produk`, `jumlah`, `harga_satuan`) VALUES (1,1,3,1,85000.00),(2,2,1,1,350000.00),(3,2,3,1,85000.00),(4,3,7,1,225000.00),(5,3,6,1,150000.00),(6,4,1,2,350000.00),(7,5,10,2,12500.00),(8,6,5,1,200000.00),(9,7,4,1,125000.00),(10,8,3,1,85000.00),(11,9,3,1,85000.00),(12,10,7,1,225000.00),(13,11,3,1,85000.00),(14,12,3,1,85000.00),(15,13,4,1,125000.00);
/*!40000 ALTER TABLE `detail_pesanan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kategori`
--

DROP TABLE IF EXISTS `kategori`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kategori` (
  `id_kategori` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_kategori`),
  UNIQUE KEY `nama_kategori` (`nama_kategori`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kategori`
--

LOCK TABLES `kategori` WRITE;
/*!40000 ALTER TABLE `kategori` DISABLE KEYS */;
INSERT INTO `kategori` VALUES (1,'Pusaka Nusantara','Koleksi keris dan benda pusaka.','2026-10-29 05:04:12'),(2,'Benda Mistis','Koleksi bertema folklore dan misteri.','2026-10-29 05:04:12'),(3,'Batu dan Jimat','Koleksi batu akik dan benda antik.','2026-10-29 05:04:12'),(4,'Seni Tradisional','Wayang, topeng, dan koleksi budaya.','2026-10-29 05:04:12'),(5,'Ramalan dan Tarot','Kartu tarot dan perlengkapan ramalan.','2026-10-29 05:04:12');
/*!40000 ALTER TABLE `kategori` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pelanggan`
--

DROP TABLE IF EXISTS `pelanggan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pelanggan` (
  `id_pelanggan` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nama_pelanggan` varchar(100) NOT NULL,
  `no_telepon` varchar(20) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_pelanggan`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pelanggan`
--

LOCK TABLES `pelanggan` WRITE;
/*!40000 ALTER TABLE `pelanggan` DISABLE KEYS */;
INSERT INTO `pelanggan` VALUES (1,'A_24_ M. R. Ilyasa','+62895324087406','elpujailyasa@gmail.com','2026-10-29 09:16:50'),(2,'ujang','081234567891','cihuy@gmail.com','2026-10-29 10:25:59'),(3,'Markonah','081234567892','maroknah@gmail.com','2026-10-29 10:27:05'),(4,'Sutrasno','081234567899','sutrasno_sidukunTampan@gmail.com','2026-10-31 04:16:40'),(5,'Pesulap Merah','0812345678910','pesulap_merahindo12@gmail.com','2026-10-31 05:23:12'),(6,'Caknan','081234567812','cak_nakpurwokerto@gmail.com','2026-10-31 11:35:30'),(7,'sule','081234567819','sule@yahoo.co.id','2026-10-31 11:42:42'),(8,'Awan','08123465781','wantutri@gmail.com','2026-10-31 11:44:04'),(9,'King','123456789','kingkangkung@gmail.com','2026-10-31 12:12:29'),(10,'Abdul','08123456789','dulab@gmail.com','2026-10-31 12:21:37'),(11,'A_24_ M. R. Ilyasa','+62895324087406','elpujailyasa@gmail.com','2026-10-31 12:38:56'),(12,'A_24_ M. R. Ilyasa','+62895324087406','elpujailyasa@gmail.com','2026-10-31 13:11:52'),(13,'A_24_ M. R. Ilyasa','+62895324087406','elpujailyasa@gmail.com','2026-10-31 13:13:33');
/*!40000 ALTER TABLE `pelanggan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pesanan`
--

DROP TABLE IF EXISTS `pesanan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pesanan` (
  `id_pesanan` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kode_pesanan` varchar(30) NOT NULL,
  `id_pelanggan` int(10) unsigned NOT NULL,
  `alamat_pengiriman` text NOT NULL,
  `catatan` text DEFAULT NULL,
  `metode_pembayaran` enum('COD','QRIS','TRANSFER') NOT NULL DEFAULT 'COD',
  `status_pembayaran` enum('menunggu_pembayaran','berhasil','kedaluwarsa') NOT NULL DEFAULT 'menunggu_pembayaran',
  `kode_pembayaran` varchar(50) DEFAULT NULL,
  `batas_pembayaran` datetime DEFAULT NULL,
  `status_pesanan` enum('menunggu_konfirmasi','diproses','dikirim','selesai','dibatalkan') NOT NULL DEFAULT 'menunggu_konfirmasi',
  `total_harga` decimal(14,2) unsigned NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_pesanan`),
  UNIQUE KEY `kode_pesanan` (`kode_pesanan`),
  KEY `fk_pesanan_pelanggan` (`id_pelanggan`),
  KEY `idx_pesanan_status` (`status_pesanan`),
  KEY `idx_pesanan_tanggal` (`created_at`),
  CONSTRAINT `fk_pesanan_pelanggan` FOREIGN KEY (`id_pelanggan`) REFERENCES `pelanggan` (`id_pelanggan`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pesanan`
--

LOCK TABLES `pesanan` WRITE;
/*!40000 ALTER TABLE `pesanan` DISABLE KEYS */;
INSERT INTO `pesanan` VALUES (1,'PG-20261029-B4CCF066',1,'Margasari, Jln Apel 1 no.1',NULL,'COD','menunggu_pembayaran',NULL,NULL,'selesai',135000.00,'2026-10-29 09:16:50','2026-10-30 14:47:21'),(2,'PG-20261029-4309D413',2,'Semarang',NULL,'COD','menunggu_pembayaran',NULL,NULL,'selesai',485000.00,'2026-10-29 10:25:59','2026-10-29 10:57:08'),(3,'PG-20261029-20734749',3,'Surabaya','Pagar Biru, depannya ada pohon Mangga','COD','menunggu_pembayaran',NULL,NULL,'dibatalkan',425000.00,'2026-10-29 10:27:05','2026-10-29 10:57:55'),(4,'PG-20261031-30A02E6D',4,'Solo','sebelah indomaret, gerbang warna merah ke ijoan','COD','menunggu_pembayaran',NULL,NULL,'dikirim',750000.00,'2026-10-31 04:16:40','2026-10-31 05:42:22'),(5,'PG-20261031-435EC557',5,'Jalan Teratai 1\nKecamatan Ngawi',NULL,'COD','menunggu_pembayaran',NULL,NULL,'dikirim',75000.00,'2026-10-31 05:23:12','2026-10-31 05:42:05'),(6,'PG-20261031-63633A9F',6,'Purwokerto',NULL,'COD','menunggu_pembayaran',NULL,NULL,'dikirim',250000.00,'2026-10-31 11:35:30','2026-10-31 11:36:11'),(7,'PG-20261031-65BF4193',7,'Bandung',NULL,'COD','menunggu_pembayaran',NULL,NULL,'menunggu_konfirmasi',175000.00,'2026-10-31 11:42:42','2026-10-31 11:42:42'),(8,'PG-20261031-0BE50068',8,'Tigaraksa',NULL,'COD','menunggu_pembayaran',NULL,NULL,'menunggu_konfirmasi',135000.00,'2026-10-31 11:44:04','2026-10-31 11:44:04'),(9,'PG-20261031-5C09B6C2',9,'Atlantis',NULL,'QRIS','menunggu_pembayaran','PGQR-54D05DC452','2026-10-31 19:27:29','menunggu_konfirmasi',135000.00,'2026-10-31 12:12:29','2026-10-31 12:12:29'),(10,'PG-20261031-174EB490',10,'Bali',NULL,'COD','menunggu_pembayaran',NULL,NULL,'menunggu_konfirmasi',275000.00,'2026-10-31 12:21:37','2026-10-31 12:21:37'),(11,'PG-20261031-30E64788',11,'Margasari, Jln Apel 1 no.1',NULL,'QRIS','berhasil','PGQR-605232C538','2026-10-31 19:53:56','menunggu_konfirmasi',135000.00,'2026-10-31 12:38:56','2026-10-31 12:40:45'),(12,'PG-20261031-8E4AA865',12,'Margasari, Jln Apel 1 no.1',NULL,'QRIS','berhasil','PGQR-1BC0D060E9','2026-10-31 20:26:52','menunggu_konfirmasi',135000.00,'2026-10-31 13:11:52','2026-10-31 13:13:15'),(13,'PG-20261031-453A2E2B',13,'Margasari, Jln Apel 1 no.1',NULL,'TRANSFER','berhasil','PGTR-90C2252D55','2026-10-31 20:28:33','menunggu_konfirmasi',175000.00,'2026-10-31 13:13:33','2026-10-31 13:13:41');
/*!40000 ALTER TABLE `pesanan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `produk`
--

DROP TABLE IF EXISTS `produk`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `produk` (
  `id_produk` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_kategori` int(10) unsigned NOT NULL,
  `nama_produk` varchar(150) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `harga` decimal(12,2) unsigned NOT NULL,
  `stok` int(10) unsigned NOT NULL DEFAULT 0,
  `gambar` varchar(255) DEFAULT NULL,
  `status_produk` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_produk`),
  KEY `fk_produk_kategori` (`id_kategori`),
  CONSTRAINT `fk_produk_kategori` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `produk`
--

LOCK TABLES `produk` WRITE;
/*!40000 ALTER TABLE `produk` DISABLE KEYS */;
INSERT INTO `produk` VALUES (1,1,'Keris Pusaka','Koleksi keris bertema pusaka Nusantara.',350000.00,0,'keris-pusaka.jpeg','aktif','2026-10-29 05:04:13','2026-10-31 04:16:40'),(2,2,'Boneka Santet','Properti koleksi bertema cerita mistis.',175000.00,1,'boneka-santet.jpeg','aktif','2026-10-29 05:04:13','2026-10-31 04:27:07'),(3,3,'Batu Akik','Koleksi batu akik dekoratif.',85000.00,6,'batu-akik.jpeg','aktif','2026-10-29 05:04:13','2026-10-31 13:11:52'),(4,2,'Jelangkung','Properti koleksi bertema folklore.',125000.00,4,'jelangkung.jpeg','aktif','2026-10-29 05:04:13','2026-10-31 13:13:33'),(5,2,'Jenglot','Miniatur koleksi bertema legenda mistis.',200000.00,3,'jenglot.jpeg','aktif','2026-10-29 05:04:13','2026-10-31 11:35:30'),(6,4,'Wayang Kulit','Koleksi seni tradisional Nusantara.',150000.00,7,'wayang-kulit.jpeg','aktif','2026-10-29 05:04:13','2026-10-29 10:57:55'),(7,4,'Topeng Rangda','Dekorasi topeng tradisional.',225000.00,4,'topeng-rangda.jpeg','aktif','2026-10-29 05:04:13','2026-10-31 12:21:37'),(8,5,'Kartu Tarot','Koleksi kartu bertema ramalan.',95000.00,10,'karrtu-tarot.jpeg','aktif','2026-10-29 05:04:13','2026-10-29 05:04:13'),(9,2,'Jarum Susuk','Properti koleksi untuk tema cerita mistis.',65000.00,10,'jarum-susuk.jpeg','aktif','2026-10-29 05:04:13','2026-10-30 16:08:37'),(10,5,'Kartu Remi','Kartu ini bisa memberi tahu berapa sisa waktu hidup kita (just kidding).',12500.00,2,'f991c80aa3fba6388269752cc0406fe0.jpg','aktif','2026-10-30 15:40:19','2026-10-31 05:23:12');
/*!40000 ALTER TABLE `produk` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-31 21:02:29
