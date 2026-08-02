<?php
session_start();
require_once 'database.php';
require_once 'gadai_helpers.php';

$role = strtolower(trim((string)($_GET['role'] ?? ($_POST['role'] ?? 'customer'))));
if (!in_array($role, ['customer', 'admin'], true)) {
    $role = 'customer';
}

if ($role === 'admin' && !empty($_SESSION['admin_logged_in'])) {
    header('Location: admin_verifikasi.php');
    exit;
}

if ($role === 'customer' && !empty($_SESSION['customer_logged_in'])) {
    header('Location: customer_dashboard.php');
    exit;
}

$message = '';
$message_type = 'danger';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nik = trim((string)($_POST['nik'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    try {
        if ($role === 'admin') {
            $stmt = $db->prepare('SELECT * FROM admin WHERE nik = ? LIMIT 1');
            $stmt->execute([$nik]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && !empty($admin['password']) && (int)($admin['is_active'] ?? 1) === 1 && password_verify($password, (string)$admin['password'])) {
                session_regenerate_id(true);
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = (int)$admin['id'];
                $_SESSION['admin_user'] = (string)($admin['nama'] ?? $admin['nik']);
                $_SESSION['admin_nik'] = (string)$admin['nik'];
                $_SESSION['login_time'] = time();

                try {
                    $update = $db->prepare('UPDATE admin SET last_login = NOW(), updated_at = NOW() WHERE id = ?');
                    $update->execute([(int)$admin['id']]);
                } catch (Throwable $e) {
                    error_log('Admin last_login update failed: ' . $e->getMessage());
                }

                header('Location: admin_verifikasi.php');
                exit;
            }

            $message = 'NIK atau password admin salah, atau akun belum aktif.';
        } else {
            $customer = gadai_verify_customer_login($db, $nik, $password);
            if ($customer) {
                session_regenerate_id(true);
                $_SESSION['customer_logged_in'] = true;
                $_SESSION['customer_id'] = (int)$customer['id'];
                $_SESSION['customer_nik'] = (string)$customer['nik'];
                $_SESSION['customer_name'] = (string)$customer['nama'];
                $_SESSION['customer_login_time'] = time();

                header('Location: customer_dashboard.php');
                exit;
            }

            $customer = gadai_get_customer_by_nik($db, $nik);
            if ($customer && empty($customer['password'])) {
                $message = 'Akun customer belum dibuat. Silakan daftar dulu untuk membuat password.';
            } else {
                $message = 'NIK atau password customer salah, atau akun belum aktif.';
            }
        }
    } catch (Throwable $e) {
        $message = 'Gagal login: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gadai Cepat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Raleway:wght@700;800;900&display=swap" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: radial-gradient(circle at top left, rgba(0, 86, 179, 0.18), transparent 34%), linear-gradient(135deg, #eaf4ff 0%, #f7fbff 100%);
            font-family: 'Poppins', sans-serif;
        }
        .shell {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 32px 0;
        }
        .card-shell {
            background: rgba(255,255,255,0.95);
            border-radius: 28px;
            box-shadow: 0 20px 60px rgba(0, 86, 179, 0.18);
            overflow: hidden;
            border: 1px solid rgba(0, 86, 179, 0.08);
        }
        .hero {
            background: linear-gradient(135deg, #0056b3, #0d6efd);
            color: #fff;
            padding: 28px;
        }
        .hero h1 {
            font-family: 'Raleway', sans-serif;
            font-weight: 800;
            margin-bottom: 8px;
        }
        .content {
            padding: 28px;
        }
        .form-control {
            border-radius: 14px;
            padding: 12px 16px;
            border: 2px solid #dbeafe;
        }
        .form-control:focus {
            border-color: #0056b3;
            box-shadow: 0 0 0 0.2rem rgba(0, 86, 179, 0.12);
        }
        .btn-primary {
            border-radius: 14px;
            padding: 12px 18px;
            font-weight: 700;
        }
        .muted-link {
            color: #0056b3;
            font-weight: 600;
            text-decoration: none;
        }
    </style>
</head>
<body>
<div class="shell">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-xl-5">
                <div class="card-shell">
                    <div class="hero">
                        <div class="small text-uppercase opacity-75">Portal Login</div>
                        <h1><?php echo $role === 'admin' ? 'Login Admin' : 'Login Customer'; ?></h1>
                        <p class="mb-0 opacity-75"><?php echo $role === 'admin' ? 'Masuk untuk membuka dashboard verifikasi, customer, dan tools admin.' : 'Masuk untuk melihat pengajuan gadai, status, dan histori transaksi Anda.'; ?></p>
                    </div>
                    <div class="content">
                        <?php if ($message !== ''): ?>
                            <div class="alert alert-<?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
                        <?php endif; ?>

                        <form method="POST" class="mt-2">
                            <input type="hidden" name="role" value="<?php echo htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">NIK</label>
                                <input type="text" name="nik" class="form-control" required placeholder="<?php echo $role === 'admin' ? 'NIK admin' : 'NIK customer'; ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Password</label>
                                <input type="password" name="password" class="form-control" required placeholder="<?php echo $role === 'admin' ? 'Password admin' : 'Password customer'; ?>">
                            </div>
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary"><?php echo $role === 'admin' ? 'Masuk Admin' : 'Masuk Customer'; ?></button>
                            </div>
                        </form>

                        <div class="d-flex justify-content-between flex-wrap gap-2">
                            <a href="customer_register.php" class="muted-link">Belum punya akun? Daftar</a>
                            <a href="index.php" class="muted-link">Kembali ke Beranda</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
