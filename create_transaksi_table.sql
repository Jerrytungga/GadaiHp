-- SQL: create_transaksi_table.sql
-- Membuat tabel `transaksi` untuk menyimpan bukti pembayaran / cicilan/pelunasan
-- Jalankan di database yang sama (mis. GadaiCepat)

CREATE TABLE IF NOT EXISTS `transaksi` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `pelanggan_nik` VARCHAR(32) NOT NULL,
  `barang_id` INT NOT NULL,
  `imei` VARCHAR(64) DEFAULT NULL,
  `serial_number` VARCHAR(100) DEFAULT NULL,
  `jenis_barang` VARCHAR(32) DEFAULT NULL,
  `merk` VARCHAR(100) DEFAULT NULL,
  `tipe` VARCHAR(100) DEFAULT NULL,
  `jumlah_bayar` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `keterangan` VARCHAR(255) DEFAULT NULL,
  `metode_pembayaran` VARCHAR(64) DEFAULT NULL,
  `bukti` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_barang` (`barang_id`),
  INDEX `idx_pelanggan` (`pelanggan_nik`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
