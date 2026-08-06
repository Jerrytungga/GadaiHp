<?php
require_once 'database.php';
require_once 'auth_guard.php';

gadai_require_admin();

$year = (int)($_GET['year'] ?? date('Y'));
if ($year < 2000 || $year > 2100) {
    $year = (int)date('Y');
}

$monthNames = [
    1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
    7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
];
$profitMonths = array_fill(1, 12, 0.0);
$pawnMonths = array_fill(1, 12, 0);
$customerMonths = array_fill(1, 12, 0);
$chartError = null;

try {
    $tableCheck = $db->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'transaksi'");
    $tableCheck->execute();
    $hasTransactions = (int)$tableCheck->fetchColumn() > 0;

    if (!$hasTransactions) {
        $chartError = 'Grafik profit membutuhkan tabel transaksi.';
    } else {
        $profitSql = "
            SELECT mm, SUM(profit) AS total_profit
            FROM (
                SELECT MONTH(MAX(t.created_at)) AS mm,
                       ((IFNULL(NULLIF(dg.jumlah_disetujui, 0), dg.jumlah_pinjaman) * (IFNULL(dg.bunga, 0) / 100) * IFNULL(dg.lama_gadai, 0))
                        + ROUND(IFNULL(NULLIF(dg.jumlah_disetujui, 0), dg.jumlah_pinjaman) * 0.01)
                        + 10000
                        + IFNULL(dg.denda_terakumulasi, 0)) AS profit
                FROM data_gadai dg
                INNER JOIN transaksi t ON t.barang_id = dg.id
                    AND t.keterangan IN ('pelunasan', 'pelunasan_admin')
                WHERE dg.status = 'Lunas' AND YEAR(t.created_at) = ?
                GROUP BY dg.id

                UNION ALL

                SELECT MONTH(t.created_at) AS mm, IFNULL(t.jumlah_bayar, 0) AS profit
                FROM transaksi t
                WHERE YEAR(t.created_at) = ? AND t.keterangan LIKE 'perpanjangan%'

                UNION ALL

                SELECT MONTH(t.created_at) AS mm,
                       IFNULL(t.jumlah_bayar, 0) - IFNULL(NULLIF(dg.jumlah_disetujui, 0), dg.jumlah_pinjaman) AS profit
                FROM transaksi t
                INNER JOIN data_gadai dg ON dg.id = t.barang_id
                WHERE YEAR(t.created_at) = ? AND t.keterangan = 'penjualan_barang'
            ) monthly_profit
            GROUP BY mm
        ";
        $profitStmt = $db->prepare($profitSql);
        $profitStmt->execute([$year, $year, $year]);
        foreach ($profitStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $month = (int)($row['mm'] ?? 0);
            if ($month >= 1 && $month <= 12) {
                $profitMonths[$month] = (float)($row['total_profit'] ?? 0);
            }
        }
    }

    $pawnSql = "SELECT MONTH(created_at) AS mm,
        COUNT(*) AS pawn_count,
        COUNT(DISTINCT COALESCE(NULLIF(nik, ''), CONCAT('gadai-', id))) AS customer_count
        FROM data_gadai
        WHERE YEAR(created_at) = ?
        GROUP BY MONTH(created_at)";
    $pawnStmt = $db->prepare($pawnSql);
    $pawnStmt->execute([$year]);
    foreach ($pawnStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $month = (int)($row['mm'] ?? 0);
        if ($month >= 1 && $month <= 12) {
            $pawnMonths[$month] = (int)($row['pawn_count'] ?? 0);
            $customerMonths[$month] = (int)($row['customer_count'] ?? 0);
        }
    }
} catch (Throwable $e) {
    $chartError = 'Gagal memuat data grafik.';
}

