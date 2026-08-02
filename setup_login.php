<?php
require_once 'database.php';

header('Content-Type: text/html; charset=utf-8');

$steps = [];

function addStep(array &$steps, string $title, bool $ok, string $detail = ''): void {
    $steps[] = [
        'title' => $title,
        'ok' => $ok,
        'detail' => $detail,
    ];
}

try {
    $db->exec("CREATE TABLE IF NOT EXISTS admin (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nik VARCHAR(20) NOT NULL UNIQUE,
        nama VARCHAR(100) NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) DEFAULT NULL,
        telepon VARCHAR(20) DEFAULT NULL,
        role VARCHAR(30) NOT NULL DEFAULT 'admin',
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL DEFAULT NULL,
        INDEX idx_nik (nik),
        INDEX idx_is_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    addStep($steps, 'Tabel admin dipastikan tersedia', true);

    $check = $db->prepare("SELECT id FROM admin WHERE nik = ? LIMIT 1");
    $check->execute(['admin123']);
    $existingId = $check->fetchColumn();

    if ($existingId) {
        addStep($steps, 'Akun admin default sudah ada', true, 'NIK: admin123');
    } else {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $insert = $db->prepare("INSERT INTO admin (nik, nama, password, role, email, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        $insert->execute(['admin123', 'Administrator', $hash, 'superadmin', 'admin@gadaicepat.local', 1]);
        addStep($steps, 'Akun admin default berhasil dibuat', true, 'NIK: admin123 | Password: admin123');
    }
} catch (Throwable $e) {
    addStep($steps, 'Setup login manual gagal', false, $e->getMessage());
}

$okCount = 0;
foreach ($steps as $s) {
    if (!empty($s['ok'])) {
        $okCount++;
    }
}
$total = count($steps);
$allOk = $total > 0 && $okCount === $total;
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setup Login Manual</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #eef5ff, #f8fbff); min-height: 100vh; font-family: 'Poppins', sans-serif; }
        .shell { max-width: 860px; margin: 30px auto; }
        .card-main { border: 0; border-radius: 16px; box-shadow: 0 16px 40px rgba(0, 86, 179, 0.12); }
        .head { border-radius: 16px 16px 0 0; background: linear-gradient(135deg, #0056b3, #0d6efd); color: #fff; }
    </style>
</head>
<body>
<div class="container shell">
    <div class="card card-main">
        <div class="card-body head p-4">
            <h1 class="h4 mb-1">Setup Login Manual</h1>
            <p class="mb-0 opacity-75">Membuat tabel admin dan akun default jika belum tersedia.</p>
        </div>
        <div class="card-body p-4">
            <div class="alert alert-<?php echo $allOk ? 'success' : 'warning'; ?>">
                <?php echo $allOk ? 'Semua langkah setup login berhasil.' : 'Ada langkah yang perlu diperiksa kembali.'; ?>
            </div>

            <ul class="list-group mb-4">
                <?php foreach ($steps as $step): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div>
                            <strong><?php echo htmlspecialchars($step['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            <?php if (!empty($step['detail'])): ?>
                                <div class="text-muted small mt-1"><?php echo htmlspecialchars($step['detail'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>
                        </div>
                        <span class="badge bg-<?php echo !empty($step['ok']) ? 'success' : 'danger'; ?>"><?php echo !empty($step['ok']) ? 'OK' : 'Gagal'; ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="d-flex gap-2 flex-wrap">
                <a href="login.php?role=admin" class="btn btn-primary">Ke Login Admin</a>
                <a href="admin_tools.php" class="btn btn-outline-secondary">Kembali ke Admin Tools</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
