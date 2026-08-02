-- Checker schema untuk fitur upload bukti transfer admin
-- Jalankan setelah migrate_transaksi_for_admin_transfer.sql

SELECT
  'table.transaksi.exists' AS check_name,
  CASE WHEN COUNT(*) > 0 THEN 'PASS' ELSE 'FAIL' END AS status,
  COUNT(*) AS found
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name = 'transaksi';

SELECT
  'column.pelanggan_nik' AS check_name,
  CASE WHEN COUNT(*) > 0 THEN 'PASS' ELSE 'FAIL' END AS status,
  COUNT(*) AS found
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND table_name = 'transaksi'
  AND column_name = 'pelanggan_nik';

SELECT
  'column.barang_id' AS check_name,
  CASE WHEN COUNT(*) > 0 THEN 'PASS' ELSE 'FAIL' END AS status,
  COUNT(*) AS found
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND table_name = 'transaksi'
  AND column_name = 'barang_id';

SELECT
  'column.jumlah_bayar' AS check_name,
  CASE WHEN COUNT(*) > 0 THEN 'PASS' ELSE 'FAIL' END AS status,
  COUNT(*) AS found
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND table_name = 'transaksi'
  AND column_name = 'jumlah_bayar';

SELECT
  'column.keterangan' AS check_name,
  CASE WHEN COUNT(*) > 0 THEN 'PASS' ELSE 'FAIL' END AS status,
  COUNT(*) AS found
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND table_name = 'transaksi'
  AND column_name = 'keterangan';

SELECT
  'column.metode_pembayaran' AS check_name,
  CASE WHEN COUNT(*) > 0 THEN 'PASS' ELSE 'FAIL' END AS status,
  COUNT(*) AS found
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND table_name = 'transaksi'
  AND column_name = 'metode_pembayaran';

SELECT
  'column.bukti' AS check_name,
  CASE WHEN COUNT(*) > 0 THEN 'PASS' ELSE 'FAIL' END AS status,
  COUNT(*) AS found
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND table_name = 'transaksi'
  AND column_name = 'bukti';

SELECT
  'column.created_at' AS check_name,
  CASE WHEN COUNT(*) > 0 THEN 'PASS' ELSE 'FAIL' END AS status,
  COUNT(*) AS found
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND table_name = 'transaksi'
  AND column_name = 'created_at';

SELECT
  'column.updated_at' AS check_name,
  CASE WHEN COUNT(*) > 0 THEN 'PASS' ELSE 'FAIL' END AS status,
  COUNT(*) AS found
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND table_name = 'transaksi'
  AND column_name = 'updated_at';

SELECT
  'index.PRIMARY' AS check_name,
  CASE WHEN COUNT(*) > 0 THEN 'PASS' ELSE 'FAIL' END AS status,
  COUNT(*) AS found
FROM information_schema.statistics
WHERE table_schema = DATABASE()
  AND table_name = 'transaksi'
  AND index_name = 'PRIMARY';

SELECT
  'index.idx_barang' AS check_name,
  CASE WHEN COUNT(*) > 0 THEN 'PASS' ELSE 'FAIL' END AS status,
  COUNT(*) AS found
FROM information_schema.statistics
WHERE table_schema = DATABASE()
  AND table_name = 'transaksi'
  AND index_name = 'idx_barang';

SELECT
  'index.idx_pelanggan' AS check_name,
  CASE WHEN COUNT(*) > 0 THEN 'PASS' ELSE 'FAIL' END AS status,
  COUNT(*) AS found
FROM information_schema.statistics
WHERE table_schema = DATABASE()
  AND table_name = 'transaksi'
  AND index_name = 'idx_pelanggan';

SELECT
  'index.idx_trx_transfer_lookup' AS check_name,
  CASE WHEN COUNT(*) > 0 THEN 'PASS' ELSE 'FAIL' END AS status,
  COUNT(*) AS found
FROM information_schema.statistics
WHERE table_schema = DATABASE()
  AND table_name = 'transaksi'
  AND index_name = 'idx_trx_transfer_lookup';

SELECT
  'index.idx_trx_barang_created' AS check_name,
  CASE WHEN COUNT(*) > 0 THEN 'PASS' ELSE 'FAIL' END AS status,
  COUNT(*) AS found
FROM information_schema.statistics
WHERE table_schema = DATABASE()
  AND table_name = 'transaksi'
  AND index_name = 'idx_trx_barang_created';
