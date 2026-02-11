-- Update tabel data_gadai yang sudah ada untuk menambahkan fitur verifikasi
-- Skrip ini aman dijalankan berulang (cek kolom sebelum tambah)

DELIMITER $$

DROP PROCEDURE IF EXISTS apply_update_data_gadai $$
CREATE PROCEDURE apply_update_data_gadai()
BEGIN
	DECLARE col_count INT DEFAULT 0;

	-- Ubah enum status untuk menambahkan Pending, Disetujui, Ditolak
	ALTER TABLE `data_gadai`
	MODIFY COLUMN `status` enum('Pending','Disetujui','Ditolak','Ditebus','Dijual','Diperpanjang','Gagal Tebus') NOT NULL DEFAULT 'Pending';

	-- Tambahkan kolom verified_at (kapan diverifikasi)
	SELECT COUNT(*) INTO col_count
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'data_gadai' AND COLUMN_NAME = 'verified_at';
	IF col_count = 0 THEN
		ALTER TABLE `data_gadai` ADD COLUMN `verified_at` timestamp NULL DEFAULT NULL AFTER `status`;
	END IF;

	-- Tambahkan kolom verified_by (admin yang memverifikasi)
	SELECT COUNT(*) INTO col_count
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'data_gadai' AND COLUMN_NAME = 'verified_by';
	IF col_count = 0 THEN
		ALTER TABLE `data_gadai` ADD COLUMN `verified_by` varchar(100) DEFAULT NULL AFTER `verified_at`;
	END IF;

	-- Tambahkan kolom reject_reason (alasan penolakan)
	SELECT COUNT(*) INTO col_count
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'data_gadai' AND COLUMN_NAME = 'reject_reason';
	IF col_count = 0 THEN
		ALTER TABLE `data_gadai` ADD COLUMN `reject_reason` text DEFAULT NULL COMMENT 'Alasan penolakan' AFTER `verified_by`;
	END IF;

	-- Tambahkan kolom keterangan_admin (catatan admin saat approve)
	SELECT COUNT(*) INTO col_count
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'data_gadai' AND COLUMN_NAME = 'keterangan_admin';
	IF col_count = 0 THEN
		ALTER TABLE `data_gadai` ADD COLUMN `keterangan_admin` text DEFAULT NULL COMMENT 'Catatan admin saat menyetujui' AFTER `reject_reason`;
	END IF;

	-- Tambahkan kolom aksi jatuh tempo dan perpanjangan
	SELECT COUNT(*) INTO col_count
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'data_gadai' AND COLUMN_NAME = 'aksi_jatuh_tempo';
	IF col_count = 0 THEN
		ALTER TABLE `data_gadai` ADD COLUMN `aksi_jatuh_tempo` enum('Perpanjangan','Pelunasan') DEFAULT NULL AFTER `status`;
	END IF;

	SELECT COUNT(*) INTO col_count
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'data_gadai' AND COLUMN_NAME = 'aksi_jatuh_tempo_at';
	IF col_count = 0 THEN
		ALTER TABLE `data_gadai` ADD COLUMN `aksi_jatuh_tempo_at` datetime DEFAULT NULL AFTER `aksi_jatuh_tempo`;
	END IF;

	SELECT COUNT(*) INTO col_count
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'data_gadai' AND COLUMN_NAME = 'perpanjangan_ke';
	IF col_count = 0 THEN
		ALTER TABLE `data_gadai` ADD COLUMN `perpanjangan_ke` int(11) NOT NULL DEFAULT 0 AFTER `aksi_jatuh_tempo_at`;
	END IF;

	SELECT COUNT(*) INTO col_count
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'data_gadai' AND COLUMN_NAME = 'perpanjangan_terakhir_at';
	IF col_count = 0 THEN
		ALTER TABLE `data_gadai` ADD COLUMN `perpanjangan_terakhir_at` datetime DEFAULT NULL AFTER `perpanjangan_ke`;
	END IF;

	SELECT COUNT(*) INTO col_count
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'data_gadai' AND COLUMN_NAME = 'gagal_tebus_at';
	IF col_count = 0 THEN
		ALTER TABLE `data_gadai` ADD COLUMN `gagal_tebus_at` datetime DEFAULT NULL AFTER `perpanjangan_terakhir_at`;
	END IF;

	SELECT COUNT(*) INTO col_count
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'data_gadai' AND COLUMN_NAME = 'denda_terakumulasi';
	IF col_count = 0 THEN
		ALTER TABLE `data_gadai` ADD COLUMN `denda_terakumulasi` decimal(15,2) NOT NULL DEFAULT 0 AFTER `gagal_tebus_at`;
	END IF;

	SELECT COUNT(*) INTO col_count
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'data_gadai' AND COLUMN_NAME = 'total_tebus';
	IF col_count = 0 THEN
		ALTER TABLE `data_gadai` ADD COLUMN `total_tebus` decimal(15,2) NOT NULL DEFAULT 0 AFTER `denda_terakumulasi`;
	END IF;

	SELECT COUNT(*) INTO col_count
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'data_gadai' AND COLUMN_NAME = 'reminder_3hari_at';
	IF col_count = 0 THEN
		ALTER TABLE `data_gadai` ADD COLUMN `reminder_3hari_at` datetime DEFAULT NULL AFTER `denda_terakumulasi`;
	END IF;

	SELECT COUNT(*) INTO col_count
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'data_gadai' AND COLUMN_NAME = 'reminder_3hari_due_date';
	IF col_count = 0 THEN
		ALTER TABLE `data_gadai` ADD COLUMN `reminder_3hari_due_date` date DEFAULT NULL AFTER `reminder_3hari_at`;
	END IF;

	SELECT COUNT(*) INTO col_count
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'data_gadai' AND COLUMN_NAME = 'reminder_telat_at';
	IF col_count = 0 THEN
		ALTER TABLE `data_gadai` ADD COLUMN `reminder_telat_at` datetime DEFAULT NULL AFTER `reminder_3hari_due_date`;
	END IF;

	SELECT COUNT(*) INTO col_count
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'data_gadai' AND COLUMN_NAME = 'reminder_telat_due_date';
	IF col_count = 0 THEN
		ALTER TABLE `data_gadai` ADD COLUMN `reminder_telat_due_date` date DEFAULT NULL AFTER `reminder_telat_at`;
	END IF;

	SELECT COUNT(*) INTO col_count
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'data_gadai' AND COLUMN_NAME = 'reminder_telat_last_day';
	IF col_count = 0 THEN
		ALTER TABLE `data_gadai` ADD COLUMN `reminder_telat_last_day` int(11) DEFAULT NULL AFTER `reminder_telat_due_date`;
	END IF;

	-- Update comment untuk kolom jumlah_pinjaman
	ALTER TABLE `data_gadai`
	MODIFY COLUMN `jumlah_pinjaman` decimal(15,2) NOT NULL COMMENT 'Nominal yang diajukan user';
END $$

CALL apply_update_data_gadai() $$
DROP PROCEDURE apply_update_data_gadai $$

DELIMITER ;
