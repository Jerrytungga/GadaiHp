<?php
/**
 * AUTOMATIC DATABASE SETUP
 * Halaman ini akan otomatis setup database lengkap
 * Akses: http://localhost/GadaiHp/setup_database.php
 */

// Start session untuk tracking
session_start();

// Configuration
$db_config = [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => '',
    'db_name' => 'GadaiCepat'
];

$results = [];
$total_steps = 0;
$success_steps = 0;

// Function untuk log hasil
function logResult($step, $status, $message, $details = '') {
    global $results, $total_steps, $success_steps;
    $total_steps++;
    if ($status) $success_steps++;
    
    $results[] = [
        'step' => $step,
        'status' => $status,
        'message' => $message,
        'details' => $details,
        'time' => date('H:i:s')
    ];
}

// ============================================
// STEP 1: Connect to MySQL (without database)
// ============================================
try {
    $mysqli = new mysqli($db_config['host'], $db_config['user'], $db_config['pass']);
    if ($mysqli->connect_error) {
        throw new Exception($mysqli->connect_error);
    }
    logResult(1, true, "Koneksi ke MySQL Server", "Host: {$db_config['host']}");
} catch (Exception $e) {
    logResult(1, false, "GAGAL koneksi MySQL", $e->getMessage());
    goto show_results;
}