$totalProfit = array_sum($profitMonths);
$totalPawns = array_sum($pawnMonths);
$totalCustomers = array_sum($customerMonths);
$labels = array_values($monthNames);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grafik Admin - Gadai Cepat Timika</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <style>
        :root { --blue: #0b5ed7; --teal: #087f5b; --orange: #d9480f; --ink: #1f2937; --paper: #f4f8fc; }
        body { background: var(--paper); color: var(--ink); font-family: 'Segoe UI', sans-serif; }
        .page-header { background: #0b5ed7; color: #fff; padding: 28px 0; }
        .page-header h1 { font-size: 1.7rem; margin: 0; }
        .summary { border: 0; border-radius: 8px; box-shadow: 0 4px 16px rgba(31, 41, 55, .08); height: 100%; }
        .summary .value { font-size: 1.4rem; font-weight: 700; }
        .chart-panel { background: #fff; border: 1px solid #dbe4ee; border-radius: 8px; padding: 20px; }
        .chart-wrap { height: 340px; position: relative; }
        @media (max-width: 767px) { .chart-wrap { height: 280px; } .page-header h1 { font-size: 1.4rem; } }
    </style>
</head>
<body>
    <header class="page-header">
        <div class="container d-flex flex-wrap gap-3 justify-content-between align-items-center">
            <div>
                <h1>Grafik Kinerja Gadai</h1>
                <div class="opacity-75">Profit, pengajuan, dan orang gadai per bulan</div>
            </div>
            <a href="admin_verifikasi.php" class="btn btn-light">Kembali ke Dashboard</a>
        </div>
    </header>

    <main class="container py-4">
        <form method="GET" class="d-flex align-items-center gap-2 mb-4">
            <label for="year" class="form-label mb-0">Tahun</label>
            <input id="year" name="year" type="number" min="2000" max="2100" value="<?php echo $year; ?>" class="form-control" style="max-width: 130px;">
            <button type="submit" class="btn btn-primary">Tampilkan</button>
        </form>

        <section class="row g-3 mb-4">
            <div class="col-md-4"><div class="summary card p-3"><div class="text-muted">Total Profit <?php echo $year; ?></div><div class="value text-primary">Rp <?php echo number_format($totalProfit, 0, ',', '.'); ?></div></div></div>
            <div class="col-md-4"><div class="summary card p-3"><div class="text-muted">Total Pengajuan <?php echo $year; ?></div><div class="value" style="color: var(--teal);"><?php echo number_format($totalPawns, 0, ',', '.'); ?></div></div></div>
            <div class="col-md-4"><div class="summary card p-3"><div class="text-muted">Orang Gadai Unik <?php echo $year; ?></div><div class="value" style="color: var(--orange);"><?php echo number_format($totalCustomers, 0, ',', '.'); ?></div></div></div>
        </section>

        <div class="alert alert-info mb-4" role="note">
            <strong>Cara membaca:</strong> pilih tahun untuk melihat data periode tersebut. Profit adalah gabungan keuntungan pelunasan, pembayaran perpanjangan, dan hasil penjualan barang. Pengajuan menghitung semua transaksi gadai, sedangkan orang gadai unik dihitung satu kali berdasarkan NIK pada setiap bulan.
        </div>

        <?php if ($chartError): ?>
            <div class="alert alert-warning"><?php echo htmlspecialchars($chartError, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <section class="row g-4">
            <div class="col-lg-7">
                <div class="chart-panel">
                    <h2 class="h5 mb-3">Profit Bulanan</h2>
                    <div class="chart-wrap"><canvas id="profitChart"></canvas></div>
                    <p class="text-muted small mb-0 mt-3">Semakin tinggi batang, semakin besar profit pada bulan tersebut. Arahkan kursor ke batang untuk melihat nominal tepatnya.</p>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="chart-panel">
                    <h2 class="h5 mb-3">Pertumbuhan Gadai</h2>
                    <div class="chart-wrap"><canvas id="pawnChart"></canvas></div>
                    <p class="text-muted small mb-0 mt-3">Garis hijau menunjukkan jumlah pengajuan. Garis oranye menunjukkan jumlah nasabah unik; satu nasabah dapat memiliki lebih dari satu pengajuan.</p>
                </div>
            </div>
        </section>
    </main>

    <script>
        const labels = <?php echo json_encode($labels, JSON_UNESCAPED_UNICODE); ?>;
        const profitData = <?php echo json_encode(array_values($profitMonths), JSON_NUMERIC_CHECK); ?>;
        const pawnData = <?php echo json_encode(array_values($pawnMonths), JSON_NUMERIC_CHECK); ?>;
        const customerData = <?php echo json_encode(array_values($customerMonths), JSON_NUMERIC_CHECK); ?>;
        const rupiah = value => 'Rp ' + Number(value).toLocaleString('id-ID');

        new Chart(document.getElementById('profitChart'), {
            type: 'bar',
            data: { labels, datasets: [{ label: 'Total Profit', data: profitData, backgroundColor: '#0b5ed7', borderRadius: 4 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { tooltip: { callbacks: { label: context => `${context.dataset.label}: ${rupiah(context.raw)}` } } }, scales: { y: { beginAtZero: true, ticks: { callback: value => rupiah(value) } } } }
        });

        new Chart(document.getElementById('pawnChart'), {
            type: 'line',
            data: { labels, datasets: [
                { label: 'Pengajuan Gadai', data: pawnData, borderColor: '#087f5b', backgroundColor: 'rgba(8, 127, 91, .12)', fill: true, tension: .3 },
                { label: 'Orang Gadai Unik', data: customerData, borderColor: '#d9480f', backgroundColor: 'rgba(217, 72, 15, .08)', fill: true, tension: .3 }
            ] },
            options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
        });
    </script>
</body>
</html>