-- Script untuk menambahkan kolom yang diperlukan untuk sistem verifikasi
-- Jalankan script ini di phpMyAdmin atau mysql command line

-- PENTING: Cek dulu apakah kolom sudah ada dengan query:
-- SHOW COLUMNS FROM data_gadai;

-- Jika kolom-kolom berikut belum ada, jalankan query-query ini:

-- 1. Tambah kolom verified_at (waktu diverifikasi)
ALTER TABLE `data_gadai` 
ADD COLUMN `verified_at` timestamp NULL DEFAULT NULL 
AFTER `reminder_telat_last_day`;

-- 2. Tambah kolom verified_by (ID admin yang memverifikasi, INT bukan VARCHAR)
ALTER TABLE `data_gadai` 
ADD COLUMN `verified_by` int(11) DEFAULT NULL 
AFTER `verified_at`;

-- 3. Tambah kolom alasan_penolakan
ALTER TABLE `data_gadai` 
ADD COLUMN `alasan_penolakan` text DEFAULT NULL COMMENT 'Alasan penolakan' 
AFTER `verified_by`;

-- 4. Tambah kolom catatan_admin
ALTER TABLE `data_gadai` 
ADD COLUMN `catatan_admin` text DEFAULT NULL COMMENT 'Catatan admin saat menyetujui' 
AFTER `alasan_penolakan`;

-- Jika verified_by sudah ada tapi bertipe VARCHAR, ubah ke INT:
-- ALTER TABLE `data_gadai` MODIFY COLUMN `verified_by` int(11) DEFAULT NULL;

-- Catatan: Jika keluar error "Duplicate column name", berarti kolom sudah ada dan tidak perlu ditambahkan lagi.
