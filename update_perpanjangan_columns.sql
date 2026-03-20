-- SQL to add missing columns for perpanjangan (extension) feature
-- Run this if you already have data_gadai table created before this update

-- Add perpanjangan_ke column if not exists
SET @dbname = DATABASE();
SET @tablename = 'data_gadai';
SET @columnname = 'perpanjangan_ke';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` int(11) DEFAULT 0 AFTER `jumlah_perpanjangan`;')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add perpanjangan_terakhir_at column if not exists
SET @columnname = 'perpanjangan_terakhir_at';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` datetime DEFAULT NULL AFTER `perpanjangan_ke`;')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add gagal_tebus_at column if not exists
SET @columnname = 'gagal_tebus_at';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` datetime DEFAULT NULL AFTER `perpanjangan_terakhir_at`;')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Update status ENUM to include 'Gagal Tebus' if not already present
ALTER TABLE `data_gadai` MODIFY COLUMN `status` enum('Pending','Disetujui','Ditolak','Lunas','Diperpanjang','Jatuh Tempo','Gagal Tebus','Barang Dijual') DEFAULT 'Pending';

SELECT 'Migration completed successfully!' as Status;
