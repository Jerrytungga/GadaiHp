<?php
session_start();
require_once 'database.php';
require_once 'whatsapp_helper.php';

// Simple authentication (ganti dengan sistem login yang lebih aman)
if (!isset($_SESSION['admin_logged_in'])) {
    // Temporary login - hapus ini dan ganti dengan sistem login proper
    if (isset($_POST['admin_login'])) {
        if ($_POST['username'] == 'admin' && $_POST['password'] == 'admin123') {
            $_SESSION['admin_logged_in'] = true;
        } else {
            $login_error = "Username atau password salah!";
        }
    }
    
    if (!isset($_SESSION['admin_logged_in'])) {
        ?>
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Login Admin - Gadai Cepat</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .login-box {
                    background: white;
                    padding: 40px;
                    border-radius: 20px;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    max-width: 400px;
                    width: 100%;
                }
            </style>
        </head>
        <body>
            <div class="login-box">
                <h2 class="text-center mb-4">🔐 Login Admin</h2>
                <?php if (isset($login_error)): ?>
                    <div class="alert alert-danger"><?php echo $login_error; ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <button type="submit" name="admin_login" class="btn btn-primary w-100">Login</button>
                    <p class="text-muted text-center mt-3 mb-0" style="font-size: 0.85rem;">Default: admin / admin123</p>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// Handle approval/rejection
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    try {
        $id = $_POST['id'];
        $action = $_POST['action'];
        
        if ($action == 'approve') {
            $jumlah_disetujui = $_POST['jumlah_disetujui'];
            $keterangan_admin = $_POST['keterangan_admin'] ?? '';
            
            $sql = "UPDATE data_gadai SET 
                    status = 'Disetujui', 
                    jumlah_disetujui = ?, 
                    keterangan_admin = ?,
                    total_tebus = (? * (1 + (bunga / 100) * lama_gadai)) + denda_terakumulasi,
                    verified_at = NOW(), 
                    verified_by = 'Admin' 
                    WHERE id = ?";
            $stmt = $db->prepare($sql);
                $stmt->execute([$jumlah_disetujui, $keterangan_admin, $jumlah_disetujui, $id]);
            
            // Ambil data lengkap untuk notifikasi
            $data_sql = "SELECT * FROM data_gadai WHERE id = ?";
            $data_stmt = $db->prepare($data_sql);
            $data_stmt->execute([$id]);
            $data = $data_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Kirim notifikasi WhatsApp ke User
            try {
                $whatsapp->notifyUserApproved($data);
            } catch(Exception $e) {
                error_log("WhatsApp notification failed: " . $e->getMessage());
            }
            
            $message = "Pengajuan berhasil disetujui dengan nominal Rp " . number_format($jumlah_disetujui, 0, ',', '.') . ". Notifikasi telah dikirim ke nasabah.";
            $message_type = "success";
            
        } elseif ($action == 'reject') {
            $alasan = $_POST['alasan_reject'] ?? 'Tidak memenuhi syarat';
            $sql = "UPDATE data_gadai SET status = 'Ditolak', reject_reason = ?, verified_at = NOW(), verified_by = 'Admin' WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$alasan, $id]);
            
            // Ambil data lengkap untuk notifikasi
            $data_sql = "SELECT * FROM data_gadai WHERE id = ?";
            $data_stmt = $db->prepare($data_sql);
            $data_stmt->execute([$id]);
            $data = $data_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Kirim notifikasi WhatsApp ke User
            try {
                $whatsapp->notifyUserRejected($data);
            } catch(Exception $e) {
                error_log("WhatsApp notification failed: " . $e->getMessage());
            }
            
            $message = "Pengajuan ditolak. Notifikasi telah dikirim ke nasabah.";
            $message_type = "danger";
        } elseif ($action == 'acc_pelunasan') {
            $no_registrasi = $_POST['id'];

            $check_bukti_sql = "SELECT COUNT(*) FROM transaksi WHERE barang_id = ? AND pelanggan_nik = (SELECT no_ktp FROM data_gadai WHERE id = ?)";
            $check_bukti_stmt = $db->prepare($check_bukti_sql);
            $check_bukti_stmt->execute([$no_registrasi, $no_registrasi]);
            $bukti_count = (int)$check_bukti_stmt->fetchColumn();

            if ($bukti_count <= 0) {
                $message = "ACC pelunasan ditolak: bukti pembayaran belum diunggah.";
                $message_type = "danger";
            } else {
                $update_sql = "UPDATE data_gadai SET status = 'Ditebus', aksi_jatuh_tempo_at = NOW(), updated_at = NOW() WHERE id = ?";
                $update_stmt = $db->prepare($update_sql);
                $update_stmt->execute([$no_registrasi]);

                $data_sql = "SELECT * FROM data_gadai WHERE id = ?";
                $data_stmt = $db->prepare($data_sql);
                $data_stmt->execute([$no_registrasi]);
                $data = $data_stmt->fetch(PDO::FETCH_ASSOC);

                if ($data) {
                    try {
                        $whatsapp->notifyUserPelunasanVerified($data);
                    } catch(Exception $e) {
                        error_log("WhatsApp notification failed: " . $e->getMessage());
                    }
                }

                $message = "Pelunasan berhasil di-ACC. Status berubah menjadi Ditebus.";
                $message_type = "success";
            }
        }
    } catch(PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = "danger";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_csv'])) {
    $upload_errors = [];
    $upload_success = 0;
    $upload_failed = 0;

    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $upload_errors[] = 'File CSV gagal diunggah.';
    } else {
        $file_tmp = $_FILES['csv_file']['tmp_name'];
        $file_name = $_FILES['csv_file']['name'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if ($ext !== 'csv') {
            $upload_errors[] = 'Format file harus CSV.';
        } else {
            $handle = fopen($file_tmp, 'r');
            if ($handle === false) {
                $upload_errors[] = 'Gagal membaca file CSV.';
            } else {
                $normalize = function ($value) {
                    $value = strtolower(trim($value));
                    $value = preg_replace('/\s+/', '_', $value);
                    return $value;
                };

                $header = fgetcsv($handle, 0, ',');
                if (!$header) {
                    $upload_errors[] = 'Header CSV tidak ditemukan.';
                } else {
                    $header = array_map($normalize, $header);
                    $required_headers = [
                        'nik',
                        'nama',
                        'no_hp',
                        'jenis_barang',
                        'merk',
                        'tipe',
                        'alamat',
                        'kondisi',
                        'imei_serial',
                        'harga_pasar',
                        'jumlah_pinjaman',
                        'bunga',
                        'lama_gadai',
                        'tanggal_gadai',
                        'tanggal_jatuh_tempo'
                    ];

                    $missing = array_diff($required_headers, $header);
                    if (!empty($missing)) {
                        $upload_errors[] = 'Kolom CSV kurang: ' . implode(', ', $missing) . '.';
                    } else {
                        $map = array_flip($header);
                        $allowed_jenis = ['HP', 'Laptop'];
                        $allowed_kondisi = ['Sangat Baik', 'Baik', 'Cukup'];

                        $insert_sql = "INSERT INTO data_gadai (
                            nama_nasabah, no_ktp, no_hp, alamat, jenis_barang, merk, tipe,
                            kondisi, imei_serial, harga_pasar, jumlah_pinjaman, bunga,
                            lama_gadai, tanggal_gadai, tanggal_jatuh_tempo, foto_barang, foto_ktp, status
                        ) VALUES (
                            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, NULL, 'Pending'
                        )";
                        $stmt = $db->prepare($insert_sql);

                        $row_number = 1;
                        while (($row = fgetcsv($handle, 0, ',')) !== false) {
                            $row_number++;
                            if (count(array_filter($row, fn($value) => trim((string)$value) !== '')) === 0) {
                                continue;
                            }

                            $nik = trim($row[$map['nik']] ?? '');
                            $nama = trim($row[$map['nama']] ?? '');
                            $no_hp = trim($row[$map['no_hp']] ?? '');
                            $jenis = trim($row[$map['jenis_barang']] ?? '');
                            $merk = trim($row[$map['merk']] ?? '');
                            $tipe = trim($row[$map['tipe']] ?? '');
                            $alamat = trim($row[$map['alamat']] ?? '');
                            $kondisi = trim($row[$map['kondisi']] ?? '');
                            $imei = trim($row[$map['imei_serial']] ?? '');
                            $harga_pasar_raw = trim($row[$map['harga_pasar']] ?? '');
                            $jumlah_pinjaman_raw = trim($row[$map['jumlah_pinjaman']] ?? '');
                            $bunga_raw = trim($row[$map['bunga']] ?? '');
                            $lama_raw = trim($row[$map['lama_gadai']] ?? '');
                            $tanggal_gadai = trim($row[$map['tanggal_gadai']] ?? '');
                            $tanggal_jatuh_tempo = trim($row[$map['tanggal_jatuh_tempo']] ?? '');

                            $jenis = strtoupper($jenis);
                            if ($jenis === 'SMARTPHONE') {
                                $jenis = 'HP';
                            }

                            if ($kondisi === '') {
                                $kondisi = 'Baik';
                            }

                            $harga_pasar = (float)str_replace(',', '.', preg_replace('/[^0-9,\.]/', '', $harga_pasar_raw));
                            $jumlah_pinjaman = (float)str_replace(',', '.', preg_replace('/[^0-9,\.]/', '', $jumlah_pinjaman_raw));
                            $bunga = (float)str_replace(',', '.', preg_replace('/[^0-9,\.]/', '', $bunga_raw));
                            $lama_gadai = (int)preg_replace('/[^0-9]/', '', $lama_raw);

                            $row_errors = [];
                            if ($nik === '' || $nama === '' || $no_hp === '') {
                                $row_errors[] = 'NIK, nama, dan no_hp wajib diisi.';
                            }
                            if (!in_array($jenis, $allowed_jenis, true)) {
                                $row_errors[] = 'Jenis barang harus HP atau Laptop.';
                            }
                            if (!in_array($kondisi, $allowed_kondisi, true)) {
                                $row_errors[] = 'Kondisi harus: Sangat Baik, Baik, atau Cukup.';
                            }
                            if ($harga_pasar <= 0 || $jumlah_pinjaman <= 0 || $bunga <= 0 || $lama_gadai <= 0) {
                                $row_errors[] = 'Harga pasar, jumlah pinjaman, bunga, dan lama gadainya harus > 0.';
                            }

                            $tgl_gadai_ok = DateTime::createFromFormat('Y-m-d', $tanggal_gadai) !== false;
                            $tgl_jatuh_ok = DateTime::createFromFormat('Y-m-d', $tanggal_jatuh_tempo) !== false;
                            if (!$tgl_gadai_ok || !$tgl_jatuh_ok) {
                                $row_errors[] = 'Tanggal gadai dan jatuh tempo harus format YYYY-MM-DD.';
                            }

                            if (!empty($row_errors)) {
                                $upload_failed++;
                                if (count($upload_errors) < 5) {
                                    $upload_errors[] = "Baris {$row_number}: " . implode(' ', $row_errors);
                                }
                                continue;
                            }

                            try {
                                $stmt->execute([
                                    $nama,
                                    $nik,
                                    $no_hp,
                                    $alamat,
                                    $jenis,
                                    $merk,
                                    $tipe,
                                    $kondisi,
                                    $imei,
                                    $harga_pasar,
                                    $jumlah_pinjaman,
                                    $bunga,
                                    $lama_gadai,
                                    $tanggal_gadai,
                                    $tanggal_jatuh_tempo
                                ]);
                                $upload_success++;
                            } catch (PDOException $e) {
                                $upload_failed++;
                                if (count($upload_errors) < 5) {
                                    $upload_errors[] = "Baris {$row_number}: gagal simpan.";
                                }
                            }
                        }
                    }
                }

                fclose($handle);
            }
        }
    }

    if ($upload_success > 0) {
        $message = "Upload selesai: {$upload_success} berhasil, {$upload_failed} gagal.";
        $message_type = $upload_failed > 0 ? 'warning' : 'success';
    } elseif (!empty($upload_errors)) {
        $message = implode(' ', $upload_errors);
        $message_type = 'danger';
    }
}

if (isset($_GET['download_template']) && $_GET['download_template'] === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="template_data_gadai.xls"');
    echo "<table border='1'>";
    echo "<tr>";
    echo "<th>nik</th><th>nama</th><th>no_hp</th><th>jenis_barang</th><th>merk</th><th>tipe</th><th>alamat</th><th>kondisi</th><th>imei_serial</th><th>harga_pasar</th><th>jumlah_pinjaman</th><th>bunga</th><th>lama_gadai</th><th>tanggal_gadai</th><th>tanggal_jatuh_tempo</th>";
    echo "</tr>";
    echo "<tr>";
    echo "<td>1234567890123456</td><td>Andi Saputra</td><td>081234567890</td><td>HP</td><td>Samsung</td><td>Galaxy A52</td><td>Jl. Merdeka No 10</td><td>Baik</td><td>IMEI1234567890</td><td>2500000</td><td>1500000</td><td>30</td><td>2</td><td>2026-02-10</td><td>2026-04-10</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td>3201123456789012</td><td>Siti Lestari</td><td>082345678901</td><td>Laptop</td><td>Asus</td><td>VivoBook 14</td><td>Jl. Kenanga No 5</td><td>Cukup</td><td>SN-ABCD-1234</td><td>5500000</td><td>3500000</td><td>30</td><td>3</td><td>2026-02-01</td><td>2026-05-01</td>";
    echo "</tr>";
    echo "</table>";
    exit;
}

// Fetch pending submissions
$pending_sql = "SELECT * FROM data_gadai WHERE status = 'Pending' ORDER BY created_at DESC";
$pending_stmt = $db->query($pending_sql);
$pending_data = $pending_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch approved submissions
$approved_sql = "SELECT * FROM data_gadai WHERE status = 'Disetujui' ORDER BY verified_at DESC LIMIT 10";
$approved_stmt = $db->query($approved_sql);
$approved_data = $approved_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch rejected submissions
$rejected_sql = "SELECT * FROM data_gadai WHERE status = 'Ditolak' ORDER BY verified_at DESC LIMIT 10";
$rejected_stmt = $db->query($rejected_sql);
$rejected_data = $rejected_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all submissions (for list table)
$all_sql = "SELECT id, nama_nasabah, no_ktp, no_hp, jenis_barang, merk, tipe, status, created_at FROM data_gadai ORDER BY created_at DESC";
$all_stmt = $db->query($all_sql);
$all_data = $all_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch pelunasan pending submissions
$pelunasan_data = [];
$pelunasan_error = null;
try {
    $pelunasan_sql = "SELECT dg.*, 
        (SELECT COUNT(*) FROM transaksi t WHERE t.barang_id = dg.id AND t.pelanggan_nik = dg.no_ktp) AS bukti_count,
        (SELECT SUM(t.jumlah_bayar) FROM transaksi t WHERE t.barang_id = dg.id AND t.pelanggan_nik = dg.no_ktp) AS bukti_total,
        (SELECT t.bukti FROM transaksi t WHERE t.barang_id = dg.id AND t.pelanggan_nik = dg.no_ktp ORDER BY t.id DESC LIMIT 1) AS bukti_latest
        FROM data_gadai dg
        WHERE dg.aksi_jatuh_tempo = 'Pelunasan' AND dg.status IN ('Disetujui', 'Diperpanjang')
        ORDER BY dg.updated_at DESC";
    $pelunasan_stmt = $db->query($pelunasan_sql);
    $pelunasan_data = $pelunasan_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pelunasan_error = "Data pelunasan belum tersedia (cek tabel transaksi).";
}

// Statistics
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'Disetujui' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'Ditolak' THEN 1 ELSE 0 END) as rejected
FROM data_gadai";
$stats = $db->query($stats_sql)->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Verifikasi - Gadai Cepat Timika</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@700;800;900&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #e3f2fd 0%, #f0f8ff 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .header {
            background: linear-gradient(135deg, #0056b3, #007bff);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0, 86, 179, 0.2);
        }
        
        .header h1 {
            font-family: 'Raleway', sans-serif;
            font-weight: 800;
            margin: 0;
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #007bff;
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: #0056b3;
        }
        
        .stats-label {
            color: #666;
            font-weight: 600;
        }
        
        .data-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }
        
        .data-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
        
        .data-card.pending {
            border-left: 5px solid #ffc107;
        }
        
        .data-card.approved {
            border-left: 5px solid #28a745;
        }
        
        .data-card.rejected {
            border-left: 5px solid #dc3545;
        }
        
        .badge-pending {
            background: linear-gradient(135deg, #ffc107, #ff9800);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .badge-approved {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .badge-rejected {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .info-label {
            font-weight: 600;
            color: #555;
        }
        
        .info-value {
            color: #333;
        }
        
        .btn-approve {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            color: white;
            font-weight: 600;
            padding: 10px 25px;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        
        .btn-approve:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
            color: white;
        }
        
        .btn-reject {
            background: linear-gradient(135deg, #dc3545, #c82333);
            border: none;
            color: white;
            font-weight: 600;
            padding: 10px 25px;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        
        .btn-reject:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
            color: white;
        }
        
        .btn-logout {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            border: none;
            color: white;
            font-weight: 600;
            padding: 8px 20px;
            border-radius: 25px;
        }
        
        .nav-tabs {
            border: none;
            margin-bottom: 25px;
        }
        
        .nav-tabs .nav-link {
            border: none;
            border-radius: 15px 15px 0 0;
            font-weight: 600;
            color: #666;
            padding: 15px 30px;
            margin-right: 5px;
        }
        
        .nav-tabs .nav-link.active {
            background: white;
            color: #0056b3;
            box-shadow: 0 -5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
            cursor: pointer;
        }
        
        .no-transaksi {
            font-family: 'Raleway', sans-serif;
            font-weight: 800;
            font-size: 1.3rem;
            color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1>🔍 Panel Verifikasi Admin</h1>
                <a href="?logout=1" class="btn btn-logout">Logout</a>
            </div>
        </div>
    </div>
    
    <?php
    if (isset($_GET['logout'])) {
        session_destroy();
        header('Location: admin_verifikasi.php');
        exit;
    }
    ?>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $stats['total']; ?></div>
                    <div class="stats-label">📊 Total Pengajuan</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="border-left-color: #ffc107;">
                    <div class="stats-number" style="color: #ff9800;"><?php echo $stats['pending']; ?></div>
                    <div class="stats-label">⏳ Menunggu Verifikasi</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="border-left-color: #28a745;">
                    <div class="stats-number" style="color: #28a745;"><?php echo $stats['approved']; ?></div>
                    <div class="stats-label">✅ Disetujui</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="border-left-color: #dc3545;">
                    <div class="stats-number" style="color: #dc3545;"><?php echo $stats['rejected']; ?></div>
                    <div class="stats-label">❌ Ditolak</div>
                </div>
            </div>
        </div>
        
        <!-- Tabs -->
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button">
                    ⏳ Menunggu Verifikasi (<?php echo count($pending_data); ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="approved-tab" data-bs-toggle="tab" data-bs-target="#approved" type="button">
                    ✅ Disetujui (<?php echo count($approved_data); ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="rejected-tab" data-bs-toggle="tab" data-bs-target="#rejected" type="button">
                    ❌ Ditolak (<?php echo count($rejected_data); ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pelunasan-tab" data-bs-toggle="tab" data-bs-target="#pelunasan" type="button">
                    💰 Pelunasan Pending (<?php echo count($pelunasan_data); ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="list-tab" data-bs-toggle="tab" data-bs-target="#list" type="button">
                    📋 Daftar Gadai (<?php echo count($all_data); ?>)
                </button>
            </li>
        </ul>
        
        <div class="tab-content" id="myTabContent">
            <!-- Pending Tab -->
            <div class="tab-pane fade show active" id="pending" role="tabpanel">
                <?php if (empty($pending_data)): ?>
                    <div class="alert alert-info">Tidak ada pengajuan yang menunggu verifikasi.</div>
                <?php else: ?>
                    <?php foreach ($pending_data as $row): ?>
                        <div class="data-card pending">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="no-transaksi">#<?php echo str_pad($row['id'], 6, '0', STR_PAD_LEFT); ?></div>
                                    <small class="text-muted"><?php echo date('d M Y H:i', strtotime($row['created_at'])); ?></small>
                                </div>
                                <span class="badge-pending">⏳ PENDING</span>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h5>👤 Data Nasabah</h5>
                                    <div class="info-row">
                                        <span class="info-label">Nama:</span>
                                        <span class="info-value"><?php echo htmlspecialchars($row['nama_nasabah']); ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">No. KTP:</span>
                                        <span class="info-value"><?php echo $row['no_ktp']; ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">No. HP:</span>
                                        <span class="info-value"><?php echo $row['no_hp']; ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Alamat:</span>
                                        <span class="info-value"><?php echo htmlspecialchars($row['alamat']); ?></span>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <h5>📱 Data Barang</h5>
                                    <div class="info-row">
                                        <span class="info-label">Jenis:</span>
                                        <span class="info-value"><?php echo $row['jenis_barang']; ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Merk & Tipe:</span>
                                        <span class="info-value"><?php echo $row['merk'] . ' ' . $row['tipe']; ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Kondisi:</span>
                                        <span class="info-value"><?php echo $row['kondisi']; ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">IMEI/Serial:</span>
                                        <span class="info-value"><?php echo $row['imei_serial']; ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h5>💰 Data Pinjaman</h5>
                                    <div class="info-row">
                                        <span class="info-label">Harga Pasar:</span>
                                        <span class="info-value">Rp <?php echo number_format($row['harga_pasar'], 0, ',', '.'); ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Pinjaman:</span>
                                        <span class="info-value"><strong>Rp <?php echo number_format($row['jumlah_pinjaman'], 0, ',', '.'); ?></strong></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Bunga:</span>
                                        <span class="info-value"><?php echo $row['bunga']; ?>% per bulan</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Durasi:</span>
                                        <span class="info-value"><?php echo $row['lama_gadai']; ?> bulan</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Jatuh Tempo:</span>
                                        <span class="info-value text-danger"><strong><?php echo date('d M Y', strtotime($row['tanggal_jatuh_tempo'])); ?></strong></span>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <h5>📸 Foto</h5>
                                    <div class="d-flex gap-3">
                                        <?php if ($row['foto_ktp']): ?>
                                            <div>
                                                <p class="mb-1"><strong>KTP:</strong></p>
                                                <img src="<?php echo $row['foto_ktp']; ?>" class="image-preview" alt="KTP" onclick="window.open(this.src)">
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($row['foto_barang']): ?>
                                            <div>
                                                <p class="mb-1"><strong>Barang:</strong></p>
                                                <img src="<?php echo $row['foto_barang']; ?>" class="image-preview" alt="Barang" onclick="window.open(this.src)">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex gap-2 justify-content-end">
                                <button type="button" class="btn btn-approve" data-bs-toggle="modal" data-bs-target="#approveModal<?php echo $row['id']; ?>">
                                    ✅ Setujui
                                </button>
                                
                                <button type="button" class="btn btn-reject" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $row['id']; ?>">
                                    ❌ Tolak
                                </button>
                            </div>
                        </div>
                        
                        <!-- Approve Modal -->
                        <div class="modal fade" id="approveModal<?php echo $row['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header" style="background: linear-gradient(135deg, #28a745, #20c997); color: white;">
                                        <h5 class="modal-title">✅ Setujui Pengajuan</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            
                                            <div class="alert alert-info">
                                                <strong>💰 Nominal yang Diajukan:</strong><br>
                                                <h4 class="mb-0 mt-2">Rp <?php echo number_format($row['jumlah_pinjaman'], 0, ',', '.'); ?></h4>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label"><strong>Nominal yang Disetujui:</strong> <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text">Rp</span>
                                                    <input type="number" class="form-control" name="jumlah_disetujui" required 
                                                           value="<?php echo $row['jumlah_pinjaman']; ?>" 
                                                           min="100000" 
                                                           max="<?php echo $row['harga_pasar'] * 0.7; ?>"
                                                           placeholder="Masukkan nominal yang disetujui">
                                                </div>
                                                <small class="text-muted">
                                                    Max: Rp <?php echo number_format($row['harga_pasar'] * 0.7, 0, ',', '.'); ?> (70% dari harga pasar)
                                                </small>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Keterangan (Opsional):</label>
                                                <textarea class="form-control" name="keterangan_admin" rows="3" placeholder="Catatan untuk nasabah (jika ada penyesuaian nominal, dll)"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-success">✅ Setujui Pengajuan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Reject Modal -->
                        <div class="modal fade" id="rejectModal<?php echo $row['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header" style="background: linear-gradient(135deg, #dc3545, #c82333); color: white;">
                                        <h5 class="modal-title">❌ Tolak Pengajuan</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <label class="form-label">Alasan Penolakan:</label>
                                            <textarea class="form-control" name="alasan_reject" rows="4" required placeholder="Masukkan alasan penolakan..."></textarea>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-danger">Tolak Pengajuan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Approved Tab -->
            <div class="tab-pane fade" id="approved" role="tabpanel">
                <?php if (empty($approved_data)): ?>
                    <div class="alert alert-info">Belum ada pengajuan yang disetujui.</div>
                <?php else: ?>
                    <?php foreach ($approved_data as $row): ?>
                        <?php
                        $pokok_admin = !empty($row['jumlah_disetujui']) ? (float)$row['jumlah_disetujui'] : (float)$row['jumlah_pinjaman'];
                        $bunga_admin = (float)$row['bunga'];
                        $lama_admin = (int)$row['lama_gadai'];
                        $bunga_total_admin = $pokok_admin * ($bunga_admin / 100) * $lama_admin;
                        $denda_admin = !empty($row['denda_terakumulasi']) ? (float)$row['denda_terakumulasi'] : 0;
                        $total_tebus_admin = !empty($row['total_tebus']) ? (float)$row['total_tebus'] : ($pokok_admin + $bunga_total_admin + $denda_admin);
                        ?>
                        <div class="data-card approved">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="no-transaksi">#<?php echo str_pad($row['id'], 6, '0', STR_PAD_LEFT); ?></div>
                                    <small class="text-muted">Disetujui: <?php echo date('d M Y H:i', strtotime($row['verified_at'])); ?></small>
                                </div>
                                <span class="badge-approved">✅ DISETUJUI</span>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <strong><?php echo htmlspecialchars($row['nama_nasabah']); ?></strong><br>
                                    <small><?php echo $row['jenis_barang']; ?>: <?php echo $row['merk'] . ' ' . $row['tipe']; ?></small>
                                </div>
                                <div class="col-md-5">
                                    <?php if ($row['jumlah_disetujui']): ?>
                                        <small class="text-muted">Diajukan: <del>Rp <?php echo number_format($row['jumlah_pinjaman'], 0, ',', '.'); ?></del></small><br>
                                        <strong class="text-success">Disetujui: Rp <?php echo number_format($row['jumlah_disetujui'], 0, ',', '.'); ?></strong>
                                        <?php if ($row['jumlah_disetujui'] != $row['jumlah_pinjaman']): ?>
                                            <span class="badge bg-warning text-dark ms-1">Disesuaikan</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <strong>Pinjaman: Rp <?php echo number_format($row['jumlah_pinjaman'], 0, ',', '.'); ?></strong>
                                    <?php endif; ?>
                                    <br>
                                    <small>Jatuh tempo: <?php echo date('d M Y', strtotime($row['tanggal_jatuh_tempo'])); ?></small>
                                    <?php if (!empty($row['denda_terakumulasi']) && $row['denda_terakumulasi'] > 0): ?>
                                        <br>
                                        <small class="text-danger">Denda: Rp <?php echo number_format($row['denda_terakumulasi'], 0, ',', '.'); ?></small>
                                    <?php endif; ?>
                                    <br>
                                    <small><strong>Total Tebus: Rp <?php echo number_format($total_tebus_admin, 0, ',', '.'); ?></strong></small>
                                </div>
                                <div class="col-md-3">
                                    <small>No. HP: <?php echo $row['no_hp']; ?></small>
                                </div>
                            </div>
                            
                            <?php if ($row['keterangan_admin']): ?>
                                <div class="alert alert-info mt-3 mb-0" style="padding: 10px; font-size: 0.9rem;">
                                    <strong>📝 Catatan Admin:</strong> <?php echo nl2br(htmlspecialchars($row['keterangan_admin'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Rejected Tab -->
            <div class="tab-pane fade" id="rejected" role="tabpanel">
                <?php if (empty($rejected_data)): ?>
                    <div class="alert alert-info">Belum ada pengajuan yang ditolak.</div>
                <?php else: ?>
                    <?php foreach ($rejected_data as $row): ?>
                        <div class="data-card rejected">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="no-transaksi">#<?php echo str_pad($row['id'], 6, '0', STR_PAD_LEFT); ?></div>
                                    <small class="text-muted">Ditolak: <?php echo date('d M Y H:i', strtotime($row['verified_at'])); ?></small>
                                </div>
                                <span class="badge-rejected">❌ DITOLAK</span>
                            </div>
                            
                            <div class="row mb-2">
                                <div class="col-md-4">
                                    <strong><?php echo htmlspecialchars($row['nama_nasabah']); ?></strong><br>
                                    <small><?php echo $row['jenis_barang']; ?>: <?php echo $row['merk'] . ' ' . $row['tipe']; ?></small>
                                </div>
                                <div class="col-md-4">
                                    <strong>Pinjaman: Rp <?php echo number_format($row['jumlah_pinjaman'], 0, ',', '.'); ?></strong>
                                </div>
                                <div class="col-md-4">
                                    <small>No. HP: <?php echo $row['no_hp']; ?></small>
                                </div>
                            </div>
                            
                            <?php if ($row['reject_reason']): ?>
                                <div class="alert alert-danger mb-0 mt-2">
                                    <strong>Alasan Penolakan:</strong> <?php echo htmlspecialchars($row['reject_reason']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Pelunasan Pending Tab -->
            <div class="tab-pane fade" id="pelunasan" role="tabpanel">
                <?php if ($pelunasan_error): ?>
                    <div class="alert alert-warning"><?php echo $pelunasan_error; ?></div>
                <?php elseif (empty($pelunasan_data)): ?>
                    <div class="alert alert-info">Tidak ada pelunasan pending.</div>
                <?php else: ?>
                    <?php foreach ($pelunasan_data as $row): ?>
                        <?php
                        $bukti_count = (int)($row['bukti_count'] ?? 0);
                        $bukti_total = !empty($row['bukti_total']) ? (float)$row['bukti_total'] : 0;
                        $bukti_file = $row['bukti_latest'] ?? null;
                        $bukti_path = $bukti_file ? 'payment/' . $row['no_ktp'] . '/' . $bukti_file : null;
                        ?>
                        <div class="data-card approved">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="no-transaksi">#<?php echo str_pad($row['id'], 6, '0', STR_PAD_LEFT); ?></div>
                                    <small class="text-muted">Permintaan: <?php echo date('d M Y H:i', strtotime($row['updated_at'])); ?></small>
                                </div>
                                <span class="badge-approved">💰 PELUNASAN</span>
                            </div>

                            <div class="row mb-2">
                                <div class="col-md-4">
                                    <strong><?php echo htmlspecialchars($row['nama_nasabah']); ?></strong><br>
                                    <small><?php echo $row['jenis_barang']; ?>: <?php echo $row['merk'] . ' ' . $row['tipe']; ?></small>
                                </div>
                                <div class="col-md-4">
                                    <small>No. KTP: <?php echo $row['no_ktp']; ?></small><br>
                                    <small>No. HP: <?php echo $row['no_hp']; ?></small>
                                </div>
                                <div class="col-md-4">
                                    <small><strong>Total Tebus:</strong> Rp <?php echo number_format($row['total_tebus'], 0, ',', '.'); ?></small><br>
                                    <small><strong>Bukti:</strong> <?php echo $bukti_count; ?> file (Rp <?php echo number_format($bukti_total, 0, ',', '.'); ?>)</small>
                                </div>
                            </div>

                            <?php if ($bukti_path): ?>
                                <div class="alert alert-info" style="padding: 10px;">
                                    <strong>🧾 Bukti Terakhir:</strong>
                                    <a href="<?php echo $bukti_path; ?>" target="_blank">Lihat bukti</a>
                                </div>
                            <?php endif; ?>

                            <div class="d-flex gap-2 justify-content-end">
                                <form method="POST">
                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="action" value="acc_pelunasan">
                                    <button type="submit" class="btn btn-approve" <?php echo $bukti_count <= 0 ? 'disabled' : ''; ?>>
                                        ✅ ACC Pelunasan
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- List Tab -->
            <div class="tab-pane fade" id="list" role="tabpanel">
                <div class="alert alert-info" style="border-radius: 15px;">
                    <strong>📥 Upload CSV:</strong> Gunakan format kolom berikut (header wajib):
                    <div style="margin-top: 6px; font-family: monospace; font-size: 0.9rem;">
                        nik,nama,no_hp,jenis_barang,merk,tipe,alamat,kondisi,imei_serial,harga_pasar,jumlah_pinjaman,bunga,lama_gadai,tanggal_gadai,tanggal_jatuh_tempo
                    </div>
                    <div style="margin-top: 6px; font-size: 0.9rem;">
                        Format tanggal: YYYY-MM-DD. Jenis barang: HP/Laptop. Kondisi: Sangat Baik/Baik/Cukup.
                    </div>
                    <div style="margin-top: 10px;">
                        <a href="admin_verifikasi.php?download_template=excel" class="btn btn-outline-primary btn-sm">Download Template Excel</a>
                    </div>
                </div>

                <form method="POST" enctype="multipart/form-data" class="mb-3">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-8">
                            <input type="file" class="form-control" name="csv_file" accept=".csv" required>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" name="upload_csv" class="btn btn-primary w-100">Upload CSV</button>
                        </div>
                    </div>
                </form>

                <?php if (empty($all_data)): ?>
                    <div class="alert alert-info">Belum ada data gadai.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Registrasi</th>
                                    <th>Nama</th>
                                    <th>No. KTP</th>
                                    <th>No. HP</th>
                                    <th>Barang</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_data as $index => $row): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>#<?php echo str_pad($row['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_nasabah']); ?></td>
                                        <td><?php echo $row['no_ktp']; ?></td>
                                        <td><?php echo $row['no_hp']; ?></td>
                                        <td><?php echo $row['jenis_barang'] . ' ' . $row['merk'] . ' ' . $row['tipe']; ?></td>
                                        <td><?php echo $row['status']; ?></td>
                                        <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
