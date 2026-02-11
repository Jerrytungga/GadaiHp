<?php
require_once 'database.php';

$result = null;
$error = null;

if (isset($_GET['no_registrasi']) || isset($_POST['no_registrasi'])) {
    $no_registrasi = $_GET['no_registrasi'] ?? $_POST['no_registrasi'];
    
    try {
        $sql = "SELECT * FROM data_gadai WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$no_registrasi]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            $error = "Nomor registrasi tidak ditemukan!";
        }
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Status Pengajuan - Gadai Cepat Timika</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 30px 0;
        }
        
        .container-box {
            background: #ffffff;
            border-radius: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 50px;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .page-title {
            font-family: 'Raleway', sans-serif;
            font-weight: 800;
            font-size: 2.5rem;
            background: linear-gradient(135deg, #0056b3, #007bff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-align: center;
            margin-bottom: 10px;
        }
        
        .page-subtitle {
            text-align: center;
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 40px;
        }
        
        .search-box {
            background: linear-gradient(135deg, #e3f2fd, #f0f8ff);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .form-control {
            border: 2px solid #e3f2fd;
            border-radius: 15px;
            padding: 12px 20px;
            font-size: 1.1rem;
        }
        
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15);
        }
        
        .btn-search {
            background: linear-gradient(135deg, #0056b3, #007bff);
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px 40px;
            border-radius: 50px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }
        
        .btn-search:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 86, 179, 0.4);
            color: white;
        }
        
        .btn-back {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            border: none;
            color: white;
            font-weight: 600;
            padding: 10px 30px;
            border-radius: 50px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-back:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.4);
        }
        
        .status-card {
            border-radius: 20px;
            padding: 30px;
            margin-top: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .status-pending {
            background: linear-gradient(135deg, #fff8e1, #fffbf0);
            border: 3px solid #ffc107;
        }
        
        .status-approved {
            background: linear-gradient(135deg, #e8f5e9, #f1f8f4);
            border: 3px solid #28a745;
        }
        
        .status-rejected {
            background: linear-gradient(135deg, #ffebee, #fef5f6);
            border: 3px solid #dc3545;
        }
        
        .status-badge {
            display: inline-block;
            padding: 15px 30px;
            border-radius: 50px;
            font-weight: 800;
            font-size: 1.3rem;
            margin-bottom: 20px;
        }
        
        .badge-pending {
            background: linear-gradient(135deg, #ffc107, #ff9800);
            color: white;
        }
        
        .badge-approved {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .badge-rejected {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }
        
        .info-section {
            margin-top: 25px;
        }
        
        .info-section h5 {
            font-family: 'Raleway', sans-serif;
            font-weight: 700;
            color: #0056b3;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e3f2fd;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .info-label {
            font-weight: 600;
            color: #555;
        }
        
        .info-value {
            color: #333;
            font-weight: 500;
        }
        
        .alert-box {
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
        }
        
        @media (max-width: 768px) {
            .container-box {
                padding: 30px 20px;
            }
            
            .page-title {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="container-box">
            <h1 class="page-title">🔍 CEK STATUS PENGAJUAN</h1>
            <p class="page-subtitle">Masukkan nomor registrasi untuk melihat status pengajuan gadai Anda</p>
            
            <a href="index.php" class="btn-back">← Kembali ke Beranda</a>
            
            <div class="search-box">
                <form method="POST">
                    <div class="input-group mb-3">
                        <span class="input-group-text" style="background: linear-gradient(135deg, #0056b3, #007bff); color: white; border: none; border-radius: 15px 0 0 15px; font-weight: 600;">#</span>
                        <input type="text" class="form-control" name="no_registrasi" placeholder="Contoh: 000001" required pattern="[0-9]+" title="Masukkan nomor registrasi (angka saja)">
                    </div>
                    <button type="submit" class="btn btn-search w-100">🔎 Cek Status</button>
                </form>
                
                <div class="alert alert-info mt-3 mb-0" style="border-radius: 15px;">
                    <strong>💡 Tip:</strong> Nomor registrasi dikirim via SMS/WhatsApp setelah Anda mengisi form pengajuan.
                </div>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-box">
                    <strong>❌ Error!</strong> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($result): ?>
                <?php
                $status_class = '';
                $badge_class = '';
                $status_icon = '';
                $status_text = '';
                $status_message = '';
                
                switch($result['status']) {
                    case 'Pending':
                        $status_class = 'status-pending';
                        $badge_class = 'badge-pending';
                        $status_icon = '⏳';
                        $status_text = 'MENUNGGU VERIFIKASI';
                        $status_message = 'Pengajuan Anda sedang dalam proses verifikasi oleh admin. Mohon tunggu notifikasi selanjutnya via WhatsApp.';
                        break;
                    case 'Disetujui':
                        $status_class = 'status-approved';
                        $badge_class = 'badge-approved';
                        $status_icon = '✅';
                        $status_text = 'DISETUJUI';
                        $status_message = 'Selamat! Pengajuan Anda telah disetujui. Silakan datang ke kantor kami untuk melanjutkan proses pencairan dana.';
                        break;
                    case 'Ditolak':
                        $status_class = 'status-rejected';
                        $badge_class = 'badge-rejected';
                        $status_icon = '❌';
                        $status_text = 'DITOLAK';
                        $status_message = 'Maaf, pengajuan Anda ditolak. Anda dapat mengajukan kembali setelah memenuhi persyaratan.';
                        break;
                }
                ?>
                
                <div class="status-card <?php echo $status_class; ?>">
                    <div class="text-center">
                        <div class="status-badge <?php echo $badge_class; ?>">
                            <?php echo $status_icon . ' ' . $status_text; ?>
                        </div>
                        <h4>Nomor Registrasi: #<?php echo str_pad($result['id'], 6, '0', STR_PAD_LEFT); ?></h4>
                        <p class="text-muted">Diajukan pada: <?php echo date('d F Y, H:i', strtotime($result['created_at'])); ?></p>
                    </div>
                    
                    <div class="alert-box" style="background: rgba(255, 255, 255, 0.7);">
                        <p class="mb-0"><strong>Status:</strong> <?php echo $status_message; ?></p>
                    </div>
                    
                    <?php if ($result['status'] == 'Ditolak' && $result['reject_reason']): ?>
                        <div class="alert alert-danger alert-box">
                            <strong>📝 Alasan Penolakan:</strong><br>
                            <?php echo htmlspecialchars($result['reject_reason']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="info-section">
                        <h5>📱 Informasi Barang</h5>
                        <div class="info-row">
                            <span class="info-label">Jenis:</span>
                            <span class="info-value"><?php echo $result['jenis_barang']; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Merk & Tipe:</span>
                            <span class="info-value"><?php echo $result['merk'] . ' ' . $result['tipe']; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Kondisi:</span>
                            <span class="info-value"><?php echo $result['kondisi']; ?></span>
                        </div>
                    </div>
                    
                    <div class="info-section">
                        <h5>💰 Informasi Pinjaman</h5>
                        <div class="info-row">
                            <span class="info-label">Pengajuan Anda:</span>
                            <span class="info-value">Rp <?php echo number_format($result['jumlah_pinjaman'], 0, ',', '.'); ?></span>
                        </div>
                        
                        <?php if ($result['jumlah_disetujui'] && $result['status'] == 'Disetujui'): ?>
                            <div class="info-row" style="background: linear-gradient(135deg, #e8f5e9, #f1f8f4); padding: 15px; border-radius: 10px; margin: 10px 0;">
                                <span class="info-label" style="font-size: 1.1rem;">✅ Nominal Disetujui:</span>
                                <span class="info-value" style="font-size: 1.3rem; font-weight: 800; color: #28a745;">
                                    Rp <?php echo number_format($result['jumlah_disetujui'], 0, ',', '.'); ?>
                                </span>
                            </div>
                            
                            <?php if ($result['jumlah_disetujui'] != $result['jumlah_pinjaman']): ?>
                                <div class="alert alert-warning" style="padding: 10px; font-size: 0.9rem; margin-top: 10px;">
                                    <strong>ℹ️ Penyesuaian Nominal:</strong> 
                                    <?php 
                                    $selisih = $result['jumlah_disetujui'] - $result['jumlah_pinjaman'];
                                    if ($selisih > 0) {
                                        echo "Ditambah Rp " . number_format(abs($selisih), 0, ',', '.');
                                    } else {
                                        echo "Dikurangi Rp " . number_format(abs($selisih), 0, ',', '.');
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <div class="info-row">
                            <span class="info-label">Bunga:</span>
                            <span class="info-value"><?php echo $result['bunga']; ?>% per bulan</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Durasi:</span>
                            <span class="info-value"><?php echo $result['lama_gadai']; ?> bulan</span>
                        </div>
                        <?php if ($result['status'] == 'Disetujui'): ?>
                            <div class="info-row">
                                <span class="info-label">Tanggal Jatuh Tempo:</span>
                                <span class="info-value text-danger"><strong><?php echo date('d F Y', strtotime($result['tanggal_jatuh_tempo'])); ?></strong></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($result['keterangan_admin'] && $result['status'] == 'Disetujui'): ?>
                        <div class="alert alert-info" style="border-radius: 15px; padding: 15px; margin-top: 20px;">
                            <strong>📝 Catatan dari Admin:</strong><br>
                            <?php echo nl2br(htmlspecialchars($result['keterangan_admin'])); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($result['verified_at']): ?>
                        <div class="info-section">
                            <h5>ℹ️ Informasi Verifikasi</h5>
                            <div class="info-row">
                                <span class="info-label">Diverifikasi pada:</span>
                                <span class="info-value"><?php echo date('d F Y, H:i', strtotime($result['verified_at'])); ?></span>
                            </div>
                            <?php if ($result['verified_by']): ?>
                                <div class="info-row">
                                    <span class="info-label">Diverifikasi oleh:</span>
                                    <span class="info-value"><?php echo $result['verified_by']; ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="text-center mt-4">
                    <a href="https://wa.me/6285823091908" target="_blank" class="btn btn-success" style="border-radius: 50px; padding: 12px 30px; font-weight: 600;">
                        💬 Hubungi Kami via WhatsApp
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