// ============================================
// STEP 2: Create Database
// ============================================
try {
    $mysqli->query("CREATE DATABASE IF NOT EXISTS `{$db_config['db_name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $mysqli->select_db($db_config['db_name']);
    logResult(2, true, "Database '{$db_config['db_name']}' dibuat/dipilih", "Character Set: utf8mb4");
} catch (Exception $e) {
    logResult(2, false, "GAGAL membuat database", $e->getMessage());
    goto show_results;
}

// ============================================
// STEP 3: Create Table ADMIN
// ============================================
$sql_admin = "CREATE TABLE IF NOT EXISTS `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nik` varchar(50) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('superadmin','admin','staff') DEFAULT 'admin',
  `email` varchar(100) DEFAULT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nik` (`nik`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    $mysqli->query($sql_admin);
    logResult(3, true, "Tabel 'admin' dibuat", "Struktur: id, nik, nama, password, role, dll");
} catch (Exception $e) {
    logResult(3, false, "GAGAL membuat tabel admin", $e->getMessage());
}

// ============================================
// STEP 4: Create Table DATA_GADAI
// ============================================
$sql_data_gadai = "CREATE TABLE IF NOT EXISTS `data_gadai` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `nik` varchar(16) NOT NULL,
  `alamat` text NOT NULL,
  `no_wa` varchar(15) NOT NULL,
  `jenis_barang` varchar(100) NOT NULL,
  `merk_barang` varchar(100) DEFAULT NULL,
  `spesifikasi_barang` text DEFAULT NULL,
  `kondisi_barang` enum('Baru','Bekas - Baik','Bekas - Cukup','Rusak Ringan') DEFAULT 'Bekas - Baik',
  `nilai_taksiran` decimal(15,2) DEFAULT NULL,
  `jumlah_pinjaman` decimal(15,2) NOT NULL,
  `jumlah_disetujui` decimal(15,2) DEFAULT NULL,
  `bunga` decimal(5,2) DEFAULT 5.00,
  `lama_gadai` int(11) DEFAULT 30,
  `tanggal_gadai` date NOT NULL,
  `tanggal_jatuh_tempo` date NOT NULL,
  `status` enum('Pending','Disetujui','Ditolak','Lunas','Diperpanjang','Jatuh Tempo','Barang Dijual') DEFAULT 'Pending',
  `foto_ktp` varchar(255) DEFAULT NULL,
  `foto_barang` varchar(255) DEFAULT NULL,
  `foto_tambahan` varchar(255) DEFAULT NULL,
  `catatan_admin` text DEFAULT NULL,
  `alasan_penolakan` text DEFAULT NULL,
  `tanggal_verifikasi` timestamp NULL DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `total_tebus` decimal(15,2) DEFAULT NULL,
  `tanggal_pelunasan` date DEFAULT NULL,
  `denda_terakumulasi` decimal(15,2) DEFAULT 0.00,
  `jumlah_perpanjangan` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_nik` (`nik`),
  KEY `idx_status` (`status`),
  KEY `idx_tanggal_gadai` (`tanggal_gadai`),
  KEY `idx_jatuh_tempo` (`tanggal_jatuh_tempo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    $mysqli->query($sql_data_gadai);
    logResult(4, true, "Tabel 'data_gadai' dibuat", "Struktur lengkap dengan indexing");
} catch (Exception $e) {
    logResult(4, false, "GAGAL membuat tabel data_gadai", $e->getMessage());
}

// ============================================
// STEP 5: Create Table ULASAN
// ============================================
$sql_ulasan = "CREATE TABLE IF NOT EXISTS `ulasan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `rating` int(1) NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
  `ulasan` text NOT NULL,
  `tanggal` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_approved` tinyint(1) DEFAULT 0,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_approved` (`is_approved`),
  KEY `idx_rating` (`rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    $mysqli->query($sql_ulasan);
    logResult(5, true, "Tabel 'ulasan' dibuat", "Untuk review pelanggan");
} catch (Exception $e) {
    logResult(5, false, "GAGAL membuat tabel ulasan", $e->getMessage());
}

// ============================================
// STEP 6: Create Table WA_LOG (WhatsApp Log)
// ============================================
$sql_wa_log = "CREATE TABLE IF NOT EXISTS `wa_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gadai_id` int(11) DEFAULT NULL,
  `phone_number` varchar(15) NOT NULL,
  `message_type` enum('approval','rejection','reminder','pelunasan','perpanjangan') NOT NULL,
  `message_content` text NOT NULL,
  `status` enum('pending','sent','failed') DEFAULT 'pending',
  `response` text DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_gadai_id` (`gadai_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    $mysqli->query($sql_wa_log);
    logResult(6, true, "Tabel 'wa_log' dibuat", "Log WhatsApp notifications");
} catch (Exception $e) {
    logResult(6, false, "GAGAL membuat tabel wa_log", $e->getMessage());
}

// ============================================
// STEP 7: Create Table PAYMENTS (Pembayaran)
// ============================================
$sql_payments = "CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gadai_id` int(11) NOT NULL,
  `payment_type` enum('pelunasan','perpanjangan','denda','cicilan') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` enum('tunai','transfer','e-wallet') DEFAULT 'tunai',
  `bukti_transfer` varchar(255) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_gadai_id` (`gadai_id`),
  KEY `idx_payment_date` (`payment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    $mysqli->query($sql_payments);
    logResult(7, true, "Tabel 'payments' dibuat", "Tracking pembayaran");
} catch (Exception $e) {
    logResult(7, false, "GAGAL membuat tabel payments", $e->getMessage());
}

// ============================================
// STEP 8: Insert Default Admin
// ============================================
$default_password = 'admin123';
$hashed_password = password_hash($default_password, PASSWORD_DEFAULT);

$check_admin = $mysqli->query("SELECT COUNT(*) as total FROM admin WHERE nik = 'admin123'");
$row = $check_admin->fetch_assoc();

if ($row['total'] == 0) {
    $sql_insert_admin = "INSERT INTO `admin` (`nik`, `nama`, `password`, `role`, `email`, `is_active`) VALUES 
    ('admin123', 'Administrator', '$hashed_password', 'superadmin', 'admin@gadaicepat.com', 1)";
    
    try {
        $mysqli->query($sql_insert_admin);
        logResult(8, true, "User Admin Default dibuat", "NIK: admin123 | Password: admin123 | Role: superadmin");
    } catch (Exception $e) {
        logResult(8, false, "GAGAL insert admin default", $e->getMessage());
    }
} else {
    logResult(8, true, "User Admin sudah ada", "NIK: admin123 (existing)");
}

// ============================================
// STEP 9: Create Database Views (Optional)
// ============================================
$sql_view = "CREATE OR REPLACE VIEW v_gadai_aktif AS
SELECT 
    d.*,
    a.nama as nama_admin,
    DATEDIFF(d.tanggal_jatuh_tempo, CURDATE()) as hari_tersisa,
    CASE 
        WHEN DATEDIFF(CURDATE(), d.tanggal_jatuh_tempo) > 0 THEN DATEDIFF(CURDATE(), d.tanggal_jatuh_tempo)
        ELSE 0
    END as hari_terlambat
FROM data_gadai d
LEFT JOIN admin a ON d.verified_by = a.id
WHERE d.status IN ('Disetujui', 'Diperpanjang', 'Jatuh Tempo')";

try {
    $mysqli->query($sql_view);
    logResult(9, true, "View 'v_gadai_aktif' dibuat", "Untuk monitoring gadai aktif");
} catch (Exception $e) {
    logResult(9, false, "GAGAL membuat view", $e->getMessage());
}

// ============================================
// STEP 10: Insert Sample Data (Optional)
// ============================================
$check_data = $mysqli->query("SELECT COUNT(*) as total FROM ulasan");
$row_ulasan = $check_data->fetch_assoc();

if ($row_ulasan['total'] == 0) {
    $sql_sample_ulasan = "INSERT INTO `ulasan` (`nama`, `rating`, `ulasan`, `is_approved`) VALUES 
    ('Budi Santoso', 5, 'Pelayanan sangat cepat dan profesional. Sangat membantu!', 1),
    ('Siti Aminah', 5, 'Proses gadai mudah dan aman. Recommended!', 1),
    ('Ahmad Wijaya', 4, 'Bunga kompetitif dan staff ramah. Good service!', 1)";
    
    try {
        $mysqli->query($sql_sample_ulasan);
        logResult(10, true, "Sample ulasan ditambahkan", "3 ulasan contoh");
    } catch (Exception $e) {
        logResult(10, false, "GAGAL insert sample ulasan", $e->getMessage());
    }
} else {
    logResult(10, true, "Sample data sudah ada", "Skip insert sample");
}

// ============================================
// STEP 11: Set Permissions & Optimize
// ============================================
try {
    $mysqli->query("OPTIMIZE TABLE admin, data_gadai, ulasan, wa_log, payments");
    logResult(11, true, "Optimasi tabel selesai", "All tables optimized");
} catch (Exception $e) {
    logResult(11, false, "Optimasi tabel gagal", $e->getMessage());
}

show_results:
$mysqli->close();

// Calculate success rate
$success_rate = $total_steps > 0 ? round(($success_steps / $total_steps) * 100, 1) : 0;
$is_success = $success_rate == 100;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Gadai Cepat Timika</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 30px 15px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            max-width: 900px;
        }
        .setup-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            margin-bottom: 20px;
        }
        .setup-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .setup-header h1 {
            margin: 0;
            font-size: 32px;
            font-weight: 700;
        }
        .setup-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        .setup-body {
            padding: 30px;
        }
        .progress-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            border: 2px solid #dee2e6;
        }
        .progress {
            height: 30px;
            border-radius: 15px;
            background: #e9ecef;
            margin-bottom: 15px;
        }
        .progress-bar {
            font-weight: 600;
            font-size: 14px;
            line-height: 30px;
        }
        .step-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .step-item {
            background: white;
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 12px;
            border-left: 5px solid #6c757d;
            display: flex;
            align-items: flex-start;
            gap: 15px;
            transition: all 0.3s;
        }
        .step-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .step-item.success {
            border-left-color: #28a745;
            background: #f1f9f3;
        }
        .step-item.error {
            border-left-color: #dc3545;
            background: #fdf4f5;
        }
        .step-icon {
            font-size: 24px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .step-icon.success {
            background: #28a745;
            color: white;
        }
        .step-icon.error {
            background: #dc3545;
            color: white;
        }
        .step-content {
            flex: 1;
        }
        .step-title {
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 16px;
        }
        .step-details {
            color: #6c757d;
            font-size: 13px;
            margin-bottom: 3px;
        }
        .step-time {
            color: #adb5bd;
            font-size: 12px;
        }
        .summary-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            border: 3px solid;
        }
        .summary-card.success {
            border-color: #28a745;
            background: linear-gradient(135deg, #f1f9f3 0%, #e8f5e9 100%);
        }
        .summary-card.warning {
            border-color: #ffc107;
            background: linear-gradient(135deg, #fff9e6 0%, #fff3cd 100%);
        }
        .summary-card.error {
            border-color: #dc3545;
            background: linear-gradient(135deg, #fdf4f5 0%, #f8d7da 100%);
        }
        .credential-box {
            background: #2d2d2d;
            color: #00ff00;
            padding: 20px;
            border-radius: 10px;
            font-family: monospace;
            margin: 20px 0;
            border: 2px solid #00ff00;
        }
        .credential-box h5 {
            color: #ffff00;
            margin-bottom: 15px;
            font-size: 18px;
        }
        .credential-item {
            display: flex;
            margin-bottom: 10px;
        }
        .credential-label {
            color: #00ffff;
            width: 150px;
            font-weight: bold;
        }
        .credential-value {
            color: #00ff00;
        }
        .action-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 25px;
        }
        .btn-custom {
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .step-item {
            animation: slideIn 0.3s ease forwards;
        }
        <?php foreach ($results as $i => $result): ?>
        .step-item:nth-child(<?php echo $i + 1; ?>) {
            animation-delay: <?php echo $i * 0.05; ?>s;
        }
        <?php endforeach; ?>
    </style>
</head>
<body>
    <div class="container">
        <div class="setup-card">
            <div class="setup-header">
                <h1><i class="fas fa-database me-3"></i>Database Setup</h1>
                <p>Automatic Database Installation - Gadai Cepat Timika</p>
            </div>
            
            <div class="setup-body">
                <!-- Progress Summary -->
                <div class="progress-card">
                    <h4 class="mb-3"><i class="fas fa-chart-line me-2"></i>Progress Setup</h4>
                    <div class="progress">
                        <div class="progress-bar <?php echo $is_success ? 'bg-success' : ($success_rate > 50 ? 'bg-warning' : 'bg-danger'); ?>" 
                             role="progressbar" 
                             style="width: <?php echo $success_rate; ?>%">
                            <?php echo $success_rate; ?>%
                        </div>
                    </div>
                    <div class="d-flex justify-content-between text-muted">
                        <span><i class="fas fa-check-circle text-success"></i> <?php echo $success_steps; ?> Success</span>
                        <span><i class="fas fa-times-circle text-danger"></i> <?php echo $total_steps - $success_steps; ?> Failed</span>
                        <span><i class="fas fa-list"></i> <?php echo $total_steps; ?> Total Steps</span>
                    </div>
                </div>

                <!-- Installation Summary -->
                <div class="summary-card <?php echo $is_success ? 'success' : ($success_rate > 50 ? 'warning' : 'error'); ?>">
                    <h3 class="mb-3">
                        <?php if ($is_success): ?>
                            <i class="fas fa-check-circle text-success"></i> Installation Complete!
                        <?php elseif ($success_rate > 50): ?>
                            <i class="fas fa-exclamation-triangle text-warning"></i> Installation Partial
                        <?php else: ?>
                            <i class="fas fa-times-circle text-danger"></i> Installation Failed
                        <?php endif; ?>
                    </h3>
                    <p class="mb-0">
                        <?php if ($is_success): ?>
                            Database berhasil di-setup dengan lengkap! Semua tabel dan data default telah dibuat.
                        <?php elseif ($success_rate > 50): ?>
                            Beberapa langkah berhasil, tetapi ada yang gagal. Periksa detail di bawah.
                        <?php else: ?>
                            Setup database gagal. Periksa koneksi database dan permission.
                        <?php endif; ?>
                    </p>
                </div>

                <!-- Setup Steps Detail -->
                <h4 class="mb-3"><i class="fas fa-list-ol me-2"></i>Setup Details</h4>
                <ul class="step-list">
                    <?php foreach ($results as $result): ?>
                    <li class="step-item <?php echo $result['status'] ? 'success' : 'error'; ?>">
                        <div class="step-icon <?php echo $result['status'] ? 'success' : 'error'; ?>">
                            <i class="fas fa-<?php echo $result['status'] ? 'check' : 'times'; ?>"></i>
                        </div>
                        <div class="step-content">
                            <div class="step-title">
                                Step <?php echo $result['step']; ?>: <?php echo $result['message']; ?>
                            </div>
                            <?php if ($result['details']): ?>
                            <div class="step-details">
                                <i class="fas fa-info-circle me-1"></i><?php echo $result['details']; ?>
                            </div>
                            <?php endif; ?>
                            <div class="step-time">
                                <i class="far fa-clock me-1"></i><?php echo $result['time']; ?>
                            </div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>

                <?php if ($is_success): ?>
                <!-- Login Credentials -->
                <div class="credential-box">
                    <h5><i class="fas fa-key me-2"></i>DEFAULT LOGIN CREDENTIALS</h5>
                    <div class="credential-item">
                        <span class="credential-label">NIK:</span>
                        <span class="credential-value">admin123</span>
                    </div>
                    <div class="credential-item">
                        <span class="credential-label">Password:</span>
                        <span class="credential-value">admin123</span>
                    </div>
                    <div class="credential-item">
                        <span class="credential-label">Role:</span>
                        <span class="credential-value">superadmin</span>
                    </div>
                    <div class="credential-item">
                        <span class="credential-label">Login URL:</span>
                        <span class="credential-value">http://localhost/GadaiHp/login.php</span>
                    </div>
                    <hr style="border-color: #00ff00; margin: 15px 0;">
                    <p style="color: #ff6b6b; margin: 0;">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        PENTING: Segera ganti password setelah login pertama kali!
                    </p>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="login.php" class="btn btn-success btn-custom">
                        <i class="fas fa-sign-in-alt"></i>
                        Login Sekarang
                    </a>
                    <a href="admin_tools.php" class="btn btn-primary btn-custom">
                        <i class="fas fa-tools"></i>
                        Admin Tools
                    </a>
                    <a href="index.php" class="btn btn-info btn-custom">
                        <i class="fas fa-home"></i>
                        Beranda
                    </a>
                    <a href="?reset=1" class="btn btn-danger btn-custom" onclick="return confirm('Reset semua data? Ini akan menghapus semua data!')">
                        <i class="fas fa-redo"></i>
                        Reset & Setup Ulang
                    </a>
                </div>
                <?php else: ?>
                <!-- Retry Button -->
                <div class="action-buttons">
                    <a href="?retry=1" class="btn btn-warning btn-custom">
                        <i class="fas fa-redo"></i>
                        Coba Lagi
                    </a>
                    <a href="index.php" class="btn btn-secondary btn-custom">
                        <i class="fas fa-home"></i>
                        Kembali ke Beranda
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Info Footer -->
        <div class="text-center text-white mt-3">
            <small>
                <i class="fas fa-info-circle me-1"></i>
                Database: <?php echo $db_config['db_name']; ?> | 
                Host: <?php echo $db_config['host']; ?> | 
                Setup Time: <?php echo date('Y-m-d H:i:s'); ?>
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php
    // Auto redirect to login if success
    if ($is_success && !isset($_GET['stay'])) {
        echo '<script>
            setTimeout(function() {
                if (confirm("Setup berhasil! Redirect ke halaman login?")) {
                    window.location.href = "login.php";
                }
            }, 2000);
        </script>';
    }
    
    // Handle reset request
    if (isset($_GET['reset']) && $_GET['reset'] == 1) {
        $mysqli = new mysqli($db_config['host'], $db_config['user'], $db_config['pass']);
        $mysqli->query("DROP DATABASE IF EXISTS `{$db_config['db_name']}`");
        $mysqli->close();
        echo '<script>window.location.href = "setup_database.php";</script>';
    }
    ?>
</body>
</html>
