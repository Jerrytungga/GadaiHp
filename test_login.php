<?php
session_start();
require_once 'database.php';
require_once 'gadai_helpers.php';

$role = strtolower(trim((string)($_GET['role'] ?? ($_POST['role'] ?? 'customer'))));
if (!in_array($role, ['customer', 'admin'], true)) {
    $role = 'customer';
}

$message = '';
$message_type = 'info';
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nik = trim((string)($_POST['nik'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    try {
        if ($role === 'admin') {
            $stmt = $db->prepare('SELECT id, nik, nama, password, is_active FROM admin WHERE nik = ? LIMIT 1');
            $stmt->execute([$nik]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $message = 'Admin tidak ditemukan.';
                $message_type = 'danger';
            } elseif ((int)($row['is_active'] ?? 1) !== 1) {
                $message = 'Akun admin sedang nonaktif.';
                $message_type = 'warning';
            } elseif (empty($row['password'])) {
                $message = 'Admin ditemukan, tetapi password belum di-set.';
                $message_type = 'warning';
            } elseif (password_verify($password, (string)$row['password'])) {
                $message = 'Login admin valid.';
                $message_type = 'success';
                $result = [
                    'id' => (int)$row['id'],
                    'nik' => (string)$row['nik'],
                    'nama' => (string)$row['nama'],
                    'status' => 'valid',
                ];
            } else {
                $message = 'Password admin tidak cocok.';
                $message_type = 'danger';
            }
        } else {
            gadai_ensure_customer_table($db);
            $row = gadai_get_customer_by_nik($db, $nik);

            if (!$row) {
                $message = 'Customer tidak ditemukan.';
                $message_type = 'danger';
            } elseif ((int)($row['is_active'] ?? 1) !== 1) {
                $message = 'Akun customer sedang nonaktif.';
                $message_type = 'warning';
            } elseif (empty($row['password'])) {
                $message = 'Customer ditemukan, tetapi password belum di-set.';
                $message_type = 'warning';
            } elseif (password_verify($password, (string)$row['password'])) {
                $message = 'Login customer valid.';
                $message_type = 'success';
                $result = [
                    'id' => (int)$row['id'],
                    'nik' => (string)$row['nik'],
                    'nama' => (string)$row['nama'],
                    'status' => 'valid',
                ];
            } else {
                $message = 'Password customer tidak cocok.';
                $message_type = 'danger';
            }
        }
    } catch (Throwable $e) {
        $message = 'Gagal test login: ' . $e->getMessage();
        $message_type = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Login - Gadai Cepat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Raleway:wght@700;800;900&display=swap" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #eef6ff 0%, #f7fbff 100%);
            font-family: 'Poppins', sans-serif;
        }
        .shell {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 32px 0;
        }
        .card-shell {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 18px 50px rgba(0, 86, 179, 0.14);
            border: 1px solid rgba(0, 86, 179, 0.08);
            overflow: hidden;
        }
        .hero {
            background: linear-gradient(135deg, #0056b3, #0d6efd);
            color: #fff;
            padding: 24px 28px;
        }
        .hero h1 {
            font-family: 'Raleway', sans-serif;
            font-weight: 800;
            margin: 0;
        }
        .content { padding: 28px; }
        .form-control { border-radius: 14px; padding: 12px 16px; border: 2px solid #dbeafe; }
        .form-control:focus { border-color: #0056b3; box-shadow: 0 0 0 0.2rem rgba(0, 86, 179, 0.12); }
        .btn-primary { border-radius: 14px; padding: 12px 18px; font-weight: 700; }
        .muted-link { color: #0056b3; font-weight: 600; text-decoration: none; }
        .result-box {
            background: #f8fbff;
            border: 1px solid #d9e9ff;
            border-radius: 16px;
            padding: 16px;
        }
    </style>
</head>
<body>
<div class="shell">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7 col-xl-6">
                <div class="card-shell">
                    <div class="hero">
                        <div class="small text-uppercase opacity-75">Utility</div>
                        <h1>Test Login <?php echo $role === 'admin' ? 'Admin' : 'Customer'; ?></h1>
                        <p class="mb-0 opacity-75">Cek apakah kredensial tersimpan dan password hash bekerja dengan benar.</p>
                    </div>
                    <div class="content">
                        <?php if ($message !== ''): ?>
                            <div class="alert alert-<?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
                        <?php endif; ?>

                        <div class="btn-group w-100 mb-3" role="group" aria-label="Pilih role test">
                            <a href="test_login.php?role=customer" class="btn btn-outline-primary <?php echo $role === 'customer' ? 'active' : ''; ?>">Customer</a>
                            <a href="test_login.php?role=admin" class="btn btn-outline-primary <?php echo $role === 'admin' ? 'active' : ''; ?>">Admin</a>
                        </div>

                        <form method="POST" class="mt-2">
                            <input type="hidden" name="role" value="<?php echo htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">NIK</label>
                                <input type="text" name="nik" class="form-control" required placeholder="Masukkan NIK">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Password</label>
                                <input type="password" name="password" class="form-control" required placeholder="Masukkan password">
                            </div>
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary">Test Login</button>
                            </div>
                        </form>

                        <?php if ($result !== null): ?>
                            <div class="result-box mb-3">
                                <div class="fw-semibold text-success mb-2">Hasil validasi</div>
                                <div>ID: <?php echo (int)$result['id']; ?></div>
                                <div>NIK: <?php echo htmlspecialchars($result['nik']); ?></div>
                                <div>Nama: <?php echo htmlspecialchars($result['nama']); ?></div>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between flex-wrap gap-2">
                            <a href="login.php?role=<?php echo $role === 'admin' ? 'admin' : 'customer'; ?>" class="muted-link">Buka Halaman Login</a>
                            <a href="admin_tools.php" class="muted-link">Admin Tools</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>