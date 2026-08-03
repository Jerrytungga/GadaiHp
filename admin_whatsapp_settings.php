<?php
require_once 'database.php';
require_once 'auth_guard.php';
require_once 'whatsapp_helper.php';

gadai_require_admin();

$message = '';
$messageType = 'success';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_whatsapp_status') {
        $enabled = isset($_POST['whatsapp_notifications_enabled']) && $_POST['whatsapp_notifications_enabled'] === '1';
        gadai_set_whatsapp_notifications_enabled($db, $enabled);
        $message = $enabled
            ? 'Semua notifikasi WhatsApp sudah diaktifkan.'
            : 'Semua notifikasi WhatsApp sudah dinonaktifkan.';
    }

    $notificationsEnabled = gadai_whatsapp_notifications_enabled($db);
} catch (Throwable $e) {
    $message = 'Gagal menyimpan pengaturan WhatsApp: ' . $e->getMessage();
    $messageType = 'danger';
    $notificationsEnabled = true;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan WhatsApp - Gadai Cepat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <main class="container py-5" style="max-width: 720px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">Pengaturan WhatsApp</h1>
                <p class="text-muted mb-0">Kontrol pengiriman notifikasi untuk seluruh sistem.</p>
            </div>
            <a href="admin_tools.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Tools</a>
        </div>

        <?php if ($message !== ''): ?>
            <div class="alert alert-<?php echo $messageType; ?>" role="alert"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <section class="card shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <span class="fs-2 text-<?php echo $notificationsEnabled ? 'success' : 'danger'; ?>"><i class="fas fa-<?php echo $notificationsEnabled ? 'bell' : 'bell-slash'; ?>"></i></span>
                    <div>
                        <h2 class="h5 mb-1">Notifikasi saat ini <?php echo $notificationsEnabled ? 'aktif' : 'nonaktif'; ?></h2>
                        <p class="text-muted mb-0">Saat dinonaktifkan, sistem tidak mengirim WhatsApp ke customer maupun admin.</p>
                    </div>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="save_whatsapp_status">
                    <div class="form-check form-switch fs-5 mb-4">
                        <input class="form-check-input" type="checkbox" role="switch" id="whatsappNotificationsEnabled" name="whatsapp_notifications_enabled" value="1" <?php echo $notificationsEnabled ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="whatsappNotificationsEnabled">Aktifkan semua notifikasi WhatsApp</label>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Simpan Pengaturan</button>
                </form>
            </div>
        </section>
    </main>
</body>
</html>