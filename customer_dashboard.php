    <?php
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL);

require_once 'database.php';
require_once 'gadai_helpers.php';
require_once 'auth_guard.php';
require_once 'whatsapp_helper.php';

gadai_require_customer();

$customerId = (int)($_SESSION['customer_id'] ?? 0);
$customer = $customerId > 0 ? gadai_get_customer_by_id($db, $customerId) : null;
if (!$customer) {
    $customer = [
        'id' => $customerId,
        'nama' => (string)($_SESSION['customer_name'] ?? ''),
        'nik' => (string)($_SESSION['customer_nik'] ?? ''),
        'no_wa' => '',
        'alamat' => '',
    ];
}

$nama = trim((string)($customer['nama'] ?? ''));
$nik = trim((string)($customer['nik'] ?? ''));
$customerDefaultLamaGadai = 1;
$customerDefaultBunga = 30.00;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'customer_submit_pinjaman') {
    try {
        $jenisBarang = trim((string)($_POST['jenis_barang'] ?? ''));
        $merkBarang = trim((string)($_POST['merk_barang'] ?? ''));
        $spesifikasiBarang = trim((string)($_POST['spesifikasi_barang'] ?? ''));
        $imeiSerial = trim((string)($_POST['imei_serial'] ?? ''));
        $kelengkapanBarang = trim((string)($_POST['kelengkapan_barang'] ?? ''));
        $kondisiBarang = trim((string)($_POST['kondisi_barang'] ?? ''));
        $jumlahPinjaman = (float)($_POST['jumlah_pinjaman'] ?? 0);
        $bunga = $customerDefaultBunga;
        $lamaGadai = $customerDefaultLamaGadai;
        $noWa = trim((string)($customer['no_wa'] ?? ''));
        $alamat = trim((string)($customer['alamat'] ?? ''));
        $spesifikasiFinal = trim($spesifikasiBarang . ($imeiSerial !== '' ? ' (IMEI: ' . $imeiSerial . ')' : ''));

        if ($nama === '' || $nik === '' || $noWa === '' || $alamat === '') {
            throw new RuntimeException('Data customer belum lengkap.');
        }

        if (
            $jenisBarang === '' ||
            $merkBarang === '' ||
            $kelengkapanBarang === '' ||
            $kondisiBarang === '' ||
            $jumlahPinjaman <= 0 ||
            $lamaGadai <= 0
        ) {
            throw new RuntimeException('Lengkapi semua field pengajuan.');
        }

        $tanggalGadai = date('Y-m-d');
        $tanggalJatuhTempo = date('Y-m-d', strtotime('+' . $lamaGadai . ' month'));
        $catatanAdmin = $kelengkapanBarang !== '' ? ('Kelengkapan Barang: ' . $kelengkapanBarang) : null;

        $sqlFull = "INSERT INTO data_gadai (
            customer_id, nama, nik, no_wa, alamat, jenis_barang, merk_barang, spesifikasi_barang,
            kondisi_barang, nilai_taksiran, jumlah_pinjaman, bunga, lama_gadai,
            catatan_admin,
            tanggal_gadai, tanggal_jatuh_tempo, status
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?,
            ?, ?, 'Pending'
        )";

        $sqlFallback = "INSERT INTO data_gadai (
            nama, nik, no_wa, alamat, jenis_barang, merk_barang, spesifikasi_barang,
            kondisi_barang, nilai_taksiran, jumlah_pinjaman, bunga, lama_gadai,
            catatan_admin,
            tanggal_gadai, tanggal_jatuh_tempo, status
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?,
            ?, ?, 'Pending'
        )";

        $paramsFull = [
            $customerId,
            $nama,
            $nik,
            $noWa,
            $alamat,
            $jenisBarang,
            $merkBarang,
            $spesifikasiFinal,
            $kondisiBarang,
            0,
            $jumlahPinjaman,
            $bunga,
            $lamaGadai,
            $catatanAdmin,
            $tanggalGadai,
            $tanggalJatuhTempo,
        ];

        $inserted = false;
        try {
            $stmt = $db->prepare($sqlFull);
            $stmt->execute($paramsFull);
            $inserted = true;
        } catch (PDOException $e) {
            if (stripos($e->getMessage(), 'Unknown column') !== false) {
                $stmt = $db->prepare($sqlFallback);
                $stmt->execute(array_slice($paramsFull, 1));
                $inserted = true;
            } else {
                throw $e;
            }
        }

        if (!$inserted) {
            throw new RuntimeException('Pengajuan gagal disimpan. Silakan coba lagi.');
        }

        $newId = (int)$db->lastInsertId();

        try {
            if (isset($whatsapp)) {
                $payload = [
                    'id' => $newId,
                    'nama' => $nama,
                    'jenis_barang' => $jenisBarang,
                    'merk_barang' => $merkBarang,
                    'spesifikasi_barang' => $spesifikasiBarang,
                    'jumlah_pinjaman' => $jumlahPinjaman,
                    'no_wa' => $noWa,
                    'tanggal_jatuh_tempo' => $tanggalJatuhTempo,
                ];
                $whatsapp->notifyAdminNewSubmission($payload);
                if (method_exists($whatsapp, 'notifyUserSubmissionReceived')) {
                    $whatsapp->notifyUserSubmissionReceived($payload);
                }
            }
        } catch (Throwable $waError) {
            error_log('WA submission notification failed: ' . $waError->getMessage());
        }

        $_SESSION['customer_flash_success'] = 'Pengajuan pinjaman berhasil dikirim. Nomor registrasi: #' . str_pad((string)$newId, 6, '0', STR_PAD_LEFT) . '.';
    } catch (Throwable $e) {
        $_SESSION['customer_flash_error'] = $e->getMessage();
    }

    header('Location: customer_dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'customer_request_naik_pinjaman') {
    try {
        $gadaiId = isset($_POST['gadai_id']) ? (int)$_POST['gadai_id'] : 0;
        $requestedAmount = (float)($_POST['requested_amount'] ?? 0);
        $alasan = trim((string)($_POST['alasan_request'] ?? ''));

        if ($gadaiId <= 0) {
            throw new RuntimeException('Data gadai tidak valid.');
        }

        if ($requestedAmount <= 0) {
            throw new RuntimeException('Nominal tambahan harus lebih besar dari nol.');
        }

        if ($alasan === '') {
            throw new RuntimeException('Alasan request wajib diisi.');
        }

        $stmtOwn = $db->prepare("SELECT * FROM data_gadai WHERE id = ? AND (customer_id = ? OR nik = ?) LIMIT 1");
        $stmtOwn->execute([$gadaiId, $customerId, (string)($customer['nik'] ?? '')]);
        $gadai = $stmtOwn->fetch(PDO::FETCH_ASSOC);

        if (!$gadai) {
            throw new RuntimeException('Data gadai tidak ditemukan atau bukan milik Anda.');
        }

        if (!in_array((string)($gadai['status'] ?? ''), ['Disetujui', 'Diperpanjang'], true)) {
            throw new RuntimeException('Request kenaikan hanya bisa diajukan untuk gadai aktif.');
        }

        $currentAmount = !empty($gadai['jumlah_disetujui']) ? (float)$gadai['jumlah_disetujui'] : (float)($gadai['jumlah_pinjaman'] ?? 0);
        $nilaiTaksiran = (float)($gadai['nilai_taksiran'] ?? 0);
        $maxAllowed = $nilaiTaksiran > 0 ? ($nilaiTaksiran * 0.70) : 0;
        $maxAdditional = max(0, $maxAllowed - $currentAmount);

        if ($maxAdditional <= 0) {
            throw new RuntimeException('Pinjaman sudah mencapai batas maksimum yang diizinkan.');
        }

        if ($requestedAmount - $maxAdditional > 0.01) {
            throw new RuntimeException('Nominal tambahan melebihi batas maksimal Rp ' . number_format($maxAdditional, 0, ',', '.') . '.');
        }

        gadai_create_pinjaman_request($db, [
            'gadai_id' => $gadaiId,
            'customer_id' => $customerId,
            'current_amount' => $currentAmount,
            'requested_amount' => $requestedAmount,
            'max_additional' => $maxAdditional,
            'alasan' => $alasan,
        ]);

        $requestId = (int)$db->lastInsertId();

        try {
            if (isset($whatsapp)) {
                $payload = [
                    'request_id' => $requestId,
                    'gadai_id' => $gadaiId,
                    'nama' => $nama,
                    'no_wa' => $noWa,
                    'jenis_barang' => $gadai['jenis_barang'] ?? '',
                    'merk_barang' => $gadai['merk_barang'] ?? '',
                    'spesifikasi_barang' => $gadai['spesifikasi_barang'] ?? '',
                    'barang_detail' => trim((string)(($gadai['merk_barang'] ?? '') . ' ' . ($gadai['spesifikasi_barang'] ?? ''))),
                    'current_amount' => $currentAmount,
                    'requested_amount' => $requestedAmount,
                    'max_additional' => $maxAdditional,
                    'alasan' => $alasan,
                    'status' => 'Pending',
                ];

                if (method_exists($whatsapp, 'notifyAdminPinjamanRequest')) {
                    $whatsapp->notifyAdminPinjamanRequest($payload);
                }
                if (method_exists($whatsapp, 'notifyUserPinjamanRequestReceived')) {
                    $whatsapp->notifyUserPinjamanRequestReceived($payload);
                }
            }
        } catch (Throwable $waError) {
            error_log('WA request naik pinjaman failed: ' . $waError->getMessage());
        }

        $_SESSION['customer_flash_success'] = 'Request kenaikan pinjaman berhasil dikirim. Menunggu verifikasi admin.';
    } catch (Throwable $e) {
        $_SESSION['customer_flash_error'] = $e->getMessage();
    }

    header('Location: customer_dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'customer_cancel_pengajuan') {
    try {
        $gadaiId = isset($_POST['gadai_id']) ? (int)$_POST['gadai_id'] : 0;
        if ($gadaiId <= 0) {
            throw new RuntimeException('ID pengajuan tidak valid.');
        }

        $stmtOwn = $db->prepare("SELECT id, status FROM data_gadai WHERE id = ? AND (customer_id = ? OR nik = ?) LIMIT 1");
        $stmtOwn->execute([$gadaiId, $customerId, (string)($customer['nik'] ?? '')]);
        $owned = $stmtOwn->fetch(PDO::FETCH_ASSOC);

        if (!$owned) {
            throw new RuntimeException('Pengajuan tidak ditemukan atau bukan milik akun Anda.');
        }

        if ((string)($owned['status'] ?? '') !== 'Pending') {
            throw new RuntimeException('Hanya pengajuan dengan status Pending yang dapat dibatalkan.');
        }

        $delete = $db->prepare("DELETE FROM data_gadai WHERE id = ? AND (customer_id = ? OR nik = ?) AND status = 'Pending' LIMIT 1");
        $delete->execute([$gadaiId, $customerId, (string)($customer['nik'] ?? '')]);

        if ($delete->rowCount() <= 0) {
            throw new RuntimeException('Pengajuan gagal dibatalkan. Silakan coba lagi.');
        }

        $_SESSION['customer_flash_success'] = 'Pengajuan pending berhasil dibatalkan.';
    } catch (Throwable $e) {
        $_SESSION['customer_flash_error'] = $e->getMessage();
    }

    header('Location: customer_dashboard.php');
    exit;
}

if (!function_exists('gadai_customer_split_catatan_admin')) {
    function gadai_customer_split_catatan_admin(?string $raw): array {
        $text = trim((string)$raw);
        $kelengkapan = '';
        $catatanAdmin = '';

        if ($text === '') {
            return ['kelengkapan' => $kelengkapan, 'catatan_admin' => $catatanAdmin];
        }

        $lines = preg_split('/\R+/', $text) ?: [];
        foreach ($lines as $line) {
            $line = trim((string)$line);
            if ($line === '') {
                continue;
            }

            if (stripos($line, 'Kelengkapan Barang:') === 0) {
                $kelengkapan = trim((string)substr($line, strlen('Kelengkapan Barang:')));
                continue;
            }

            if (stripos($line, 'Catatan Admin:') === 0) {
                $catatanAdmin = trim((string)substr($line, strlen('Catatan Admin:')));
                continue;
            }

            if ($catatanAdmin === '') {
                $catatanAdmin = $line;
            } else {
                $catatanAdmin .= "\n" . $line;
            }
        }

        return [
            'kelengkapan' => $kelengkapan,
            'catatan_admin' => $catatanAdmin,
        ];
    }
}

$flashSuccess = isset($_SESSION['customer_flash_success']) ? (string)$_SESSION['customer_flash_success'] : '';
$flashError = isset($_SESSION['customer_flash_error']) ? (string)$_SESSION['customer_flash_error'] : '';
unset($_SESSION['customer_flash_success'], $_SESSION['customer_flash_error']);

$stmt = $db->prepare("SELECT dg.id, dg.nama, dg.nik, dg.no_wa, dg.jenis_barang, dg.merk_barang, dg.spesifikasi_barang, dg.status, dg.nilai_taksiran, dg.jumlah_pinjaman, dg.jumlah_disetujui, dg.total_tebus, dg.tanggal_gadai, dg.tanggal_jatuh_tempo, dg.updated_at, dg.created_at, dg.perpanjangan_ke, dg.catatan_admin
    FROM data_gadai dg
    WHERE dg.customer_id = ? OR dg.nik = ?
    ORDER BY dg.created_at DESC");
$stmt->execute([$customerId, $customer['nik']]);
$gadaiList = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pinjamanRequests = gadai_get_customer_pinjaman_requests($db, $customerId);
$pinjamanRequestsByGadai = [];
foreach ($pinjamanRequests as $requestRow) {
    $gadaiKey = (int)($requestRow['gadai_id'] ?? 0);
    if (!isset($pinjamanRequestsByGadai[$gadaiKey])) {
        $pinjamanRequestsByGadai[$gadaiKey] = [];
    }
    $pinjamanRequestsByGadai[$gadaiKey][] = $requestRow;
}

$totalGadai = count($gadaiList);
$gadaiAktif = 0;
$customerRealtimeSnapshot = [];
foreach ($gadaiList as $row) {
    $gadaiId = (int)($row['id'] ?? 0);
    $customerRealtimeSnapshot[(string)$gadaiId] = [
        'id' => $gadaiId,
        'status' => (string)($row['status'] ?? ''),
        'updated_at' => (string)($row['updated_at'] ?? ''),
        'disetujui' => !empty($row['jumlah_disetujui']) ? (float)$row['jumlah_disetujui'] : null,
        'total_tebus' => !empty($row['total_tebus']) ? (float)$row['total_tebus'] : null,
    ];
    if (in_array((string)($row['status'] ?? ''), ['Disetujui', 'Diperpanjang', 'Gagal Tebus', 'Siap Dijual'], true)) {
        $gadaiAktif++;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Customer - Gadai Cepat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Raleway:wght@700;800;900&display=swap" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #eef6ff 0%, #f8fbff 100%);
            font-family: 'Poppins', sans-serif;
        }
        .hero {
            background: linear-gradient(135deg, #0056b3, #0b74e5);
            color: #fff;
            border-radius: 24px;
            box-shadow: 0 18px 50px rgba(0, 86, 179, 0.16);
        }
        .metric {
            background: #fff;
            border-radius: 18px;
            padding: 18px;
            box-shadow: 0 8px 24px rgba(0, 86, 179, 0.08);
            border: 1px solid #dcecff;
            height: 100%;
        }
        .metric-label { color: #6c7a89; font-size: 0.9rem; }
        .metric-value { font-size: 1.8rem; font-weight: 800; color: #0f315f; }
        .panel {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 18px 50px rgba(0, 86, 179, 0.10);
            border: 1px solid rgba(0, 86, 179, 0.08);
        }
        .badge-status {
            padding: 8px 12px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.85rem;
        }
        .status-Pending { background: #fff3cd; color: #856404; }
        .status-Disetujui, .status-Diperpanjang { background: #d1e7dd; color: #0f5132; }
        .status-Ditolak { background: #f8d7da; color: #842029; }
        .status-Lunas { background: #cff4fc; color: #055160; }
        .status-Gagal { background: #e2e3e5; color: #41464b; }
        .table thead th { background: #eef6ff; color: #214a7a; }

        .customer-history-table {
            margin-bottom: 0;
            width: 100%;
        }

        .customer-history-table {
            min-width: 980px;
        }

        .customer-history-table thead th {
            white-space: nowrap;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .customer-history-table td {
            vertical-align: middle;
        }

        .customer-history-table td:first-child {
            width: 56px;
            white-space: nowrap;
        }

        .customer-history-table td:nth-child(2) {
            min-width: 230px;
        }

        .customer-history-table td:nth-child(3) {
            min-width: 150px;
            white-space: nowrap;
        }

        .customer-history-table td:nth-child(4) {
            min-width: 120px;
            white-space: nowrap;
        }

        .customer-history-table td:nth-child(5),
        .customer-history-table td:nth-child(6) {
            white-space: nowrap;
        }

        .table-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
            min-width: 180px;
        }

        .pinjaman-history-list {
            display: grid;
            gap: 12px;
        }

        .pinjaman-history-item {
            border: 1px solid #dcecff;
            border-radius: 14px;
            background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
            padding: 14px;
        }

        .pinjaman-history-item.approved {
            border-color: #bbf7d0;
            background: linear-gradient(180deg, #f0fdf4 0%, #fbfffd 100%);
        }

        .pinjaman-history-item.rejected {
            border-color: #fecdd3;
            background: linear-gradient(180deg, #fff1f2 0%, #fffdfd 100%);
        }

        .pinjaman-history-item.pending {
            border-color: #ffe69c;
            background: linear-gradient(180deg, #fffaf0 0%, #fffefd 100%);
        }

        .history-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 8px;
            margin-top: 10px;
        }

        .history-meta .detail-item {
            padding: 10px 12px;
            box-shadow: none;
        }

        .detail-summary {
            background: linear-gradient(135deg, #f8fbff 0%, #eef6ff 100%);
            border: 1px solid #dcecff;
            border-radius: 16px;
            padding: 14px;
            margin-bottom: 14px;
        }

        .detail-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 10px;
        }

        .detail-item {
            background: #fff;
            border: 1px solid #dcecff;
            border-radius: 12px;
            padding: 12px 14px;
            box-shadow: 0 6px 14px rgba(15, 49, 95, 0.04);
        }

        .detail-summary .detail-item {
            background: rgba(255,255,255,0.88);
        }

        .detail-item.detail-span-full {
            display: flex;
            flex-direction: column;
            gap: 10px;
            background: linear-gradient(135deg, #ffffff, #f7fbff);
        }

        .detail-item.detail-span-full .detail-value {
            text-align: left;
            max-width: 100%;
        }

        .detail-span-full {
            grid-column: 1 / -1;
        }

        .detail-modal-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 10px;
        }

        .detail-modal-grid .detail-item:first-child {
            grid-column: 1 / -1;
        }

        .detail-admin-note {
            grid-column: 1 / -1;
            min-height: 108px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .detail-admin-note {
            background: linear-gradient(135deg, #fff5f5, #fffdfd);
            border: 1px solid #f5c2c7;
            box-shadow: 0 10px 24px rgba(176, 42, 55, 0.08);
        }

        .detail-admin-note .detail-label,
        .detail-admin-note .detail-value {
            color: #b02a37;
            font-weight: 600;
        }

        .detail-admin-note .detail-value {
            white-space: pre-line;
            font-size: 0.96rem;
            line-height: 1.55;
            margin-top: 4px;
        }

        .detail-label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #607089;
        }

        .detail-value {
            color: #16324f;
            font-weight: 500;
            line-height: 1.45;
            word-break: break-word;
        }

        .modal-content {
            border: none;
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 24px 60px rgba(0, 86, 179, 0.18);
        }

        .modal-header {
            background: linear-gradient(135deg, #0056b3, #0d6efd);
            color: #fff;
            border-bottom: none;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.9;
        }

        .modal-footer {
            background: #f8fbff;
            border-top: 1px solid #dcecff;
        }

        .detail-surface {
            border-radius: 18px;
            overflow: hidden;
        }

        .detail-modal-compact {
            max-width: 640px;
        }

        .detail-body-copy {
            color: #4f6478;
            font-size: 0.92rem;
        }

        .detail-section-title {
            font-size: 0.85rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #5a6d82;
            margin-bottom: 12px;
        }

        .detail-status-chip {
            display: inline-flex;
            align-items: center;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(0, 86, 179, 0.10);
            color: #0056b3;
            font-weight: 700;
        }

        .detail-note-badge {
            display: inline-block;
            margin-bottom: 10px;
            padding: 6px 10px;
            border-radius: 999px;
            background: #ffe3e7;
            color: #b02a37;
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .request-panel {
            background: #fff;
            border: 1px solid #dcecff;
            border-radius: 18px;
            box-shadow: 0 12px 28px rgba(15, 49, 95, 0.06);
        }

        .request-status {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 800;
        }

        .request-status.pending { background: #fff3cd; color: #8a6d00; }
        .request-status.approved { background: #d1e7dd; color: #0f5132; }
        .request-status.rejected { background: #f8d7da; color: #842029; }

        .request-note {
            margin-top: 12px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 14px;
            padding: 12px 14px;
            color: #9a3412;
            white-space: pre-line;
        }

        .request-note.is-approved {
            background: #f0fdf4;
            border-color: #bbf7d0;
            color: #166534;
        }

        .request-note.is-rejected {
            background: #fff1f2;
            border-color: #fecdd3;
            color: #9f1239;
        }

        .request-modal-compact .modal-content {
            border-radius: 18px;
        }

        .inbox-notification {
            border: 1px solid #bfdcff;
            border-radius: 16px;
            background: linear-gradient(180deg, #ffffff 0%, #f5f9ff 100%);
            box-shadow: 0 10px 28px rgba(15, 49, 95, 0.09);
            margin-bottom: 1rem;
            overflow: hidden;
        }

        .inbox-notification-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 14px;
            background: linear-gradient(135deg, #e9f3ff, #f6fbff);
            border-bottom: 1px solid #dcecff;
        }

        .inbox-notification-title {
            margin: 0;
            font-weight: 800;
            letter-spacing: 0.02em;
            color: #0f315f;
        }

        .inbox-notification-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 28px;
            height: 28px;
            padding: 0 8px;
            border-radius: 999px;
            background: #0d6efd;
            color: #fff;
            font-size: 0.8rem;
            font-weight: 800;
        }

        .inbox-notification-list {
            display: grid;
            gap: 8px;
            padding: 12px 14px 14px;
        }

        .inbox-item {
            border: 1px solid #dcecff;
            border-radius: 12px;
            background: #fff;
            padding: 10px 12px;
        }

        .inbox-item-subject {
            font-weight: 700;
            color: #0f315f;
            margin-bottom: 4px;
        }

        .inbox-item-preview {
            color: #495f78;
            font-size: 0.9rem;
            line-height: 1.45;
            margin-bottom: 6px;
        }

        .inbox-item-meta {
            font-size: 0.78rem;
            color: #64748b;
            font-weight: 600;
        }
    </style>
</head>
<body>
<div class="container py-4 py-md-5">
    <div class="hero p-4 p-md-5 mb-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="small text-uppercase opacity-75">Portal Customer</div>
                <h1 class="display-6 fw-bold mb-2">Selamat datang, <?php echo htmlspecialchars($customer['nama']); ?></h1>
                <p class="mb-0 opacity-75">Pantau pengajuan gadai, status, dan histori transaksi Anda dalam satu halaman.</p>
            </div>
            <div class="text-end">
                <a href="customer_logout.php" class="btn btn-light fw-semibold">Logout</a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="metric">
                <div class="metric-label">Total Pengajuan</div>
                <div class="metric-value" data-field="metric_total"><?php echo (int)$totalGadai; ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="metric">
                <div class="metric-label">Pengajuan Aktif</div>
                <div class="metric-value" data-field="metric_active"><?php echo (int)$gadaiAktif; ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="metric">
                <div class="metric-label">NIK Login</div>
                <div class="metric-value" style="font-size:1.1rem;"><?php echo htmlspecialchars($customer['nik']); ?></div>
            </div>
        </div>
    </div>

    <div class="panel p-4 mb-4">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="fw-semibold text-muted">Nama</div>
                <div class="fs-5 fw-bold"><?php echo htmlspecialchars($customer['nama']); ?></div>
            </div>
            <div class="col-md-6">
                <div class="fw-semibold text-muted">No. WhatsApp</div>
                <div class="fs-5 fw-bold"><?php echo htmlspecialchars($customer['no_wa'] ?? '-'); ?></div>
            </div>
            <div class="col-md-12">
                <div class="fw-semibold text-muted">Alamat</div>
                <div class="fs-6"><?php echo nl2br(htmlspecialchars($customer['alamat'] ?? '-')); ?></div>
            </div>
        </div>
    </div>

    <div class="panel p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h2 class="h4 mb-0">Form Pengajuan Pinjaman</h2>
            <span class="badge bg-primary">Status awal: Pending</span>
        </div>

        <div
            id="customerFlashData"
            data-success="<?php echo htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8'); ?>"
            data-error="<?php echo htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8'); ?>"
        ></div>

        <div id="realtimeStatusAlert"></div>

        <form method="POST" class="row g-3" id="customerSubmitForm" novalidate>
            <input type="hidden" name="action" value="customer_submit_pinjaman">

            <div class="col-12">
                <div class="alert alert-light border mb-1">
                    Data NIK, nama, No. WhatsApp, dan alamat otomatis diambil dari akun customer yang sedang login.
                </div>
            </div>

            <div class="col-md-4">
                <label class="form-label">Jenis Barang <span class="text-danger">*</span></label>
                <input type="text" name="jenis_barang" class="form-control" placeholder="Contoh: HP / Laptop" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Merk Barang <span class="text-danger">*</span></label>
                <input type="text" name="merk_barang" class="form-control" placeholder="Contoh: Apple / Samsung" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Spesifikasi / Tipe <span class="text-danger">*</span></label>
                <input type="text" name="spesifikasi_barang" class="form-control" placeholder="Contoh: iPhone 11 128GB" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Serial Number / IMEI <span class="text-danger">*</span></label>
                <input type="text" name="imei_serial" class="form-control" placeholder="Contoh: 3569xxxxxxxxxxxx" required>
            </div>
            <div class="col-12">
                <label class="form-label">Kelengkapan Barang <span class="text-danger">*</span></label>
                <textarea name="kelengkapan_barang" class="form-control" rows="2" placeholder="Contoh: Dus, charger, kabel data, headset, nota" required></textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label">Kondisi Barang <span class="text-danger">*</span></label>
                <select name="kondisi_barang" class="form-select" required>
                    <option value="Baru">Baru</option>
                    <option value="Bekas - Baik" selected>Bekas - Baik</option>
                    <option value="Bekas - Cukup">Bekas - Cukup</option>
                    <option value="Rusak Ringan">Rusak Ringan</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Jumlah Pinjaman <span class="text-danger">*</span></label>
                <input type="hidden" name="jumlah_pinjaman" value="">
                <input
                    type="text"
                    class="form-control"
                    data-rupiah-input="jumlah_pinjaman"
                    inputmode="numeric"
                    autocomplete="off"
                    placeholder="Rp 0"
                    required
                >
            </div>
            <div class="col-md-4">
                <label class="form-label">Bunga / Jasa</label>
                <input type="text" class="form-control" value="Dihitung otomatis oleh sistem" readonly>
                <small class="text-muted">Bunga 30.00% per bulan diterapkan otomatis oleh sistem.</small>
            </div>

            <div class="col-12 d-grid">
                <button type="submit" class="btn btn-primary btn-lg">Kirim Pengajuan Pinjaman</button>
            </div>
        </form>
    </div>

    <div class="panel p-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h2 class="h4 mb-0">Riwayat Pengajuan Gadai</h2>
            <a href="customer_register.php" class="btn btn-outline-primary btn-sm">Ubah / Daftar Ulang</a>
        </div>

        <?php if (empty($gadaiList)): ?>
            <div class="alert alert-info mb-0">Belum ada pengajuan gadai yang terhubung ke akun ini.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle customer-history-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Barang</th>
                            <th>Pinjaman</th>
                            <th>Status</th>
                            <th>Jatuh Tempo</th>
                            <th>Update</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($gadaiList as $index => $row): ?>
                            <?php
                                $barang = trim((string)(($row['jenis_barang'] ?? '') . ': ' . ($row['merk_barang'] ?? '') . ' ' . ($row['spesifikasi_barang'] ?? '')));
                                $barang = trim((string)preg_replace('/\s+/', ' ', $barang));
                                $status = (string)($row['status'] ?? '');
                                $notes = gadai_customer_split_catatan_admin($row['catatan_admin'] ?? null);
                                $kelengkapan = $notes['kelengkapan'] !== '' ? $notes['kelengkapan'] : '-';
                                $catatanAdmin = $notes['catatan_admin'] !== '' ? $notes['catatan_admin'] : '-';
                            ?>
                            <tr data-gadai-id="<?php echo (int)$row['id']; ?>">
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($barang !== '' ? $barang : '-'); ?></div>
                                    <small class="text-muted">#<?php echo str_pad((string)$row['id'], 6, '0', STR_PAD_LEFT); ?></small>
                                </td>
                                <td>
                                    <div>Diajukan: Rp <?php echo number_format((float)($row['jumlah_pinjaman'] ?? 0), 0, ',', '.'); ?></div>
                                    <div>Disetujui: Rp <span data-field="disetujui_value"><?php echo !empty($row['jumlah_disetujui']) ? number_format((float)$row['jumlah_disetujui'], 0, ',', '.') : '-'; ?></span></div>
                                </td>
                                <?php
                                    $badgeClass = 'badge-secondary';
                                    if ($status === 'Pending') {
                                        $badgeClass = 'status-Pending';
                                    } elseif (in_array($status, ['Disetujui', 'Diperpanjang'], true)) {
                                        $badgeClass = 'status-Disetujui';
                                    } elseif ($status === 'Ditolak') {
                                        $badgeClass = 'status-Ditolak';
                                    } elseif ($status === 'Lunas') {
                                        $badgeClass = 'status-Lunas';
                                    } elseif (in_array($status, ['Gagal Tebus', 'Siap Dijual'], true)) {
                                        $badgeClass = 'status-Gagal';
                                    }
                                    $currentAmount = !empty($row['jumlah_disetujui']) ? (float)$row['jumlah_disetujui'] : (float)($row['jumlah_pinjaman'] ?? 0);
                                    $nilaiTaksiran = (float)($row['nilai_taksiran'] ?? 0);
                                    $maxAllowed = $nilaiTaksiran > 0 ? ($nilaiTaksiran * 0.70) : 0;
                                    $maxAdditional = max(0, $maxAllowed - $currentAmount);
                                    $isActiveGadai = in_array($status, ['Disetujui', 'Diperpanjang'], true) && $maxAdditional > 0;
                                ?>
                                <td><span class="badge-status <?php echo $badgeClass; ?>" data-field="status_badge"><?php echo htmlspecialchars($status !== '' ? $status : '-'); ?></span></td>
                                <td><?php echo !empty($row['tanggal_jatuh_tempo']) ? date('d M Y', strtotime($row['tanggal_jatuh_tempo'])) : '-'; ?></td>
                                <td data-field="updated_at_value"><?php echo !empty($row['updated_at']) ? date('d M Y H:i', strtotime($row['updated_at'])) : '-'; ?></td>
                                <td>
                                    <div class="table-actions">
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                            onclick='openCustomerDetailModal(<?php echo json_encode([
                                                "id" => (int)$row["id"],
                                                "nama" => (string)($row["nama"] ?? "-"),
                                                "nik" => (string)($row["nik"] ?? "-"),
                                                "no_wa" => (string)($row["no_wa"] ?? "-"),
                                                "status" => $status,
                                                "barang" => ($barang !== "" ? $barang : "-"),
                                                "kelengkapan" => $kelengkapan,
                                                "catatan_admin" => $catatanAdmin,
                                                "pengajuan" => (float)($row["jumlah_pinjaman"] ?? 0),
                                                "disetujui" => !empty($row["jumlah_disetujui"]) ? (float)$row["jumlah_disetujui"] : null,
                                                "total_tebus" => !empty($row["total_tebus"]) ? (float)$row["total_tebus"] : null,
                                                "tgl_gadai" => !empty($row["tanggal_gadai"]) ? date("d M Y", strtotime($row["tanggal_gadai"])) : "-",
                                                "jatuh_tempo" => !empty($row["tanggal_jatuh_tempo"]) ? date("d M Y", strtotime($row["tanggal_jatuh_tempo"])) : "-",
                                                "updated_at" => !empty($row["updated_at"]) ? date("d M Y H:i", strtotime($row["updated_at"])) : "-",
                                                "badge_class" => $badgeClass,
                                            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>)'>
                                            Lihat Detail
                                        </button>
                                        <?php if (!empty($pinjamanRequestsByGadai[(int)$row['id']])): ?>
                                            <button type="button" class="btn btn-sm btn-outline-warning"
                                                onclick='openPinjamanHistoryModal(<?php echo json_encode([
                                                    "gadai_id" => (int)$row["id"],
                                                    "barang" => ($barang !== "" ? $barang : "-"),
                                                    "status_gadai" => $status,
                                                    "requests" => $pinjamanRequestsByGadai[(int)$row["id"]],
                                                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>)'>
                                                Histori Naik Pinjaman
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($isActiveGadai): ?>
                                            <button type="button" class="btn btn-sm btn-outline-success"
                                                onclick='openNaikPinjamanModal(<?php echo json_encode([
                                                    "gadai_id" => (int)$row["id"],
                                                    "current_amount" => $currentAmount,
                                                    "max_additional" => $maxAdditional,
                                                    "max_total" => $maxAllowed,
                                                    "barang" => ($barang !== "" ? $barang : "-"),
                                                    "status" => $status,
                                                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>)'>
                                                Naik Pinjaman
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($status === 'Pending'): ?>
                                            <form method="POST" onsubmit="return confirmCancelPengajuan(event, this);">
                                                <input type="hidden" name="action" value="customer_cancel_pengajuan">
                                                <input type="hidden" name="gadai_id" value="<?php echo (int)$row['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Batalkan</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    var customerRealtimeSnapshot = <?php echo json_encode($customerRealtimeSnapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

    function confirmCancelPengajuan(event, formEl) {
        if (event) {
            event.preventDefault();
        }

        if (typeof Swal === 'undefined') {
            if (confirm('Batalkan pengajuan ini?')) {
                formEl.submit();
            }
            return false;
        }

        Swal.fire({
            icon: 'warning',
            title: 'Batalkan Pengajuan?',
            text: 'Pengajuan yang dibatalkan tidak bisa dikembalikan.',
            showCancelButton: true,
            confirmButtonText: 'Ya, batalkan',
            cancelButtonText: 'Tidak',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d'
        }).then(function (result) {
            if (result.isConfirmed) {
                submitWithLoading(
                    formEl,
                    'Membatalkan Pengajuan',
                    'Mohon tunggu, sistem sedang memproses pembatalan.'
                );
            }
        });

        return false;
    }

    function showCustomerFlashNotification() {
        if (typeof Swal === 'undefined') {
            return;
        }

        var flashEl = document.getElementById('customerFlashData');
        if (!flashEl) {
            return;
        }

        var successMessage = String(flashEl.getAttribute('data-success') || '').trim();
        var errorMessage = String(flashEl.getAttribute('data-error') || '').trim();

        if (successMessage) {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: successMessage,
                confirmButtonText: 'OK',
                confirmButtonColor: '#0d6efd'
            });
            return;
        }

        if (errorMessage) {
            Swal.fire({
                icon: 'error',
                title: 'Terjadi Kesalahan',
                text: errorMessage,
                confirmButtonText: 'Mengerti',
                confirmButtonColor: '#dc3545'
            });
        }
    }

    function showSubmittingDialog(title, text) {
        if (typeof Swal === 'undefined') {
            return;
        }

        Swal.fire({
            title: title,
            text: text,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: function () {
                Swal.showLoading();
            }
        });
    }

    function submitWithLoading(formEl, title, text) {
        showSubmittingDialog(title, text);

        window.setTimeout(function () {
            formEl.submit();
        }, 120);
    }

    function bindSubmitLoading(selector, options) {
        var targetEl = document.querySelector(selector);
        if (!targetEl) {
            return;
        }

        var formEl = targetEl.tagName === 'FORM' ? targetEl : targetEl.closest('form');
        if (!formEl) {
            return;
        }

        formEl.addEventListener('submit', function (event) {
            if (event.defaultPrevented) {
                return;
            }

            showSubmittingDialog(options.title, options.text);

            var submitButton = formEl.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.setAttribute('aria-busy', 'true');
            }
        });
    }

    function openCustomerDetailModal(data) {
        var modal = document.getElementById('customerDetailModal');
        if (!modal) return;

        var setText = function (selector, value) {
            var el = modal.querySelector(selector);
            if (el) {
                el.textContent = value;
            }
        };

        setText('[data-field="title"]', 'Detail Pengajuan #' + String(data.id).padStart(6, '0'));
        setText('[data-field="nama"]', data.nama || '-');
        setText('[data-field="nik"]', data.nik || '-');
        setText('[data-field="no_wa"]', data.no_wa || '-');
        setText('[data-field="status"]', data.status || '-');
        setText('[data-field="barang"]', data.barang || '-');
        setText('[data-field="kelengkapan"]', data.kelengkapan || '-');
        setText('[data-field="catatan_admin"]', data.catatan_admin || '-');
        setText('[data-field="pengajuan"]', 'Rp ' + Number(data.pengajuan || 0).toLocaleString('id-ID'));
        setText('[data-field="disetujui"]', data.disetujui !== null && data.disetujui !== undefined ? ('Rp ' + Number(data.disetujui).toLocaleString('id-ID')) : '-');
        setText('[data-field="total_tebus"]', data.total_tebus !== null && data.total_tebus !== undefined ? ('Rp ' + Number(data.total_tebus).toLocaleString('id-ID')) : '-');
        setText('[data-field="tgl_gadai"]', data.tgl_gadai || '-');
        setText('[data-field="jatuh_tempo"]', data.jatuh_tempo || '-');
        setText('[data-field="updated_at"]', data.updated_at || '-');

        var badge = modal.querySelector('[data-field="status_badge"]');
        if (badge) {
            badge.className = 'badge-status ' + (data.badge_class || 'badge-secondary');
            badge.textContent = data.status || '-';
        }

        bootstrap.Modal.getOrCreateInstance(modal).show();
    }

    function openNaikPinjamanModal(data) {
        var modal = document.getElementById('naikPinjamanModal');
        if (!modal) return;

        var setText = function (selector, value) {
            var el = modal.querySelector(selector);
            if (el) {
                el.textContent = value;
            }
        };

        modal.querySelector('input[name="gadai_id"]').value = data.gadai_id || '';
        modal.querySelector('input[name="requested_amount"]').value = '';
        modal.querySelector('textarea[name="alasan_request"]').value = '';

        setText('[data-field="naik_barang"]', data.barang || '-');
        setText('[data-field="naik_status"]', data.status || '-');
        setText('[data-field="naik_current"]', 'Rp ' + Number(data.current_amount || 0).toLocaleString('id-ID'));
        setText('[data-field="naik_max_additional"]', 'Rp ' + Number(data.max_additional || 0).toLocaleString('id-ID'));
        setText('[data-field="naik_max_total"]', 'Rp ' + Number(data.max_total || 0).toLocaleString('id-ID'));

        bootstrap.Modal.getOrCreateInstance(modal).show();
    }

    function openRequestHistoryModal(data) {
        var modal = document.getElementById('requestHistoryModal');
        if (!modal) return;

        var setText = function (selector, value) {
            var el = modal.querySelector(selector);
            if (el) {
                el.textContent = value;
            }
        };

        setText('[data-field="request_title"]', 'Request #' + String(data.id || '').padStart(6, '0'));
        setText('[data-field="request_barang"]', data.barang || '-');
        setText('[data-field="request_status"]', data.status || '-');
        setText('[data-field="request_current"]', 'Rp ' + Number(data.current_amount || 0).toLocaleString('id-ID'));
        setText('[data-field="request_additional"]', 'Rp ' + Number(data.requested_amount || 0).toLocaleString('id-ID'));
        setText('[data-field="request_new_total"]', 'Rp ' + Number(data.new_total || 0).toLocaleString('id-ID'));
        setText('[data-field="request_alasan"]', data.alasan || '-');
        setText('[data-field="request_admin_note"]', data.admin_note || '-');
        setText('[data-field="request_created_at"]', data.created_at || '-');
        setText('[data-field="request_reviewed_at"]', data.reviewed_at || '-');
        setText('[data-field="request_updated_at"]', data.updated_at || '-');

        var badge = modal.querySelector('[data-field="request_status"]');
        if (badge) {
            badge.className = 'detail-status-chip request-status-' + String(data.status || 'pending').toLowerCase();
        }

        bootstrap.Modal.getOrCreateInstance(modal).show();
    }

    function openPinjamanHistoryModal(data) {
        var modal = document.getElementById('pinjamanHistoryModal');
        if (!modal) return;

        var escapeHtml = function (value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        };

        var setText = function (selector, value) {
            var el = modal.querySelector(selector);
            if (el) {
                el.textContent = value;
            }
        };

        var historyList = modal.querySelector('[data-field="pinjaman_history_list"]');
        if (historyList) {
            historyList.innerHTML = '';
            var requests = Array.isArray(data.requests) ? data.requests : [];

            if (!requests.length) {
                historyList.innerHTML = '<div class="alert alert-info mb-0">Belum ada histori request naik pinjaman untuk gadai ini.</div>';
            } else {
                requests.forEach(function (requestRow) {
                    var status = String(requestRow.status || 'Pending');
                    var total = Number(requestRow.current_amount || 0) + Number(requestRow.requested_amount || 0);
                    var reviewedAt = requestRow.reviewed_at ? new Date(requestRow.reviewed_at.replace(' ', 'T')).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-';
                    var updatedAt = requestRow.updated_at ? new Date(requestRow.updated_at.replace(' ', 'T')).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-';
                    var itemClass = 'pinjaman-history-item ' + status.toLowerCase();

                    var html = ''
                        + '<div class="' + itemClass + '">'
                        + '<div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">'
                        + '<div>'
                        + '<div class="fw-bold">Request #' + String(requestRow.id || '').padStart(6, '0') + '</div>'
                        + '<div class="text-muted small">' + escapeHtml(requestRow.created_at || '-') + '</div>'
                        + '</div>'
                        + '<span class="detail-status-chip request-status-' + status.toLowerCase() + '">' + escapeHtml(status) + '</span>'
                        + '</div>'
                        + '<div class="history-meta">'
                        + '<div class="detail-item"><span class="detail-label">Tambahan</span><div class="detail-value">Rp ' + Number(requestRow.requested_amount || 0).toLocaleString('id-ID') + '</div></div>'
                        + '<div class="detail-item"><span class="detail-label">Pinjaman Baru</span><div class="detail-value">Rp ' + total.toLocaleString('id-ID') + '</div></div>'
                        + '<div class="detail-item"><span class="detail-label">Diajukan</span><div class="detail-value">' + escapeHtml(requestRow.created_at || '-') + '</div></div>'
                        + '<div class="detail-item"><span class="detail-label">Diproses</span><div class="detail-value">' + escapeHtml(reviewedAt) + '</div></div>'
                        + '<div class="detail-item"><span class="detail-label">Update</span><div class="detail-value">' + escapeHtml(updatedAt) + '</div></div>'
                        + '</div>'
                        + '<div class="detail-item mt-2"><span class="detail-label">Alasan Request</span><div class="detail-value">' + escapeHtml(requestRow.alasan || '-').replace(/\n/g, '<br>') + '</div></div>'
                        + '<div class="detail-item mt-2"><span class="detail-label">Catatan Admin</span><div class="detail-value">' + escapeHtml(requestRow.admin_note || '-').replace(/\n/g, '<br>') + '</div></div>'
                        + '</div>';

                    var wrapper = document.createElement('div');
                    wrapper.innerHTML = html;
                    historyList.appendChild(wrapper.firstElementChild);
                });
            }
        }

        setText('[data-field="pinjaman_history_title"]', 'Histori Naik Pinjaman #' + String(data.gadai_id || '').padStart(6, '0'));
        setText('[data-field="pinjaman_history_barang"]', data.barang || '-');
        setText('[data-field="pinjaman_history_status_gadai"]', data.status_gadai || '-');

        bootstrap.Modal.getOrCreateInstance(modal).show();
    }

    (function () {
        var stateKey = 'gadai_customer_dashboard_live';
        var snapshotKey = stateKey + ':snapshot';
        var liveSnapshot = customerRealtimeSnapshot || {};

        function setupRupiahInputFormatting() {
            var actionInput = document.querySelector('form input[name="action"][value="customer_submit_pinjaman"]');
            if (!actionInput) {
                return;
            }

            var form = actionInput.closest('form');
            if (!form) {
                return;
            }

            var displayInput = form.querySelector('[data-rupiah-input="jumlah_pinjaman"]');
            var hiddenInput = form.querySelector('input[type="hidden"][name="jumlah_pinjaman"]');
            if (!displayInput || !hiddenInput) {
                return;
            }

            function getDigits(value) {
                return String(value || '').replace(/[^\d]/g, '').replace(/^0+(?=\d)/, '');
            }

            function toRupiah(digits) {
                if (!digits) {
                    return '';
                }
                return 'Rp ' + digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }

            function syncValue() {
                var digits = getDigits(displayInput.value);
                hiddenInput.value = digits;
                displayInput.value = toRupiah(digits);
            }

            displayInput.addEventListener('input', function () {
                syncValue();
            });

            displayInput.addEventListener('blur', function () {
                syncValue();
            });

            form.addEventListener('submit', function (event) {
                syncValue();

                var requiredFields = [
                    { name: 'jenis_barang', label: 'Jenis Barang' },
                    { name: 'merk_barang', label: 'Merk Barang' },
                    { name: 'spesifikasi_barang', label: 'Spesifikasi / Tipe' },
                    { name: 'imei_serial', label: 'Serial Number / IMEI' },
                    { name: 'kelengkapan_barang', label: 'Kelengkapan Barang' },
                    { name: 'kondisi_barang', label: 'Kondisi Barang' }
                ];

                var firstInvalidField = null;
                var missingFields = requiredFields.filter(function (field) {
                    var inputEl = form.querySelector('[name="' + field.name + '"]');
                    var isMissing = !inputEl || String(inputEl.value || '').trim() === '';
                    if (isMissing && !firstInvalidField) {
                        firstInvalidField = inputEl;
                    }
                    return isMissing;
                }).map(function (field) {
                    return field.label;
                });

                if (missingFields.length > 0) {
                    event.preventDefault();
                    if (typeof Swal !== 'undefined') {
                        var missingListHtml = '<ul style="text-align:left;margin:8px 0 0 0;padding-left:18px;">'
                            + missingFields.map(function (fieldLabel) {
                                return '<li>' + escapeHtml(fieldLabel) + '</li>';
                            }).join('')
                            + '</ul>';

                        Swal.fire({
                            icon: 'warning',
                            title: 'Data Belum Lengkap',
                            html: 'Mohon lengkapi field berikut:' + missingListHtml,
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#f59e0b'
                        });
                    }
                    if (firstInvalidField && typeof firstInvalidField.focus === 'function') {
                        firstInvalidField.focus();
                    }
                    return;
                }

                var value = parseInt(hiddenInput.value || '0', 10);
                if (!value || value <= 0) {
                    event.preventDefault();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Jumlah Pinjaman Tidak Valid',
                            text: 'Jumlah pinjaman wajib lebih besar dari 0.',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#f59e0b'
                        });
                    }
                    displayInput.focus();
                    return;
                }
            });

            syncValue();
        }

        function escapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function formatCurrency(value) {
            var number = Number(value || 0);
            return 'Rp ' + number.toLocaleString('id-ID');
        }

        function formatDateTime(value) {
            if (!value) {
                return '-';
            }
            var safeValue = String(value).replace(' ', 'T');
            var date = new Date(safeValue);
            if (isNaN(date.getTime())) {
                return String(value);
            }
            return date.toLocaleString('id-ID', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function showRealtimeSweetAlert(inbox) {
            if (typeof Swal === 'undefined' || !inbox || !Array.isArray(inbox.items)) {
                return;
            }

            var previewItems = inbox.items.slice(0, 3).map(function (item) {
                return '<li style="margin-bottom:4px;">'
                    + '<strong>' + escapeHtml(item.subject) + '</strong><br>'
                    + '<span>' + escapeHtml(item.preview) + '</span>'
                    + '</li>';
            }).join('');

            var extraCount = inbox.items.length > 3 ? (inbox.items.length - 3) : 0;
            var extraText = extraCount > 0 ? '<div style="margin-top:4px;">+' + String(extraCount) + ' update lainnya</div>' : '';

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'info',
                title: 'Update verifikasi baru',
                html: '<ul style="padding-left:18px;margin:6px 0 0 0;text-align:left;">' + previewItems + '</ul>' + extraText,
                showConfirmButton: false,
                timer: 9000,
                timerProgressBar: true,
                width: 440
            });
        }

        function showRealtimeAlert(inbox) {
            var container = document.getElementById('realtimeStatusAlert');
            if (!container) return;

            var itemsHtml = inbox.items.map(function (item) {
                return ''
                    + '<div class="inbox-item">'
                    + '<div class="inbox-item-subject">' + escapeHtml(item.subject) + '</div>'
                    + '<div class="inbox-item-preview">' + escapeHtml(item.preview) + '</div>'
                    + '<div class="inbox-item-meta">Update: ' + escapeHtml(item.timeLabel) + '</div>'
                    + '</div>';
            }).join('');

            container.innerHTML = ''
                + '<div class="inbox-notification alert-dismissible fade show" role="alert">'
                + '<div class="inbox-notification-header">'
                + '<div>'
                + '<div class="small text-uppercase text-muted fw-semibold">Notifikasi Real-time</div>'
                + '<h6 class="inbox-notification-title">Kotak Masuk Verifikasi Admin</h6>'
                + '</div>'
                + '<div class="d-flex align-items-center gap-2">'
                + '<span class="inbox-notification-count">' + String(inbox.count) + '</span>'
                + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
                + '</div>'
                + '</div>'
                + '<div class="inbox-notification-list">'
                + itemsHtml
                + '</div>'
                + '</div>';
        }

        function buildRealtimeInbox(previousSnapshot, currentSnapshot) {
            var items = [];

            Object.keys(currentSnapshot || {}).forEach(function (gadaiId) {
                var currentRow = currentSnapshot[gadaiId] || {};
                var previousRow = previousSnapshot ? previousSnapshot[gadaiId] : null;

                if (!previousRow) {
                    return;
                }

                var previousStatus = String(previousRow.status || '');
                var currentStatus = String(currentRow.status || '');
                var previousUpdatedAt = String(previousRow.updated_at || '');
                var currentUpdatedAt = String(currentRow.updated_at || '');
                var previousDisetujui = previousRow.disetujui === null || previousRow.disetujui === undefined ? null : Number(previousRow.disetujui);
                var currentDisetujui = currentRow.disetujui === null || currentRow.disetujui === undefined ? null : Number(currentRow.disetujui);
                var previousTotalTebus = previousRow.total_tebus === null || previousRow.total_tebus === undefined ? null : Number(previousRow.total_tebus);
                var currentTotalTebus = currentRow.total_tebus === null || currentRow.total_tebus === undefined ? null : Number(currentRow.total_tebus);

                var statusChanged = previousStatus !== currentStatus;
                var amountChanged = previousDisetujui !== currentDisetujui || previousTotalTebus !== currentTotalTebus;
                var updatedChanged = previousUpdatedAt !== currentUpdatedAt;

                if (!statusChanged && !amountChanged && !updatedChanged) {
                    return;
                }

                var refNumber = '#' + String(currentRow.id || gadaiId).padStart(6, '0');
                var subject = 'Update Verifikasi ' + refNumber;
                var previewParts = [];

                if (statusChanged) {
                    previewParts.push('Status: ' + (previousStatus || '-') + ' -> ' + (currentStatus || '-'));
                } else {
                    previewParts.push('Ada pembaruan verifikasi terbaru dari admin');
                }

                if (currentDisetujui !== null) {
                    previewParts.push('Pinjaman Disetujui: ' + formatCurrency(currentDisetujui));
                }

                if (currentTotalTebus !== null) {
                    previewParts.push('Total Tebus: ' + formatCurrency(currentTotalTebus));
                }

                items.push({
                    subject: subject,
                    preview: previewParts.join(' | '),
                    timeLabel: formatDateTime(currentUpdatedAt),
                });
            });

            if (!items.length) {
                return null;
            }

            return {
                count: items.length,
                items: items,
            };
        }

        function syncRealtimeAlert(currentSnapshot) {
            try {
                var previousSnapshot = null;
                var rawPrevious = sessionStorage.getItem(snapshotKey);
                if (rawPrevious) {
                    previousSnapshot = JSON.parse(rawPrevious);
                }

                var inbox = buildRealtimeInbox(previousSnapshot, currentSnapshot || {});
                if (inbox) {
                    showRealtimeAlert(inbox);
                    showRealtimeSweetAlert(inbox);
                }

                sessionStorage.setItem(snapshotKey, JSON.stringify(currentSnapshot || {}));
            } catch (e) {
                return;
            }
        }

        function applyRealtimeRows(payload) {
            if (!payload || !payload.rows) {
                return;
            }

            var totalEl = document.querySelector('[data-field="metric_total"]');
            if (totalEl && typeof payload.total_count === 'number') {
                totalEl.textContent = String(payload.total_count);
            }

            var activeEl = document.querySelector('[data-field="metric_active"]');
            if (activeEl && typeof payload.active_count === 'number') {
                activeEl.textContent = String(payload.active_count);
            }

            Object.keys(payload.rows).forEach(function (gadaiId) {
                var rowPayload = payload.rows[gadaiId] || {};
                var row = document.querySelector('tr[data-gadai-id="' + gadaiId + '"]');
                if (!row) {
                    return;
                }

                var statusBadge = row.querySelector('[data-field="status_badge"]');
                if (statusBadge) {
                    statusBadge.className = 'badge-status ' + (rowPayload.badge_class || 'badge-secondary');
                    statusBadge.textContent = rowPayload.status_label || '-';
                }

                var disetujuiEl = row.querySelector('[data-field="disetujui_value"]');
                if (disetujuiEl) {
                    disetujuiEl.textContent = rowPayload.disetujui_display || '-';
                }

                var updatedEl = row.querySelector('[data-field="updated_at_value"]');
                if (updatedEl) {
                    updatedEl.textContent = rowPayload.updated_at_display || '-';
                }
            });
        }

        function saveState() {
            try {
                sessionStorage.setItem(stateKey + ':scrollY', String(window.scrollY || 0));
            } catch (e) {
                return;
            }
        }

        function restoreState() {
            try {
                var scrollY = parseInt(sessionStorage.getItem(stateKey + ':scrollY') || '0', 10);
                if (scrollY > 0) {
                    window.scrollTo(0, scrollY);
                }
            } catch (e) {
                return;
            }
        }

        function fetchRealtimeData() {
            if (document.hidden) {
                return;
            }

            fetch('customer_realtime.php', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(function (payload) {
                if (!payload || !payload.success) {
                    return;
                }

                var nextSnapshot = payload.snapshot || {};
                syncRealtimeAlert(nextSnapshot);
                liveSnapshot = nextSnapshot;
                applyRealtimeRows(payload);
            })
            .catch(function () {
                return;
            });
        }

        window.addEventListener('beforeunload', saveState);
        showCustomerFlashNotification();
        restoreState();
        setupRupiahInputFormatting();
        syncRealtimeAlert(liveSnapshot);
        setInterval(fetchRealtimeData, 20000);
    })();

    document.addEventListener('DOMContentLoaded', function () {
        bindSubmitLoading('form input[name="action"][value="customer_submit_pinjaman"]', {
            title: 'Mengirim Pengajuan',
            text: 'Mohon tunggu, data pinjaman sedang dikirim.'
        });

        bindSubmitLoading('form input[name="action"][value="customer_request_naik_pinjaman"]', {
            title: 'Mengirim Request',
            text: 'Mohon tunggu, request naik pinjaman sedang diproses.'
        });
    });
</script>

<div class="modal fade" id="customerDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered detail-modal-compact">
        <div class="modal-content detail-surface">
            <div class="modal-header">
                <div>
                    <div class="small text-uppercase opacity-75">Detail Pengajuan</div>
                    <h5 class="modal-title mb-0" data-field="title">Detail Pengajuan</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 p-md-4">
                <div class="detail-summary">
                    <div class="detail-section-title">Ringkasan Cepat</div>
                    <div class="detail-summary-grid">
                        <div class="detail-item detail-span-full">
                            <div>
                                <span class="detail-label">Barang</span>
                                <div class="detail-value detail-body-copy" data-field="barang"></div>
                            </div>
                            <div>
                                <span class="detail-label mb-2">Status</span>
                                <span class="detail-status-chip" data-field="status_badge"></span>
                            </div>
                        </div>
                        <div class="detail-item"><span class="detail-label">Nama</span><div class="detail-value" data-field="nama"></div></div>
                        <div class="detail-item"><span class="detail-label">NIK</span><div class="detail-value" data-field="nik"></div></div>
                        <div class="detail-item"><span class="detail-label">No. WhatsApp</span><div class="detail-value" data-field="no_wa"></div></div>
                    </div>
                </div>

                <div class="detail-modal-grid">
                    <div class="detail-item"><span class="detail-label">Kelengkapan Barang</span><div class="detail-value" data-field="kelengkapan"></div></div>
                    <div class="detail-item detail-admin-note">
                        <div class="detail-note-badge">Catatan Internal</div>
                        <span class="detail-label">Catatan Admin</span>
                        <div class="detail-value" data-field="catatan_admin"></div>
                    </div>
                    <div class="detail-item"><span class="detail-label">Pinjaman Diajukan</span><div class="detail-value" data-field="pengajuan"></div></div>
                    <div class="detail-item"><span class="detail-label">Pinjaman Disetujui</span><div class="detail-value" data-field="disetujui"></div></div>
                    <div class="detail-item"><span class="detail-label">Total Tebus</span><div class="detail-value" data-field="total_tebus"></div></div>
                    <div class="detail-item"><span class="detail-label">Tanggal Gadai</span><div class="detail-value" data-field="tgl_gadai"></div></div>
                    <div class="detail-item"><span class="detail-label">Jatuh Tempo</span><div class="detail-value" data-field="jatuh_tempo"></div></div>
                    <div class="detail-item"><span class="detail-label">Update Terakhir</span><div class="detail-value" data-field="updated_at"></div></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="naikPinjamanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered request-modal-compact">
        <div class="modal-content detail-surface">
            <div class="modal-header">
                <div>
                    <div class="small text-uppercase opacity-75">Request Customer</div>
                    <h5 class="modal-title mb-0">Ajukan Naik Pinjaman</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="customer_request_naik_pinjaman">
                    <input type="hidden" name="gadai_id" value="">

                    <div class="detail-summary mb-3">
                        <div class="detail-section-title">Info Request</div>
                        <div class="detail-summary-grid">
                            <div class="detail-item detail-span-full">
                                <div>
                                    <span class="detail-label">Barang</span>
                                    <div class="detail-value" data-field="naik_barang"></div>
                                </div>
                                <div>
                                    <span class="detail-label mb-2">Status</span>
                                    <span class="detail-status-chip" data-field="naik_status"></span>
                                </div>
                            </div>
                            <div class="detail-item"><span class="detail-label">Pinjaman Saat Ini</span><div class="detail-value" data-field="naik_current"></div></div>
                            <div class="detail-item"><span class="detail-label">Tambahan Maksimal</span><div class="detail-value" data-field="naik_max_additional"></div></div>
                            <div class="detail-item"><span class="detail-label">Batas Total</span><div class="detail-value" data-field="naik_max_total"></div></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nominal Tambahan <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="requested_amount" class="form-control" min="1" step="0.01" required>
                        </div>
                        <small class="text-muted">Maksimal tambahan mengikuti batas dari nilai taksiran dan pinjaman aktif.</small>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-semibold">Alasan Request <span class="text-danger">*</span></label>
                        <textarea name="alasan_request" class="form-control" rows="3" required placeholder="Contoh: butuh tambahan dana untuk kebutuhan mendesak."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Kirim Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="requestHistoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered detail-modal-compact">
        <div class="modal-content detail-surface">
            <div class="modal-header">
                <div>
                    <div class="small text-uppercase opacity-75">Histori Kenaikan Pinjaman</div>
                    <h5 class="modal-title mb-0" data-field="request_title">Detail Request</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 p-md-4">
                <div class="detail-summary">
                    <div class="detail-section-title">Ringkasan Request</div>
                    <div class="detail-summary-grid">
                        <div class="detail-item detail-span-full">
                            <div>
                                <span class="detail-label">Barang</span>
                                <div class="detail-value detail-body-copy" data-field="request_barang"></div>
                            </div>
                            <div>
                                <span class="detail-label mb-2">Status</span>
                                <span class="detail-status-chip" data-field="request_status"></span>
                            </div>
                        </div>
                        <div class="detail-item"><span class="detail-label">Request Saat Ini</span><div class="detail-value" data-field="request_current"></div></div>
                        <div class="detail-item"><span class="detail-label">Tambahan Diminta</span><div class="detail-value" data-field="request_additional"></div></div>
                        <div class="detail-item"><span class="detail-label">Pinjaman Baru</span><div class="detail-value" data-field="request_new_total"></div></div>
                    </div>
                </div>

                <div class="detail-modal-grid">
                    <div class="detail-item"><span class="detail-label">Alasan Request</span><div class="detail-value" data-field="request_alasan"></div></div>
                    <div class="detail-item detail-admin-note">
                        <div class="detail-note-badge">Catatan Admin</div>
                        <span class="detail-label">Catatan Proses</span>
                        <div class="detail-value" data-field="request_admin_note"></div>
                    </div>
                    <div class="detail-item"><span class="detail-label">Diajukan</span><div class="detail-value" data-field="request_created_at"></div></div>
                    <div class="detail-item"><span class="detail-label">Diproses</span><div class="detail-value" data-field="request_reviewed_at"></div></div>
                    <div class="detail-item"><span class="detail-label">Update Terakhir</span><div class="detail-value" data-field="request_updated_at"></div></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="pinjamanHistoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content detail-surface">
            <div class="modal-header">
                <div>
                    <div class="small text-uppercase opacity-75">Histori Request</div>
                    <h5 class="modal-title mb-0" data-field="pinjaman_history_title">Histori Naik Pinjaman</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="detail-summary mb-3">
                    <div class="detail-section-title">Ringkasan Gadai</div>
                    <div class="detail-summary-grid">
                        <div class="detail-item detail-span-full">
                            <div>
                                <span class="detail-label">Barang</span>
                                <div class="detail-value detail-body-copy" data-field="pinjaman_history_barang"></div>
                            </div>
                            <div>
                                <span class="detail-label mb-2">Status Gadai</span>
                                <span class="detail-status-chip" data-field="pinjaman_history_status_gadai"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div data-field="pinjaman_history_list" class="pinjaman-history-list"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
</body>
</html>
