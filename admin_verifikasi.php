<?php
require_once 'database.php';
require_once 'whatsapp_helper.php';
require_once 'gadai_helpers.php';
require_once 'admin_verifikasi_actions.php';

// Global message for user feedback (initialize to avoid undefined variable warnings)
$message = '';
$message_type = '';
$active_status_sql = gadai_active_status_sql_list();
$sale_status_sql = gadai_sale_status_sql_list();
$list_search = trim((string)($_GET['list_search'] ?? ''));

function calculateGadaiBreakdown(array $row, ?float $overrideDenda = null): array {
    return gadai_calculate_breakdown($row, $overrideDenda);
}

function formatExcelExportText($value): string {
    $text = trim((string)$value);
    if ($text === '') {
        return '-';
    }

    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function getExcelRupiahStyle(): string {
    return 'mso-number-format:"\\0022Rp\\0022\\ #,##0";';
}

function exportTotalGadaiExcel(array $rows, string $listSearch): void {
    $filename = 'total_gadai_' . date('Ymd_His') . '.xls';
    $totalPengajuan = 0.0;
    $totalDisetujui = 0.0;
    $totalProfitAktual = 0.0;
    $totalTebus = 0.0;
    $columnCount = 20;

    $sheetStyle = 'font-family:Calibri, Arial, sans-serif;font-size:11pt;color:#1f2937;';
    $tableStyle = 'border-collapse:collapse;width:100%;';
    $titleStyle = 'background:#0f4c81;color:#ffffff;font-weight:bold;font-size:16pt;text-align:center;padding:14px;border:1px solid #0b3b63;';
    $metaLabelStyle = 'background:#dbeafe;font-weight:bold;padding:8px;border:1px solid #bfdbfe;';
    $metaValueStyle = 'background:#f8fbff;padding:8px;border:1px solid #dbeafe;';
    $headerStyle = 'background:#1d4ed8;color:#ffffff;font-weight:bold;text-align:center;vertical-align:middle;padding:10px 8px;border:1px solid #bfdbfe;';
    $cellStyle = 'padding:7px 8px;border:1px solid #d1d9e6;vertical-align:top;';
    $centerCellStyle = $cellStyle . 'text-align:center;';
    $textCellStyle = $cellStyle . 'mso-number-format:"\\@";';
    $wrapCellStyle = $cellStyle . 'white-space:normal;';
    $totalLabelStyle = 'background:#dbeafe;font-weight:bold;padding:9px;border:1px solid #93c5fd;text-align:center;';
    $totalValueStyle = 'background:#eff6ff;font-weight:bold;padding:9px;border:1px solid #93c5fd;';

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo "\xEF\xBB\xBF";
    echo '<html><head><meta charset="UTF-8"></head><body style="' . $sheetStyle . '">';
    echo '<table style="' . $tableStyle . '">';
    echo '<colgroup>';
    echo '<col style="width:45px">';
    echo '<col style="width:60px">';
    echo '<col style="width:160px">';
    echo '<col style="width:130px">';
    echo '<col style="width:130px">';
    echo '<col style="width:220px">';
    echo '<col style="width:120px">';
    echo '<col style="width:120px">';
    echo '<col style="width:180px">';
    echo '<col style="width:130px">';
    echo '<col style="width:120px">';
    echo '<col style="width:120px">';
    echo '<col style="width:90px">';
    echo '<col style="width:120px">';
    echo '<col style="width:130px">';
    echo '<col style="width:110px">';
    echo '<col style="width:110px">';
    echo '<col style="width:110px">';
    echo '<col style="width:90px">';
    echo '<col style="width:220px">';
    echo '</colgroup>';
    echo '<tr><th colspan="' . $columnCount . '" style="' . $titleStyle . '">Laporan Total Gadai</th></tr>';
    echo '<tr><td colspan="5" style="' . $metaLabelStyle . '">Tanggal Export</td><td colspan="15" style="' . $metaValueStyle . '">' . htmlspecialchars(date('d-m-Y H:i:s'), ENT_QUOTES, 'UTF-8') . '</td></tr>';
    echo '<tr><td colspan="5" style="' . $metaLabelStyle . '">Filter Pencarian</td><td colspan="15" style="' . $metaValueStyle . '">' . ($listSearch !== '' ? htmlspecialchars($listSearch, ENT_QUOTES, 'UTF-8') : 'Semua Data') . '</td></tr>';
    echo '<tr>';
    echo '<th style="' . $headerStyle . '">No</th>';
    echo '<th style="' . $headerStyle . '">ID</th>';
    echo '<th style="' . $headerStyle . '">Nama</th>';
    echo '<th style="' . $headerStyle . '">NIK</th>';
    echo '<th style="' . $headerStyle . '">No WA</th>';
    echo '<th style="' . $headerStyle . '">Alamat</th>';
    echo '<th style="' . $headerStyle . '">Jenis Barang</th>';
    echo '<th style="' . $headerStyle . '">Merek</th>';
    echo '<th style="' . $headerStyle . '">Spesifikasi</th>';
    echo '<th style="' . $headerStyle . '">Kondisi</th>';
    echo '<th style="' . $headerStyle . '">Pinjaman Diajukan</th>';
    echo '<th style="' . $headerStyle . '">Pinjaman Disetujui</th>';
    echo '<th style="' . $headerStyle . '">Bunga (%)</th>';
    echo '<th style="' . $headerStyle . '">Profit Aktual</th>';
    echo '<th style="' . $headerStyle . '">Total Tebus</th>';
    echo '<th style="' . $headerStyle . '">Tanggal Gadai</th>';
    echo '<th style="' . $headerStyle . '">Jatuh Tempo</th>';
    echo '<th style="' . $headerStyle . '">Status</th>';
    echo '<th style="' . $headerStyle . '">Perpanjangan Ke</th>';
    echo '<th style="' . $headerStyle . '">Catatan Admin</th>';
    echo '</tr>';

    foreach ($rows as $index => $row) {
        $pengajuan = (float)($row['jumlah_pinjaman'] ?? 0);
        $disetujui = !empty($row['jumlah_disetujui']) ? (float)$row['jumlah_disetujui'] : 0.0;
        $dendaInfo = gadai_calculate_denda($row['tanggal_jatuh_tempo'] ?? null, $row['denda_terakumulasi'] ?? 0);
        $calcList = calculateGadaiBreakdown($row, $dendaInfo['denda']);
        $profitBunga = (float)($calcList['bunga_total'] ?? 0);
        $profitPerpanjangan = (float)($row['total_profit_perpanjangan'] ?? 0);
        $profitAktual = $profitBunga + $profitPerpanjangan;
        $totalKembali = !empty($row['total_tebus']) && (float)$row['total_tebus'] > 0
            ? (float)$row['total_tebus']
            : (float)$calcList['total_tebus'];

        $totalPengajuan += $pengajuan;
        $totalDisetujui += $disetujui;
        $totalProfitAktual += $profitAktual;
        $totalTebus += $totalKembali;

        $rowBackground = $index % 2 === 0 ? '#ffffff' : '#f8fbff';
        $rowCellStyle = $cellStyle . 'background:' . $rowBackground . ';';
        $rowCenterStyle = $centerCellStyle . 'background:' . $rowBackground . ';';
        $rowTextStyle = $textCellStyle . 'background:' . $rowBackground . ';';
        $rowWrapStyle = $wrapCellStyle . 'background:' . $rowBackground . ';';

        echo '<tr>';
        echo '<td style="' . $rowCenterStyle . '">' . ($index + 1) . '</td>';
        echo '<td style="' . $rowCenterStyle . '">' . (int)($row['id'] ?? 0) . '</td>';
        echo '<td style="' . $rowCellStyle . '">' . formatExcelExportText($row['nama'] ?? '') . '</td>';
        echo '<td style="' . $rowTextStyle . '">' . formatExcelExportText($row['nik'] ?? '') . '</td>';
        echo '<td style="' . $rowTextStyle . '">' . formatExcelExportText($row['no_wa'] ?? '') . '</td>';
        echo '<td style="' . $rowWrapStyle . '">' . formatExcelExportText($row['alamat'] ?? '') . '</td>';
        echo '<td style="' . $rowCellStyle . '">' . formatExcelExportText($row['jenis_barang'] ?? '') . '</td>';
        echo '<td style="' . $rowCellStyle . '">' . formatExcelExportText($row['merk_barang'] ?? '') . '</td>';
        echo '<td style="' . $rowWrapStyle . '">' . formatExcelExportText($row['spesifikasi_barang'] ?? '') . '</td>';
        echo '<td style="' . $rowCellStyle . '">' . formatExcelExportText($row['kondisi_barang'] ?? '') . '</td>';
        echo '<td style="' . $rowCellStyle . getExcelRupiahStyle() . '">' . round($pengajuan) . '</td>';
        echo '<td style="' . $rowCellStyle . getExcelRupiahStyle() . '">' . round($disetujui) . '</td>';
        echo '<td style="' . $rowCenterStyle . '">' . rtrim(rtrim(number_format((float)($calcList['bunga_pct'] ?? 0), 2, '.', ''), '0'), '.') . '</td>';
        echo '<td style="' . $rowCellStyle . getExcelRupiahStyle() . '">' . round($profitAktual) . '</td>';
        echo '<td style="' . $rowCellStyle . getExcelRupiahStyle() . '">' . round($totalKembali) . '</td>';
        echo '<td style="' . $rowCenterStyle . '">' . (!empty($row['tanggal_gadai']) ? htmlspecialchars(date('d-m-Y', strtotime($row['tanggal_gadai'])), ENT_QUOTES, 'UTF-8') : '-') . '</td>';
        echo '<td style="' . $rowCenterStyle . '">' . (!empty($row['tanggal_jatuh_tempo']) ? htmlspecialchars(date('d-m-Y', strtotime($row['tanggal_jatuh_tempo'])), ENT_QUOTES, 'UTF-8') : '-') . '</td>';
        echo '<td style="' . $rowCenterStyle . '">' . formatExcelExportText($row['status'] ?? '') . '</td>';
        echo '<td style="' . $rowCenterStyle . '">' . (int)($row['perpanjangan_ke'] ?? 0) . '</td>';
        echo '<td style="' . $rowWrapStyle . '">' . formatExcelExportText($row['catatan_admin'] ?? '') . '</td>';
        echo '</tr>';
    }

    echo '<tr>';
    echo '<td colspan="10" style="' . $totalLabelStyle . '">Total</td>';
    echo '<td style="' . $totalValueStyle . getExcelRupiahStyle() . '">' . round($totalPengajuan) . '</td>';
    echo '<td style="' . $totalValueStyle . getExcelRupiahStyle() . '">' . round($totalDisetujui) . '</td>';
    echo '<td style="' . $totalValueStyle . 'text-align:center;">-</td>';
    echo '<td style="' . $totalValueStyle . getExcelRupiahStyle() . '">' . round($totalProfitAktual) . '</td>';
    echo '<td style="' . $totalValueStyle . getExcelRupiahStyle() . '">' . round($totalTebus) . '</td>';
    echo '<td colspan="5" style="' . $totalValueStyle . '">Jumlah Data: ' . count($rows) . '</td>';
    echo '</tr>';
    echo '</table>';
    echo '</body></html>';
    exit;
}

// --- Aksi verifikasi admin dipindahkan ke file proses terpisah ---
handleAdminStatusActions($db, $whatsapp, $message, $message_type);

// --- Aksi admin pendukung (input manual, reminder, notifikasi, nota) dipindahkan ke file proses terpisah ---
handleAdminUtilityActions($db, $whatsapp, $message, $message_type);

// --- Manual pelunasan oleh admin dipindahkan ke file proses terpisah ---
handleAdminManualLunasAction($db, $whatsapp, $message, $message_type);

// Authentication handled by auth_check.php (included at top of file)

// --- Auto: jika telat >7 hari, tandai sebagai Gagal Tebus (gagal bayar) ---
// Ini menjaga konsistensi walau cron reminder tidak berjalan.
try {
    $sqlAutoFail = "SELECT id, jumlah_disetujui, jumlah_pinjaman, bunga, lama_gadai
        FROM data_gadai
        WHERE status IN ($active_status_sql)
          AND tanggal_jatuh_tempo IS NOT NULL
          AND tanggal_jatuh_tempo < CURDATE()
          AND DATEDIFF(CURDATE(), tanggal_jatuh_tempo) >= 8
          AND (gagal_tebus_at IS NULL)";
    $stmtAutoFail = $db->query($sqlAutoFail);
    $rowsAutoFail = $stmtAutoFail->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rowsAutoFail as $r) {
        try {
            $idFail = (int)$r['id'];
            if ($idFail <= 0) continue;

            $denda_harian = 30000;
            $denda_total = $denda_harian * 7;

            $calcAutoFail = calculateGadaiBreakdown($r, $denda_total);
            $total_tebus = (float)$calcAutoFail['total_tebus'];

            $updFail = $db->prepare("UPDATE data_gadai SET status = 'Gagal Tebus', gagal_tebus_at = NOW(), denda_terakumulasi = ?, total_tebus = ?, updated_at = NOW() WHERE id = ?");
            $updFail->execute([$denda_total, $total_tebus, $idFail]);
        } catch (Throwable $e) {
            // jangan ganggu render halaman
            error_log('Auto fail update error: ' . $e->getMessage());
        }
    }
} catch (Throwable $e) {
    // ignore jika kolom/fitur belum tersedia
}



