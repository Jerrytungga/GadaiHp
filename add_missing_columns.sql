-- Script untuk menambahkan kolom yang hilang pada tabel data_gadai
-- Jalankan script ini di phpMyAdmin atau mysql command line

-- PENTING: Cek dulu apakah kolom sudah ada dengan query:
-- SHOW COLUMNS FROM data_gadai LIKE 'imei_serial';
-- SHOW COLUMNS FROM data_gadai LIKE 'kelengkapan_hp';

-- Jika kolom belum ada, jalankan query berikut:

-- Tambah kolom imei_serial
ALTER TABLE `data_gadai` 
ADD COLUMN `imei_serial` varchar(100) DEFAULT NULL 
AFTER `kondisi_barang`;

-- Tambah kolom kelengkapan_hp
ALTER TABLE `data_gadai` 
ADD COLUMN `kelengkapan_hp` text DEFAULT NULL 
AFTER `imei_serial`;

-- Jika keluar error "Duplicate column name", berarti kolom sudah ada dan tidak perlu ditambahkan lagi.
