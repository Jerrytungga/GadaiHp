-- Normalisasi PRIMARY KEY dan AUTO_INCREMENT untuk tabel inti sistem GadaiHp
-- Aman dijalankan berulang (idempotent) selama kolom `id` pada tabel tidak duplikat.
--
-- Cara pakai:
-- 1) Backup database terlebih dahulu.
-- 2) Jalankan file ini di phpMyAdmin / MySQL client.
-- 3) Cek hasil log pada SELECT terakhir.

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_normalize_pk_ai $$
CREATE PROCEDURE sp_normalize_pk_ai()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE v_table VARCHAR(64);
    DECLARE v_column_type VARCHAR(255);
    DECLARE v_extra VARCHAR(255);
    DECLARE v_has_id INT DEFAULT 0;
    DECLARE v_has_pk INT DEFAULT 0;
    DECLARE v_pk_on_id INT DEFAULT 0;
    DECLARE v_null_id INT DEFAULT 0;
    DECLARE v_dup_id INT DEFAULT 0;

    DECLARE cur CURSOR FOR
        SELECT t.table_name
        FROM information_schema.tables t
        WHERE t.table_schema = DATABASE()
          AND t.table_name IN (
              'admin',
              'data_gadai',
              'ulasan',
              'transaksi',
              'wa_log',
              'payments',
              'customers',
              'pinjaman_requests'
          )
        ORDER BY t.table_name;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    DROP TEMPORARY TABLE IF EXISTS tmp_pk_ai_log;
    CREATE TEMPORARY TABLE tmp_pk_ai_log (
        table_name VARCHAR(64) NOT NULL,
        status VARCHAR(20) NOT NULL,
        message VARCHAR(255) NOT NULL
    );

    OPEN cur;

    read_loop: LOOP
        FETCH cur INTO v_table;
        IF done = 1 THEN
            LEAVE read_loop;
        END IF;

        -- Cek kolom id
        SELECT COUNT(*) INTO v_has_id
        FROM information_schema.columns c
        WHERE c.table_schema = DATABASE()
          AND c.table_name = v_table
          AND c.column_name = 'id';

        IF v_has_id = 0 THEN
            INSERT INTO tmp_pk_ai_log(table_name, status, message)
            VALUES (v_table, 'SKIP', 'Kolom id tidak ditemukan');
            ITERATE read_loop;
        END IF;

        -- Cek kualitas data id (null/duplikat)
        SET @sql := CONCAT('SELECT COUNT(*) INTO @v_null_id FROM `', v_table, '` WHERE `id` IS NULL');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
        SET v_null_id := IFNULL(@v_null_id, 0);

        SET @sql := CONCAT('SELECT COUNT(*) - COUNT(DISTINCT `id`) INTO @v_dup_id FROM `', v_table, '`');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
        SET v_dup_id := IFNULL(@v_dup_id, 0);

        IF v_null_id > 0 OR v_dup_id > 0 THEN
            INSERT INTO tmp_pk_ai_log(table_name, status, message)
            VALUES (
                v_table,
                'SKIP',
                CONCAT('Data id bermasalah (NULL: ', v_null_id, ', DUP: ', v_dup_id, ')')
            );
            ITERATE read_loop;
        END IF;

        -- Cek PRIMARY KEY
        SELECT COUNT(*) INTO v_has_pk
        FROM information_schema.table_constraints tc
        WHERE tc.table_schema = DATABASE()
          AND tc.table_name = v_table
          AND tc.constraint_type = 'PRIMARY KEY';

        SELECT COUNT(*) INTO v_pk_on_id
        FROM information_schema.key_column_usage kcu
        WHERE kcu.table_schema = DATABASE()
          AND kcu.table_name = v_table
          AND kcu.constraint_name = 'PRIMARY'
          AND kcu.column_name = 'id';

        IF v_has_pk > 0 AND v_pk_on_id = 0 THEN
            INSERT INTO tmp_pk_ai_log(table_name, status, message)
            VALUES (v_table, 'SKIP', 'PRIMARY KEY bukan pada kolom id');
            ITERATE read_loop;
        END IF;

        -- Tambah PRIMARY KEY bila belum ada
        IF v_has_pk = 0 THEN
            SET @sql := CONCAT('ALTER TABLE `', v_table, '` ADD PRIMARY KEY (`id`)');
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        END IF;

        -- Pastikan AUTO_INCREMENT pada id
        SELECT c.column_type, c.extra INTO v_column_type, v_extra
        FROM information_schema.columns c
        WHERE c.table_schema = DATABASE()
          AND c.table_name = v_table
          AND c.column_name = 'id'
        LIMIT 1;

        IF LOCATE('auto_increment', LOWER(IFNULL(v_extra, ''))) = 0 THEN
            SET @sql := CONCAT(
                'ALTER TABLE `', v_table, '` MODIFY COLUMN `id` ',
                v_column_type,
                ' NOT NULL AUTO_INCREMENT'
            );
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        END IF;

        -- Rapikan seed AUTO_INCREMENT agar selalu >= MAX(id)+1
        SET @sql := CONCAT('SELECT COALESCE(MAX(`id`), 0) + 1 INTO @next_ai FROM `', v_table, '`');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;

        SET @sql := CONCAT('ALTER TABLE `', v_table, '` AUTO_INCREMENT = ', IFNULL(@next_ai, 1));
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;

        INSERT INTO tmp_pk_ai_log(table_name, status, message)
        VALUES (v_table, 'OK', CONCAT('PK+AI terverifikasi, next AUTO_INCREMENT=', IFNULL(@next_ai, 1)));
    END LOOP;

    CLOSE cur;

    SELECT * FROM tmp_pk_ai_log ORDER BY table_name;
END $$

CALL sp_normalize_pk_ai() $$
DROP PROCEDURE sp_normalize_pk_ai $$

DELIMITER ;
