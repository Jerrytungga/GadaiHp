-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Apr 07, 2025 at 01:04 PM
-- Server version: 8.0.40
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `GadaiCepat`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int NOT NULL,
  `nik` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `nik`) VALUES
(1, 123456789);

-- --------------------------------------------------------

--
-- Table structure for table `barang_gadai`
--

CREATE TABLE `barang_gadai` (
  `id` int NOT NULL,
  `pelanggan_nik` varchar(20) NOT NULL,
  `nama_barang` varchar(255) NOT NULL,
  `deskripsi` text,
  `foto` varchar(255) DEFAULT NULL,
  `nilai_taksir` decimal(10,2) NOT NULL,
  `pinjaman` decimal(10,2) NOT NULL,
  `bunga` decimal(5,2) NOT NULL,
  `jatuh_tempo` date NOT NULL,
  `status` enum('aktif','ditebus','lelang') DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `imei` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `barang_gadai`
--

INSERT INTO `barang_gadai` (`id`, `pelanggan_nik`, `nama_barang`, `deskripsi`, `foto`, `nilai_taksir`, `pinjaman`, `bunga`, `jatuh_tempo`, `status`, `created_at`, `imei`) VALUES
(6, '7471082910920001', 'Samsung A55', 'Hp Only, kondisi hp mulus', 'uploads/WhatsApp Image 2025-03-16 at 22.03.41.jpeg', 2000000.00, 2300000.00, 31.00, '2025-03-23', 'aktif', '2025-03-18 14:29:02', '355326629963703'),
(7, '7105100404880001', 'Oppo', 'Hp dan case', 'uploads/WhatsApp Image 2025-03-18 at 23.34.59.jpeg', 800000.00, 800000.00, 25.00, '2025-03-27', 'ditebus', '2025-03-18 14:40:34', '001'),
(9, '7471082910920001', 'Samsung', 'mulus', 'uploads/5603881-middle.png', 1000000.00, 1000000.00, 10.00, '2025-03-27', 'aktif', '2025-03-20 14:04:32', '9999999'),
(10, '7471082910920001', 'sadasdasd', 'dssadasd', NULL, 190000.00, 190000.00, 10.00, '2025-03-22', 'aktif', '2025-03-20 16:22:53', '324324234'),
(11, '7471082910920001', 'IPhone', '-', NULL, 2000000.00, 2000000.00, 20.00, '2025-03-29', 'aktif', '2025-03-27 00:09:25', '9999999');

-- --------------------------------------------------------

--
-- Table structure for table `notifikasi`
--

CREATE TABLE `notifikasi` (
  `id` int NOT NULL,
  `pelanggan_nik` varchar(20) NOT NULL,
  `barang_id` int NOT NULL,
  `pesan` text NOT NULL,
  `status` enum('terkirim','pending') DEFAULT 'pending',
  `tanggal_kirim` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pelanggan`
--

CREATE TABLE `pelanggan` (
  `nik` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `nomor_hp` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `alamat` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `foto_diri` text NOT NULL,
  `foto_ktp` text NOT NULL,
  `status` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pelanggan`
--

INSERT INTO `pelanggan` (`nik`, `nama`, `nomor_hp`, `alamat`, `created_at`, `foto_diri`, `foto_ktp`, `status`) VALUES
('7105100404880001', 'Aprilia F. Tahendung', '082239902688', 'jln kihanjar Dewantara', '2025-03-18 14:37:35', 'uploads/WhatsApp Image 2025-03-18 at 23.34.43.jpeg', 'uploads/WhatsApp Image 2025-03-18 at 23.34.43.jpeg', 'Aktif'),
('7471082910920001', 'Mickhail Soppang', '‪81241935244', 'jl, p2id no 56b', '2025-03-18 14:26:58', 'uploads/WhatsApp Image 2025-03-16 at 22.03.41.jpeg', 'uploads/WhatsApp Image 2025-03-16 at 22.03.41.jpeg', 'Aktif');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `id` int NOT NULL,
  `pelanggan_nik` varchar(20) NOT NULL,
  `barang_id` int NOT NULL,
  `jumlah_bayar` text NOT NULL,
  `tanggal_bayar` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `keterangan` text,
  `metode_pembayaran` text NOT NULL,
  `bukti` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `transaksi`
--

INSERT INTO `transaksi` (`id`, `pelanggan_nik`, `barang_id`, `jumlah_bayar`, `tanggal_bayar`, `keterangan`, `metode_pembayaran`, `bukti`) VALUES
(4, '7471082910920001', 6, '3000000', '2025-03-06 14:29:47', 'lunas', '', ''),
(5, '7105100404880001', 7, '1000000', '2025-03-01 03:41:13', 'lunas', '', ''),
(6, '7471082910920001', 6, '3013000', '2025-03-20 04:14:03', 'lunas', '', ''),
(7, '7471082910920001', 6, '31.00', '2025-03-20 04:15:01', 'cicilan', '', ''),
(8, '7471082910920001', 6, '31.00', '2025-03-20 12:21:32', 'cicilan', '', ''),
(9, '7471082910920001', 6, '31.00', '2025-03-20 14:08:09', 'cicilan', '', ''),
(10, '7471082910920001', 9, '900.000', '2025-03-20 15:38:08', 'cicilan', 'Transfer bank', 'Rp 900.000.png'),
(11, '7471082910920001', 9, '900.000', '2025-03-20 15:39:09', 'cicilan', 'Transfer bank', 'Rp 900.000.png'),
(12, '7471082910920001', 9, '99.900', '2025-03-20 15:40:09', 'cicilan', 'Transfer bank', 'Rp 99.900.png');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `barang_gadai`
--
ALTER TABLE `barang_gadai`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pelanggan_nik` (`pelanggan_nik`);

--
-- Indexes for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pelanggan_nik` (`pelanggan_nik`),
  ADD KEY `barang_id` (`barang_id`);

--
-- Indexes for table `pelanggan`
--
ALTER TABLE `pelanggan`
  ADD PRIMARY KEY (`nik`);

--
-- Indexes for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pelanggan_nik` (`pelanggan_nik`),
  ADD KEY `barang_id` (`barang_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `barang_gadai`
--
ALTER TABLE `barang_gadai`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `barang_gadai`
--
ALTER TABLE `barang_gadai`
  ADD CONSTRAINT `barang_gadai_ibfk_1` FOREIGN KEY (`pelanggan_nik`) REFERENCES `pelanggan` (`nik`) ON DELETE CASCADE;

--
-- Constraints for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD CONSTRAINT `notifikasi_ibfk_1` FOREIGN KEY (`pelanggan_nik`) REFERENCES `pelanggan` (`nik`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifikasi_ibfk_2` FOREIGN KEY (`barang_id`) REFERENCES `barang_gadai` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`pelanggan_nik`) REFERENCES `pelanggan` (`nik`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaksi_ibfk_2` FOREIGN KEY (`barang_id`) REFERENCES `barang_gadai` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
