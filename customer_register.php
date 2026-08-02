<?php
session_start();
require_once 'database.php';
require_once 'gadai_helpers.php';

if (!empty($_SESSION['customer_logged_in'])) {
    header('Location: customer_dashboard.php');
    exit;
}

$message = '';
$message_type = 'danger';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim((string)($_POST['nama'] ?? ''));
    $nik = trim((string)($_POST['nik'] ?? ''));
    $no_wa = trim((string)($_POST['no_wa'] ?? ''));
    $alamat = trim((string)($_POST['alamat'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $password_confirm = (string)($_POST['password_confirm'] ?? '');

    if ($nama === '' || $nik === '' || $no_wa === '' || $alamat === '' || $password === '' || $password_confirm === '') {
        $message = 'Semua field wajib harus diisi.';
    } elseif ($password !== $password_confirm) {
        $message = 'Password dan konfirmasi password tidak sama.';
    } elseif (strlen($password) < 6) {
        $message = 'Password minimal 6 karakter.';
    } else {
        try {
            gadai_ensure_customer_table($db);
            $customer = gadai_get_customer_by_nik($db, $nik);
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            if ($customer) {
                $stmt = $db->prepare("UPDATE customers SET nama = ?, no_wa = ?, alamat = ?, email = ?, password = ?, is_active = 1, updated_at = NOW() WHERE nik = ?");
                $stmt->execute([$nama, $no_wa, $alamat, $email !== '' ? $email : null, $passwordHash, $nik]);
                $customerId = (int)$customer['id'];
                $message = 'Akun customer berhasil diperbarui. Silakan login.';
                $message_type = 'success';
            } else {
                $stmt = $db->prepare("INSERT INTO customers (nama, nik, no_wa, alamat, email, password, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
                $stmt->execute([$nama, $nik, $no_wa, $alamat, $email !== '' ? $email : null, $passwordHash]);
                $customerId = (int)$db->lastInsertId();
                $message = 'Akun customer berhasil dibuat. Silakan login.';
                $message_type = 'success';
            }

            $customer = gadai_get_customer_by_id($db, $customerId);
            if ($customer && !empty($customer['password'])) {
                header('Location: customer_login.php?registered=1');
                exit;
            }
        } catch (Throwable $e) {
            $message = 'Gagal mendaftar customer: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Customer - Gadai Cepat</title>
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
            <div class="col-lg-8 col-xl-7">
                <div class="card-shell">
                    <div class="hero">
                        <div class="small text-uppercase opacity-75">Buat Akun Customer</div>
                        <h1>Daftar Customer</h1>
                        <p class="mb-0 opacity-75">Gunakan NIK yang sama dengan data gadai Anda untuk menyambungkan akun customer.</p>
                    </div>
                    <div class="content">
                        <?php if ($message !== ''): ?>
                            <div class="alert alert-<?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
                        <?php endif; ?>

                        <form method="POST" class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nama Lengkap</label>
                                <input type="text" name="nama" class="form-control" required placeholder="Nama customer">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">NIK</label>
                                <input type="text" name="nik" class="form-control" required placeholder="NIK / No KTP">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">No. WhatsApp</label>
                                <input type="text" name="no_wa" class="form-control" required placeholder="08xxxxxxxxxx">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email (opsional)</label>
                                <input type="email" name="email" class="form-control" placeholder="alamat@email.com">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Alamat</label>
                                <textarea name="alamat" class="form-control" rows="3" required placeholder="Alamat lengkap"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Password</label>
                                <input type="password" name="password" class="form-control" required placeholder="Minimal 6 karakter">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Konfirmasi Password</label>
                                <input type="password" name="password_confirm" class="form-control" required placeholder="Ulangi password">
                            </div>
                            <div class="col-12 d-grid mt-2">
                                <button type="submit" class="btn btn-primary">Daftar Sekarang</button>
                            </div>
                        </form>

                        <div class="mt-3 d-flex justify-content-between flex-wrap gap-2">
                            <a href="customer_login.php" class="muted-link">Sudah punya akun? Login</a>
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
