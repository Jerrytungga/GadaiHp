<?php
require_once 'database.php';
require_once 'gadai_helpers.php';
require_once 'auth_guard.php';

gadai_require_admin();
gadai_ensure_customer_table($db);

$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = (string)$_POST['action'];
    $customerId = (int)($_POST['customer_id'] ?? 0);

    try {
        if ($customerId <= 0) {
            throw new RuntimeException('Customer tidak valid.');
        }

        $customer = gadai_get_customer_by_id($db, $customerId);
        if (!$customer) {
            throw new RuntimeException('Customer tidak ditemukan.');
        }

        if ($action === 'set_password') {
            $password = (string)($_POST['password'] ?? '');
            $passwordConfirm = (string)($_POST['password_confirm'] ?? '');
            if ($password === '' || $passwordConfirm === '') {
                throw new RuntimeException('Password wajib diisi.');
            }
            if ($password !== $passwordConfirm) {
                throw new RuntimeException('Password dan konfirmasi tidak sama.');
            }
            if (strlen($password) < 6) {
                throw new RuntimeException('Password minimal 6 karakter.');
            }

            gadai_set_customer_password($db, $customerId, password_hash($password, PASSWORD_DEFAULT));
            $message = 'Password customer berhasil disimpan.';
        } elseif ($action === 'toggle_status') {
            $newStatus = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 0;
            $stmt = $db->prepare('UPDATE customers SET is_active = ?, updated_at = NOW() WHERE id = ?');
            $stmt->execute([$newStatus ? 1 : 0, $customerId]);
            $message = $newStatus ? 'Akun customer diaktifkan.' : 'Akun customer dinonaktifkan.';
        } else {
            throw new RuntimeException('Aksi tidak dikenali.');
        }
    } catch (Throwable $e) {
        $message = $e->getMessage();
        $message_type = 'danger';
    }
}

$search = trim((string)($_GET['q'] ?? ''));
$sql = "SELECT c.id, c.nama, c.nik, c.no_wa, c.alamat, c.email, c.is_active, c.password, c.last_login, c.created_at, c.updated_at,
               COUNT(dg.id) AS total_gadai
        FROM customers c
        LEFT JOIN data_gadai dg ON dg.customer_id = c.id";
$params = [];

if ($search !== '') {
    $sql .= " WHERE c.nama LIKE ? OR c.nik LIKE ? OR c.no_wa LIKE ? OR c.alamat LIKE ? OR c.email LIKE ?";
    $like = '%' . $search . '%';
    $params = [$like, $like, $like, $like, $like];
}

$sql .= " GROUP BY c.id, c.nama, c.nik, c.no_wa, c.alamat, c.email, c.is_active, c.password, c.last_login, c.created_at, c.updated_at
          ORDER BY c.updated_at DESC, c.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalCustomers = count($customers);