// Fetch pending submissions
$pending_sql = "SELECT * FROM data_gadai WHERE status = 'Pending' ORDER BY created_at DESC";
$pending_stmt = $db->query($pending_sql);
$pending_data = $pending_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all active approved/extended submissions so the result stays consistent with the list table
$approved_sql = "SELECT * FROM data_gadai WHERE status IN ($active_status_sql) ORDER BY updated_at DESC";
$approved_stmt = $db->query($approved_sql);
$approved_data = $approved_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch rejected submissions
$rejected_sql = "SELECT * FROM data_gadai WHERE status = 'Ditolak' ORDER BY updated_at DESC";
$rejected_stmt = $db->query($rejected_sql);
$rejected_data = $rejected_stmt->fetchAll(PDO::FETCH_ASSOC);

$transaksi_table_exists = false;
try {
    $checkTransaksiTableStmt = $db->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'transaksi'");
    $checkTransaksiTableStmt->execute();
    $transaksi_table_exists = (int)$checkTransaksiTableStmt->fetchColumn() > 0;
} catch (Throwable $e) {
    $transaksi_table_exists = false;
}

// Fetch all submissions (for list table) - include fields needed for search and detail view
$all_profit_select = $transaksi_table_exists
    ? ", (SELECT COALESCE(SUM(t.jumlah_bayar), 0) FROM transaksi t WHERE t.barang_id = data_gadai.id AND t.keterangan LIKE 'perpanjangan%') AS total_profit_perpanjangan"
    : ", 0 AS total_profit_perpanjangan";
$all_sql = "SELECT id, nama, nik, no_wa, alamat, jenis_barang, merk_barang, spesifikasi_barang, kondisi_barang, nilai_taksiran, jumlah_pinjaman, jumlah_disetujui, bunga, lama_gadai, denda_terakumulasi, total_tebus, tanggal_gadai, tanggal_jatuh_tempo, status, catatan_admin, perpanjangan_ke, created_at, updated_at" . $all_profit_select . " FROM data_gadai ORDER BY created_at DESC";
$all_stmt = $db->query($all_sql);
$all_data = $all_stmt->fetchAll(PDO::FETCH_ASSOC);

if ($list_search !== '') {
    $needle = function_exists('mb_strtolower') ? mb_strtolower($list_search, 'UTF-8') : strtolower($list_search);
    $all_data = array_values(array_filter($all_data, static function (array $row) use ($needle): bool {
        $haystack = implode(' ', [
            (string)($row['id'] ?? ''),
            (string)($row['nama'] ?? ''),
            (string)($row['nik'] ?? ''),
            (string)($row['no_wa'] ?? ''),
            (string)($row['alamat'] ?? ''),
            (string)($row['jenis_barang'] ?? ''),
            (string)($row['merk_barang'] ?? ''),
            (string)($row['spesifikasi_barang'] ?? ''),
            (string)($row['kondisi_barang'] ?? ''),
            (string)($row['status'] ?? ''),
            (string)($row['catatan_admin'] ?? ''),
        ]);
        $haystack = function_exists('mb_strtolower') ? mb_strtolower($haystack, 'UTF-8') : strtolower($haystack);
        return strpos($haystack, $needle) !== false;
    }));
}

if (isset($_GET['export']) && $_GET['export'] === 'excel_total_gadai') {
    exportTotalGadaiExcel($all_data, $list_search);
}

// Fetch pelunasan pending submissions
$pelunasan_data = [];
$pelunasan_error = null;
try {
    // Note: aksi_jatuh_tempo column removed - pelunasan pending now based on transaksi with keterangan 'pelunasan'
    $pelunasan_sql = "SELECT dg.*, 
        (SELECT COUNT(*) FROM transaksi t WHERE t.barang_id = dg.id AND t.pelanggan_nik = dg.nik AND t.keterangan = 'pelunasan') AS bukti_count,
        (SELECT SUM(t.jumlah_bayar) FROM transaksi t WHERE t.barang_id = dg.id AND t.pelanggan_nik = dg.nik AND t.keterangan = 'pelunasan') AS bukti_total,
        (SELECT t.bukti FROM transaksi t WHERE t.barang_id = dg.id AND t.pelanggan_nik = dg.nik AND t.keterangan = 'pelunasan' ORDER BY t.id DESC LIMIT 1) AS bukti_latest
        FROM data_gadai dg
        WHERE dg.status IN ($active_status_sql)
        AND EXISTS (SELECT 1 FROM transaksi t WHERE t.barang_id = dg.id AND t.pelanggan_nik = dg.nik AND t.keterangan = 'pelunasan')
        ORDER BY dg.updated_at DESC";
    $pelunasan_stmt = $db->query($pelunasan_sql);
    $pelunasan_data = $pelunasan_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pelunasan_error = "Data pelunasan belum tersedia (cek tabel transaksi).";
}

    // Fetch perpanjangan pending submissions
    $perpanjangan_data = [];
    $perpanjangan_error = null;
    try {
        // Note: aksi_jatuh_tempo column removed - perpanjangan pending now based on transaksi with keterangan 'perpanjangan'
        $perpanjangan_sql = "SELECT dg.*, 
            (SELECT COUNT(*) FROM transaksi t WHERE t.barang_id = dg.id AND t.pelanggan_nik = dg.nik AND t.keterangan = 'perpanjangan') AS bukti_count,
            (SELECT SUM(t.jumlah_bayar) FROM transaksi t WHERE t.barang_id = dg.id AND t.pelanggan_nik = dg.nik AND t.keterangan = 'perpanjangan') AS bukti_total,
            (SELECT t.bukti FROM transaksi t WHERE t.barang_id = dg.id AND t.pelanggan_nik = dg.nik AND t.keterangan = 'perpanjangan' ORDER BY t.id DESC LIMIT 1) AS bukti_latest
            FROM data_gadai dg
            WHERE dg.status IN ($active_status_sql)
            AND EXISTS (SELECT 1 FROM transaksi t WHERE t.barang_id = dg.id AND t.pelanggan_nik = dg.nik AND t.keterangan = 'perpanjangan')
            ORDER BY dg.updated_at DESC";
        $perpanjangan_stmt = $db->query($perpanjangan_sql);
        $perpanjangan_data = $perpanjangan_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $perpanjangan_error = "Data perpanjangan belum tersedia (cek tabel transaksi).";
    }

// Fetch overdue submissions for manual reminders
$reminder_data = [];
$reminder_error = null;
try {
    $reminder_sql = "SELECT dg.*, DATEDIFF(CURDATE(), dg.tanggal_jatuh_tempo) AS days_overdue
        FROM data_gadai dg
        WHERE dg.status IN ($active_status_sql)
          AND dg.tanggal_jatuh_tempo IS NOT NULL
          AND dg.tanggal_jatuh_tempo < CURDATE()
          AND DATEDIFF(CURDATE(), dg.tanggal_jatuh_tempo) BETWEEN 1 AND 7
        ORDER BY dg.tanggal_jatuh_tempo ASC";
    $reminder_stmt = $db->query($reminder_sql);
    $reminder_data = $reminder_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $reminder_error = 'Data reminder belum tersedia.';
}

// Fetch internal sale pipeline data
$sale_data = [];
$sale_error = null;
try {
    $sale_sql = "SELECT dg.*,
        (SELECT t.jumlah_bayar FROM transaksi t WHERE t.barang_id = dg.id AND t.keterangan = 'penjualan_barang' ORDER BY t.id DESC LIMIT 1) AS harga_jual_terakhir,
        (SELECT t.created_at FROM transaksi t WHERE t.barang_id = dg.id AND t.keterangan = 'penjualan_barang' ORDER BY t.id DESC LIMIT 1) AS tanggal_terjual
        FROM data_gadai dg
        WHERE dg.status IN ($sale_status_sql)
        ORDER BY dg.updated_at DESC";
    $sale_stmt = $db->query($sale_sql);
    $sale_data = $sale_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $sale_error = 'Data penjualan internal belum tersedia.';
}

// --- Fetch transaksi records (used for admin view of bukti perpanjangan / pelunasan) ---
$transaksi_data = [];
$transaksi_error = null;
try {
    // Include item identity fields from data_gadai so we can show a friendly item name
    $transaksi_sql = "SELECT t.*, dg.nama AS nama_nasabah, dg.jenis_barang, dg.merk_barang AS merk, dg.spesifikasi_barang AS tipe FROM transaksi t LEFT JOIN data_gadai dg ON t.barang_id = dg.id ORDER BY t.created_at DESC";
    $transaksi_stmt = $db->query($transaksi_sql);
    $transaksi_data = $transaksi_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $transaksi_error = "Data transaksi belum tersedia (cek tabel transaksi).";
}

// --- Keuntungan Bulanan (berdasarkan transaksi) ---
// Definisi:
// - Lunas: keuntungan = bunga_total + admin_fee(1%) + asuransi(10.000) + denda
// - Perpanjangan: keuntungan = total pembayaran perpanjangan (keterangan LIKE 'perpanjangan%')
// - Penjualan Barang: estimasi laba/rugi = harga_jual - pokok pinjaman
$profit_error = null;
$profit_year = (int)($_GET['profit_year'] ?? date('Y'));
if ($profit_year < 2000 || $profit_year > 2100) {
    $profit_year = (int)date('Y');
}

$profit_months = [];
for ($m = 1; $m <= 12; $m++) {
    $profit_months[$m] = [
        'lunas_count' => 0,
        'lunas_profit' => 0.0,
        'perp_count' => 0,
        'perp_profit' => 0.0,
        'jual_count' => 0,
        'jual_profit' => 0.0,
    ];
}

try {
    // Jika tabel transaksi belum ada, fitur ini tidak bisa menghitung per bulan.
    $checkTbl = $db->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'transaksi'");
    $checkTbl->execute();
    $tblExists = (int)$checkTbl->fetchColumn() > 0;

    if (!$tblExists) {
        $profit_error = 'Fitur keuntungan membutuhkan tabel transaksi.';
    } else {
        // 1) Keuntungan Lunas per bulan (pakai tanggal transaksi pelunasan terakhir)
        $sqlLunas = "
            SELECT x.mm,
                   COUNT(*) AS lunas_count,
                   SUM(x.profit) AS lunas_profit
            FROM (
                SELECT dg.id,
                       MONTH(MAX(t.created_at)) AS mm,
                       (
                           (
                               (IFNULL(NULLIF(dg.jumlah_disetujui, 0), dg.jumlah_pinjaman) * (IFNULL(dg.bunga,0) / 100) * IFNULL(dg.lama_gadai,0))
                               + ROUND(IFNULL(NULLIF(dg.jumlah_disetujui, 0), dg.jumlah_pinjaman) * 0.01)
                               + 10000
                               + IFNULL(dg.denda_terakumulasi, 0)
                           )
                       ) AS profit
                FROM data_gadai dg
                INNER JOIN transaksi t
                    ON t.barang_id = dg.id
                   AND (t.keterangan = 'pelunasan' OR t.keterangan = 'pelunasan_admin')
                WHERE dg.status = 'Lunas'
                  AND YEAR(t.created_at) = ?
                GROUP BY dg.id
            ) x
            GROUP BY x.mm
        ";
        $stmtLunas = $db->prepare($sqlLunas);
        $stmtLunas->execute([$profit_year]);
        $rowsLunas = $stmtLunas->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rowsLunas as $r) {
            $mm = (int)($r['mm'] ?? 0);
            if ($mm >= 1 && $mm <= 12) {
                $profit_months[$mm]['lunas_count'] = (int)($r['lunas_count'] ?? 0);
                $profit_months[$mm]['lunas_profit'] = (float)($r['lunas_profit'] ?? 0);
            }
        }

        // 2) Keuntungan Perpanjangan per bulan (jumlah bayar dianggap keuntungan)
        $sqlPerp = "
            SELECT MONTH(t.created_at) AS mm,
                   COUNT(*) AS perp_count,
                   SUM(IFNULL(t.jumlah_bayar, 0)) AS perp_profit
            FROM transaksi t
            WHERE YEAR(t.created_at) = ?
              AND t.keterangan LIKE 'perpanjangan%'
            GROUP BY MONTH(t.created_at)
        ";
        $stmtPerp = $db->prepare($sqlPerp);
        $stmtPerp->execute([$profit_year]);
        $rowsPerp = $stmtPerp->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rowsPerp as $r) {
            $mm = (int)($r['mm'] ?? 0);
            if ($mm >= 1 && $mm <= 12) {
                $profit_months[$mm]['perp_count'] = (int)($r['perp_count'] ?? 0);
                $profit_months[$mm]['perp_profit'] = (float)($r['perp_profit'] ?? 0);
            }
        }

        // 3) Penjualan barang per bulan (harga jual - pokok pinjaman)
        $sqlJual = "
            SELECT MONTH(t.created_at) AS mm,
                   COUNT(*) AS jual_count,
                   SUM(IFNULL(t.jumlah_bayar, 0) - IFNULL(NULLIF(dg.jumlah_disetujui, 0), dg.jumlah_pinjaman)) AS jual_profit
            FROM transaksi t
            INNER JOIN data_gadai dg ON dg.id = t.barang_id
            WHERE YEAR(t.created_at) = ?
              AND t.keterangan = 'penjualan_barang'
            GROUP BY MONTH(t.created_at)
        ";
        $stmtJual = $db->prepare($sqlJual);
        $stmtJual->execute([$profit_year]);
        $rowsJual = $stmtJual->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rowsJual as $r) {
            $mm = (int)($r['mm'] ?? 0);
            if ($mm >= 1 && $mm <= 12) {
                $profit_months[$mm]['jual_count'] = (int)($r['jual_count'] ?? 0);
                $profit_months[$mm]['jual_profit'] = (float)($r['jual_profit'] ?? 0);
            }
        }
    }
} catch (Throwable $e) {
    $profit_error = 'Gagal menghitung keuntungan: ' . $e->getMessage();
}

