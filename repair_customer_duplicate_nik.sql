-- Repair customer duplikat berdasarkan NIK + aktifkan UNIQUE NIK.
-- Backup database sebelum menjalankan file ini.
--
-- Strategi:
-- - Customer dengan ID terkecil untuk setiap NIK dipertahankan.
-- - data_gadai.customer_id dan pinjaman_requests.customer_id dipindahkan ke ID utama.
-- - Baris customer duplikat kemudian dihapus.

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_repair_customer_duplicate_nik $$
CREATE PROCEDURE sp_repair_customer_duplicate_nik()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE v_nik VARCHAR(64);
    DECLARE v_keep_id INT;
    DECLARE v_data_gadai_exists INT DEFAULT 0;
    DECLARE v_pinjaman_exists INT DEFAULT 0;
    DECLARE v_unique_exists INT DEFAULT 0;

    DECLARE duplicate_cursor CURSOR FOR
        SELECT nik
        FROM customers
        WHERE nik IS NOT NULL AND nik <> ''
        GROUP BY nik
        HAVING COUNT(*) > 1;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    SELECT COUNT(*) INTO v_data_gadai_exists
    FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'data_gadai';

    SELECT COUNT(*) INTO v_pinjaman_exists
    FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pinjaman_requests';

    OPEN duplicate_cursor;

    repair_loop: LOOP
        FETCH duplicate_cursor INTO v_nik;
        IF done = 1 THEN
            LEAVE repair_loop;
        END IF;

        SELECT MIN(id) INTO v_keep_id
        FROM customers
        WHERE nik = v_nik;

        IF v_data_gadai_exists > 0 THEN
            UPDATE data_gadai
            SET customer_id = v_keep_id
            WHERE customer_id IN (
                SELECT id FROM (
                    SELECT id FROM customers WHERE nik = v_nik AND id <> v_keep_id
                ) AS duplicate_customer_ids
            );
        END IF;

        IF v_pinjaman_exists > 0 THEN
            UPDATE pinjaman_requests
            SET customer_id = v_keep_id
            WHERE customer_id IN (
                SELECT id FROM (
                    SELECT id FROM customers WHERE nik = v_nik AND id <> v_keep_id
                ) AS duplicate_customer_ids
            );
        END IF;

        DELETE FROM customers
        WHERE nik = v_nik AND id <> v_keep_id;
    END LOOP;

    CLOSE duplicate_cursor;

    SELECT COUNT(*) INTO v_unique_exists
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'customers'
      AND index_name = 'uq_customers_nik';

    IF v_unique_exists = 0 THEN
        ALTER TABLE customers ADD UNIQUE KEY uq_customers_nik (nik);
    END IF;

    SELECT
        'OK' AS status,
        'Customer duplikat berdasarkan NIK sudah digabung dan UNIQUE NIK aktif.' AS message;
END $$

CALL sp_repair_customer_duplicate_nik() $$
DROP PROCEDURE sp_repair_customer_duplicate_nik $$

DELIMITER ;
