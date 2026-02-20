<?php
/**
 * Debug Session - untuk troubleshoot masalah login
 * 
 * Akses: http://localhost/GadaiHp/debug_session.php
 */

session_start();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Session</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 50px 20px;
            font-family: 'Courier New', monospace;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            max-width: 800px;
            margin: 0 auto;
        }
        .session-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .debug-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 15px 15px 0 0;
        }
        pre {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            margin: 0;
        }
        .badge-status {
            font-size: 14px;
            padding: 8px 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="debug-header">
                <h3 class="mb-0">🔍 Session Debug Information</h3>
                <p class="mb-0 mt-2" style="opacity: 0.9;">Gunakan halaman ini untuk troubleshoot masalah login</p>
            </div>
            <div class="card-body p-4">
                
                <!-- Session Status -->
                <div class="mb-4">
                    <h5 class="mb-3">📊 Status Login:</h5>
                    <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
                        <div class="alert alert-success mb-0">
                            <strong>✅ SUDAH LOGIN</strong>
                            <p class="mb-0 mt-2">Session admin_logged_in ditemukan dan bernilai TRUE</p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger mb-0">
                            <strong>❌ BELUM LOGIN</strong>
                            <p class="mb-0 mt-2">Session admin_logged_in tidak ditemukan atau bernilai FALSE</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Session ID -->
                <div class="session-info">
                    <strong>Session ID:</strong><br>
                    <code><?php echo session_id(); ?></code>
                </div>
                
                <!-- Session Variables -->
                <div class="mb-4">
                    <h5 class="mb-3">📦 Session Variables:</h5>
                    <pre><?php 
                    if (empty($_SESSION)) {
                        echo "[ Session kosong - tidak ada data ]";
                    } else {
                        print_r($_SESSION); 
                    }
                    ?></pre>
                </div>
                
                <!-- PHP Info -->
                <div class="mb-4">
                    <h5 class="mb-3">⚙️ PHP Configuration:</h5>
                    <div class="session-info">
                        <strong>Session Save Path:</strong> <code><?php echo session_save_path(); ?></code><br>
                        <strong>Session Cookie Params:</strong><br>
                        <pre><?php print_r(session_get_cookie_params()); ?></pre>
                    </div>
                </div>
                
                <!-- Server Info -->
                <div class="mb-4">
                    <h5 class="mb-3">🖥️ Server Info:</h5>
                    <div class="session-info">
                        <strong>PHP Version:</strong> <?php echo phpversion(); ?><br>
                        <strong>Server Software:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?><br>
                        <strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'N/A'; ?>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="d-grid gap-2">
                    <a href="login.php" class="btn btn-primary">
                        🔐 Ke Halaman Login
                    </a>
                    <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
                    <a href="admin_verifikasi.php" class="btn btn-success">
                        ✅ Ke Halaman Admin
                    </a>
                    <?php endif; ?>
                    <a href="?clear_session=1" class="btn btn-warning">
                        🗑️ Clear Session (Logout)
                    </a>
                    <a href="debug_session.php" class="btn btn-secondary">
                        🔄 Refresh Page
                    </a>
                </div>
                
                <?php
                // Clear session if requested
                if (isset($_GET['clear_session'])) {
                    session_destroy();
                    echo '<script>alert("Session cleared!"); window.location.href = "debug_session.php";</script>';
                }
                ?>
                
            </div>
        </div>
        
        <div class="text-center mt-4">
            <p class="text-white">
                <small>💡 Jika status menunjukkan "BELUM LOGIN" padahal sudah login, cek apakah ada error di browser console atau PHP error log.</small>
            </p>
        </div>
    </div>
</body>
</html>
