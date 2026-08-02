-- Repair + Normalisasi PRIMARY KEY dan AUTO_INCREMENT untuk tabel inti GadaiHp
--
-- Versi ini mencoba memperbaiki data id yang NULL/duplikat terlebih dahulu,
-- lalu menormalkan PRIMARY KEY + AUTO_INCREMENT.
--
-- Catatan keamanan:
-- - Jika tabel memiliki foreign key masuk (direferensikan tabel lain) dan id bermasalah,
--   skrip akan SKIP auto-fix untuk tabel itu agar tidak memutus relasi.
-- - Tetap WAJIB backup database sebelum eksekusi.
--
-- Cara pakai:
-- 1) Backup database
-- 2) Jalankan file ini di phpMyAdmin / MySQL client
-- 3) Lihat hasil log pada SELECT terakhir

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_repair_and_normalize_pk_ai $$
CREATE PROCEDURE sp_repair_and_normalize_pk_ai()
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
    DECLARE v_incoming_fk INT DEFAULT 0;
    DECLARE v_next_id BIGINT DEFAULT 1;
    DECLARE v_dup_value BIGINT DEFAULT NULL;

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

    DROP TEMPORARY TABLE IF EXISTS tmp_pk_ai_repair_log;
    CREATE TEMPORARY TABLE tmp_pk_ai_repair_log (
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

        -- Pastikan kolom id ada
        SELECT COUNT(*) INTO v_has_id
        FROM information_schema.columns c
        WHERE c.table_schema = DATABASE()
          AND c.table_name = v_table
          AND c.column_name = 'id';

        IF v_has_id = 0 THEN
            INSERT INTO tmp_pk_ai_repair_log(table_name, status, message)
            VALUES (v_table, 'SKIP', 'Kolom id tidak ditemukan');
            ITERATE read_loop;
        END IF;

        -- Hitung id NULL dan duplikat
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

        -- Hitung foreign key masuk (tabel lain yang referensi tabel ini.id)
        SELECT COUNT(*) INTO v_incoming_fk
        FROM information_schema.key_column_usage kcu
        WHERE kcu.referenced_table_schema = DATABASE()
          AND kcu.referenced_table_name = v_table
          AND kcu.referenced_column_name = 'id';

        -- Perbaiki id NULL/duplikat jika aman
        IF v_null_id > 0 OR v_dup_id > 0 THEN
            IF v_incoming_fk > 0 THEN
                INSERT INTO tmp_pk_ai_repair_log(table_name, status, message)
                VALUES (
                    v_table,
                    'SKIP',
                    CONCAT('Ada masalah id (NULL: ', v_null_id, ', DUP: ', v_dup_id, ') namun tabel direferensikan FK (', v_incoming_fk, ')')
                );
                ITERATE read_loop;
            END IF;

            -- Isi id NULL satu per satu dengan MAX(id)+1
            SET @sql := CONCAT('SELECT COALESCE(MAX(`id`), 0) + 1 INTO @v_next_id FROM `', v_table, '`');
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
            SET v_next_id := IFNULL(@v_next_id, 1);

            null_fix_loop: LOOP
                SET @sql := CONCAT('SELECT COUNT(*) INTO @v_null_check FROM `', v_table, '` WHERE `id` IS NULL');
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;

                IF IFNULL(@v_null_check, 0) = 0 THEN
                    LEAVE null_fix_loop;
                END IF;

                SET @sql := CONCAT('UPDATE `', v_table, '` SET `id` = ', v_next_id, ' WHERE `id` IS NULL LIMIT 1');
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;

                SET v_next_id := v_next_id + 1;
            END LOOP;

            -- Perbaiki duplikat id: sisakan 1 baris lama, lainnya pindah ke id baru
            dup_group_loop: LOOP
                SET @sql := CONCAT(
                    'SELECT `id` INTO @v_dup_value FROM (',
                    'SELECT `id` FROM `', v_table, '` WHERE `id` IS NOT NULL GROUP BY `id` HAVING COUNT(*) > 1 LIMIT 1',
                    ') d'
                );
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;

                SET v_dup_value := @v_dup_value;
                IF v_dup_value IS NULL THEN
                    LEAVE dup_group_loop;
                END IF;

                dup_row_loop: LOOP
                    SET @sql := CONCAT('SELECT COUNT(*) INTO @v_dup_count FROM `', v_table, '` WHERE `id` = ', v_dup_value);
                    PREPARE stmt FROM @sql;
                    EXECUTE stmt;
                    DEALLOCATE PREPARE stmt;

                    IF IFNULL(@v_dup_count, 0) <= 1 THEN
                        LEAVE dup_row_loop;
                    END IF;

                    SET @sql := CONCAT('SELECT COALESCE(MAX(`id`), 0) + 1 INTO @v_next_id FROM `', v_table, '`');
                    PREPARE stmt FROM @sql;
                    EXECUTE stmt;
                    DEALLOCATE PREPARE stmt;
                    SET v_next_id := IFNULL(@v_next_id, 1);

                    SET @sql := CONCAT('UPDATE `', v_table, '` SET `id` = ', v_next_id, ' WHERE `id` = ', v_dup_value, ' LIMIT 1');
                    PREPARE stmt FROM @sql;
                    EXECUTE stmt;
                    DEALLOCATE PREPARE stmt;
                END LOOP;

                SET @v_dup_value := NULL;
                SET v_dup_value := NULL;
            END LOOP;

            INSERT INTO tmp_pk_ai_repair_log(table_name, status, message)
            VALUES (v_table, 'REPAIR', 'Perbaikan id NULL/duplikat selesai');
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
            INSERT INTO tmp_pk_ai_repair_log(table_name, status, message)
            VALUES (v_table, 'SKIP', 'PRIMARY KEY bukan pada kolom id');
            ITERATE read_loop;
        END IF;

        IF v_has_pk = 0 THEN
            SET @sql := CONCAT('ALTER TABLE `', v_table, '` ADD PRIMARY KEY (`id`)');
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        END IF;

        -- Pastikan AUTO_INCREMENT
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

        -- Set next AUTO_INCREMENT ke MAX(id)+1
        SET @sql := CONCAT('SELECT COALESCE(MAX(`id`), 0) + 1 INTO @next_ai FROM `', v_table, '`');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;

        SET @sql := CONCAT('ALTER TABLE `', v_table, '` AUTO_INCREMENT = ', IFNULL(@next_ai, 1));
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;

        INSERT INTO tmp_pk_ai_repair_log(table_name, status, message)
        VALUES (v_table, 'OK', CONCAT('PK+AI terverifikasi, next AUTO_INCREMENT=', IFNULL(@next_ai, 1)));
    END LOOP;

    CLOSE cur;

    SELECT * FROM tmp_pk_ai_repair_log ORDER BY table_name, FIELD(status, 'SKIP', 'REPAIR', 'OK');
END $$

CALL sp_repair_and_normalize_pk_ai() $$
DROP PROCEDURE sp_repair_and_normalize_pk_ai $$

DELIMITER ;