$totalReady = 0;
foreach ($customers as $row) {
    if (!empty($row['password'])) {
        $totalReady++;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akun Customer - Gadai Cepat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Raleway:wght@700;800;900&display=swap" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #eef6ff 0%, #f7fbff 100%);
            font-family: 'Poppins', sans-serif;
        }
        .hero {
            background: linear-gradient(135deg, #0d6efd, #0056b3);
            color: #fff;
            border-radius: 24px;
            box-shadow: 0 18px 50px rgba(0, 86, 179, 0.15);
        }
        .panel {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 18px 50px rgba(0, 86, 179, 0.10);
            border: 1px solid rgba(0, 86, 179, 0.08);
        }
        .metric {
            background: #f8fbff;
            border-radius: 18px;
            padding: 16px;
            border: 1px solid #dcecff;
            height: 100%;
        }
        .metric-label { color: #6c7a89; font-size: 0.9rem; }
        .metric-value { font-size: 1.7rem; font-weight: 800; color: #0f315f; }
        .status-on { background: #d1e7dd; color: #0f5132; }
        .status-off { background: #f8d7da; color: #842029; }
        .badge-soft { padding: 6px 10px; border-radius: 999px; font-weight: 700; }
        .table thead th { background: #eef6ff; color: #214a7a; }
    </style>
</head>
<body>
<div class="container py-4 py-md-5">
    <div class="hero p-4 p-md-5 mb-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="small text-uppercase opacity-75">Admin Flow</div>
                <h1 class="display-6 fw-bold mb-2">Kelola Akun Customer</h1>
                <p class="mb-0 opacity-75">Buat password login, aktif/nonaktif akun, dan cek customer yang sudah siap masuk portal.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="admin_tools.php" class="btn btn-light fw-semibold">Tools</a>
                <a href="customer_list.php" class="btn btn-outline-light fw-semibold">Master Customer</a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="metric">
                <div class="metric-label">Total Customer</div>
                <div class="metric-value"><?php echo (int)$totalCustomers; ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="metric">
                <div class="metric-label">Akun Siap Login</div>
                <div class="metric-value"><?php echo (int)$totalReady; ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="metric">
                <div class="metric-label">Pencarian</div>
                <div class="metric-value" style="font-size:1.05rem;"><?php echo $search !== '' ? htmlspecialchars($search, ENT_QUOTES, 'UTF-8') : 'Semua data'; ?></div>
            </div>
        </div>
    </div>

    <div class="panel p-4">
        <form method="GET" class="row g-2 align-items-center mb-4">
            <div class="col-md-9">
                <input type="text" name="q" class="form-control form-control-lg" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Cari nama, NIK, WA, email, atau alamat customer...">
            </div>
            <div class="col-md-3 d-grid gap-2 d-md-flex">
                <button type="submit" class="btn btn-primary">Cari</button>
                <a href="admin_customer_accounts.php" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>

        <?php if (isset($message) && $message !== ''): ?>
            <div class="alert alert-<?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if (empty($customers)): ?>
            <div class="alert alert-info mb-0">Belum ada customer yang tersimpan.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Kontak</th>
                            <th>Status Akun</th>
                            <th>Login Terakhir</th>
                            <th>Gadai</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $row): ?>
                            <tr>
                                <td><?php echo (int)$row['id']; ?></td>
                                <td>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($row['nama'] ?? '-'); ?></div>
                                    <small class="text-muted">NIK: <?php echo htmlspecialchars($row['nik'] ?? '-'); ?></small>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($row['no_wa'] ?? '-'); ?></div>
                                    <small class="text-muted d-block"><?php echo htmlspecialchars($row['email'] ?? '-'); ?></small>
                                </td>
                                <td>
                                    <span class="badge-soft <?php echo !empty($row['password']) && (int)$row['is_active'] === 1 ? 'status-on' : 'status-off'; ?>">
                                        <?php echo !empty($row['password']) ? ((int)$row['is_active'] === 1 ? 'Aktif' : 'Nonaktif') : 'Belum Ada Password'; ?>
                                    </span>
                                </td>
                                <td><?php echo !empty($row['last_login']) ? date('d M Y H:i', strtotime($row['last_login'])) : '-'; ?></td>
                                <td><?php echo (int)($row['total_gadai'] ?? 0); ?></td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#passwordModal<?php echo (int)$row['id']; ?>">Set Password</button>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="customer_id" value="<?php echo (int)$row['id']; ?>">
                                            <input type="hidden" name="is_active" value="<?php echo (int)$row['is_active'] === 1 ? 0 : 1; ?>">
                                            <button type="submit" class="btn btn-sm btn-<?php echo (int)$row['is_active'] === 1 ? 'danger' : 'success'; ?>">
                                                <?php echo (int)$row['is_active'] === 1 ? 'Nonaktifkan' : 'Aktifkan'; ?>
                                            </button>
                                        </form>
                                    </div>

                                    <div class="modal fade" id="passwordModal<?php echo (int)$row['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <form method="POST">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Set Password Customer</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="action" value="set_password">
                                                        <input type="hidden" name="customer_id" value="<?php echo (int)$row['id']; ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label">Password Baru</label>
                                                            <input type="password" name="password" class="form-control" required minlength="6">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Konfirmasi Password</label>
                                                            <input type="password" name="password_confirm" class="form-control" required minlength="6">
                                                        </div>
                                                        <div class="alert alert-info mb-0">Jika password diisi, customer dapat login ke portal customer.</div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-primary">Simpan</button>
                                                    </div>
                                                </form>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