// Statistics
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status IN ($active_status_sql) THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'Ditolak' THEN 1 ELSE 0 END) as rejected
FROM data_gadai";
$stats = $db->query($stats_sql)->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Verifikasi - Gadai Cepat Timika</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@700;800;900&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background:
                radial-gradient(circle at top left, rgba(0, 123, 255, 0.12), transparent 0, transparent 35%),
                linear-gradient(135deg, #eef6ff 0%, #f8fbff 48%, #f1f8ff 100%);
            color: #1f2a37;
            min-height: 100vh;
            padding: 0 0 40px;
        }
        
        .header {
            background: linear-gradient(135deg, #0056b3, #007bff 55%, #3aa0ff 100%);
            color: white;
            padding: 32px 0;
            margin-top: 0;
            margin-bottom: 26px;
            box-shadow: 0 10px 30px rgba(0, 86, 179, 0.22);
            border-radius: 0 0 24px 24px;
        }
        
        .header-wrap {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .header h1 {
            font-family: 'Raleway', sans-serif;
            font-weight: 800;
            margin: 0 0 8px 0;
        }

        .header-subtitle {
            margin: 0;
            max-width: 720px;
            color: rgba(255, 255, 255, 0.92);
            font-size: 0.98rem;
        }

        .header-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .quick-chip {
            border: 1px solid rgba(255, 255, 255, 0.28);
            background: rgba(255, 255, 255, 0.14);
            color: white;
            border-radius: 999px;
            padding: 10px 14px;
            font-size: 0.92rem;
            font-weight: 600;
            backdrop-filter: blur(4px);
            transition: all 0.2s ease;
        }

        .quick-chip:hover {
            transform: translateY(-1px);
            background: rgba(255, 255, 255, 0.24);
            color: white;
        }

        .quick-chip.light {
            background: white;
            color: #0056b3;
            border-color: white;
        }

        .quick-chip.light:hover {
            color: #004494;
            background: #f6fbff;
        }
        
        .stats-card {
            background: white;
            border-radius: 18px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
            border-left: 5px solid #007bff;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.12);
        }
        
        .stats-number {
            font-size: 2.35rem;
            font-weight: 800;
            color: #0056b3;
        }
        
        .stats-label {
            color: #5f6b7a;
            font-weight: 600;
        }

        .dashboard-intro {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(0, 123, 255, 0.08);
            border-radius: 18px;
            padding: 18px 20px;
            margin-bottom: 18px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
        }

        .dashboard-intro h5 {
            margin: 0 0 4px 0;
            color: #0f315f;
            font-weight: 700;
        }

        .dashboard-intro p {
            margin: 0;
            color: #607086;
        }

        .quick-action-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .data-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }
        
        .data-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
        
        .data-card.pending {
            border-left: 5px solid #ffc107;
        }
        
        .data-card.approved {
            border-left: 5px solid #28a745;
        }
        
        .data-card.rejected {
            border-left: 5px solid #dc3545;
        }
        
        .badge-pending {
            background: linear-gradient(135deg, #ffc107, #ff9800);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .badge-approved {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .badge-rejected {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .info-label {
            font-weight: 600;
            color: #555;
        }
        
        .info-value {
            color: #333;
        }
        
        .btn-approve {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            color: white;
            font-weight: 600;
            padding: 10px 25px;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        
        .btn-approve:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
            color: white;
        }
        
        .btn-reject {
            background: linear-gradient(135deg, #dc3545, #c82333);
            border: none;
            color: white;
            font-weight: 600;
            padding: 10px 25px;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        
        .btn-reject:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
            color: white;
        }
        
        .nav-tabs {
            border: none;
            margin-bottom: 0;
            padding: 10px;
            gap: 8px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
        }
        
        .nav-tabs .nav-link {
            border: none;
            border-radius: 12px;
            font-weight: 600;
            color: #5f6b7a;
            padding: 12px 18px;
            margin-right: 0;
            transition: all 0.2s ease;
        }

        .nav-tabs .nav-link:hover {
            background: #edf5ff;
            color: #0056b3;
        }
        
        .nav-tabs .nav-link.active {
            background: linear-gradient(135deg, #ffffff, #f1f8ff);
            color: #0056b3;
            box-shadow: 0 6px 16px rgba(0, 86, 179, 0.12);
        }

        .tab-content {
            margin-top: 18px;
        }

        .tab-pane {
            background: rgba(255, 255, 255, 0.78);
            border-radius: 18px;
            padding: 20px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
        }
        
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
            cursor: pointer;
        }
        
        .no-transaksi {
            font-family: 'Raleway', sans-serif;
            font-weight: 800;
            font-size: 1.3rem;
            color: #0056b3;
        }

        .alert {
            border: none;
            border-radius: 14px;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.05);
        }

        .table {
            overflow: hidden;
            border-radius: 14px;
        }

        .table thead th {
            background: #f0f6ff;
            border-bottom-width: 1px;
            color: #214a7a;
        }

        .search-toolbar {
            background: #f8fbff;
            border: 1px solid #d9e9ff;
            border-radius: 16px;
            padding: 14px 16px;
            margin-bottom: 16px;
        }

        .table-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
        }

        .detail-item {
            background: #f8fbff;
            border: 1px solid #e3eefc;
            border-radius: 12px;
            padding: 12px 14px;
        }

        .detail-label {
            display: block;
            font-size: 0.82rem;
            font-weight: 700;
            color: #537096;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .detail-value {
            color: #1f2a37;
            word-break: break-word;
        }

        @media (max-width: 768px) {
            .header {
                border-radius: 0 0 18px 18px;
                padding: 24px 0;
            }

            .header-actions,
            .quick-action-group {
                width: 100%;
            }

            .dashboard-intro {
                padding: 14px 16px;
            }

            .quick-chip {
                width: 100%;
                text-align: center;
            }

            .tab-pane,
            .data-card {
                padding: 16px;
            }

            .info-row {
                flex-direction: column;
                gap: 4px;
            }
        }

        /* Ensure modals appear above transformed/animated elements (stacking context fixes) */
        .modal {
            z-index: 20000 !important;
        }
        .modal-backdrop.show {
            z-index: 19999 !important;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="header-wrap">
                <div>
                    <h1>🔍 Panel Verifikasi Admin</h1>
                    <p class="header-subtitle">Kelola pengajuan gadai, pelunasan, perpanjangan, reminder WhatsApp, dan transaksi dengan tampilan yang lebih nyaman dibaca.</p>
                </div>
                <div class="header-actions">
                    <span class="quick-chip">📅 <?php echo date('d M Y'); ?></span>
                    <button type="button" class="quick-chip light" data-bs-toggle="modal" data-bs-target="#addGadaiModal">➕ Input Gadai</button>
                    <button type="button" class="quick-chip" data-bs-toggle="tab" data-bs-target="#pending">⏳ Lihat Pending</button>
                    <button type="button" class="quick-chip" data-bs-toggle="tab" data-bs-target="#profit">📈 Cek Profit</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['total']; ?></div>
                    <div class="stats-label">📊 Total Pengajuan</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="border-left-color: #ffc107;">
                    <div class="stats-number" style="color: #ff9800;"><?php echo $stats['pending']; ?></div>
                    <div class="stats-label">⏳ Menunggu Verifikasi</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="border-left-color: #28a745;">
                    <div class="stats-number" style="color: #28a745;"><?php echo $stats['approved']; ?></div>
                    <div class="stats-label">✅ Disetujui</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="border-left-color: #dc3545;">
                    <div class="stats-number" style="color: #dc3545;"><?php echo $stats['rejected']; ?></div>
                    <div class="stats-label">❌ Ditolak</div>
                </div>
            </div>
        </div>
        
        <div class="dashboard-intro">
            <div>
                <h5>✨ Menu cepat admin</h5>
                <p>Pilih tab sesuai pekerjaan: verifikasi baru, cek bukti bayar, kirim reminder, atau lihat daftar gadai lengkap.</p>
            </div>
            <div class="quick-action-group">
                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill" data-bs-toggle="tab" data-bs-target="#approved">Gadai Aktif</button>
                <button type="button" class="btn btn-sm btn-outline-success rounded-pill" data-bs-toggle="tab" data-bs-target="#pelunasan">Pelunasan</button>
                <button type="button" class="btn btn-sm btn-outline-warning rounded-pill" data-bs-toggle="tab" data-bs-target="#reminder">Reminder</button>
                <button type="button" class="btn btn-sm btn-outline-dark rounded-pill" data-bs-toggle="tab" data-bs-target="#list">Daftar Lengkap</button>
            </div>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button">
                    ⏳ Menunggu Verifikasi (<?php echo count($pending_data); ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="approved-tab" data-bs-toggle="tab" data-bs-target="#approved" type="button">
                    ✅ Disetujui / Aktif (<?php echo count($approved_data); ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="rejected-tab" data-bs-toggle="tab" data-bs-target="#rejected" type="button">
                    ❌ Ditolak (<?php echo count($rejected_data); ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pelunasan-tab" data-bs-toggle="tab" data-bs-target="#pelunasan" type="button">
                    💰 Pelunasan Pending (<?php echo count($pelunasan_data); ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="perpanjangan-tab" data-bs-toggle="tab" data-bs-target="#perpanjangan" type="button">
                    🔁 Perpanjangan Pending (<?php echo count($perpanjangan_data); ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="reminder-tab" data-bs-toggle="tab" data-bs-target="#reminder" type="button">
                    ⏰ Reminder Manual (<?php echo count($reminder_data); ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="sale-tab" data-bs-toggle="tab" data-bs-target="#sale" type="button">
                    🏷️ Penjualan Barang (<?php echo count($sale_data); ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="transaksi-tab" data-bs-toggle="tab" data-bs-target="#transaksi" type="button">
                    🧾 Transaksi (<?php echo !empty($transaksi_data) ? count($transaksi_data) : 0; ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="profit-tab" data-bs-toggle="tab" data-bs-target="#profit" type="button">
                    📈 Keuntungan Bulanan
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="list-tab" data-bs-toggle="tab" data-bs-target="#list" type="button">
                    📋 Daftar Gadai (<?php echo count($all_data); ?>)
                </button>
            </li>
        </ul>
        
        <div class="tab-content" id="myTabContent">
            <!-- Pending Tab -->
            <div class="tab-pane fade show active" id="pending" role="tabpanel">
                <?php if (empty($pending_data)): ?>
                    <div class="alert alert-info">Tidak ada pengajuan yang menunggu verifikasi.</div>
                <?php else: ?>
                    <?php foreach ($pending_data as $row): ?>
                        <div class="data-card pending">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="no-transaksi">#<?php echo str_pad($row['id'], 6, '0', STR_PAD_LEFT); ?></div>
                                    <small class="text-muted"><?php echo date('d M Y H:i', strtotime($row['created_at'])); ?></small>
                                </div>
                                <span class="badge-pending">⏳ PENDING</span>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h5>👤 Data Nasabah</h5>
                                    <div class="info-row">
                                        <span class="info-label">Nama:</span>
                                        <span class="info-value"><?php echo htmlspecialchars($row['nama']); ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">No. KTP:</span>
                                        <span class="info-value"><?php echo $row['nik']; ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">No. HP:</span>
                                        <span class="info-value"><?php echo $row['no_wa']; ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Alamat:</span>
                                        <span class="info-value"><?php echo htmlspecialchars($row['alamat']); ?></span>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <h5>📱 Data Barang</h5>
                                    <div class="info-row">
                                        <span class="info-label">Jenis:</span>
                                        <span class="info-value"><?php echo $row['jenis_barang']; ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Merk & Tipe:</span>
                                        <span class="info-value"><?php echo $row['merk_barang'] . ' ' . $row['spesifikasi_barang']; ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Kondisi:</span>
                                        <span class="info-value"><?php echo $row['kondisi_barang']; ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Spesifikasi:</span>
                                        <span class="info-value"><?php echo $row['spesifikasi_barang']; ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h5>💰 Data Pinjaman</h5>
                                    <div class="info-row">
                                        <span class="info-label">Harga Pasar:</span>
                                        <span class="info-value">Rp <?php echo number_format($row['nilai_taksiran'] ?? 0, 0, ',', '.'); ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Pinjaman:</span>
                                        <span class="info-value"><strong>Rp <?php echo number_format($row['jumlah_pinjaman'], 0, ',', '.'); ?></strong></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Bunga:</span>
                                        <span class="info-value"><?php echo $row['bunga']; ?>% per bulan</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Durasi:</span>
                                        <span class="info-value"><?php echo $row['lama_gadai']; ?> bulan</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Jatuh Tempo:</span>
                                        <span class="info-value text-danger"><strong><?php echo date('d M Y', strtotime($row['tanggal_jatuh_tempo'])); ?></strong></span>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <h5>📸 Foto</h5>
                                    <div class="d-flex gap-3">
                                        <?php if ($row['foto_ktp']): ?>
                                            <div>
                                                <p class="mb-1"><strong>KTP:</strong></p>
                                                <img src="<?php echo $row['foto_ktp']; ?>" class="image-preview" alt="KTP" onclick="window.open(this.src)">
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($row['foto_barang']): ?>
                                            <div>
                                                <p class="mb-1"><strong>Barang:</strong></p>
                                                <img src="<?php echo $row['foto_barang']; ?>" class="image-preview" alt="Barang" onclick="window.open(this.src)">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex gap-2 justify-content-end">
                                <button type="button" class="btn btn-approve" data-bs-toggle="modal" data-bs-target="#approveModal<?php echo $row['id']; ?>">
                                    ✅ Setujui
                                </button>
                                
                                <button type="button" class="btn btn-reject" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $row['id']; ?>">
                                    ❌ Tolak
                                </button>
                            </div>
                        </div>
                        
                        <!-- Approve Modal -->
                        <div class="modal fade" id="approveModal<?php echo $row['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header" style="background: linear-gradient(135deg, #28a745, #20c997); color: white;">
                                        <h5 class="modal-title">✅ Setujui Pengajuan</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            
                                            <div class="alert alert-info">
                                                <strong>💰 Nominal yang Diajukan:</strong><br>
                                                <h4 class="mb-0 mt-2">Rp <?php echo number_format($row['jumlah_pinjaman'], 0, ',', '.'); ?></h4>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label"><strong>Harga Pasar (Estimasi oleh Admin):</strong> <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text">Rp</span>
                                                    <input type="number" class="form-control" id="harga_pasar_<?php echo $row['id']; ?>" name="harga_pasar" required 
                                                           value="<?php echo isset($row['nilai_taksiran']) ? $row['nilai_taksiran'] : ''; ?>" 
                                                           min="0" step="0.01"
                                                           placeholder="Masukkan estimasi harga pasar">
                                                </div>
                                                <small class="text-muted">Masukkan estimasi harga pasar; gunakan opsi persentase di bawah untuk menentukan nominal yang disetujui.</small>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label"><strong>Opsi Persentase Disetujui:</strong></label>
                                                <div class="d-flex gap-2 align-items-center">
                                                    <div class="form-check">
                                                        <input class="form-check-input percent-option" type="radio" name="percent_option" id="percent50_<?php echo $row['id']; ?>" value="50" checked>
                                                        <label class="form-check-label" for="percent50_<?php echo $row['id']; ?>">50%</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input percent-option" type="radio" name="percent_option" id="percent60_<?php echo $row['id']; ?>" value="60">
                                                        <label class="form-check-label" for="percent60_<?php echo $row['id']; ?>">60%</label>
                                                    </div>
                                                    <div class="form-check d-flex align-items-center">
                                                        <input class="form-check-input percent-option" type="radio" name="percent_option" id="percentCustom_<?php echo $row['id']; ?>" value="custom">
                                                        <label class="form-check-label me-2" for="percentCustom_<?php echo $row['id']; ?>">Custom</label>
                                                        <input type="number" class="form-control" id="custom_percent_<?php echo $row['id']; ?>" name="custom_percent" placeholder="%" min="1" max="100" style="width:100px; display:none;">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label"><strong>Nominal yang Disetujui:</strong> <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text">Rp</span>
                                                    <input type="number" class="form-control" id="jumlah_disetujui_<?php echo $row['id']; ?>" name="jumlah_disetujui" required 
                                                           value="<?php echo $row['jumlah_pinjaman']; ?>" 
                                                           min="100000" 
                                                           placeholder="Nominal yang disetujui" readonly>
                                                </div>
                                                <small class="text-muted">Nominal terisi otomatis sesuai persentase. Untuk custom, masukkan persen di field Custom.</small>
                                            </div>

                                            <!-- BRIVA generation removed per request -->

                                            <div class="mb-3">
                                                <label class="form-label">Keterangan (Opsional):</label>
                                                <textarea class="form-control" name="keterangan_admin" rows="3" placeholder="Catatan untuk nasabah (jika ada penyesuaian nominal, dll)"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-success">✅ Setujui Pengajuan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <script>
                            (function() {
                                var hargaInput = document.getElementById('harga_pasar_<?php echo $row['id']; ?>');
                                var jumlahInput = document.getElementById('jumlah_disetujui_<?php echo $row['id']; ?>');
                                var percentRadios = document.querySelectorAll('#approveModal<?php echo $row['id']; ?> input[name="percent_option"]');
                                var customPercent = document.getElementById('custom_percent_<?php echo $row['id']; ?>');
                                var generateBriva = document.getElementById('generate_briva_<?php echo $row['id']; ?>');

                                function getSelectedPercent() {
                                    // Query the checked radio inside this modal to avoid global collisions
                                    var sel = document.querySelector('#approveModal<?php echo $row['id']; ?> input[name="percent_option"]:checked');
                                    if (!sel) {
                                        // Fallback to default 50%
                                        return 50;
                                    }
                                    var selected = sel.value;
                                    if (selected === 'custom') {
                                        var v = parseFloat(customPercent.value) || 0;
                                        return v;
                                    }
                                    return parseFloat(selected) || 50;
                                }

                                function compute() {
                                    var harga = parseFloat(hargaInput.value) || 0;
                                    var pct = getSelectedPercent();
                                    var approved = Math.round(harga * (pct / 100) * 100) / 100;
                                    jumlahInput.value = approved;
                                }

                                // Show/hide custom percent input
                                percentRadios.forEach(function(radio) {
                                    radio.addEventListener('change', function() {
                                        if (this.value === 'custom') {
                                            customPercent.style.display = 'inline-block';
                                            customPercent.focus();
                                        } else {
                                            customPercent.style.display = 'none';
                                        }
                                        compute();
                                    });
                                });

                                hargaInput.addEventListener('input', compute);
                                customPercent.addEventListener('input', compute);

                                // Initialize
                                compute();
                            })();
                        </script>
                        
                        <!-- Reject Modal -->
                        <div class="modal fade" id="rejectModal<?php echo $row['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header" style="background: linear-gradient(135deg, #dc3545, #c82333); color: white;">
                                        <h5 class="modal-title">❌ Tolak Pengajuan</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <label class="form-label">Alasan Penolakan:</label>
                                            <textarea class="form-control" name="alasan_reject" rows="4" required placeholder="Masukkan alasan penolakan..."></textarea>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-danger">Tolak Pengajuan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Approved Tab -->
            <div class="tab-pane fade" id="approved" role="tabpanel">
                <div class="alert alert-info mb-3">Tab ini sekarang menampilkan <strong>semua data aktif</strong> agar konsisten dengan data pada tabel daftar gadai.</div>
                <?php if (empty($approved_data)): ?>
                    <div class="alert alert-info">Belum ada pengajuan yang disetujui.</div>
                <?php else: ?>
                    <?php foreach ($approved_data as $row): ?>
                        <?php
                        $denda_info_admin = gadai_calculate_denda($row['tanggal_jatuh_tempo'] ?? null, $row['denda_terakumulasi'] ?? 0);
                        $days_late = $denda_info_admin['days_late'];
                        $daily_penalty = $denda_info_admin['daily_rate'];
                        $denda_display = $denda_info_admin['denda'];

                        $calcAdmin = calculateGadaiBreakdown($row, $denda_display);
                        $pokok_admin = $calcAdmin['pokok'];
                        $bunga_admin = $calcAdmin['bunga_pct'];
                        $lama_admin = $calcAdmin['lama'];
                        $bunga_total_admin = $calcAdmin['bunga_total'];
                        $admin_fee = $calcAdmin['admin_fee'];
                        $biaya_asuransi = $calcAdmin['biaya_asuransi'];
                        $total_tebus_admin = !empty($row['total_tebus']) ? (float)$row['total_tebus'] : (float)$calcAdmin['total_tebus'];
                        $biaya_perpanjangan_manual = (float)$calcAdmin['biaya_perpanjangan'];
                        $tanggal_jt_baru_preview = !empty($row['tanggal_jatuh_tempo']) ? date('d M Y', strtotime($row['tanggal_jatuh_tempo'] . ' +30 days')) : date('d M Y', strtotime('+30 days'));
                        ?>
                        <div class="data-card approved">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="no-transaksi">#<?php echo str_pad($row['id'], 6, '0', STR_PAD_LEFT); ?></div>
                                    <small class="text-muted"><?php echo ($row['status'] === 'Diperpanjang' ? 'Diperpanjang' : 'Disetujui'); ?>: <?php echo !empty($row['verified_at']) ? date('d M Y H:i', strtotime($row['verified_at'])) : date('d M Y H:i', strtotime($row['created_at'])); ?></small>
                                </div>
                                <span class="badge-approved"><?php echo $row['status'] === 'Diperpanjang' ? '🔁 DIPERPANJANG' : '✅ DISETUJUI'; ?></span>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <strong><?php echo htmlspecialchars($row['nama']); ?></strong><br>
                                    <small><?php echo $row['jenis_barang']; ?>: <?php echo $row['merk_barang'] . ' ' . $row['spesifikasi_barang']; ?></small>
                                </div>
                                <div class="col-md-5">
                                    <?php if ($row['jumlah_disetujui']): ?>
                                        <small class="text-muted">Diajukan: <del>Rp <?php echo number_format($row['jumlah_pinjaman'], 0, ',', '.'); ?></del></small><br>
                                        <strong class="text-success">Disetujui: Rp <?php echo number_format($row['jumlah_disetujui'], 0, ',', '.'); ?></strong>
                                        <?php if ($row['jumlah_disetujui'] != $row['jumlah_pinjaman']): ?>
                                            <span class="badge bg-warning text-dark ms-1">Disesuaikan</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <strong>Pinjaman: Rp <?php echo number_format($row['jumlah_pinjaman'], 0, ',', '.'); ?></strong>
                                    <?php endif; ?>
                                    <br>
                                    <small>Jatuh tempo: <?php echo date('d M Y', strtotime($row['tanggal_jatuh_tempo'])); ?></small>
                                    
                                    <!-- Detail Perhitungan Total Tebus -->
                                    <div class="mt-2 p-2" style="background-color: #f8f9fa; border-radius: 8px; border-left: 3px solid #28a745;">
                                        <small><strong>📊 Detail Perhitungan:</strong></small><br>
                                        <small>
                                            • Pokok: <strong>Rp <?php echo number_format($pokok_admin, 0, ',', '.'); ?></strong><br>
                                            • Bunga (<?php echo $bunga_admin; ?>% × <?php echo $lama_admin; ?> bulan): <strong>Rp <?php echo number_format($bunga_total_admin, 0, ',', '.'); ?></strong><br>
                                            • Admin Fee (1%): <strong>Rp <?php echo number_format($admin_fee, 0, ',', '.'); ?></strong><br>
                                            • Biaya Asuransi: <strong>Rp <?php echo number_format($biaya_asuransi, 0, ',', '.'); ?></strong><br>
                                            <?php if ($denda_display > 0): ?>
                                            • Denda Keterlambatan (<?php echo $days_late; ?> hari): <strong class="text-danger">Rp <?php echo number_format($denda_display, 0, ',', '.'); ?></strong><br>
                                            <?php endif; ?>
                                            <hr style="margin: 5px 0;">
                                            • <strong>Total Tebus: <span class="text-success">Rp <?php echo number_format($total_tebus_admin, 0, ',', '.'); ?></span></strong>
                                        </small>
                                    </div>
                                    
                                    <?php if ($days_late > 0 && $days_late < 8): ?>
                                        <div class="mt-2">
                                            <small class="text-muted">Telat: <?php echo $days_late; ?> hari &middot; Tarif: Rp <?php echo number_format($daily_penalty, 0, ',', '.'); ?>/hari (max 7 hari)</small>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($days_late >= 8 || $row['status'] === 'Gagal Tebus'): ?>
                                        <div class="alert alert-danger mt-2 mb-0" style="padding:8px; font-size:0.9rem;">
                                            <strong>Gagal Tebus:</strong> item telah melewati batas maksimal pelunasan (lebih dari 7 hari terlambat). Sistem akan (atau telah) mengubah status dan mengirim notifikasi WhatsApp.
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-3">
                                    <small>No. HP: <?php echo $row['no_wa']; ?></small>
                                </div>
                            </div>
                            
                            <?php if ($row['catatan_admin']): ?>
                                <div class="alert alert-info mt-3 mb-0" style="padding: 10px; font-size: 0.9rem;">
                                    <strong>📝 Catatan Admin:</strong> <?php echo nl2br(htmlspecialchars($row['catatan_admin'])); ?>
                                </div>
                            <?php endif; ?>
                            <div class="d-flex gap-2 justify-content-end mt-3 flex-wrap">
                                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#perpanjangManualModal<?php echo $row['id']; ?>">
                                    🔁 Perpanjang Manual
                                </button>
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#lunaskanModal<?php echo $row['id']; ?>">
                                    💸 Lunaskan Sekarang
                                </button>
                            </div>

                            <!-- Modal: Perpanjang Manual -->
                            <div class="modal fade" id="perpanjangManualModal<?php echo $row['id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header" style="background: linear-gradient(135deg, #f39c12, #f1c40f); color: #212529;">
                                            <h5 class="modal-title">🔁 Konfirmasi Perpanjangan Manual</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                <input type="hidden" name="action" value="manual_perpanjang">

                                                <div class="alert alert-warning mb-3">
                                                    <strong>Pastikan pembayaran perpanjangan sudah diterima.</strong><br>
                                                    Proses ini akan menambah masa gadai <strong>30 hari</strong> dan mencatat transaksi admin.
                                                </div>

                                                <div class="p-3 mb-3" style="background:#fff8e1; border:1px solid #f6d365; border-radius:10px;">
                                                    <div><strong>Nasabah:</strong> <?php echo htmlspecialchars($row['nama']); ?></div>
                                                    <div><strong>Barang:</strong> <?php echo htmlspecialchars(trim(($row['jenis_barang'] ?? '') . ': ' . ($row['merk_barang'] ?? '') . ' ' . ($row['spesifikasi_barang'] ?? ''))); ?></div>
                                                    <div><strong>Perpanjangan ke:</strong> <?php echo (int)($row['perpanjangan_ke'] ?? 0) + 1; ?></div>
                                                    <hr style="margin:8px 0;">
                                                    <div><strong>Jatuh tempo saat ini:</strong> <?php echo date('d M Y', strtotime($row['tanggal_jatuh_tempo'])); ?></div>
                                                    <div><strong>Jatuh tempo baru:</strong> <?php echo $tanggal_jt_baru_preview; ?></div>
                                                    <div class="mt-2"><strong>Biaya perpanjangan:</strong> Rp <?php echo number_format($biaya_perpanjangan_manual, 0, ',', '.'); ?></div>
                                                    <small class="text-muted">Bunga + Admin 1% + Asuransi + Denda (jika ada)</small>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Metode Pembayaran</label>
                                                    <select name="metode" class="form-control">
                                                        <option value="tunai_admin">Tunai - Admin</option>
                                                        <option value="transfer_admin">Transfer - Admin</option>
                                                        <option value="manual_admin">Input Manual - Admin</option>
                                                    </select>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Catatan Admin (opsional)</label>
                                                    <textarea name="keterangan_admin" class="form-control" rows="2" placeholder="Contoh: nasabah datang langsung ke toko dan sudah bayar biaya perpanjangan."></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-warning">✅ Ya, Perpanjang Sekarang</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal: Lunaskan Sekarang -->
                            <div class="modal fade" id="lunaskanModal<?php echo $row['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header" style="background: linear-gradient(135deg, #28a745, #20c997); color: white;">
                                            <h5 class="modal-title">💸 Konfirmasi Pelunasan Manual</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                <input type="hidden" name="action" value="manual_lunas">
                                                <p>Anda akan menandai pengajuan ini sebagai <strong>lunas</strong>. Estimasi total tebus:</p>
                                                <p><strong>Rp <?php echo number_format($total_tebus_admin, 0, ',', '.'); ?></strong></p>

                                                <div class="mb-3">
                                                    <label class="form-label">Metode Pembayaran (opsional)</label>
                                                    <select name="metode" class="form-control">
                                                        <option value="tunai_admin">Tunai - Admin</option>
                                                        <option value="transfer_admin">Transfer - Admin</option>
                                                    </select>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Catatan Admin (opsional)</label>
                                                    <textarea name="keterangan_admin" class="form-control" rows="2"></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-approve">✅ Lunaskan Sekarang</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Rejected Tab -->
            <div class="tab-pane fade" id="rejected" role="tabpanel">
                <?php if (empty($rejected_data)): ?>
                    <div class="alert alert-info">Belum ada pengajuan yang ditolak.</div>
                <?php else: ?>
                    <?php foreach ($rejected_data as $row): ?>
                        <div class="data-card rejected">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="no-transaksi">#<?php echo str_pad($row['id'], 6, '0', STR_PAD_LEFT); ?></div>
                                    <small class="text-muted">Ditolak: <?php echo !empty($row['verified_at']) ? date('d M Y H:i', strtotime($row['verified_at'])) : date('d M Y H:i', strtotime($row['created_at'])); ?></small>
                                </div>
                                <span class="badge-rejected">❌ DITOLAK</span>
                            </div>
                            
                            <div class="row mb-2">
                                <div class="col-md-4">
                                    <strong><?php echo htmlspecialchars($row['nama']); ?></strong><br>
                                    <small><?php echo $row['jenis_barang']; ?>: <?php echo $row['merk_barang'] . ' ' . $row['spesifikasi_barang']; ?></small>
                                </div>
                                <div class="col-md-4">
                                    <strong>Pinjaman: Rp <?php echo number_format($row['jumlah_pinjaman'], 0, ',', '.'); ?></strong>
                                </div>
                                <div class="col-md-4">
                                    <small>No. HP: <?php echo $row['no_wa']; ?></small>
                                </div>
                            </div>
                            
                            <?php if ($row['alasan_penolakan']): ?>
                                <div class="alert alert-danger mb-0 mt-2">
                                    <strong>Alasan Penolakan:</strong> <?php echo htmlspecialchars($row['alasan_penolakan']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Pelunasan Pending Tab -->
            <div class="tab-pane fade" id="pelunasan" role="tabpanel">
                <?php if ($pelunasan_error): ?>
                    <div class="alert alert-warning"><?php echo $pelunasan_error; ?></div>
                <?php elseif (empty($pelunasan_data)): ?>
                    <div class="alert alert-info">Tidak ada pelunasan pending.</div>
                <?php else: ?>
                    <?php foreach ($pelunasan_data as $row): ?>
                        <?php
                        $bukti_count = (int)($row['bukti_count'] ?? 0);
                        $bukti_total = !empty($row['bukti_total']) ? (float)$row['bukti_total'] : 0;
                        $bukti_file = $row['bukti_latest'] ?? null;
                        $bukti_path = $bukti_file ? 'payment/' . $row['nik'] . '/' . $bukti_file : null;

                        // Compute days late & denda for pelunasan view
                        $days_late_p = 0;
                        if (!empty($row['tanggal_jatuh_tempo'])) {
                            $due_ts_p = strtotime($row['tanggal_jatuh_tempo']);
                            if ($due_ts_p !== false) {
                                $diff_days_p = floor((time() - $due_ts_p) / 86400);
                                if ($diff_days_p > 0) $days_late_p = (int)$diff_days_p;
                            }
                        }
                        $daily_penalty_p = 30000;
                        $denda_calc_p = min($days_late_p, 7) * $daily_penalty_p;
                        $denda_display_p = !empty($row['denda_terakumulasi']) ? (float)$row['denda_terakumulasi'] : $denda_calc_p;
                        
                        // Compute breakdown for display
                        $pokok_p = !empty($row['jumlah_disetujui']) ? (float)$row['jumlah_disetujui'] : (float)$row['jumlah_pinjaman'];
                        $bunga_p = (float)$row['bunga'];
                        $lama_p = (int)$row['lama_gadai'];
                        $bunga_total_p = $pokok_p * ($bunga_p / 100) * $lama_p;
                        $admin_fee_p = round($pokok_p * 0.01);
                        $biaya_asuransi_p = 10000;
                        ?>
                        <div class="data-card approved">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="no-transaksi">#<?php echo str_pad($row['id'], 6, '0', STR_PAD_LEFT); ?></div>
                                    <small class="text-muted">Permintaan: <?php echo date('d M Y H:i', strtotime($row['updated_at'])); ?></small>
                                </div>
                                <span class="badge-approved">💰 PELUNASAN</span>
                            </div>

                            <div class="row mb-2">
                                <div class="col-md-4">
                                    <strong><?php echo htmlspecialchars($row['nama']); ?></strong><br>
                                    <small><?php echo $row['jenis_barang']; ?>: <?php echo $row['merk_barang'] . ' ' . $row['spesifikasi_barang']; ?></small>
                                </div>
                                <div class="col-md-4">
                                    <small>No. KTP: <?php echo $row['nik']; ?></small><br>
                                    <small>No. HP: <?php echo $row['no_wa']; ?></small>
                                </div>
                                <div class="col-md-4">
                                    <!-- Detail Perhitungan Total Tebus -->
                                    <div class="p-2" style="background-color: #f8f9fa; border-radius: 8px; border-left: 3px solid #28a745;">
                                        <small><strong>📊 Detail Total Tebus:</strong></small><br>
                                        <small>
                                            • Pokok: <strong>Rp <?php echo number_format($pokok_p, 0, ',', '.'); ?></strong><br>
                                            • Bunga (<?php echo $bunga_p; ?>% × <?php echo $lama_p; ?> bulan): <strong>Rp <?php echo number_format($bunga_total_p, 0, ',', '.'); ?></strong><br>
                                            • Admin Fee (1%): <strong>Rp <?php echo number_format($admin_fee_p, 0, ',', '.'); ?></strong><br>
                                            • Biaya Asuransi: <strong>Rp <?php echo number_format($biaya_asuransi_p, 0, ',', '.'); ?></strong><br>
                                            <?php if ($denda_display_p > 0): ?>
                                            • Denda (<?php echo $days_late_p; ?> hari): <strong class="text-danger">Rp <?php echo number_format($denda_display_p, 0, ',', '.'); ?></strong><br>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <small class="mt-2 d-block"><strong>Bukti:</strong> <?php echo $bukti_count; ?> file (Rp <?php echo number_format($bukti_total, 0, ',', '.'); ?>)</small>
                                </div>
                            </div>

                            <?php if ($bukti_path): ?>
                                <div class="alert alert-info" style="padding: 10px;">
                                    <strong>🧾 Bukti Terakhir:</strong>
                                    <a href="<?php echo $bukti_path; ?>" target="_blank">Lihat bukti</a>
                                </div>
                            <?php endif; ?>

                            <div class="d-flex gap-2 justify-content-end">
                                <form method="POST">
                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="action" value="acc_pelunasan">
                                    <button type="submit" class="btn btn-approve" <?php echo $bukti_count <= 0 ? 'disabled' : ''; ?>>
                                        ✅ ACC Pelunasan
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Perpanjangan Pending Tab -->
            <div class="tab-pane fade" id="perpanjangan" role="tabpanel">
                <?php if ($perpanjangan_error): ?>
                    <div class="alert alert-warning"><?php echo $perpanjangan_error; ?></div>
                <?php elseif (empty($perpanjangan_data)): ?>
                    <div class="alert alert-info">Tidak ada perpanjangan pending.</div>
                <?php else: ?>
                    <?php foreach ($perpanjangan_data as $row): ?>
                        <?php
                        $bukti_count = (int)($row['bukti_count'] ?? 0);
                        $bukti_total = !empty($row['bukti_total']) ? (float)$row['bukti_total'] : 0;
                        $bukti_file = $row['bukti_latest'] ?? null;
                        $bukti_path = $bukti_file ? 'payment/' . $row['nik'] . '/' . $bukti_file : null;

                        // Compute days late & denda for perpanjangan view
                        $days_late_p = 0;
                        if (!empty($row['tanggal_jatuh_tempo'])) {
                            $due_ts_p = strtotime($row['tanggal_jatuh_tempo']);
                            if ($due_ts_p !== false) {
                                $diff_days_p = floor((time() - $due_ts_p) / 86400);
                                if ($diff_days_p > 0) $days_late_p = (int)$diff_days_p;
                            }
                        }
                        $daily_penalty_p = 30000;
                        $denda_calc_p = min($days_late_p, 7) * $daily_penalty_p;
                        $denda_display_p = !empty($row['denda_terakumulasi']) ? (float)$row['denda_terakumulasi'] : $denda_calc_p;
                        
                        // Compute breakdown for display
                        $pokok_perp = !empty($row['jumlah_disetujui']) ? (float)$row['jumlah_disetujui'] : (float)$row['jumlah_pinjaman'];
                        $bunga_perp = (float)$row['bunga'];
                        $lama_perp = (int)$row['lama_gadai'];
                        $bunga_total_perp = $pokok_perp * ($bunga_perp / 100) * $lama_perp;
                        $admin_fee_perp = round($pokok_perp * 0.01);
                        $biaya_asuransi_perp = 10000;
                        ?>
                        <div class="data-card approved">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="no-transaksi">#<?php echo str_pad($row['id'], 6, '0', STR_PAD_LEFT); ?></div>
                                    <small class="text-muted">Permintaan: <?php echo date('d M Y H:i', strtotime($row['updated_at'])); ?></small>
                                </div>
                                <span class="badge-approved">🔁 PERPANJANGAN</span>
                            </div>

                            <div class="row mb-2">
                                <div class="col-md-4">
                                    <strong><?php echo htmlspecialchars($row['nama']); ?></strong><br>
                                    <small><?php echo $row['jenis_barang']; ?>: <?php echo $row['merk_barang'] . ' ' . $row['spesifikasi_barang']; ?></small>
                                </div>
                                <div class="col-md-4">
                                    <small>No. KTP: <?php echo $row['nik']; ?></small><br>
                                    <small>No. HP: <?php echo $row['no_wa']; ?></small>
                                </div>
                                <div class="col-md-4">
                                    <!-- Detail Perhitungan -->  
                                    <div class="p-2 mb-2" style="background-color: #f8f9fa; border-radius: 8px; border-left: 3px solid #20c997;">
                                        <small><strong>📊 Info Gadai:</strong></small><br>
                                        <small>
                                            • Pokok: <strong>Rp <?php echo number_format($pokok_perp, 0, ',', '.'); ?></strong><br>
                                            • Bunga: <?php echo $bunga_perp; ?>% × <?php echo $lama_perp; ?> bulan<br>
                                            • Admin + Asuransi: Rp <?php echo number_format($admin_fee_perp + $biaya_asuransi_perp, 0, ',', '.'); ?><br>
                                            <?php if ($denda_display_p > 0): ?>
                                            • Denda (<?php echo $days_late_p; ?> hari): <strong class="text-danger">Rp <?php echo number_format($denda_display_p, 0, ',', '.'); ?></strong><br>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <small><strong>Bukti:</strong> <?php echo $bukti_count; ?> file (Rp <?php echo number_format($bukti_total, 0, ',', '.'); ?>)</small>
                                </div>
                            </div>

                            <?php if ($bukti_path): ?>
                                <div class="alert alert-info" style="padding: 10px;">
                                    <strong>🧾 Bukti Terakhir:</strong>
                                    <a href="<?php echo $bukti_path; ?>" target="_blank">Lihat bukti</a>
                                </div>
                            <?php endif; ?>

                            <div class="d-flex gap-2 justify-content-end">
                                <form method="POST">
                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="action" value="acc_perpanjangan">
                                    <button type="submit" class="btn btn-approve" <?php echo $bukti_count <= 0 ? 'disabled' : ''; ?>>
                                        ✅ ACC Perpanjangan
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Reminder Manual Tab (Overdue) -->
            <div class="tab-pane fade" id="reminder" role="tabpanel">
                <?php if ($reminder_error): ?>
                    <div class="alert alert-warning"><?php echo $reminder_error; ?></div>
                <?php elseif (empty($reminder_data)): ?>
                    <div class="alert alert-info">Tidak ada data lewat jatuh tempo.</div>
                <?php else: ?>
                    <div class="table-responsive mt-3">
                        <table class="table table-striped table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama</th>
                                    <th>NIK</th>
                                    <th>No. HP</th>
                                    <th>Barang</th>
                                    <th>Jatuh Tempo</th>
                                    <th>Telat (hari)</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reminder_data as $r): ?>
                                    <?php
                                        $days_overdue = isset($r['days_overdue']) ? (int)$r['days_overdue'] : 0;
                                        if ($days_overdue < 0) $days_overdue = 0;
                                        $barang_label = trim(($r['jenis_barang'] ?? '') . ': ' . ($r['merk_barang'] ?? '') . ' ' . ($r['spesifikasi_barang'] ?? ''));
                                        $barang_label = trim(preg_replace('/\s+/', ' ', $barang_label));
                                    ?>
                                    <tr>
                                        <td>#<?php echo str_pad($r['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo htmlspecialchars($r['nama'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($r['nik'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($r['no_wa'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($barang_label !== '' ? $barang_label : ('ID: ' . ($r['id'] ?? ''))); ?></td>
                                        <td><?php echo !empty($r['tanggal_jatuh_tempo']) ? date('d M Y', strtotime($r['tanggal_jatuh_tempo'])) : '-'; ?></td>
                                        <td><span class="badge bg-danger"><?php echo (int)$days_overdue; ?></span></td>
                                        <td><?php echo htmlspecialchars($r['status'] ?? ''); ?></td>
                                        <td>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Kirim reminder WhatsApp untuk #' + <?php echo json_encode(str_pad($r['id'], 6, '0', STR_PAD_LEFT)); ?> + ' (telat ' + <?php echo json_encode((string)$days_overdue); ?> + ' hari)?');">
                                                <input type="hidden" name="action" value="manual_reminder_overdue">
                                                <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-warning">Kirim Reminder</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Penjualan Barang Tab -->
            <div class="tab-pane fade" id="sale" role="tabpanel">
                <?php if ($sale_error): ?>
                    <div class="alert alert-warning"><?php echo htmlspecialchars($sale_error); ?></div>
                <?php elseif (empty($sale_data)): ?>
                    <div class="alert alert-info">Belum ada barang dalam alur penjualan internal.</div>
                <?php else: ?>
                    <?php foreach ($sale_data as $row): ?>
                        <?php
                            $pokok_sale = gadai_get_pokok($row);
                            $harga_jual_terakhir = isset($row['harga_jual_terakhir']) && $row['harga_jual_terakhir'] !== null ? (float)$row['harga_jual_terakhir'] : null;
                            $hasil_sale = $harga_jual_terakhir !== null ? ($harga_jual_terakhir - $pokok_sale) : null;
                            $barangSale = trim(($row['jenis_barang'] ?? '') . ': ' . ($row['merk_barang'] ?? '') . ' ' . ($row['spesifikasi_barang'] ?? ''));
                            $barangSale = trim(preg_replace('/\s+/', ' ', $barangSale));
                        ?>
                        <div class="data-card <?php echo in_array(($row['status'] ?? ''), ['Terjual', 'Barang Dijual'], true) ? 'approved' : 'rejected'; ?>">
                            <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                                <div>
                                    <div class="no-transaksi">#<?php echo str_pad($row['id'], 6, '0', STR_PAD_LEFT); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($row['nama'] ?? ''); ?> • <?php echo htmlspecialchars($barangSale !== '' ? $barangSale : '-'); ?></small>
                                </div>
                                <?php if (($row['status'] ?? '') === 'Gagal Tebus'): ?>
                                    <span class="badge-rejected">⚠️ GAGAL TEBUS</span>
                                <?php elseif (($row['status'] ?? '') === 'Siap Dijual'): ?>
                                    <span class="badge bg-warning text-dark">🏷️ SIAP DIJUAL</span>
                                <?php else: ?>
                                    <span class="badge-approved">✅ TERJUAL</span>
                                <?php endif; ?>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="info-row"><span class="info-label">Nasabah</span><span class="info-value"><?php echo htmlspecialchars($row['nama'] ?? '-'); ?></span></div>
                                    <div class="info-row"><span class="info-label">No. HP</span><span class="info-value"><?php echo htmlspecialchars($row['no_wa'] ?? '-'); ?></span></div>
                                    <div class="info-row"><span class="info-label">Barang</span><span class="info-value"><?php echo htmlspecialchars($barangSale !== '' ? $barangSale : '-'); ?></span></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-row"><span class="info-label">Pokok</span><span class="info-value"><strong>Rp <?php echo number_format($pokok_sale, 0, ',', '.'); ?></strong></span></div>
                                    <div class="info-row"><span class="info-label">Total Tebus</span><span class="info-value">Rp <?php echo number_format((float)($row['total_tebus'] ?? 0), 0, ',', '.'); ?></span></div>
                                    <div class="info-row"><span class="info-label">Status</span><span class="info-value"><?php echo htmlspecialchars($row['status'] ?? '-'); ?></span></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-row"><span class="info-label">Harga Jual</span><span class="info-value"><?php echo $harga_jual_terakhir !== null ? ('Rp ' . number_format($harga_jual_terakhir, 0, ',', '.')) : '-'; ?></span></div>
                                    <div class="info-row"><span class="info-label">Hasil</span><span class="info-value <?php echo $hasil_sale !== null && $hasil_sale < 0 ? 'text-danger' : 'text-success'; ?>"><?php echo $hasil_sale !== null ? ('Rp ' . number_format($hasil_sale, 0, ',', '.')) : '-'; ?></span></div>
                                    <div class="info-row"><span class="info-label">Tanggal</span><span class="info-value"><?php echo !empty($row['tanggal_terjual']) ? date('d M Y H:i', strtotime($row['tanggal_terjual'])) : '-'; ?></span></div>
                                </div>
                            </div>

                            <?php if (!empty($row['catatan_admin'])): ?>
                                <div class="alert alert-info mt-3 mb-0" style="padding:10px; font-size:0.92rem;">
                                    <strong>📝 Catatan Internal:</strong> <?php echo nl2br(htmlspecialchars($row['catatan_admin'])); ?>
                                </div>
                            <?php endif; ?>

                            <div class="d-flex gap-2 justify-content-end mt-3 flex-wrap">
                                <?php if (($row['status'] ?? '') === 'Gagal Tebus'): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Pindahkan barang #' + <?php echo json_encode(str_pad($row['id'], 6, '0', STR_PAD_LEFT)); ?> + ' ke status Siap Dijual?');">
                                        <input type="hidden" name="action" value="mark_siap_jual">
                                        <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                                        <button type="submit" class="btn btn-warning">🏷️ Masukkan ke Penjualan</button>
                                    </form>
                                <?php endif; ?>

                                <?php if (in_array(($row['status'] ?? ''), ['Gagal Tebus', 'Siap Dijual'], true)): ?>
                                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#saleModal<?php echo $row['id']; ?>">
                                        💵 Tandai Terjual
                                    </button>
                                <?php endif; ?>
                            </div>

                            <?php if (in_array(($row['status'] ?? ''), ['Gagal Tebus', 'Siap Dijual'], true)): ?>
                                <div class="modal fade" id="saleModal<?php echo $row['id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header" style="background: linear-gradient(135deg, #198754, #20c997); color: white;">
                                                <h5 class="modal-title">💵 Tandai Barang Terjual</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="action" value="mark_terjual">
                                                    <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">

                                                    <div class="alert alert-success">
                                                        Proses ini hanya mencatat penjualan internal. <strong>Tidak ada broadcast WhatsApp tambahan</strong> dari aksi ini.
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Harga Jual <span class="text-danger">*</span></label>
                                                        <div class="input-group">
                                                            <span class="input-group-text">Rp</span>
                                                            <input type="number" name="harga_jual" class="form-control" min="0" step="0.01" required>
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Metode</label>
                                                        <select name="metode" class="form-control">
                                                            <option value="penjualan_toko">Penjualan Toko</option>
                                                            <option value="penjualan_online">Penjualan Online</option>
                                                            <option value="penjualan_internal">Penjualan Internal</option>
                                                        </select>
                                                    </div>

                                                    <div class="mb-0">
                                                        <label class="form-label">Catatan Admin (opsional)</label>
                                                        <textarea name="keterangan_admin" class="form-control" rows="2" placeholder="Contoh: barang laku di etalase toko."></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-success">Simpan Penjualan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Transaksi Tab -->
            <div class="tab-pane fade" id="transaksi" role="tabpanel">
                <?php if ($transaksi_error): ?>
                    <div class="alert alert-warning"><?php echo $transaksi_error; ?></div>
                <?php elseif (empty($transaksi_data)): ?>
                    <div class="alert alert-info">Belum ada transaksi (bukti) tercatat.</div>
                <?php else: ?>
                    <?php
                        $tx_perpanjangan = array_filter($transaksi_data, function($t){ return stripos($t['keterangan'] ?? '', 'perpanjangan') !== false; });
                        $tx_pelunasan = array_filter($transaksi_data, function($t){ return stripos($t['keterangan'] ?? '', 'pelunasan') !== false; });
                    ?>

                    <h5>🔁 Perpanjangan (<?php echo count($tx_perpanjangan); ?>)</h5>
                    <?php if (empty($tx_perpanjangan)): ?>
                        <div class="alert alert-info">Tidak ada bukti perpanjangan.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Pelanggan NIK</th>
                                        <th>Nama</th>
                                        <th>Barang</th>
                                        <th>Serial</th>
                                        <th>Jumlah</th>
                                        <th>Metode</th>
                                        <th>Bukti</th>
                                        <th>Tanggal</th>
                                        <th>Keterangan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tx_perpanjangan as $t): ?>
                                        <?php $bukti = !empty($t['bukti']) ? 'payment/'.($t['pelanggan_nik'] ?? '').'/'.$t['bukti'] : null; ?>
                                                        <tr>
                                                            <td><?php echo $t['id']; ?></td>
                                                            <td><?php echo htmlspecialchars($t['pelanggan_nik']); ?></td>
                                                            <td><?php echo htmlspecialchars($t['nama_nasabah'] ?? ''); ?></td>
                                                            <?php
                                                                // Build a friendly item label from available data_gadai fields
                                                                $barang_label = '';
                                                                if (!empty($t['jenis_barang'])) {
                                                                    $barang_label .= $t['jenis_barang'] . ': ';
                                                                }
                                                                if (!empty($t['merk']) || !empty($t['tipe'])) {
                                                                    $barang_label .= trim(($t['merk'] ?? '') . ' ' . ($t['tipe'] ?? ''));
                                                                }
                                                                $barang_label = trim($barang_label);
                                                                if ($barang_label === '') {
                                                                    $barang_label = isset($t['barang_id']) ? 'ID: ' . $t['barang_id'] : '-';
                                                                }
                                                            ?>
                                                            <td><?php echo htmlspecialchars($barang_label); ?></td>
                                                            <td><?php echo htmlspecialchars($t['serial_number'] ?? ($t['imei'] ?? '-')); ?></td>
                                                    <td>Rp <?php echo number_format((float)$t['jumlah_bayar'],0,',','.'); ?></td>
                                                    <td><?php echo htmlspecialchars($t['metode_pembayaran'] ?? ''); ?></td>
                                                    <td><?php if ($bukti): ?><a href="<?php echo $bukti; ?>" target="_blank">Lihat Bukti</a><?php else: ?>-<?php endif; ?></td>
                                                    <td><?php echo htmlspecialchars($t['created_at'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($t['keterangan'] ?? ''); ?></td>
                                                    <td>
                                                        <?php if (!empty($t['barang_id']) && (($t['keterangan'] ?? '') === 'perpanjangan')): ?>
                                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Anda yakin ingin ACC perpanjangan untuk ' + <?php echo json_encode($barang_label); ?> + '?');">
                                                                <input type="hidden" name="action" value="acc_perpanjangan">
                                                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($t['barang_id']); ?>">
                                                                <button type="submit" class="btn btn-sm btn-primary">ACC Perpanjangan</button>
                                                            </form>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <hr>

                    <h5>💰 Pelunasan (<?php echo count($tx_pelunasan); ?>)</h5>
                    <?php if (empty($tx_pelunasan)): ?>
                        <div class="alert alert-info">Tidak ada bukti pelunasan.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Pelanggan NIK</th>
                                        <th>Nama</th>
                                        <th>Barang</th>
                                        <th>Serial</th>
                                        <th>Jumlah</th>
                                        <th>Metode</th>
                                        <th>Bukti</th>
                                        <th>Tanggal</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tx_pelunasan as $t): ?>
                                        <?php $bukti = !empty($t['bukti']) ? 'payment/'.($t['pelanggan_nik'] ?? '').'/'.$t['bukti'] : null; ?>
                                        <tr>
                                            <td><?php echo $t['id']; ?></td>
                                            <td><?php echo htmlspecialchars($t['pelanggan_nik']); ?></td>
                                            <td><?php echo htmlspecialchars($t['nama_nasabah'] ?? ''); ?></td>
                                            <?php
                                                // Friendly item label (fallback to barang_id)
                                                $barang_label = '';
                                                if (!empty($t['jenis_barang'])) {
                                                    $barang_label .= $t['jenis_barang'] . ': ';
                                                }
                                                if (!empty($t['merk']) || !empty($t['tipe'])) {
                                                    $barang_label .= trim(($t['merk'] ?? '') . ' ' . ($t['tipe'] ?? ''));
                                                }
                                                $barang_label = trim($barang_label);
                                                if ($barang_label === '') {
                                                    $barang_label = isset($t['barang_id']) ? 'ID: ' . $t['barang_id'] : '-';
                                                }
                                            ?>
                                            <td><?php echo htmlspecialchars($barang_label); ?></td>
                                            <td><?php echo htmlspecialchars($t['serial_number'] ?? ($t['imei'] ?? '-')); ?></td>
                                            <td>Rp <?php echo number_format((float)$t['jumlah_bayar'],0,',','.'); ?></td>
                                            <td><?php echo htmlspecialchars($t['metode_pembayaran'] ?? ''); ?></td>
                                            <td><?php if ($bukti): ?><a href="<?php echo $bukti; ?>" target="_blank">Lihat Bukti</a><?php else: ?>-<?php endif; ?></td>
                                            <td><?php echo htmlspecialchars($t['created_at'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($t['keterangan'] ?? ''); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Keuntungan Bulanan Tab -->
            <div class="tab-pane fade" id="profit" role="tabpanel">
                <div class="d-flex flex-wrap justify-content-between align-items-center mt-3">
                    <div>
                        <h5 class="mb-1">📈 Keuntungan Bulanan</h5>
                        <div class="text-muted" style="font-size: 0.95rem;">
                            Lunas = bunga + admin 1% + asuransi 10.000 + denda. Perpanjangan = total pembayaran perpanjangan. Penjualan = harga jual - pokok pinjaman.
                        </div>
                    </div>
                    <form class="d-flex align-items-center gap-2" method="GET">
                        <label class="form-label mb-0">Tahun</label>
                        <input type="number" name="profit_year" class="form-control" style="max-width:120px;" min="2000" max="2100" value="<?php echo (int)$profit_year; ?>">
                        <button type="submit" class="btn btn-primary">Tampilkan</button>
                    </form>
                </div>

                <?php if ($profit_error): ?>
                    <div class="alert alert-warning mt-3"><?php echo htmlspecialchars($profit_error); ?></div>
                <?php else: ?>
                    <?php
                        $bulanNama = [
                            1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
                            7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'
                        ];
                        $sumLunas = 0.0;
                        $sumPerp = 0.0;
                        $sumJual = 0.0;
                    ?>
                    <div class="table-responsive mt-3">
                        <table class="table table-striped table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Bulan</th>
                                    <th class="text-center">Lunas (qty)</th>
                                    <th class="text-end">Keuntungan Lunas</th>
                                    <th class="text-center">Perpanjangan (trx)</th>
                                    <th class="text-end">Keuntungan Perpanjangan</th>
                                    <th class="text-center">Penjualan (trx)</th>
                                    <th class="text-end">Hasil Penjualan</th>
                                    <th class="text-end">Total Keuntungan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for ($m=1; $m<=12; $m++): ?>
                                    <?php
                                        $lunasCount = (int)$profit_months[$m]['lunas_count'];
                                        $lunasProfit = (float)$profit_months[$m]['lunas_profit'];
                                        $perpCount = (int)$profit_months[$m]['perp_count'];
                                        $perpProfit = (float)$profit_months[$m]['perp_profit'];
                                        $jualCount = (int)$profit_months[$m]['jual_count'];
                                        $jualProfit = (float)$profit_months[$m]['jual_profit'];
                                        $totalProfit = $lunasProfit + $perpProfit + $jualProfit;
                                        $sumLunas += $lunasProfit;
                                        $sumPerp += $perpProfit;
                                        $sumJual += $jualProfit;
                                    ?>
                                    <tr>
                                        <td><?php echo $bulanNama[$m]; ?></td>
                                        <td class="text-center"><?php echo $lunasCount; ?></td>
                                        <td class="text-end">Rp <?php echo number_format($lunasProfit, 0, ',', '.'); ?></td>
                                        <td class="text-center"><?php echo $perpCount; ?></td>
                                        <td class="text-end">Rp <?php echo number_format($perpProfit, 0, ',', '.'); ?></td>
                                        <td class="text-center"><?php echo $jualCount; ?></td>
                                        <td class="text-end <?php echo $jualProfit < 0 ? 'text-danger' : 'text-success'; ?>">Rp <?php echo number_format($jualProfit, 0, ',', '.'); ?></td>
                                        <td class="text-end"><strong>Rp <?php echo number_format($totalProfit, 0, ',', '.'); ?></strong></td>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Total</th>
                                    <th class="text-center">-</th>
                                    <th class="text-end">Rp <?php echo number_format($sumLunas, 0, ',', '.'); ?></th>
                                    <th class="text-center">-</th>
                                    <th class="text-end">Rp <?php echo number_format($sumPerp, 0, ',', '.'); ?></th>
                                    <th class="text-center">-</th>
                                    <th class="text-end <?php echo $sumJual < 0 ? 'text-danger' : 'text-success'; ?>">Rp <?php echo number_format($sumJual, 0, ',', '.'); ?></th>
                                    <th class="text-end"><strong>Rp <?php echo number_format($sumLunas + $sumPerp + $sumJual, 0, ',', '.'); ?></strong></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- List Tab -->
            <div class="tab-pane fade" id="list" role="tabpanel">
                                <!-- CSV upload UI and instructions removed -->

                <div class="d-flex justify-content-end mt-3 mb-3">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGadaiModal">
                        ➕ Tambah Data Gadai
                    </button>
                </div>

                <!-- Modal: Input Manual Data Gadai (akan kirim notifikasi WhatsApp) -->
                <div class="modal fade" id="addGadaiModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header" style="background: linear-gradient(135deg, #0056b3, #007bff); color: white;">
                                <h5 class="modal-title">➕ Input Manual Data Gadai</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form method="POST">
                                <div class="modal-body">
                                    <p class="text-muted mb-3">Setelah disimpan, sistem akan mengirim notifikasi WhatsApp ke user sebagai bukti gadai.</p>
                                    <input type="hidden" name="action" value="manual_add_gadai">

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Nama <span class="text-danger">*</span></label>
                                            <input type="text" name="nama" class="form-control" required>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">No. WhatsApp <span class="text-danger">*</span></label>
                                            <input type="text" name="no_wa" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Jenis Barang <span class="text-danger">*</span></label>
                                            <input type="text" name="jenis_barang" class="form-control" required placeholder="Contoh: HP / Laptop">
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">Alamat <span class="text-danger">*</span></label>
                                            <textarea name="alamat" class="form-control" rows="2" required></textarea>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label">Merk Barang</label>
                                            <input type="text" name="merk_barang" class="form-control">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Spesifikasi / Tipe</label>
                                            <input type="text" name="spesifikasi_barang" class="form-control">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Kondisi</label>
                                            <select name="kondisi_barang" class="form-control">
                                                <option value="Baru">Baru</option>
                                                <option value="Bekas - Baik" selected>Bekas - Baik</option>
                                                <option value="Bekas - Cukup">Bekas - Cukup</option>
                                                <option value="Rusak Ringan">Rusak Ringan</option>
                                            </select>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">Kelengkapan Barang</label>
                                            <textarea name="kelengkapan_barang" class="form-control" rows="2" placeholder="Contoh: Dus, charger, kabel data, headset, nota, dll"></textarea>
                                            <small class="text-muted">Opsional. Akan tersimpan sebagai catatan admin.</small>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label">Nilai Taksiran</label>
                                            <input type="number" name="nilai_taksiran" class="form-control" min="0" step="0.01" placeholder="Opsional">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Jumlah Pinjaman <span class="text-danger">*</span></label>
                                            <input type="number" name="jumlah_pinjaman" class="form-control" min="0" step="0.01" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Bunga (%)</label>
                                            <input type="number" name="bunga" class="form-control" min="0" step="0.01" value="5.00">
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label">Lama Gadai</label>
                                            <input type="number" name="lama_gadai" class="form-control" min="1" step="1" value="30">
                                            <small class="text-muted">Sesuai konfigurasi sistem.</small>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Tanggal Gadai <span class="text-danger">*</span></label>
                                            <input type="date" name="tanggal_gadai" class="form-control" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Tanggal Jatuh Tempo <span class="text-danger">*</span></label>
                                            <input type="date" name="tanggal_jatuh_tempo" class="form-control" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-primary">Simpan Data</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="search-toolbar">
                    <form method="GET" class="row g-2 align-items-center">
                        <input type="hidden" name="tab" value="list">
                        <?php if (!empty($profit_year)): ?>
                            <input type="hidden" name="profit_year" value="<?php echo (int)$profit_year; ?>">
                        <?php endif; ?>
                        <div class="col-lg-6 col-md-7">
                            <input type="text" name="list_search" class="form-control" value="<?php echo htmlspecialchars($list_search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Cari nama, NIK, no HP, barang, atau status...">
                        </div>
                        <div class="col-md-auto d-flex gap-2">
                            <button type="submit" class="btn btn-primary">🔎 Cari</button>
                            <a href="admin_verifikasi.php?tab=list" class="btn btn-outline-secondary">Reset</a>
                            <a href="admin_verifikasi.php?tab=list&amp;export=excel_total_gadai<?php echo $list_search !== '' ? '&amp;list_search=' . urlencode($list_search) : ''; ?><?php echo !empty($profit_year) ? '&amp;profit_year=' . (int)$profit_year : ''; ?>" class="btn btn-success">⬇ Download Excel</a>
                        </div>
                        <div class="col text-md-end">
                            <small class="text-muted">Menampilkan <strong><?php echo count($all_data); ?></strong> data<?php echo $list_search !== '' ? ' untuk pencarian “' . htmlspecialchars($list_search, ENT_QUOTES, 'UTF-8') . '”' : ''; ?>.</small>
                        </div>
                    </form>
                </div>

                <?php if (empty($all_data)): ?>
                    <div class="alert alert-info">Belum ada data gadai<?php echo $list_search !== '' ? ' yang cocok dengan pencarian.' : '.'; ?></div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>No Hp</th>
                                    <th>Merek Hp</th>
                                    <th>Kelengkapan</th>
                                    <th>Kondisi hp</th>
                                    <th>Pengajuan / Disetujui</th>
                                    <th>Bunga</th>
                                    <th>Total Yang Balik</th>
                                    <th>Tanggal Gadai</th>
                                    <th>Tanggal Jatuh Tempo</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_data as $index => $row): ?>
                                    <?php
                                        // Determine principal: approved amount if present, otherwise requested
                                        $pengajuan_list = (float)($row['jumlah_pinjaman'] ?? 0);
                                        $disetujui_list = !empty($row['jumlah_disetujui']) ? (float)$row['jumlah_disetujui'] : null;
                                        $denda_info_list = gadai_calculate_denda($row['tanggal_jatuh_tempo'] ?? null, $row['denda_terakumulasi'] ?? 0);
                                        $calcList = calculateGadaiBreakdown($row, $denda_info_list['denda']);
                                        $pokok = $calcList['pokok'];
                                        $bunga_pct = $calcList['bunga_pct'];
                                        if (!empty($row['total_tebus']) && (float)$row['total_tebus'] > 0) {
                                            $total_kembali = (float)$row['total_tebus'];
                                        } else {
                                            $total_kembali = (float)$calcList['total_tebus'];
                                        }

                                        $barang_detail_label = trim((string)(($row['jenis_barang'] ?? '') . ': ' . ($row['merk_barang'] ?? '') . ' ' . ($row['spesifikasi_barang'] ?? '')));
                                        $barang_detail_label = trim((string)preg_replace('/\s+/', ' ', $barang_detail_label));

                                        $kelengkapan = '';
                                        if (!empty($row['catatan_admin'])) {
                                            $note = (string)$row['catatan_admin'];
                                            $prefix = 'Kelengkapan Barang:';
                                            if (stripos($note, $prefix) === 0) {
                                                $kelengkapan = trim(substr($note, strlen($prefix)));
                                            } else {
                                                $kelengkapan = $note;
                                            }
                                        }

                                        $status_value = (string)($row['status'] ?? '-');
                                        $status_badge = 'bg-secondary';
                                        if ($status_value === 'Pending') {
                                            $status_badge = 'bg-warning text-dark';
                                        } elseif (in_array($status_value, ['Disetujui', 'Diperpanjang', 'Lunas'], true)) {
                                            $status_badge = 'bg-success';
                                        } elseif ($status_value === 'Ditolak') {
                                            $status_badge = 'bg-danger';
                                        } elseif ($status_value === 'Gagal Tebus') {
                                            $status_badge = 'bg-dark';
                                        } elseif ($status_value === 'Siap Dijual') {
                                            $status_badge = 'bg-warning text-dark';
                                        } elseif (in_array($status_value, ['Terjual', 'Barang Dijual'], true)) {
                                            $status_badge = 'bg-primary';
                                        }
                                    ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($row['nama']); ?></td>
                                        <td><?php echo htmlspecialchars($row['no_wa']); ?></td>
                                        <td><?php echo htmlspecialchars($row['merk_barang']); ?></td>
                                        <td><?php echo htmlspecialchars($kelengkapan !== '' ? $kelengkapan : '-'); ?></td>
                                        <td><?php echo htmlspecialchars($row['kondisi_barang']); ?></td>
                                        <td>
                                            <?php if ($disetujui_list !== null): ?>
                                                <small class="text-muted d-block">Diajukan: Rp <?php echo number_format($pengajuan_list, 0, ',', '.'); ?></small>
                                                <strong class="text-success d-block">Disetujui: Rp <?php echo number_format($disetujui_list, 0, ',', '.'); ?></strong>
                                                <?php if (abs($disetujui_list - $pengajuan_list) > 0.009): ?>
                                                    <span class="badge bg-warning text-dark">Disesuaikan</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                Rp <?php echo number_format($pengajuan_list, 0, ',', '.'); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($bunga_pct) . '%'; ?></td>
                                        <td>Rp <?php echo number_format($total_kembali, 0, ',', '.'); ?></td>
                                        <td><?php echo $row['tanggal_gadai'] ? date('d M Y', strtotime($row['tanggal_gadai'])) : '-'; ?></td>
                                        <td><?php echo $row['tanggal_jatuh_tempo'] ? date('d M Y', strtotime($row['tanggal_jatuh_tempo'])) : '-'; ?></td>
                                        <td><span class="badge <?php echo $status_badge; ?>"><?php echo htmlspecialchars($status_value); ?></span></td>
                                        <td>
                                            <div class="table-actions">
                                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#detailGadaiModal<?php echo (int)$row['id']; ?>">Detail</button>

                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Kirim nota gadai untuk #' + <?php echo json_encode(str_pad($row['id'], 6, '0', STR_PAD_LEFT)); ?> + ' via WhatsApp?');">
                                                    <input type="hidden" name="action" value="manual_send_nota">
                                                    <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                                                    <button type="submit" name="id_btn" value="<?php echo (int)$row['id']; ?>" class="btn btn-sm btn-primary">Kirim Nota PDF</button>
                                                </form>

                                                <?php if (($row['status'] ?? '') === 'Gagal Tebus'): ?>
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Kirim notifikasi Gagal Tebus untuk #' + <?php echo json_encode(str_pad($row['id'], 6, '0', STR_PAD_LEFT)); ?> + '?');">
                                                        <input type="hidden" name="action" value="manual_notify_gagal_tebus">
                                                        <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">Kirim Notif</button>
                                                    </form>
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Pindahkan #' + <?php echo json_encode(str_pad($row['id'], 6, '0', STR_PAD_LEFT)); ?> + ' ke status Siap Dijual?');">
                                                        <input type="hidden" name="action" value="mark_siap_jual">
                                                        <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-warning">Siap Jual</button>
                                                    </form>
                                                <?php elseif (($row['status'] ?? '') === 'Siap Dijual'): ?>
                                                    <span class="badge bg-info text-dark">Lanjutkan di tab Penjualan</span>
                                                <?php endif; ?>
                                            </div>

                                            <div class="modal fade" id="detailGadaiModal<?php echo (int)$row['id']; ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header" style="background: linear-gradient(135deg, #0d6efd, #4dabf7); color: white;">
                                                            <h5 class="modal-title">📄 Detail Gadai #<?php echo str_pad((string)$row['id'], 6, '0', STR_PAD_LEFT); ?></h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="detail-grid">
                                                                <div class="detail-item"><span class="detail-label">Nama</span><div class="detail-value"><?php echo htmlspecialchars($row['nama'] ?? '-'); ?></div></div>
                                                                <div class="detail-item"><span class="detail-label">NIK</span><div class="detail-value"><?php echo htmlspecialchars($row['nik'] ?? '-'); ?></div></div>
                                                                <div class="detail-item"><span class="detail-label">No. WhatsApp</span><div class="detail-value"><?php echo htmlspecialchars($row['no_wa'] ?? '-'); ?></div></div>
                                                                <div class="detail-item"><span class="detail-label">Alamat</span><div class="detail-value"><?php echo htmlspecialchars($row['alamat'] ?? '-'); ?></div></div>
                                                                <div class="detail-item"><span class="detail-label">Barang</span><div class="detail-value"><?php echo htmlspecialchars($barang_detail_label !== '' ? $barang_detail_label : '-'); ?></div></div>
                                                                <div class="detail-item"><span class="detail-label">Kondisi</span><div class="detail-value"><?php echo htmlspecialchars($row['kondisi_barang'] ?? '-'); ?></div></div>
                                                                <div class="detail-item"><span class="detail-label">Nilai Taksiran</span><div class="detail-value">Rp <?php echo number_format((float)($row['nilai_taksiran'] ?? 0), 0, ',', '.'); ?></div></div>
                                                                <div class="detail-item"><span class="detail-label">Pinjaman Diajukan</span><div class="detail-value">Rp <?php echo number_format($pengajuan_list, 0, ',', '.'); ?></div></div>
                                                                <div class="detail-item"><span class="detail-label">Pinjaman Disetujui</span><div class="detail-value"><?php echo $disetujui_list !== null ? ('Rp ' . number_format($disetujui_list, 0, ',', '.')) : '-'; ?></div></div>
                                                                <div class="detail-item"><span class="detail-label">Bunga</span><div class="detail-value"><?php echo htmlspecialchars((string)$bunga_pct); ?>%</div></div>
                                                                <div class="detail-item"><span class="detail-label">Total Tebus</span><div class="detail-value">Rp <?php echo number_format($total_kembali, 0, ',', '.'); ?></div></div>
                                                                <div class="detail-item"><span class="detail-label">Status</span><div class="detail-value"><?php echo htmlspecialchars($status_value); ?></div></div>
                                                                <div class="detail-item"><span class="detail-label">Perpanjangan Ke</span><div class="detail-value"><?php echo (int)($row['perpanjangan_ke'] ?? 0); ?></div></div>
                                                                <div class="detail-item"><span class="detail-label">Tanggal Gadai</span><div class="detail-value"><?php echo !empty($row['tanggal_gadai']) ? date('d M Y', strtotime($row['tanggal_gadai'])) : '-'; ?></div></div>
                                                                <div class="detail-item"><span class="detail-label">Jatuh Tempo</span><div class="detail-value"><?php echo !empty($row['tanggal_jatuh_tempo']) ? date('d M Y', strtotime($row['tanggal_jatuh_tempo'])) : '-'; ?></div></div>
                                                            </div>

                                                            <div class="detail-item mt-3">
                                                                <span class="detail-label">Catatan Admin / Kelengkapan</span>
                                                                <div class="detail-value"><?php echo nl2br(htmlspecialchars($row['catatan_admin'] ?? '-')); ?></div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                        </div>
                                                    </div>
                                                </div>
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
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Move modals to document.body when opened to avoid stacking-context/z-index issues inside cards/tabs
        (function () {
            try {
                document.querySelectorAll('button[data-bs-toggle="modal"]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var targetSelector = btn.getAttribute('data-bs-target');
                        if (!targetSelector) return;

                        var modalEl = document.querySelector(targetSelector);
                        if (!modalEl) return;

                        if (modalEl.parentNode !== document.body) {
                            document.body.appendChild(modalEl);
                        }
                    });
                });
            } catch (e) {
                console.error('Error moving modals to body', e);
            }
        })();

        (function () {
            try {
                var params = new URLSearchParams(window.location.search);
                var tabName = params.get('tab');
                if (!tabName) return;

                var trigger = document.querySelector('button[data-bs-target="#' + tabName + '"]');
                if (!trigger || typeof bootstrap === 'undefined') return;

                bootstrap.Tab.getOrCreateInstance(trigger).show();
            } catch (e) {
                console.error('Error restoring active tab', e);
            }
        })();
    </script>
</body>
</html>
