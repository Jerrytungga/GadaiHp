-- Repair FK-safe + Normalisasi PRIMARY KEY / AUTO_INCREMENT
--
-- Tujuan:
-- 1) Menangani id NULL/duplikat pada tabel inti.
-- 2) Tetap aman untuk tabel yang direferensikan FK (incoming FK).
-- 3) Menormalkan PRIMARY KEY(id) + AUTO_INCREMENT + seed next id.
--
-- Strategi FK-safe untuk duplikat id:
-- - Jika tabel direferensikan FK dari tabel lain, baris dengan id duplikat akan diperlakukan sbb:
--   - 1 baris pertama (canonical row) tetap memakai id lama.
--   - baris duplikat lainnya dipindah ke id baru unik.
-- - Referensi FK lama tetap menunjuk canonical row (tidak memutus relasi).
--
-- Catatan penting:
-- - Tetap WAJIB backup database sebelum eksekusi.
-- - Jika sudah ada PRIMARY KEY bukan di kolom id, tabel akan di-SKIP.

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_repair_fk_safe_pk_ai $$
CREATE PROCEDURE sp_repair_fk_safe_pk_ai()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE v_table VARCHAR(64);
    DECLARE v_has_id INT DEFAULT 0;
    DECLARE v_has_pk INT DEFAULT 0;
    DECLARE v_pk_on_id INT DEFAULT 0;
    DECLARE v_null_id INT DEFAULT 0;
    DECLARE v_dup_id INT DEFAULT 0;
    DECLARE v_incoming_fk INT DEFAULT 0;
    DECLARE v_has_tmp_uid INT DEFAULT 0;
    DECLARE v_next_id BIGINT DEFAULT 1;
    DECLARE v_column_type VARCHAR(255);
    DECLARE v_extra VARCHAR(255);

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

    DROP TEMPORARY TABLE IF EXISTS tmp_fk_safe_log;
    CREATE TEMPORARY TABLE tmp_fk_safe_log (
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
            INSERT INTO tmp_fk_safe_log(table_name, status, message)
            VALUES (v_table, 'SKIP', 'Kolom id tidak ditemukan');
            ITERATE read_loop;
        END IF;

        -- Cek PK
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
            INSERT INTO tmp_fk_safe_log(table_name, status, message)
            VALUES (v_table, 'SKIP', 'PRIMARY KEY bukan pada kolom id');
            ITERATE read_loop;
        END IF;

        -- Hitung masalah data id
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

        -- Hitung incoming FK (tabel lain mereferensi tabel ini.id)
        SELECT COUNT(*) INTO v_incoming_fk
        FROM information_schema.key_column_usage kcu
        WHERE kcu.referenced_table_schema = DATABASE()
          AND kcu.referenced_table_name = v_table
          AND kcu.referenced_column_name = 'id';

        IF v_null_id > 0 OR v_dup_id > 0 THEN
            -- Pastikan kolom helper __tmp_uid tersedia
            SELECT COUNT(*) INTO v_has_tmp_uid
            FROM information_schema.columns c
            WHERE c.table_schema = DATABASE()
              AND c.table_name = v_table
              AND c.column_name = '__tmp_uid';

            IF v_has_tmp_uid = 0 THEN
                SET @sql := CONCAT('ALTER TABLE `', v_table, '` ADD COLUMN `__tmp_uid` BIGINT NULL');
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            END IF;

            -- Isi uid unik per baris
            SET @rn := 0;
            SET @sql := CONCAT('UPDATE `', v_table, '` SET `__tmp_uid` = (@rn := @rn + 1) ORDER BY `id`, `created_at`');
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;

            -- Tentukan titik next id
            SET @sql := CONCAT('SELECT COALESCE(MAX(`id`), 0) + 1 INTO @v_next_id FROM `', v_table, '`');
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
            SET v_next_id := IFNULL(@v_next_id, 1);

            -- Perbaiki id NULL (aman untuk semua skenario)
            null_fix_loop: LOOP
                SET @sql := CONCAT('SELECT COUNT(*) INTO @v_null_check FROM `', v_table, '` WHERE `id` IS NULL');
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;

                IF IFNULL(@v_null_check, 0) = 0 THEN
                    LEAVE null_fix_loop;
                END IF;

                SET @sql := CONCAT(
                    'UPDATE `', v_table, '` SET `id` = ', v_next_id,
                    ' WHERE `id` IS NULL ORDER BY `__tmp_uid` LIMIT 1'
                );
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;

                SET v_next_id := v_next_id + 1;
            END LOOP;

            -- Perbaiki duplikat id
            -- Untuk incoming FK > 0: pertahankan 1 canonical row dengan id lama, sisanya pindah ke id baru.
            -- Untuk incoming FK = 0: mekanisme sama, tetap aman.
            DROP TEMPORARY TABLE IF EXISTS tmp_dup_fix;
            SET @next := v_next_id;

            SET @sql := CONCAT(
                'CREATE TEMPORARY TABLE tmp_dup_fix AS ',
                'SELECT x.__tmp_uid, x.id AS old_id, (@next := @next + 1) AS new_id ',
                'FROM `', v_table, '` x ',
                'JOIN (',
                '  SELECT id, MIN(__tmp_uid) AS keep_uid, COUNT(*) AS cnt ',
                '  FROM `', v_table, '` ',
                '  WHERE id IS NOT NULL ',
                '  GROUP BY id HAVING cnt > 1',
                ') d ON d.id = x.id AND x.__tmp_uid <> d.keep_uid ',
                'ORDER BY x.id, x.__tmp_uid'
            );
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;

            SET @sql := CONCAT(
                'UPDATE `', v_table, '` t ',
                'JOIN tmp_dup_fix f ON f.__tmp_uid = t.__tmp_uid ',
                'SET t.id = f.new_id'
            );
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;

            -- Bersihkan helper
            SET @sql := CONCAT('ALTER TABLE `', v_table, '` DROP COLUMN `__tmp_uid`');
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;

            -- Hitung ulang masalah id
            SET @sql := CONCAT('SELECT COUNT(*) INTO @v_null_id2 FROM `', v_table, '` WHERE `id` IS NULL');
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;

            SET @sql := CONCAT('SELECT COUNT(*) - COUNT(DISTINCT `id`) INTO @v_dup_id2 FROM `', v_table, '`');
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;

            IF IFNULL(@v_null_id2, 0) > 0 OR IFNULL(@v_dup_id2, 0) > 0 THEN
                INSERT INTO tmp_fk_safe_log(table_name, status, message)
                VALUES (
                    v_table,
                    'SKIP',
                    CONCAT('Gagal membersihkan id (NULL: ', IFNULL(@v_null_id2, 0), ', DUP: ', IFNULL(@v_dup_id2, 0), ')')
                );
                ITERATE read_loop;
            END IF;

            INSERT INTO tmp_fk_safe_log(table_name, status, message)
            VALUES (
                v_table,
                'REPAIR',
                CONCAT('Perbaikan id selesai. Incoming FK=', v_incoming_fk, ' (canonical duplicate strategy)')
            );
        END IF;

        -- Tambah PK jika belum ada
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

        -- Set next AI
        SET @sql := CONCAT('SELECT COALESCE(MAX(`id`), 0) + 1 INTO @next_ai FROM `', v_table, '`');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;

        SET @sql := CONCAT('ALTER TABLE `', v_table, '` AUTO_INCREMENT = ', IFNULL(@next_ai, 1));
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;

        INSERT INTO tmp_fk_safe_log(table_name, status, message)
        VALUES (v_table, 'OK', CONCAT('PK+AI terverifikasi, next AUTO_INCREMENT=', IFNULL(@next_ai, 1)));
    END LOOP;

    CLOSE cur;

    SELECT *
    FROM tmp_fk_safe_log
    ORDER BY table_name, FIELD(status, 'SKIP', 'REPAIR', 'OK');
END $$

CALL sp_repair_fk_safe_pk_ai() $$
DROP PROCEDURE sp_repair_fk_safe_pk_ai $$

DELIMITER ;
