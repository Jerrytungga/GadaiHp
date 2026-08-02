-- Idempotent migration untuk fitur upload bukti transfer admin
-- Aman dijalankan berulang kali di server production.
--
-- Cakupan:
-- 1) Membuat tabel transaksi jika belum ada.
-- 2) Memastikan kolom-kolom penting tersedia.
-- 3) Menambahkan index untuk performa query histori transfer.

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_migrate_transaksi_for_admin_transfer $$
CREATE PROCEDURE sp_migrate_transaksi_for_admin_transfer()
BEGIN
    DECLARE v_table_exists INT DEFAULT 0;
    DECLARE v_count INT DEFAULT 0;

    -- 1) Buat tabel transaksi bila belum ada
    SELECT COUNT(*) INTO v_table_exists
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'transaksi';

    IF v_table_exists = 0 THEN
        CREATE TABLE transaksi (
            id INT NOT NULL AUTO_INCREMENT,
            pelanggan_nik VARCHAR(32) NOT NULL,
            barang_id INT NOT NULL,
            imei VARCHAR(64) DEFAULT NULL,
            serial_number VARCHAR(100) DEFAULT NULL,
            jenis_barang VARCHAR(32) DEFAULT NULL,
            merk VARCHAR(100) DEFAULT NULL,
            tipe VARCHAR(100) DEFAULT NULL,
            jumlah_bayar DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            keterangan VARCHAR(255) DEFAULT NULL,
            metode_pembayaran VARCHAR(64) DEFAULT NULL,
            bukti VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_barang (barang_id),
            INDEX idx_pelanggan (pelanggan_nik)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    END IF;

    -- 2) Pastikan kolom penting tersedia (aman jika tabel lama belum lengkap)
    SELECT COUNT(*) INTO v_count
    FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'transaksi' AND column_name = 'pelanggan_nik';
    IF v_count = 0 THEN
        ALTER TABLE transaksi ADD COLUMN pelanggan_nik VARCHAR(32) NULL AFTER id;
    END IF;

    SELECT COUNT(*) INTO v_count
    FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'transaksi' AND column_name = 'barang_id';
    IF v_count = 0 THEN
        ALTER TABLE transaksi ADD COLUMN barang_id INT NULL AFTER pelanggan_nik;
    END IF;

    SELECT COUNT(*) INTO v_count
    FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'transaksi' AND column_name = 'jumlah_bayar';
    IF v_count = 0 THEN
        ALTER TABLE transaksi ADD COLUMN jumlah_bayar DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER barang_id;
    END IF;

    SELECT COUNT(*) INTO v_count
    FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'transaksi' AND column_name = 'keterangan';
    IF v_count = 0 THEN
        ALTER TABLE transaksi ADD COLUMN keterangan VARCHAR(255) DEFAULT NULL AFTER jumlah_bayar;
    END IF;

    SELECT COUNT(*) INTO v_count
    FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'transaksi' AND column_name = 'metode_pembayaran';
    IF v_count = 0 THEN
        ALTER TABLE transaksi ADD COLUMN metode_pembayaran VARCHAR(64) DEFAULT NULL AFTER keterangan;
    END IF;

    SELECT COUNT(*) INTO v_count
    FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'transaksi' AND column_name = 'bukti';
    IF v_count = 0 THEN
        ALTER TABLE transaksi ADD COLUMN bukti VARCHAR(255) DEFAULT NULL AFTER metode_pembayaran;
    END IF;

    SELECT COUNT(*) INTO v_count
    FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'transaksi' AND column_name = 'created_at';
    IF v_count = 0 THEN
        ALTER TABLE transaksi ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER bukti;
    END IF;

    SELECT COUNT(*) INTO v_count
    FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'transaksi' AND column_name = 'updated_at';
    IF v_count = 0 THEN
        ALTER TABLE transaksi ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;
    END IF;

    -- 3) Index performa untuk fitur histori transfer pinjaman admin
    SELECT COUNT(*) INTO v_count
    FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'transaksi' AND index_name = 'idx_barang';
    IF v_count = 0 THEN
        ALTER TABLE transaksi ADD INDEX idx_barang (barang_id);
    END IF;

    SELECT COUNT(*) INTO v_count
    FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'transaksi' AND index_name = 'idx_pelanggan';
    IF v_count = 0 THEN
        ALTER TABLE transaksi ADD INDEX idx_pelanggan (pelanggan_nik);
    END IF;

    SELECT COUNT(*) INTO v_count
    FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'transaksi' AND index_name = 'idx_trx_transfer_lookup';
    IF v_count = 0 THEN
        ALTER TABLE transaksi ADD INDEX idx_trx_transfer_lookup (barang_id, keterangan, created_at);
    END IF;

    SELECT COUNT(*) INTO v_count
    FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'transaksi' AND index_name = 'idx_trx_barang_created';
    IF v_count = 0 THEN
        ALTER TABLE transaksi ADD INDEX idx_trx_barang_created (barang_id, created_at);
    END IF;

    SELECT 'OK' AS status, 'Schema transaksi untuk fitur bukti transfer admin sudah siap.' AS message;
END $$

CALL sp_migrate_transaksi_for_admin_transfer() $$
DROP PROCEDURE sp_migrate_transaksi_for_admin_transfer $$

DELIMITER ;
