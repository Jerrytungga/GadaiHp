<?php
require_once 'database.php';
require_once 'gadai_helpers.php';
require_once 'auth_guard.php';

gadai_require_admin();

$search = trim((string)($_GET['q'] ?? ''));
$customers = [];
$totalCustomers = 0;
$totalGadai = 0;

$sql = "SELECT c.id, c.nama, c.nik, c.no_wa, c.alamat, c.email, c.created_at, c.updated_at,
               COUNT(dg.id) AS total_gadai,
               MAX(dg.updated_at) AS last_gadai_at
        FROM customers c
        LEFT JOIN data_gadai dg ON dg.customer_id = c.id";

$params = [];
if ($search !== '') {
    $sql .= " WHERE c.nama LIKE ? OR c.nik LIKE ? OR c.no_wa LIKE ? OR c.alamat LIKE ?";
    $like = '%' . $search . '%';
    $params = [$like, $like, $like, $like];
}

$sql .= " GROUP BY c.id, c.nama, c.nik, c.no_wa, c.alamat, c.email, c.created_at, c.updated_at
          ORDER BY c.updated_at DESC, c.id DESC";

try {
    gadai_ensure_customer_table($db);
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalCustomers = count($customers);
    foreach ($customers as $row) {
        $totalGadai += (int)($row['total_gadai'] ?? 0);
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Customer - Gadai Cepat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #eef6ff 0%, #f7fbff 100%);
            min-height: 100vh;
            padding: 30px 0 50px;
        }
        .page-card {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 18px 50px rgba(0, 86, 179, 0.12);
            border: 1px solid rgba(0, 86, 179, 0.08);
        }
        .hero {
            background: linear-gradient(135deg, #0056b3, #0b74e5);
            color: #fff;
            border-radius: 24px 24px 0 0;
            padding: 24px 28px;
        }
        .stat-box {
            background: #f8fbff;
            border: 1px solid #d9e9ff;
            border-radius: 16px;
            padding: 16px;
            height: 100%;
        }
        .stat-label {
            color: #5f6b7a;
            font-size: 0.9rem;
        }
        .stat-value {
            font-size: 1.7rem;
            font-weight: 700;
            color: #0f315f;
        }
        .table thead th {
            background: #eef6ff;
            color: #214a7a;
            border-bottom: 1px solid #d7e7fb;
        }
        .pill {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 999px;
            background: #edf5ff;
            color: #0056b3;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .muted-small {
            color: #6c7a89;
            font-size: 0.86rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-card overflow-hidden">
            <div class="hero d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h1 class="h3 mb-1">Daftar Customer</h1>
                    <p class="mb-0 opacity-75">Master customer sederhana yang terhubung ke data gadai.</p>
                </div>
                <a href="admin_tools.php" class="btn btn-light fw-semibold">Kembali ke Tools</a>
            </div>

            <div class="p-4 p-md-4">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="stat-box">
                            <div class="stat-label">Total Customer</div>
                            <div class="stat-value"><?php echo (int)$totalCustomers; ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-box">
                            <div class="stat-label">Total Gadai Terkait</div>
                            <div class="stat-value"><?php echo (int)$totalGadai; ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-box">
                            <div class="stat-label">Pencarian</div>
                            <div class="stat-value" style="font-size:1.05rem;"><?php echo $search !== '' ? htmlspecialchars($search, ENT_QUOTES, 'UTF-8') : 'Semua data'; ?></div>
                        </div>
                    </div>
                </div>

                <form method="GET" class="row g-2 mb-4">
                    <div class="col-md-8">
                        <input type="text" name="q" class="form-control form-control-lg" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Cari nama, NIK, WA, atau alamat customer...">
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Cari</button>
                    </div>
                    <div class="col-md-2 d-grid">
                        <a href="customer_list.php" class="btn btn-outline-secondary btn-lg">Reset</a>
                    </div>
                </form>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php elseif (empty($customers)): ?>
                    <div class="alert alert-info mb-0">Belum ada customer yang tersimpan.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama</th>
                                    <th>NIK</th>
                                    <th>No. WA</th>
                                    <th>Alamat</th>
                                    <th>Gadai</th>
                                    <th>Update Terakhir</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customers as $row): ?>
                                    <tr>
                                        <td><?php echo (int)$row['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['nama'] ?? '-'); ?></strong></td>
                                        <td><span class="pill"><?php echo htmlspecialchars($row['nik'] ?? '-'); ?></span></td>
                                        <td><?php echo htmlspecialchars($row['no_wa'] ?? '-'); ?></td>
                                        <td style="max-width:320px;"><?php echo htmlspecialchars($row['alamat'] ?? '-'); ?></td>
                                        <td>
                                            <strong><?php echo (int)($row['total_gadai'] ?? 0); ?></strong>
                                            <div class="muted-small"><?php echo !empty($row['last_gadai_at']) ? 'Terakhir: ' . date('d M Y H:i', strtotime($row['last_gadai_at'])) : 'Belum ada pengajuan'; ?></div>
                                        </td>
                                        <td><?php echo !empty($row['updated_at']) ? date('d M Y H:i', strtotime($row['updated_at'])) : '-'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
