<?php

if (!function_exists('handleAdminStatusActions')) {
    function handleAdminStatusActions(PDO $db, $whatsapp, string &$message, string &$message_type): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action']) || !in_array($_POST['action'], ['approve', 'reject', 'acc_pelunasan', 'acc_perpanjangan', 'manual_perpanjang'], true)) {
            return;
        }

        $action = (string)$_POST['action'];
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        try {
            if ($id <= 0) {
                throw new RuntimeException('ID data gadai tidak valid.');
            }

            $stmt = $db->prepare("SELECT * FROM data_gadai WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $data_row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$data_row) {
                throw new RuntimeException('Data gadai tidak ditemukan.');
            }

            $verified_by = (isset($_SESSION) && isset($_SESSION['admin_id'])) ? (int)$_SESSION['admin_id'] : 1;

            if ($action === 'approve') {
                if (!gadai_can_transition($data_row['status'] ?? '', 'Disetujui')) {
                    throw new RuntimeException('Hanya pengajuan dengan status Pending yang dapat disetujui.');
                }

                $harga_pasar = (float)($_POST['harga_pasar'] ?? 0);
                $jumlah_disetujui = (float)($_POST['jumlah_disetujui'] ?? 0);
                $keterangan_admin = trim((string)($_POST['keterangan_admin'] ?? ''));

                if ($harga_pasar <= 0 || $jumlah_disetujui <= 0) {
                    throw new RuntimeException('Harga pasar dan nominal yang disetujui wajib diisi.');
                }

                $maksimal_disetujui = $harga_pasar * 0.70;
                if ($jumlah_disetujui - $maksimal_disetujui > 0.01) {
                    throw new RuntimeException('Nominal yang disetujui melebihi batas 70% dari harga pasar.');
                }

                $calc = gadai_calculate_breakdown(array_merge($data_row, [
                    'jumlah_disetujui' => $jumlah_disetujui,
                    'denda_terakumulasi' => 0,
                ]), 0);

                $update = $db->prepare("UPDATE data_gadai SET status = 'Disetujui', nilai_taksiran = ?, jumlah_disetujui = ?, total_tebus = ?, catatan_admin = ?, alasan_penolakan = NULL, verified_at = NOW(), verified_by = ?, updated_at = NOW() WHERE id = ?");
                $update->execute([$harga_pasar, $jumlah_disetujui, $calc['total_tebus'], $keterangan_admin !== '' ? $keterangan_admin : null, $verified_by, $id]);

                $stmtFresh = $db->prepare("SELECT * FROM data_gadai WHERE id = ? LIMIT 1");
                $stmtFresh->execute([$id]);
                $fresh = $stmtFresh->fetch(PDO::FETCH_ASSOC) ?: $data_row;

                try {
                    if (isset($whatsapp)) {
                        $whatsapp->notifyUserApproved($fresh);
                    }
                } catch (Throwable $e) {
                    error_log('WhatsApp notify approve failed: ' . $e->getMessage());
                }

                $message = 'Pengajuan berhasil disetujui untuk #' . str_pad((string)$id, 6, '0', STR_PAD_LEFT) . '.';
                $message_type = 'success';
                return;
            }

            if ($action === 'reject') {
                if (!gadai_can_transition($data_row['status'] ?? '', 'Ditolak')) {
                    throw new RuntimeException('Hanya pengajuan dengan status Pending yang dapat ditolak.');
                }

                $alasan_reject = trim((string)($_POST['alasan_reject'] ?? ''));
                if ($alasan_reject === '') {
                    throw new RuntimeException('Alasan penolakan wajib diisi.');
                }

                $update = $db->prepare("UPDATE data_gadai SET status = 'Ditolak', alasan_penolakan = ?, catatan_admin = NULL, verified_at = NOW(), verified_by = ?, updated_at = NOW() WHERE id = ?");
                $update->execute([$alasan_reject, $verified_by, $id]);

                $stmtFresh = $db->prepare("SELECT * FROM data_gadai WHERE id = ? LIMIT 1");
                $stmtFresh->execute([$id]);
                $fresh = $stmtFresh->fetch(PDO::FETCH_ASSOC) ?: $data_row;
                if (!isset($fresh['reject_reason']) && isset($fresh['alasan_penolakan'])) {
                    $fresh['reject_reason'] = $fresh['alasan_penolakan'];
                }

                try {
                    if (isset($whatsapp)) {
                        $whatsapp->notifyUserRejected($fresh);
                    }
                } catch (Throwable $e) {
                    error_log('WhatsApp notify reject failed: ' . $e->getMessage());
                }

                $message = 'Pengajuan berhasil ditolak untuk #' . str_pad((string)$id, 6, '0', STR_PAD_LEFT) . '.';
                $message_type = 'success';
                return;
            }

            if ($action === 'acc_pelunasan') {
                if (!gadai_can_transition($data_row['status'] ?? '', 'Lunas')) {
                    throw new RuntimeException('ACC pelunasan hanya dapat dilakukan untuk gadai aktif.');
                }

                $trxStmt = $db->prepare("SELECT COUNT(*) AS bukti_count, COALESCE(SUM(jumlah_bayar), 0) AS bukti_total FROM transaksi WHERE barang_id = ? AND pelanggan_nik = ? AND keterangan = 'pelunasan'");
                $trxStmt->execute([$id, $data_row['nik']]);
                $trx = $trxStmt->fetch(PDO::FETCH_ASSOC) ?: ['bukti_count' => 0, 'bukti_total' => 0];

                if ((int)($trx['bukti_count'] ?? 0) <= 0) {
                    throw new RuntimeException('Belum ada bukti pembayaran pelunasan yang bisa di-ACC.');
                }

                $days_late = gadai_calculate_days_late($data_row['tanggal_jatuh_tempo'] ?? null);
                $denda = max(min($days_late, 7) * 30000, !empty($data_row['denda_terakumulasi']) ? (float)$data_row['denda_terakumulasi'] : 0.0);
                $calc = gadai_calculate_breakdown($data_row, $denda);
                $total_tebus_final = (float)$calc['total_tebus'];

                $db->beginTransaction();
                $update = $db->prepare("UPDATE data_gadai SET status = 'Lunas', denda_terakumulasi = ?, total_tebus = ?, verified_at = NOW(), verified_by = ?, updated_at = NOW() WHERE id = ?");
                $update->execute([$denda, $total_tebus_final, $verified_by, $id]);

                $updateTrx = $db->prepare("UPDATE transaksi SET keterangan = 'pelunasan_admin', updated_at = NOW() WHERE barang_id = ? AND pelanggan_nik = ? AND keterangan = 'pelunasan'");
                $updateTrx->execute([$id, $data_row['nik']]);
                $db->commit();

                $stmtFresh = $db->prepare("SELECT * FROM data_gadai WHERE id = ? LIMIT 1");
                $stmtFresh->execute([$id]);
                $fresh = $stmtFresh->fetch(PDO::FETCH_ASSOC) ?: $data_row;

                try {
                    if (isset($whatsapp)) {
                        $whatsapp->notifyUserPelunasanVerified($fresh);
                    }
                } catch (Throwable $e) {
                    error_log('WhatsApp notify pelunasan verified failed: ' . $e->getMessage());
                }

                $message = 'Pelunasan berhasil di-ACC untuk #' . str_pad((string)$id, 6, '0', STR_PAD_LEFT) . '. Status berubah menjadi Lunas.';
                $message_type = 'success';
                return;
            }

            if ($action === 'acc_perpanjangan') {
                if (!gadai_can_transition($data_row['status'] ?? '', 'Diperpanjang')) {
                    throw new RuntimeException('ACC perpanjangan hanya dapat dilakukan untuk gadai aktif.');
                }

                $trxStmt = $db->prepare("SELECT COUNT(*) AS bukti_count, COALESCE(SUM(jumlah_bayar), 0) AS bukti_total FROM transaksi WHERE barang_id = ? AND pelanggan_nik = ? AND keterangan = 'perpanjangan'");
                $trxStmt->execute([$id, $data_row['nik']]);
                $trx = $trxStmt->fetch(PDO::FETCH_ASSOC) ?: ['bukti_count' => 0, 'bukti_total' => 0];

                $bukti_count = (int)($trx['bukti_count'] ?? 0);
                $bukti_total = (float)($trx['bukti_total'] ?? 0);
                if ($bukti_count <= 0) {
                    throw new RuntimeException('Belum ada bukti pembayaran perpanjangan yang bisa di-ACC.');
                }

                $days_late = gadai_calculate_days_late($data_row['tanggal_jatuh_tempo'] ?? null);
                $denda = max(min($days_late, 7) * 30000, !empty($data_row['denda_terakumulasi']) ? (float)$data_row['denda_terakumulasi'] : 0.0);
                $calcCurrent = gadai_calculate_breakdown($data_row, $denda);
                $tagihan_perpanjangan = (float)$calcCurrent['biaya_perpanjangan'];

                if ($bukti_total + 0.01 < $tagihan_perpanjangan) {
                    throw new RuntimeException('Pembayaran perpanjangan masih kurang. Minimal Rp ' . number_format($tagihan_perpanjangan, 0, ',', '.') . ', baru masuk Rp ' . number_format($bukti_total, 0, ',', '.') . '.');
                }

                $current_due = !empty($data_row['tanggal_jatuh_tempo']) ? (string)$data_row['tanggal_jatuh_tempo'] : date('Y-m-d');
                $new_due = date('Y-m-d', strtotime($current_due . ' +30 days'));
                $calcNext = gadai_calculate_breakdown($data_row, 0);

                $db->beginTransaction();
                $update = $db->prepare("UPDATE data_gadai SET status = 'Diperpanjang', tanggal_jatuh_tempo = ?, perpanjangan_ke = COALESCE(perpanjangan_ke, 0) + 1, perpanjangan_terakhir_at = NOW(), denda_terakumulasi = 0, total_tebus = ?, verified_at = NOW(), verified_by = ?, updated_at = NOW() WHERE id = ?");
                $update->execute([$new_due, $calcNext['total_tebus'], $verified_by, $id]);

                $updateTrx = $db->prepare("UPDATE transaksi SET keterangan = 'perpanjangan_acc', updated_at = NOW() WHERE barang_id = ? AND pelanggan_nik = ? AND keterangan = 'perpanjangan'");
                $updateTrx->execute([$id, $data_row['nik']]);
                $db->commit();

                $stmtFresh = $db->prepare("SELECT * FROM data_gadai WHERE id = ? LIMIT 1");
                $stmtFresh->execute([$id]);
                $fresh = $stmtFresh->fetch(PDO::FETCH_ASSOC) ?: $data_row;

                try {
                    if (isset($whatsapp)) {
                        $whatsapp->notifyUserExtension($fresh, $new_due);
                        $whatsapp->notifyAdminExtension($fresh, $new_due);
                    }
                } catch (Throwable $e) {
                    error_log('WhatsApp notify extension failed: ' . $e->getMessage());
                }

                $message = 'Perpanjangan berhasil di-ACC untuk #' . str_pad((string)$id, 6, '0', STR_PAD_LEFT) . '. Jatuh tempo baru: ' . date('d M Y', strtotime($new_due)) . '.';
                $message_type = 'success';
                return;
            }

            if ($action === 'manual_perpanjang') {
                if (!gadai_can_transition($data_row['status'] ?? '', 'Diperpanjang')) {
                    throw new RuntimeException('Perpanjangan manual hanya dapat dilakukan untuk gadai aktif.');
                }

                $metode_input = !empty($_POST['metode']) ? trim((string)$_POST['metode']) : 'tunai_admin';
                $keterangan_admin_input = trim((string)($_POST['keterangan_admin'] ?? ''));

                $days_late = gadai_calculate_days_late($data_row['tanggal_jatuh_tempo'] ?? null);
                $denda = max(min($days_late, 7) * 30000, !empty($data_row['denda_terakumulasi']) ? (float)$data_row['denda_terakumulasi'] : 0.0);
                $calcCurrent = gadai_calculate_breakdown($data_row, $denda);
                $biaya_perpanjangan = (float)$calcCurrent['biaya_perpanjangan'];

                $current_due = !empty($data_row['tanggal_jatuh_tempo']) ? (string)$data_row['tanggal_jatuh_tempo'] : date('Y-m-d');
                $new_due = date('Y-m-d', strtotime($current_due . ' +30 days'));
                $calcNext = gadai_calculate_breakdown($data_row, 0);

                $existing_catatan = trim((string)($data_row['catatan_admin'] ?? ''));
                $catatan_parts = [];
                if ($existing_catatan !== '') {
                    $catatan_parts[] = $existing_catatan;
                }
                if ($keterangan_admin_input !== '') {
                    $catatan_parts[] = $keterangan_admin_input;
                }
                $catatan_parts[] = 'Perpanjangan manual oleh admin. Jatuh tempo baru: ' . date('d M Y', strtotime($new_due)) . '.';
                $catatan_admin_final = implode("\n", array_filter($catatan_parts));

                $db->beginTransaction();
                $update = $db->prepare("UPDATE data_gadai SET status = 'Diperpanjang', tanggal_jatuh_tempo = ?, perpanjangan_ke = COALESCE(perpanjangan_ke, 0) + 1, perpanjangan_terakhir_at = NOW(), denda_terakumulasi = 0, total_tebus = ?, catatan_admin = ?, verified_at = NOW(), verified_by = ?, updated_at = NOW() WHERE id = ?");
                $update->execute([$new_due, $calcNext['total_tebus'], $catatan_admin_final !== '' ? $catatan_admin_final : null, $verified_by, $id]);

                $tblExists = false;
                try {
                    $checkTbl = $db->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'transaksi'");
                    $checkTbl->execute();
                    $tblExists = (int)$checkTbl->fetchColumn() > 0;
                } catch (Throwable $e) {
                    $tblExists = false;
                }

                if ($tblExists) {
                    $insert = $db->prepare("INSERT INTO transaksi (imei, serial_number, jenis_barang, merk, tipe, pelanggan_nik, barang_id, jumlah_bayar, keterangan, metode_pembayaran, bukti) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $insert->execute([
                        $data_row['imei_serial'] ?? null,
                        $data_row['imei_serial'] ?? null,
                        $data_row['jenis_barang'] ?? null,
                        $data_row['merk_barang'] ?? null,
                        $data_row['spesifikasi_barang'] ?? null,
                        $data_row['nik'] ?? null,
                        $id,
                        $biaya_perpanjangan,
                        'perpanjangan_admin',
                        $metode_input,
                        null,
                    ]);
                }

                $db->commit();

                $stmtFresh = $db->prepare("SELECT * FROM data_gadai WHERE id = ? LIMIT 1");
                $stmtFresh->execute([$id]);
                $fresh = $stmtFresh->fetch(PDO::FETCH_ASSOC) ?: $data_row;

                try {
                    if (isset($whatsapp)) {
                        $whatsapp->notifyUserExtension($fresh, $new_due);
                        $whatsapp->notifyAdminExtension($fresh, $new_due);
                    }
                } catch (Throwable $e) {
                    error_log('WhatsApp notify manual extension failed: ' . $e->getMessage());
                }

                $message = 'Perpanjangan manual berhasil untuk #' . str_pad((string)$id, 6, '0', STR_PAD_LEFT) . '. Biaya tercatat: Rp ' . number_format($biaya_perpanjangan, 0, ',', '.') . '. Jatuh tempo baru: ' . date('d M Y', strtotime($new_due)) . '.';
                $message_type = 'success';
            }
        } catch (Throwable $e) {
            if ($db instanceof PDO && $db->inTransaction()) {
                $db->rollBack();
            }
            $message = 'Gagal memproses aksi admin: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

if (!function_exists('handleAdminManualLunasAction')) {
    function handleAdminManualLunasAction(PDO $db, $whatsapp, string &$message, string &$message_type): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action']) || $_POST['action'] !== 'manual_lunas') {
            return;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        try {
            $stmt = $db->prepare("SELECT * FROM data_gadai WHERE id = ?");
            $stmt->execute([$id]);
            $data_row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$data_row) {
                $message = 'Data gadai tidak ditemukan.';
                $message_type = 'danger';
            } elseif (!gadai_can_transition($data_row['status'] ?? '', 'Lunas')) {
                $message = 'Pelunasan manual hanya dapat dilakukan untuk status Disetujui atau Diperpanjang.';
                $message_type = 'warning';
            } else {
                $denda_existing = !empty($data_row['denda_terakumulasi']) ? (float)$data_row['denda_terakumulasi'] : 0.0;
                $calcPelunasan = gadai_calculate_breakdown($data_row, $denda_existing);
                $total_tebus = (float)$calcPelunasan['total_tebus'];

                $metode_input = !empty($_POST['metode']) ? trim((string)$_POST['metode']) : 'tunai_admin';
                $keterangan_admin_input = !empty($_POST['keterangan_admin']) ? trim((string)$_POST['keterangan_admin']) : null;

                try {
                    $db->beginTransaction();
                    $update = $db->prepare("UPDATE data_gadai SET status = 'Lunas', total_tebus = ?, catatan_admin = ?, updated_at = NOW() WHERE id = ?");
                    $update->execute([$total_tebus, $keterangan_admin_input, $id]);

                    $checkTbl = $db->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'transaksi'");
                    try {
                        $checkTbl->execute();
                        $tblExists = (int)$checkTbl->fetchColumn() > 0;
                    } catch (Throwable $e) {
                        $tblExists = false;
                    }

                    if ($tblExists) {
                        $insert = $db->prepare("INSERT INTO transaksi (imei, serial_number, jenis_barang, merk, tipe, pelanggan_nik, barang_id, jumlah_bayar, keterangan, metode_pembayaran, bukti) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $insert->execute([
                            null,
                            null,
                            $data_row['jenis_barang'] ?? null,
                            $data_row['merk_barang'] ?? null,
                            $data_row['spesifikasi_barang'] ?? null,
                            $data_row['nik'],
                            $id,
                            $total_tebus,
                            'pelunasan_admin',
                            $metode_input,
                            null
                        ]);
                    }

                    $db->commit();

                    $stmt2 = $db->prepare("SELECT * FROM data_gadai WHERE id = ?");
                    $stmt2->execute([$id]);
                    $fresh = $stmt2->fetch(PDO::FETCH_ASSOC);
                    try {
                        if (isset($whatsapp)) {
                            $whatsapp->notifyUserPelunasanVerified($fresh);
                        }
                    } catch (Throwable $e) {
                        error_log('WhatsApp notify failed: ' . $e->getMessage());
                    }

                    $message = 'Pelunasan manual berhasil. Total tebus: Rp ' . number_format($total_tebus, 0, ',', '.') . '.';
                    $message_type = 'success';
                } catch (Throwable $e) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                    $message = 'Gagal memproses pelunasan: ' . $e->getMessage();
                    $message_type = 'danger';
                }
            }
        } catch (Throwable $e) {
            $message = 'Gagal memproses permintaan: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

if (!function_exists('adminResolveBaseUrlContext')) {
    function adminResolveBaseUrlContext(): array {
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $basePath = '';

        if (!empty($_SERVER['SCRIPT_NAME'])) {
            $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            if ($basePath === '.') {
                $basePath = '';
            }
        }

        try {
            $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath((string)$_SERVER['DOCUMENT_ROOT']) : null;
            $appRoot = realpath(__DIR__);
            if ($docRoot && $appRoot && strcasecmp(rtrim($docRoot, '\\/'), rtrim($appRoot, '\\/')) === 0) {
                $basePath = '';
            }
        } catch (Throwable $e) {
            // ignore
        }

        $baseUrl = '';
        if ($host !== '') {
            $baseUrl = $scheme . '://' . $host;
            if ($host === 'localhost' || $host === '127.0.0.1') {
                $localIp = gethostbyname(gethostname());
                if ($localIp && $localIp !== '127.0.0.1' && filter_var($localIp, FILTER_VALIDATE_IP)) {
                    $baseUrl = $scheme . '://' . $localIp;
                }
            }
        }

        return [
            'scheme' => $scheme,
            'host' => $host,
            'base_path' => $basePath,
            'base_url' => $baseUrl,
        ];
    }
}

if (!function_exists('adminBuildStatusUrl')) {
    function adminBuildStatusUrl(int $id, bool $usePaddedId = false): string {
        $ctx = adminResolveBaseUrlContext();
        $noReg = $usePaddedId ? str_pad((string)$id, 6, '0', STR_PAD_LEFT) : (string)$id;
        return $ctx['base_url'] . $ctx['base_path'] . '/cek_status.php?no_registrasi=' . rawurlencode($noReg);
    }
}

if (!function_exists('adminNormalizeWhatsAppNumber')) {
    function adminNormalizeWhatsAppNumber(string $phone): string {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if ($phone === '') {
            return '';
        }
        if (substr($phone, 0, 1) === '0') {
            $phone = '62' . substr($phone, 1);
        }
        if (substr($phone, 0, 2) !== '62') {
            $phone = '62' . $phone;
        }
        return $phone;
    }
}

if (!function_exists('adminBuildWhatsAppLink')) {
    function adminBuildWhatsAppLink(string $phone, string $text): string {
        $normalized = adminNormalizeWhatsAppNumber($phone);
        return $normalized !== '' ? ('https://wa.me/' . $normalized . '?text=' . urlencode($text)) : '';
    }
}

if (!function_exists('adminAppendCatatanAdmin')) {
    function adminAppendCatatanAdmin(?string $existingNote, array $notes): ?string {
        $parts = [];
        $existing = trim((string)$existingNote);
        if ($existing !== '') {
            $parts[] = $existing;
        }

        foreach ($notes as $note) {
            $note = trim((string)$note);
            if ($note !== '') {
                $parts[] = $note;
            }
        }

        $final = trim(implode("\n", $parts));
        return $final !== '' ? $final : null;
    }
}

if (!function_exists('adminHandleManualAddGadaiAction')) {
    function adminHandleManualAddGadaiAction(PDO $db, $whatsapp, string &$message, string &$message_type): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action']) || $_POST['action'] !== 'manual_add_gadai') {
            return;
        }

        try {
            $nama = trim((string)($_POST['nama'] ?? ''));
            $no_wa = trim((string)($_POST['no_wa'] ?? ''));
            $alamat = trim((string)($_POST['alamat'] ?? ''));
            $jenis_barang = trim((string)($_POST['jenis_barang'] ?? ''));
            $merk_barang = trim((string)($_POST['merk_barang'] ?? ''));
            $spesifikasi_barang = trim((string)($_POST['spesifikasi_barang'] ?? ''));
            $kondisi_barang = trim((string)($_POST['kondisi_barang'] ?? 'Bekas - Baik'));
            $kelengkapan_barang = trim((string)($_POST['kelengkapan_barang'] ?? ''));

            $nilai_taksiran = ($_POST['nilai_taksiran'] ?? null);
            $nilai_taksiran = ($nilai_taksiran === '' || $nilai_taksiran === null) ? null : (float)$nilai_taksiran;

            $jumlah_pinjaman = (float)($_POST['jumlah_pinjaman'] ?? 0);
            $bunga = (float)($_POST['bunga'] ?? 5.00);
            $lama_gadai = (int)($_POST['lama_gadai'] ?? 30);
            $tanggal_gadai = trim((string)($_POST['tanggal_gadai'] ?? ''));
            $tanggal_jatuh_tempo = trim((string)($_POST['tanggal_jatuh_tempo'] ?? ''));

            if ($nama === '' || $no_wa === '' || $alamat === '' || $jenis_barang === '' || $jumlah_pinjaman <= 0 || $tanggal_gadai === '' || $tanggal_jatuh_tempo === '') {
                $message = 'Lengkapi field wajib (Nama, No. WhatsApp, Alamat, Jenis Barang, Jumlah Pinjaman, Tanggal).';
                $message_type = 'warning';
                return;
            }

            $catatan_admin = $kelengkapan_barang !== '' ? $kelengkapan_barang : null;
            $kelengkapan_hp = $kelengkapan_barang !== '' ? $kelengkapan_barang : null;

            $baseParams = [
                $nama,
                null,
                $no_wa,
                $alamat,
                $jenis_barang,
                ($merk_barang !== '' ? $merk_barang : null),
                ($spesifikasi_barang !== '' ? $spesifikasi_barang : null),
                $kondisi_barang,
                $nilai_taksiran,
                $jumlah_pinjaman,
                $bunga,
                $lama_gadai,
                $tanggal_gadai,
                $tanggal_jatuh_tempo,
            ];

            $sqlFull = "INSERT INTO data_gadai (
                nama, nik, no_wa, alamat, jenis_barang, merk_barang, spesifikasi_barang,
                kondisi_barang, nilai_taksiran, jumlah_pinjaman, bunga, lama_gadai,
                tanggal_gadai, tanggal_jatuh_tempo, status, catatan_admin, kelengkapan_hp
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, 'Disetujui', ?, ?
            )";

            $sqlFallback = "INSERT INTO data_gadai (
                nama, nik, no_wa, alamat, jenis_barang, merk_barang, spesifikasi_barang,
                kondisi_barang, nilai_taksiran, jumlah_pinjaman, bunga, lama_gadai,
                tanggal_gadai, tanggal_jatuh_tempo, status
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, 'Disetujui'
            )";

            $attempts = 0;
            $inserted = false;
            while (!$inserted && $attempts < 3) {
                $attempts++;
                $nik = date('ymdHis') . str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);

                $paramsFull = $baseParams;
                $paramsFull[1] = $nik;

                try {
                    $stmt = $db->prepare($sqlFull);
                    $stmt->execute(array_merge($paramsFull, [$catatan_admin, $kelengkapan_hp]));
                    $inserted = true;
                } catch (PDOException $e) {
                    $msg = $e->getMessage();
                    if (stripos($msg, 'Unknown column') !== false) {
                        $stmt = $db->prepare($sqlFallback);
                        $stmt->execute($paramsFull);
                        $inserted = true;
                    } elseif (stripos($msg, 'Duplicate') !== false || stripos($msg, 'duplicate') !== false) {
                        $inserted = false;
                        continue;
                    } else {
                        throw $e;
                    }
                }
            }

            if (!$inserted) {
                throw new RuntimeException('Gagal membuat NIK unik untuk input manual.');
            }

            $newId = (int)$db->lastInsertId();
            $reg = '#' . str_pad((string)$newId, 6, '0', STR_PAD_LEFT);
            $waSentOk = false;
            $waLink = '';
            $waErr = '';

            try {
                if (!isset($whatsapp) || !is_object($whatsapp) || !method_exists($whatsapp, 'sendMessage')) {
                    throw new RuntimeException('WhatsApp helper tidak tersedia.');
                }

                $stmtNew = $db->prepare("SELECT * FROM data_gadai WHERE id = ?");
                $stmtNew->execute([$newId]);
                $newRow = $stmtNew->fetch(PDO::FETCH_ASSOC);

                $statusUrl = adminBuildStatusUrl($newId);
                $namaUser = (string)($newRow['nama'] ?? $nama);
                $barang = trim((string)(($newRow['jenis_barang'] ?? $jenis_barang) . ' ' . ($newRow['merk_barang'] ?? $merk_barang) . ' ' . ($newRow['spesifikasi_barang'] ?? $spesifikasi_barang)));
                $barang = trim((string)preg_replace('/\s+/', ' ', $barang));
                $tglGadaiFmt = !empty($newRow['tanggal_gadai']) ? date('d M Y', strtotime((string)$newRow['tanggal_gadai'])) : (!empty($tanggal_gadai) ? date('d M Y', strtotime($tanggal_gadai)) : '-');
                $tglJtFmt = !empty($newRow['tanggal_jatuh_tempo']) ? date('d M Y', strtotime((string)$newRow['tanggal_jatuh_tempo'])) : (!empty($tanggal_jatuh_tempo) ? date('d M Y', strtotime($tanggal_jatuh_tempo)) : '-');
                $pinjaman = !empty($newRow['jumlah_disetujui']) ? (float)$newRow['jumlah_disetujui'] : (float)($newRow['jumlah_pinjaman'] ?? $jumlah_pinjaman);
                $fmt = static function($v) { return 'Rp ' . number_format((float)$v, 0, ',', '.'); };

                $storeName = 'Gadai Cepat Timika Papua';
                $storeWhatsapp = '0858-2309-1908';

                $waText  = "Assalamualaikum " . ($namaUser !== '' ? $namaUser : '') . ",\n\n";
                $waText .= "Data gadai Anda sudah dibuat dan *Disetujui*.\n\n";
                $waText .= "No. Registrasi: " . $reg . "\n";
                if ($barang !== '') {
                    $waText .= "Barang: " . $barang . "\n";
                }
                $waText .= "Tanggal Gadai: " . $tglGadaiFmt . "\n";
                $waText .= "Jatuh Tempo: " . $tglJtFmt . "\n";
                $waText .= "Pinjaman: " . $fmt($pinjaman) . "\n\n";
                $waText .= "Cek status/riwayat di link berikut:\n";
                $waText .= $statusUrl . "\n\n";
                $waText .= "Simpan pesan ini sebagai bukti transaksi.\n";
                $waText .= "Terima kasih.\n";
                $waText .= $storeName . "\n";
                $waText .= "WA: " . $storeWhatsapp;

                $sendResp = $whatsapp->sendMessage($no_wa, $waText);
                $waIsManualMode = is_array($sendResp) && isset($sendResp['link']);
                if ($waIsManualMode) {
                    $waLink = (string)$sendResp['link'];
                }

                $waSentOk = is_array($sendResp) && (
                    (!empty($sendResp['success']) && $sendResp['success'] === true && !$waIsManualMode)
                    || (!empty($sendResp['status']) && $sendResp['status'] === true)
                );

                if (!$waSentOk && is_array($sendResp) && isset($sendResp['message'])) {
                    $waErr = (string)$sendResp['message'];
                }

                if (!$waSentOk && $waLink === '') {
                    $waLink = adminBuildWhatsAppLink($no_wa, $waText);
                }
            } catch (Throwable $e) {
                $waErr = $e->getMessage();
                $waLink = adminBuildWhatsAppLink($no_wa, $waText ?? '');
            }

            if ($waSentOk) {
                $message = 'Data gadai berhasil ditambahkan. Notif WhatsApp berhasil diproses. ID: ' . $reg;
                $message_type = 'success';
            } else {
                $extra = $waErr !== '' ? (' (' . htmlspecialchars($waErr, ENT_QUOTES, 'UTF-8') . ')') : '';
                $message = 'Data gadai berhasil ditambahkan, namun notif WhatsApp tidak terkirim otomatis' . $extra . '. ID: ' . $reg;
                if ($waLink !== '') {
                    $message .= ' <a href="' . htmlspecialchars($waLink, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">Buka WhatsApp</a>';
                }
                $message_type = 'warning';
            }
        } catch (Throwable $e) {
            $message = 'Gagal menambahkan data gadai: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

if (!function_exists('adminHandleReminderOverdueAction')) {
    function adminHandleReminderOverdueAction(PDO $db, $whatsapp, string &$message, string &$message_type): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action']) || $_POST['action'] !== 'manual_reminder_overdue') {
            return;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        try {
            if ($id <= 0) {
                $message = 'ID tidak valid.';
                $message_type = 'warning';
                return;
            }

            $stmt = $db->prepare("SELECT dg.*, DATEDIFF(CURDATE(), dg.tanggal_jatuh_tempo) AS days_overdue FROM data_gadai dg WHERE dg.id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $message = 'Data gadai tidak ditemukan.';
                $message_type = 'danger';
            } elseif (empty($row['no_wa'])) {
                $message = 'Nomor WhatsApp kosong. Tidak dapat mengirim reminder.';
                $message_type = 'warning';
            } elseif (empty($row['tanggal_jatuh_tempo'])) {
                $message = 'Tanggal jatuh tempo belum ada. Tidak dapat mengirim reminder.';
                $message_type = 'warning';
            } else {
                $days_overdue = isset($row['days_overdue']) ? (int)$row['days_overdue'] : 0;
                if ($days_overdue < 0) {
                    $days_overdue = 0;
                }

                if ($days_overdue <= 0) {
                    $message = 'Belum terlambat jatuh tempo. Reminder overdue tidak dikirim.';
                    $message_type = 'info';
                    return;
                }

                if (!isset($whatsapp)) {
                    throw new RuntimeException('WhatsApp helper tidak terinisialisasi.');
                }

                if ($days_overdue >= 8) {
                    $denda_total = 30000 * 7;
                    $calc = gadai_calculate_breakdown($row, $denda_total);
                    $total_tebus = (float)$calc['total_tebus'];

                    $updFail = $db->prepare("UPDATE data_gadai SET status = 'Gagal Tebus', gagal_tebus_at = NOW(), denda_terakumulasi = ?, total_tebus = ?, updated_at = NOW() WHERE id = ?");
                    $updFail->execute([$denda_total, $total_tebus, $id]);

                    $stmtRef = $db->prepare("SELECT * FROM data_gadai WHERE id = ?");
                    $stmtRef->execute([$id]);
                    $fresh = $stmtRef->fetch(PDO::FETCH_ASSOC);

                    try {
                        $whatsapp->notifyUserGagalTebus($fresh);
                        $whatsapp->notifyAdminGagalTebus($fresh);
                    } catch (Throwable $e) {
                        error_log('WhatsApp notify gagal tebus failed: ' . $e->getMessage());
                    }

                    $message = 'Telat lebih dari 7 hari. Status diubah menjadi Gagal Tebus (gagal bayar) untuk #' . str_pad((string)$id, 6, '0', STR_PAD_LEFT) . '.';
                    $message_type = 'warning';
                    return;
                }

                $resp = null;
                try {
                    $resp = $whatsapp->notifyUserOverdue($row, $days_overdue);
                } catch (Throwable $e) {
                    $reg = '#' . str_pad((string)$id, 6, '0', STR_PAD_LEFT);
                    $text = "Reminder jatuh tempo: {$reg} telat {$days_overdue} hari. Mohon segera pelunasan/perpanjangan.\n";
                    $text .= "Cek status: " . adminBuildStatusUrl($id);
                    $link = adminBuildWhatsAppLink((string)$row['no_wa'], $text);

                    $message = 'Gagal kirim otomatis (' . $e->getMessage() . '). Silakan kirim manual: ';
                    $message .= '<a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">Buka WhatsApp</a>';
                    $message_type = 'warning';
                    $resp = ['success' => false, 'message' => 'fallback_manual', 'link' => $link];
                }

                try {
                    $upd = $db->prepare("UPDATE data_gadai SET reminder_telat_at = NOW(), reminder_telat_due_date = CURDATE(), updated_at = NOW() WHERE id = ?");
                    $upd->execute([$id]);
                } catch (Throwable $e) {
                    // ignore jika kolom tidak tersedia
                }

                $ok = false;
                $extra = '';
                if (is_array($resp)) {
                    $ok = (!empty($resp['success']) && $resp['success'] === true) || (!empty($resp['status']) && $resp['status'] === true);
                    if (!$ok && isset($resp['message'])) {
                        $extra = ' (' . (string)$resp['message'] . ')';
                    }
                    if (!empty($resp['link'])) {
                        $link = (string)$resp['link'];
                        $extra .= ' <a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">Buka WhatsApp</a>';
                    }
                }

                if ($ok) {
                    $message = 'Reminder WhatsApp berhasil diproses untuk #' . str_pad((string)$id, 6, '0', STR_PAD_LEFT) . ' (telat ' . $days_overdue . ' hari).' . $extra;
                    $message_type = 'success';
                } elseif ($message === '') {
                    $message = 'Reminder WhatsApp gagal diproses untuk #' . str_pad((string)$id, 6, '0', STR_PAD_LEFT) . '.' . $extra . ' (Cek log_wa.txt)';
                    $message_type = 'warning';
                }
            }
        } catch (Throwable $e) {
            $message = 'Gagal mengirim reminder WhatsApp: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

if (!function_exists('adminHandleNotifyGagalTebusAction')) {
    function adminHandleNotifyGagalTebusAction(PDO $db, $whatsapp, string &$message, string &$message_type): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action']) || $_POST['action'] !== 'manual_notify_gagal_tebus') {
            return;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        try {
            if ($id <= 0) {
                $message = 'ID tidak valid.';
                $message_type = 'warning';
                return;
            }

            $stmt = $db->prepare("SELECT * FROM data_gadai WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $message = 'Data gadai tidak ditemukan.';
                $message_type = 'danger';
            } elseif (($row['status'] ?? '') !== 'Gagal Tebus') {
                $message = 'Status belum Gagal Tebus. Notifikasi tidak dikirim.';
                $message_type = 'info';
            } else {
                if (!isset($whatsapp)) {
                    throw new RuntimeException('WhatsApp helper tidak terinisialisasi.');
                }

                $respUser = null;
                $respAdmin = null;
                try {
                    $respUser = $whatsapp->notifyUserGagalTebus($row);
                } catch (Throwable $e) {
                    $respUser = ['success' => false, 'message' => $e->getMessage()];
                }
                try {
                    $respAdmin = $whatsapp->notifyAdminGagalTebus($row);
                } catch (Throwable $e) {
                    $respAdmin = ['success' => false, 'message' => $e->getMessage()];
                }

                $okUser = is_array($respUser) && ((!empty($respUser['success']) && $respUser['success'] === true) || (!empty($respUser['status']) && $respUser['status'] === true));
                $okAdmin = is_array($respAdmin) && ((!empty($respAdmin['success']) && $respAdmin['success'] === true) || (!empty($respAdmin['status']) && $respAdmin['status'] === true));

                if ($okUser || $okAdmin) {
                    $message = 'Notifikasi Gagal Tebus diproses untuk #' . str_pad((string)$id, 6, '0', STR_PAD_LEFT) . '.';
                    $message_type = 'success';
                } else {
                    $detail = '';
                    if (is_array($respUser) && isset($respUser['message'])) {
                        $detail .= ' User: ' . (string)$respUser['message'] . '.';
                    }
                    if (is_array($respAdmin) && isset($respAdmin['message'])) {
                        $detail .= ' Admin: ' . (string)$respAdmin['message'] . '.';
                    }
                    $message = 'Gagal memproses notifikasi Gagal Tebus untuk #' . str_pad((string)$id, 6, '0', STR_PAD_LEFT) . '.' . $detail . ' (Cek log_wa.txt)';
                    $message_type = 'warning';
                }
            }
        } catch (Throwable $e) {
            $message = 'Gagal memproses notifikasi Gagal Tebus: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

if (!function_exists('adminHandleSendNotaAction')) {
    function adminHandleSendNotaAction(PDO $db, $whatsapp, string &$message, string &$message_type): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action']) || $_POST['action'] !== 'manual_send_nota') {
            return;
        }

        $idRaw = $_POST['id'] ?? ($_POST['id_btn'] ?? ($_POST['no_registrasi'] ?? 0));
        $id = (int)preg_replace('/[^0-9]/', '', (string)$idRaw);

        try {
            if ($id <= 0) {
                $message = 'ID tidak valid.';
                $message_type = 'warning';
                return;
            }

            $stmt = $db->prepare("SELECT * FROM data_gadai WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $message = 'Data gadai tidak ditemukan.';
                $message_type = 'danger';
            } elseif (empty($row['no_wa'])) {
                $message = 'Nomor WhatsApp kosong. Nota tidak bisa dikirim.';
                $message_type = 'warning';
            } else {
                $autoload = __DIR__ . '/vendor/autoload.php';
                if (!file_exists($autoload)) {
                    throw new RuntimeException('vendor/autoload.php tidak ditemukan. Jalankan composer install.');
                }
                require_once $autoload;

                $ctx = adminResolveBaseUrlContext();
                $phone = adminNormalizeWhatsAppNumber((string)$row['no_wa']);
                $denda = !empty($row['denda_terakumulasi']) ? (float)$row['denda_terakumulasi'] : 0.0;
                $calcNota = gadai_calculate_breakdown($row, $denda);
                $pokok = $calcNota['pokok'];
                $bunga_pct = $calcNota['bunga_pct'];
                $lama = $calcNota['lama'];
                $bunga_total = $calcNota['bunga_total'];
                $admin_fee = $calcNota['admin_fee'];
                $biaya_asuransi = $calcNota['biaya_asuransi'];

                $total_tebus = (!empty($row['total_tebus']) && (float)$row['total_tebus'] > 0)
                    ? (float)$row['total_tebus']
                    : (float)$calcNota['total_tebus'];

                $reg = '#' . str_pad((string)$row['id'], 6, '0', STR_PAD_LEFT);
                $nama = (string)($row['nama'] ?? '-');
                $barang = trim((string)(($row['jenis_barang'] ?? '') . ' ' . ($row['merk_barang'] ?? '') . ' ' . ($row['spesifikasi_barang'] ?? '')));
                $barang = trim((string)preg_replace('/\s+/', ' ', $barang));
                $tglGadai = !empty($row['tanggal_gadai']) ? date('d M Y', strtotime((string)$row['tanggal_gadai'])) : '-';
                $tglJt = !empty($row['tanggal_jatuh_tempo']) ? date('d M Y', strtotime((string)$row['tanggal_jatuh_tempo'])) : '-';
                $status = (string)($row['status'] ?? '-');
                $statusUrl = adminBuildStatusUrl((int)$row['id'], true);

                $fmt = static function($v) { return 'Rp ' . number_format((float)$v, 0, ',', '.'); };
                $safe = static function($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };

                $logoDataUri = null;
                foreach ([__DIR__ . '/image/logo_baru.png', __DIR__ . '/image/GC.png'] as $candidate) {
                    if (is_file($candidate)) {
                        $ext = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
                        $mime = ($ext === 'jpg' || $ext === 'jpeg') ? 'image/jpeg' : (($ext === 'png') ? 'image/png' : null);
                        if ($mime) {
                            $bin = @file_get_contents($candidate);
                            if ($bin !== false && $bin !== '') {
                                $logoDataUri = 'data:' . $mime . ';base64,' . base64_encode($bin);
                                break;
                            }
                        }
                    }
                }

                $storeName = 'Gadai Cepat Timika Papua';
                $storeAddress = 'Timika, Papua';
                $storeWhatsapp = '0858-2309-1908';
                $printedAt = date('d M Y H:i');

                $html = '<html><head><meta charset="utf-8">'
                    . '<style>'
                    . 'body{font-family:DejaVu Sans, Arial, sans-serif;font-size:12px;color:#111;margin:0;padding:18px;}'
                    . '.card{border:1px solid #ddd;padding:14px;}'
                    . '.muted{color:#555;}'
                    . '.right{text-align:right;}'
                    . '.header{width:100%;border-collapse:collapse;margin:0 0 10px 0;}'
                    . '.header td{padding:0;vertical-align:top;}'
                    . '.logo{height:44px;}'
                    . '.title{font-size:16px;font-weight:bold;margin:0;line-height:1.2;}'
                    . '.slogan{font-size:12px;font-weight:bold;margin-top:2px;line-height:1.2;}'
                    . '.meta{font-size:11px;line-height:1.35;}'
                    . '.divider{border-top:1px solid #ddd;margin:10px 0;}'
                    . '.info{width:100%;border-collapse:collapse;margin:0;}'
                    . '.info td{padding:2px 0;vertical-align:top;}'
                    . '.label{width:34%;color:#555;}'
                    . '.items{width:100%;border-collapse:collapse;margin-top:6px;}'
                    . '.items th{font-weight:bold;text-align:left;padding:6px 0;border-bottom:1px solid #ddd;}'
                    . '.items td{padding:6px 0;border-bottom:1px solid #ddd;vertical-align:top;}'
                    . '.items .amount{text-align:right;white-space:nowrap;}'
                    . '.total-row td{border-bottom:none;border-top:1px solid #111;padding-top:8px;font-weight:bold;font-size:13px;}'
                    . '.footer{margin-top:12px;font-size:10.5px;line-height:1.4;color:#555;}'
                    . '</style></head><body>';

                $html .= '<div class="card">';
                $html .= '<table class="header"><tr>';
                if ($logoDataUri) {
                    $html .= '<td style="width:1%;white-space:nowrap;padding-right:12px;"><img class="logo" src="' . $safe($logoDataUri) . '" alt="Logo"></td>';
                }
                $html .= '<td>';
                $html .= '<div class="title">NOTA GADAI</div>';
                $html .= '<div class="slogan">Gadai Cepat Timika</div>';
                $html .= '<div class="muted">' . $safe($storeName) . '</div>';
                $html .= '<div class="muted">' . $safe($storeAddress) . '</div>';
                $html .= '<div class="muted">WA: ' . $safe($storeWhatsapp) . '</div>';
                $html .= '</td>';
                $html .= '<td class="right meta" style="width:36%;">'
                    . '<div><span class="muted">No. Registrasi</span><br><strong>' . $safe($reg) . '</strong></div>'
                    . '<div style="margin-top:6px;"><span class="muted">Dicetak</span><br>' . $safe($printedAt) . '</div>'
                    . '</td>';
                $html .= '</tr></table>';
                $html .= '<div class="divider"></div>';
                $html .= '<table class="info">';
                $html .= '<tr><td class="label">Nama</td><td>' . $safe($nama) . '</td></tr>';
                if ($barang !== '') {
                    $html .= '<tr><td class="label">Barang</td><td>' . $safe($barang) . '</td></tr>';
                }
                $html .= '<tr><td class="label">Tanggal Gadai</td><td>' . $safe($tglGadai) . '</td></tr>';
                $html .= '<tr><td class="label">Jatuh Tempo</td><td>' . $safe($tglJt) . '</td></tr>';
                $html .= '<tr><td class="label">Status</td><td>' . $safe($status) . '</td></tr>';
                $html .= '</table>';
                $html .= '<div class="divider"></div>';
                $html .= '<div style="font-weight:bold;margin-bottom:4px;">Rincian Biaya</div>';
                $html .= '<table class="items">';
                $html .= '<thead><tr><th>Deskripsi</th><th class="amount">Nominal</th></tr></thead><tbody>';
                $html .= '<tr><td>Pokok</td><td class="amount">' . $safe($fmt($pokok)) . '</td></tr>';
                $html .= '<tr><td>Bunga (' . $safe($bunga_pct) . '% x ' . $safe($lama) . ')</td><td class="amount">' . $safe($fmt($bunga_total)) . '</td></tr>';
                $html .= '<tr><td>Admin Fee (1%)</td><td class="amount">' . $safe($fmt($admin_fee)) . '</td></tr>';
                $html .= '<tr><td>Asuransi</td><td class="amount">' . $safe($fmt($biaya_asuransi)) . '</td></tr>';
                if ($denda > 0) {
                    $html .= '<tr><td>Denda</td><td class="amount">' . $safe($fmt($denda)) . '</td></tr>';
                }
                $html .= '<tr class="total-row"><td>Total Tebus</td><td class="amount">' . $safe($fmt($total_tebus)) . '</td></tr>';
                $html .= '</tbody></table>';
                $html .= '<div class="divider"></div>';
                $html .= '<div class="footer">'
                    . 'Cek status: ' . $safe($statusUrl) . '<br>'
                    . 'Simpan nota ini sebagai bukti transaksi. Untuk bantuan silakan hubungi WhatsApp ' . $safe($storeWhatsapp) . '.'
                    . '</div>';
                $html .= '</div>';
                $html .= '</body></html>';

                $token = bin2hex(random_bytes(8));
                $dir = __DIR__ . '/uploads/nota';
                if (!is_dir($dir)) {
                    @mkdir($dir, 0755, true);
                }
                if (!is_dir($dir)) {
                    throw new RuntimeException('Gagal membuat folder uploads/nota');
                }

                $fileName = 'nota_' . (int)$row['id'] . '_' . $token . '.pdf';
                $filePath = $dir . '/' . $fileName;

                $dompdf = new \Dompdf\Dompdf();
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->loadHtml($html, 'UTF-8');
                $dompdf->render();
                file_put_contents($filePath, $dompdf->output());

                $pdfUrl = $ctx['base_url'] . $ctx['base_path'] . '/uploads/nota/' . rawurlencode($fileName);

                $waText  = "Assalamualaikum " . ($nama !== '' ? $nama : '') . ",\n\n";
                $waText .= "Berikut *Nota Gadai* Anda dari *Gadai Cepat Timika*.\n\n";
                $waText .= "No. Registrasi: " . $reg . "\n";
                if ($barang !== '') {
                    $waText .= "Barang: " . $barang . "\n";
                }
                $waText .= "Jatuh Tempo: " . $tglJt . "\n";
                $waText .= "Total Tebus: " . $fmt($total_tebus) . "\n\n";
                $waText .= "Silakan buka/unduh PDF nota di link berikut:\n";
                $waText .= $pdfUrl . "\n\n";
                $waText .= "Terima kasih.\n";
                $waText .= $storeName . "\n";
                $waText .= "WA: " . $storeWhatsapp;

                $sendResp = null;
                $sentOk = false;
                $isManualMode = false;
                try {
                    if (!isset($whatsapp) || !is_object($whatsapp) || !method_exists($whatsapp, 'sendMessage')) {
                        throw new RuntimeException('WhatsApp helper tidak tersedia.');
                    }
                    $sendResp = $whatsapp->sendMessage($row['no_wa'], $waText);
                    $isManualMode = is_array($sendResp) && isset($sendResp['link']);
                    $sentOk = is_array($sendResp) && (
                        (!empty($sendResp['success']) && $sendResp['success'] === true && !$isManualMode)
                        || (!empty($sendResp['status']) && $sendResp['status'] === true)
                    );
                } catch (Throwable $e) {
                    $sendResp = ['success' => false, 'message' => $e->getMessage()];
                }

                $fallbackWaLink = adminBuildWhatsAppLink((string)$row['no_wa'], $waText);

                if ($sentOk) {
                    $message = 'Nota PDF berhasil dikirim otomatis. '
                        . '<a href="' . htmlspecialchars($pdfUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">Buka PDF</a>';
                    $message_type = 'success';
                } elseif ($isManualMode) {
                    $message = 'Provider WhatsApp masih mode manual (tidak bisa kirim otomatis). '
                        . '<a href="' . htmlspecialchars($pdfUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">Buka PDF</a>'
                        . ' | <a href="' . htmlspecialchars($fallbackWaLink, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">Buka WhatsApp</a>';
                    $message_type = 'warning';
                } else {
                    $detail = '';
                    if (is_array($sendResp) && isset($sendResp['message'])) {
                        $detail = ' (' . htmlspecialchars((string)$sendResp['message'], ENT_QUOTES, 'UTF-8') . ')';
                    }
                    $message = 'Gagal kirim nota otomatis' . $detail . '. '
                        . '<a href="' . htmlspecialchars($pdfUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">Buka PDF</a>'
                        . ' | <a href="' . htmlspecialchars($fallbackWaLink, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">Buka WhatsApp</a>'
                        . ' (Cek log_wa.txt)';
                    $message_type = 'warning';
                }
            }
        } catch (Throwable $e) {
            $message = 'Gagal membuat nota: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

if (!function_exists('adminHandleMarkSiapJualAction')) {
    function adminHandleMarkSiapJualAction(PDO $db, string &$message, string &$message_type): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action']) || $_POST['action'] !== 'mark_siap_jual') {
            return;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $keterangan = trim((string)($_POST['keterangan_admin'] ?? ''));

        try {
            if ($id <= 0) {
                throw new RuntimeException('ID data gadai tidak valid.');
            }

            $stmt = $db->prepare("SELECT * FROM data_gadai WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                throw new RuntimeException('Data gadai tidak ditemukan.');
            }

            if (!gadai_can_transition($row['status'] ?? '', 'Siap Dijual')) {
                throw new RuntimeException('Hanya data dengan status Gagal Tebus yang dapat dipindahkan ke Siap Dijual.');
            }

            $catatan = adminAppendCatatanAdmin(
                $row['catatan_admin'] ?? null,
                [
                    'Masuk status Siap Dijual pada ' . date('d M Y H:i') . '.',
                    $keterangan,
                ]
            );

            $update = $db->prepare("UPDATE data_gadai SET status = 'Siap Dijual', catatan_admin = ?, updated_at = NOW() WHERE id = ?");
            $update->execute([$catatan, $id]);

            $message = 'Barang untuk #' . str_pad((string)$id, 6, '0', STR_PAD_LEFT) . ' dipindahkan ke status Siap Dijual.';
            $message_type = 'success';
        } catch (Throwable $e) {
            $message = 'Gagal memindahkan barang ke Siap Dijual: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

if (!function_exists('adminHandleMarkTerjualAction')) {
    function adminHandleMarkTerjualAction(PDO $db, string &$message, string &$message_type): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action']) || $_POST['action'] !== 'mark_terjual') {
            return;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $hargaJual = (float)($_POST['harga_jual'] ?? 0);
        $metode = trim((string)($_POST['metode'] ?? 'penjualan_internal'));
        $keterangan = trim((string)($_POST['keterangan_admin'] ?? ''));

        try {
            if ($id <= 0) {
                throw new RuntimeException('ID data gadai tidak valid.');
            }
            if ($hargaJual <= 0) {
                throw new RuntimeException('Harga jual wajib diisi.');
            }

            $stmt = $db->prepare("SELECT * FROM data_gadai WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                throw new RuntimeException('Data gadai tidak ditemukan.');
            }

            if (!gadai_can_transition($row['status'] ?? '', 'Terjual')) {
                throw new RuntimeException('Status ini belum bisa ditandai Terjual.');
            }

            $pokok = gadai_get_pokok($row);
            $estimasiProfit = $hargaJual - $pokok;
            $profitLabel = $estimasiProfit >= 0 ? 'estimasi laba' : 'estimasi rugi';
            $profitFormatted = 'Rp ' . number_format(abs($estimasiProfit), 0, ',', '.');

            $catatan = adminAppendCatatanAdmin(
                $row['catatan_admin'] ?? null,
                [
                    'Barang ditandai Terjual pada ' . date('d M Y H:i') . ' dengan harga Rp ' . number_format($hargaJual, 0, ',', '.') . ' (' . $profitLabel . ': ' . $profitFormatted . ').',
                    $keterangan,
                ]
            );

            $db->beginTransaction();
            $update = $db->prepare("UPDATE data_gadai SET status = 'Terjual', catatan_admin = ?, updated_at = NOW() WHERE id = ?");
            $update->execute([$catatan, $id]);

            $tblExists = false;
            try {
                $checkTbl = $db->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'transaksi'");
                $checkTbl->execute();
                $tblExists = (int)$checkTbl->fetchColumn() > 0;
            } catch (Throwable $e) {
                $tblExists = false;
            }

            if ($tblExists) {
                $insert = $db->prepare("INSERT INTO transaksi (imei, serial_number, jenis_barang, merk, tipe, pelanggan_nik, barang_id, jumlah_bayar, keterangan, metode_pembayaran, bukti) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $insert->execute([
                    $row['imei_serial'] ?? null,
                    $row['imei_serial'] ?? null,
                    $row['jenis_barang'] ?? null,
                    $row['merk_barang'] ?? null,
                    $row['spesifikasi_barang'] ?? null,
                    $row['nik'] ?? null,
                    $id,
                    $hargaJual,
                    'penjualan_barang',
                    $metode !== '' ? $metode : 'penjualan_internal',
                    null,
                ]);
            }

            $db->commit();

            $message = 'Barang #' . str_pad((string)$id, 6, '0', STR_PAD_LEFT) . ' ditandai Terjual secara internal. Harga jual: Rp ' . number_format($hargaJual, 0, ',', '.') . ' (' . $profitLabel . ': ' . $profitFormatted . ').';
            $message_type = 'success';
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $message = 'Gagal menandai barang sebagai Terjual: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

if (!function_exists('handleAdminUtilityActions')) {
    function handleAdminUtilityActions(PDO $db, $whatsapp, string &$message, string &$message_type): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
            return;
        }

        $action = (string)$_POST['action'];
        if (!in_array($action, ['manual_add_gadai', 'manual_reminder_overdue', 'manual_notify_gagal_tebus', 'manual_send_nota', 'mark_siap_jual', 'mark_terjual'], true)) {
            return;
        }

        switch ($action) {
            case 'manual_add_gadai':
                adminHandleManualAddGadaiAction($db, $whatsapp, $message, $message_type);
                return;
            case 'manual_reminder_overdue':
                adminHandleReminderOverdueAction($db, $whatsapp, $message, $message_type);
                return;
            case 'manual_notify_gagal_tebus':
                adminHandleNotifyGagalTebusAction($db, $whatsapp, $message, $message_type);
                return;
            case 'manual_send_nota':
                adminHandleSendNotaAction($db, $whatsapp, $message, $message_type);
                return;
            case 'mark_siap_jual':
                adminHandleMarkSiapJualAction($db, $message, $message_type);
                return;
            case 'mark_terjual':
                adminHandleMarkTerjualAction($db, $message, $message_type);
                return;
        }
    }
}
