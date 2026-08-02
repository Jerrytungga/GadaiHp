<?php

if (!function_exists('gadai_get_pokok')) {
    function gadai_get_pokok(array $row): float {
        return !empty($row['jumlah_disetujui'])
            ? (float)$row['jumlah_disetujui']
            : (float)($row['jumlah_pinjaman'] ?? 0);
    }
}

if (!function_exists('gadai_ensure_customer_table')) {
    function gadai_normalize_nik(string $nik): string {
        return preg_replace('/\D+/', '', trim($nik)) ?? '';
    }

    function gadai_customer_column_exists(PDO $db, string $columnName): bool {
        $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'customers' AND column_name = ?");
        $stmt->execute([$columnName]);
        return (int)$stmt->fetchColumn() > 0;
    }

    function gadai_customer_index_exists(PDO $db, string $indexName): bool {
        $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'customers' AND index_name = ?");
        $stmt->execute([$indexName]);
        return (int)$stmt->fetchColumn() > 0;
    }

    function gadai_ensure_customer_table(PDO $db): void {
        $db->exec("CREATE TABLE IF NOT EXISTS customers (
            id int(11) NOT NULL AUTO_INCREMENT,
            nama varchar(100) NOT NULL,
            nik varchar(16) NOT NULL,
            no_wa varchar(20) DEFAULT NULL,
            alamat text DEFAULT NULL,
            email varchar(100) DEFAULT NULL,
            password varchar(255) DEFAULT NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            last_login timestamp NULL DEFAULT NULL,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_customers_nik (nik),
            KEY idx_customers_nama (nama),
            KEY idx_customers_no_wa (no_wa),
            KEY idx_customers_is_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        if (!gadai_customer_column_exists($db, 'password')) {
            $db->exec("ALTER TABLE customers ADD COLUMN password varchar(255) DEFAULT NULL AFTER email");
        }

        if (!gadai_customer_column_exists($db, 'is_active')) {
            $db->exec("ALTER TABLE customers ADD COLUMN is_active tinyint(1) NOT NULL DEFAULT 1 AFTER password");
        }

        if (!gadai_customer_column_exists($db, 'last_login')) {
            $db->exec("ALTER TABLE customers ADD COLUMN last_login timestamp NULL DEFAULT NULL AFTER is_active");
        }

        if (!gadai_customer_index_exists($db, 'uq_customers_nik')) {
            $duplicateStmt = $db->query("SELECT nik FROM customers WHERE nik IS NOT NULL AND nik <> '' GROUP BY nik HAVING COUNT(*) > 1 LIMIT 1");
            $duplicateNik = $duplicateStmt->fetchColumn();
            if ($duplicateNik !== false) {
                throw new RuntimeException('Ditemukan NIK customer duplikat. Jalankan migration repair_customer_duplicate_nik.sql terlebih dahulu.');
            }
            $db->exec("ALTER TABLE customers ADD UNIQUE KEY uq_customers_nik (nik)");
        }
    }
}

if (!function_exists('gadai_upsert_customer')) {
    function gadai_upsert_customer(PDO $db, array $data): int {
        gadai_ensure_customer_table($db);

        $nama = trim((string)($data['nama'] ?? ''));
        $nik = gadai_normalize_nik((string)($data['nik'] ?? ''));
        $noWa = trim((string)($data['no_wa'] ?? ''));
        $alamat = trim((string)($data['alamat'] ?? ''));
        $email = trim((string)($data['email'] ?? ''));

        if ($nama === '' || $nik === '') {
            throw new RuntimeException('Data customer harus memiliki nama dan NIK.');
        }

        $stmt = $db->prepare("INSERT INTO customers (nama, nik, no_wa, alamat, email)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                nama = VALUES(nama),
                no_wa = VALUES(no_wa),
                alamat = VALUES(alamat),
                email = VALUES(email),
                updated_at = CURRENT_TIMESTAMP");
        $stmt->execute([$nama, $nik, $noWa !== '' ? $noWa : null, $alamat !== '' ? $alamat : null, $email !== '' ? $email : null]);

        $select = $db->prepare("SELECT id FROM customers WHERE nik = ? LIMIT 1");
        $select->execute([$nik]);
        $customerId = (int)$select->fetchColumn();

        if ($customerId <= 0) {
            throw new RuntimeException('Gagal menyimpan data customer.');
        }

        return $customerId;
    }
}

if (!function_exists('gadai_get_customer_by_nik')) {
    function gadai_get_customer_by_nik(PDO $db, string $nik): ?array {
        gadai_ensure_customer_table($db);
        $normalizedNik = gadai_normalize_nik($nik);
        $stmt = $db->prepare("SELECT * FROM customers WHERE nik = ? LIMIT 1");
        $stmt->execute([$normalizedNik]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}

if (!function_exists('gadai_get_customer_by_id')) {
    function gadai_get_customer_by_id(PDO $db, int $customerId): ?array {
        gadai_ensure_customer_table($db);
        $stmt = $db->prepare("SELECT * FROM customers WHERE id = ? LIMIT 1");
        $stmt->execute([$customerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}

if (!function_exists('gadai_ensure_pinjaman_request_table')) {
    function gadai_ensure_pinjaman_request_table(PDO $db): void {
        $db->exec("CREATE TABLE IF NOT EXISTS pinjaman_requests (
            id int(11) NOT NULL AUTO_INCREMENT,
            gadai_id int(11) NOT NULL,
            customer_id int(11) DEFAULT NULL,
            current_amount decimal(15,2) NOT NULL DEFAULT 0,
            requested_amount decimal(15,2) NOT NULL DEFAULT 0,
            max_additional decimal(15,2) NOT NULL DEFAULT 0,
            alasan text NOT NULL,
            status enum('Pending','Disetujui','Ditolak') NOT NULL DEFAULT 'Pending',
            admin_note text DEFAULT NULL,
            reviewed_at timestamp NULL DEFAULT NULL,
            reviewed_by int(11) DEFAULT NULL,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_pinjaman_requests_status (status),
            KEY idx_pinjaman_requests_gadai_id (gadai_id),
            KEY idx_pinjaman_requests_customer_id (customer_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
}

if (!function_exists('gadai_create_pinjaman_request')) {
    function gadai_create_pinjaman_request(PDO $db, array $data): int {
        gadai_ensure_pinjaman_request_table($db);

        $stmt = $db->prepare("INSERT INTO pinjaman_requests (
            gadai_id, customer_id, current_amount, requested_amount, max_additional, alasan, status
        ) VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
        $stmt->execute([
            (int)($data['gadai_id'] ?? 0),
            !empty($data['customer_id']) ? (int)$data['customer_id'] : null,
            (float)($data['current_amount'] ?? 0),
            (float)($data['requested_amount'] ?? 0),
            (float)($data['max_additional'] ?? 0),
            trim((string)($data['alasan'] ?? '')),
        ]);

        return (int)$db->lastInsertId();
    }
}

if (!function_exists('gadai_get_pinjaman_request')) {
    function gadai_get_pinjaman_request(PDO $db, int $requestId): ?array {
        gadai_ensure_pinjaman_request_table($db);
        $stmt = $db->prepare("SELECT pr.*, dg.nama, dg.nik, dg.no_wa, dg.jenis_barang, dg.merk_barang, dg.spesifikasi_barang, dg.status AS gadai_status, dg.jumlah_pinjaman, dg.jumlah_disetujui, dg.nilai_taksiran, dg.total_tebus
            FROM pinjaman_requests pr
            INNER JOIN data_gadai dg ON dg.id = pr.gadai_id
            WHERE pr.id = ? LIMIT 1");
        $stmt->execute([$requestId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}

if (!function_exists('gadai_get_pending_pinjaman_requests')) {
    function gadai_get_pending_pinjaman_requests(PDO $db): array {
        gadai_ensure_pinjaman_request_table($db);
        $stmt = $db->query("SELECT pr.*, dg.nama, dg.nik, dg.no_wa, dg.jenis_barang, dg.merk_barang, dg.spesifikasi_barang, dg.status AS gadai_status, dg.jumlah_pinjaman, dg.jumlah_disetujui, dg.nilai_taksiran, dg.total_tebus
            FROM pinjaman_requests pr
            INNER JOIN data_gadai dg ON dg.id = pr.gadai_id
            WHERE pr.status = 'Pending'
            ORDER BY pr.created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('gadai_get_customer_pinjaman_requests')) {
    function gadai_get_customer_pinjaman_requests(PDO $db, int $customerId): array {
        gadai_ensure_pinjaman_request_table($db);
        $stmt = $db->prepare("SELECT pr.*, dg.nama, dg.nik, dg.no_wa, dg.jenis_barang, dg.merk_barang, dg.spesifikasi_barang, dg.status AS gadai_status, dg.jumlah_pinjaman, dg.jumlah_disetujui, dg.nilai_taksiran, dg.total_tebus
            FROM pinjaman_requests pr
            INNER JOIN data_gadai dg ON dg.id = pr.gadai_id
            WHERE pr.customer_id = ?
            ORDER BY pr.created_at DESC");
        $stmt->execute([$customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('gadai_set_customer_password')) {
    function gadai_set_customer_password(PDO $db, int $customerId, string $passwordHash): void {
        gadai_ensure_customer_table($db);
        $stmt = $db->prepare("UPDATE customers SET password = ?, is_active = 1, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$passwordHash, $customerId]);
    }
}

if (!function_exists('gadai_verify_customer_login')) {
    function gadai_verify_customer_login(PDO $db, string $nik, string $password): ?array {
        $customer = gadai_get_customer_by_nik($db, $nik);
        if (!$customer || empty($customer['password']) || !(int)($customer['is_active'] ?? 1)) {
            return null;
        }

        if (!password_verify($password, (string)$customer['password'])) {
            return null;
        }

        $stmt = $db->prepare("UPDATE customers SET last_login = NOW(), updated_at = NOW() WHERE id = ?");
        $stmt->execute([(int)$customer['id']]);

        $customer['last_login'] = date('Y-m-d H:i:s');
        return $customer;
    }
}

if (!function_exists('gadai_customer_is_account_ready')) {
    function gadai_customer_is_account_ready(array $customer): bool {
        return !empty($customer['password']) && (int)($customer['is_active'] ?? 1) === 1;
    }
}

if (!function_exists('gadai_get_active_statuses')) {
    function gadai_get_active_statuses(): array {
        return ['Disetujui', 'Diperpanjang'];
    }
}

if (!function_exists('gadai_get_sale_statuses')) {
    function gadai_get_sale_statuses(): array {
        return ['Gagal Tebus', 'Siap Dijual', 'Terjual', 'Barang Dijual'];
    }
}

if (!function_exists('gadai_active_status_sql_list')) {
    function gadai_active_status_sql_list(): string {
        return "'" . implode("','", gadai_get_active_statuses()) . "'";
    }
}

if (!function_exists('gadai_sale_status_sql_list')) {
    function gadai_sale_status_sql_list(): string {
        return "'" . implode("','", gadai_get_sale_statuses()) . "'";
    }
}

if (!function_exists('gadai_is_active_status')) {
    function gadai_is_active_status($status): bool {
        return in_array((string)$status, gadai_get_active_statuses(), true);
    }
}

if (!function_exists('gadai_is_sale_status')) {
    function gadai_is_sale_status($status): bool {
        return in_array((string)$status, gadai_get_sale_statuses(), true);
    }
}

if (!function_exists('gadai_can_transition')) {
    function gadai_can_transition($currentStatus, string $targetStatus): bool {
        $currentStatus = (string)$currentStatus;

        switch ($targetStatus) {
            case 'Disetujui':
            case 'Ditolak':
                return $currentStatus === 'Pending';
            case 'Diperpanjang':
            case 'Lunas':
            case 'Gagal Tebus':
                return gadai_is_active_status($currentStatus);
            case 'Siap Dijual':
                return $currentStatus === 'Gagal Tebus';
            case 'Terjual':
                return in_array($currentStatus, ['Gagal Tebus', 'Siap Dijual'], true);
            default:
                return false;
        }
    }
}

if (!function_exists('gadai_calculate_days_late')) {
    function gadai_calculate_days_late($tanggal_jatuh_tempo): int {
        if (empty($tanggal_jatuh_tempo)) {
            return 0;
        }

        $dueTs = strtotime((string)$tanggal_jatuh_tempo);
        if ($dueTs === false) {
            return 0;
        }

        return max(0, (int)floor((time() - $dueTs) / 86400));
    }
}

if (!function_exists('gadai_calculate_denda')) {
    function gadai_calculate_denda($tanggal_jatuh_tempo, $persistedDenda = 0.0, int $dailyRate = 30000, int $maxDays = 7): array {
        $daysLate = gadai_calculate_days_late($tanggal_jatuh_tempo);
        $calculatedDenda = min($daysLate, $maxDays) * $dailyRate;
        $finalDenda = max((float)$persistedDenda, (float)$calculatedDenda);

        return [
            'days_late' => $daysLate,
            'daily_rate' => $dailyRate,
            'max_days' => $maxDays,
            'denda' => $finalDenda,
            'denda_calculated' => (float)$calculatedDenda,
        ];
    }
}

if (!function_exists('gadai_calculate_breakdown')) {
    function gadai_calculate_breakdown(array $row, ?float $overrideDenda = null): array {
        $pokok = gadai_get_pokok($row);
        $bungaPct = isset($row['bunga']) ? (float)$row['bunga'] : 0.0;
        $lama = isset($row['lama_gadai']) ? (int)$row['lama_gadai'] : 0;
        $bungaTotal = $pokok * ($bungaPct / 100) * $lama;
        $adminFee = round($pokok * 0.01);
        $biayaAsuransi = 10000;
        $denda = $overrideDenda !== null
            ? (float)$overrideDenda
            : (!empty($row['denda_terakumulasi']) ? (float)$row['denda_terakumulasi'] : 0.0);
        $totalTebus = $pokok + $bungaTotal + $adminFee + $biayaAsuransi + $denda;

        return [
            'pokok' => $pokok,
            'bunga_pct' => $bungaPct,
            'lama' => $lama,
            'bunga_total' => $bungaTotal,
            'admin_fee' => $adminFee,
            'biaya_asuransi' => $biayaAsuransi,
            'denda' => $denda,
            'biaya_perpanjangan' => round($bungaTotal + $adminFee + $biayaAsuransi + $denda),
            'total_tebus' => $totalTebus,
        ];
    }
}

if (!function_exists('gadai_get_harga_jual_rekomendasi')) {
    function gadai_get_harga_jual_rekomendasi(array $row): float {
        $nilaiTaksiran = isset($row['nilai_taksiran']) ? (float)$row['nilai_taksiran'] : 0.0;
        if ($nilaiTaksiran > 0) {
            return round($nilaiTaksiran);
        }

        $pokok = gadai_get_pokok($row);
        if ($pokok <= 0) {
            return 0.0;
        }

        return round($pokok);
    }
}
