-- Update tabel data_gadai yang sudah ada untuk menambahkan fitur verifikasi
-- Jalankan SQL ini jika tabel data_gadai sudah ada sebelumnya

-- Ubah enum status untuk menambahkan Pending, Disetujui, Ditolak
ALTER TABLE `data_gadai` 
MODIFY COLUMN `status` enum('Pending','Disetujui','Ditolak','Ditebus','Dijual','Diperpanjang') NOT NULL DEFAULT 'Pending';

-- Tambahkan kolom jumlah_disetujui (nominal yang disetujui admin)
ALTER TABLE `data_gadai` 
ADD COLUMN `jumlah_disetujui` decimal(15,2) DEFAULT NULL COMMENT 'Nominal yang disetujui admin' AFTER `jumlah_pinjaman`;

-- Tambahkan kolom verified_at (kapan diverifikasi)
ALTER TABLE `data_gadai` 
ADD COLUMN `verified_at` timestamp NULL DEFAULT NULL AFTER `status`;

-- Tambahkan kolom verified_by (admin yang memverifikasi)
ALTER TABLE `data_gadai` 
ADD COLUMN `verified_by` varchar(100) DEFAULT NULL AFTER `verified_at`;

-- Tambahkan kolom reject_reason (alasan penolakan)
ALTER TABLE `data_gadai` 
ADD COLUMN `reject_reason` text DEFAULT NULL COMMENT 'Alasan penolakan' AFTER `verified_by`;

-- Tambahkan kolom keterangan_admin (catatan admin saat approve)
ALTER TABLE `data_gadai` 
ADD COLUMN `keterangan_admin` text DEFAULT NULL COMMENT 'Catatan admin saat menyetujui' AFTER `reject_reason`;

-- Update comment untuk kolom jumlah_pinjaman
ALTER TABLE `data_gadai` 
MODIFY COLUMN `jumlah_pinjaman` decimal(15,2) NOT NULL COMMENT 'Nominal yang diajukan user';
