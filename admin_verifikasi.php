<?php
require_once 'database.php';
require_once 'whatsapp_helper.php';

// Global message for user feedback (initialize to avoid undefined variable warnings)
$message = '';
$message_type = '';

// --- Manual pelunasan oleh admin (lunas sebelum jatuh tempo) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'manual_lunas') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    try {
        $stmt = $db->prepare("SELECT * FROM data_gadai WHERE id = ?");
        $stmt->execute([$id]);
        $data_row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data_row) {
            $message = 'Data gadai tidak ditemukan.';
            $message_type = 'danger';
        } elseif (!in_array($data_row['status'], ['Disetujui', 'Diperpanjang'], true)) {
            $message = 'Pelunasan manual hanya dapat dilakukan untuk status Disetujui atau Diperpanjang.';
            $message_type = 'warning';
        } else {
            // Hitung total tebus (pokok + bunga penuh + denda sudah tercatat)
            $pokok = !empty($data_row['jumlah_disetujui']) ? (float)$data_row['jumlah_disetujui'] : (float)$data_row['jumlah_pinjaman'];
            $bunga_pct = isset($data_row['bunga']) ? (float)$data_row['bunga'] : 0.0;
            $lama = isset($data_row['lama_gadai']) ? (int)$data_row['lama_gadai'] : 0;
            $denda_existing = !empty($data_row['denda_terakumulasi']) ? (float)$data_row['denda_terakumulasi'] : 0.0;

            $bunga_total = $pokok * ($bunga_pct / 100) * $lama;
            $admin_fee = round($pokok * 0.01);
            $biaya_asuransi = 10000;
            $total_tebus = $pokok + $bunga_total + $admin_fee + $biaya_asuransi + $denda_existing;

            // Ambil optional metode dan catatan dari form
            $metode_input = !empty($_POST['metode']) ? trim($_POST['metode']) : 'tunai_admin';
            $keterangan_admin_input = !empty($_POST['keterangan_admin']) ? trim($_POST['keterangan_admin']) : null;

            // Simpan perubahan dan catat transaksi pelunasan (tunai oleh admin)
            try {
                $db->beginTransaction();
                $update = $db->prepare("UPDATE data_gadai SET status = 'Lunas', total_tebus = ?, catatan_admin = ?, updated_at = NOW() WHERE id = ?");
                $update->execute([$total_tebus, $keterangan_admin_input, $id]);

                // Insert transaksi (kolom sesuai penggunaan di cek_status.php)
                // Jika tabel `transaksi` tidak ada, lewati insert agar proses pelunasan tetap berhasil.
                $skippedTransaksi = false;
                $checkTbl = $db->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'transaksi'");
                try {
                    $checkTbl->execute();
                    $tblExists = (int)$checkTbl->fetchColumn() > 0;
                } catch (Exception $e) {
                    $tblExists = false;
                }

                if ($tblExists) {
                    $insert = $db->prepare("INSERT INTO transaksi (imei, serial_number, jenis_barang, merk, tipe, pelanggan_nik, barang_id, jumlah_bayar, keterangan, metode_pembayaran, bukti) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $insert->execute([
                        null, // imei tidak ada kolom terpisah di data_gadai
                        null, // serial_number tidak ada kolom terpisah
                        $data_row['jenis_barang'] ?? null,
                        $data_row['merk_barang'] ?? null,
                        $data_row['spesifikasi_barang'] ?? null,
                        $data_row['nik'],
                        $id,
                        $total_tebus,
                        'pelunasan_admin',
                        $metode_input,
                        null
                    ]);
                } else {
                    // tidak ada tabel transaksi; lewati perekaman transaksi
                    $skippedTransaksi = true;
                }

                $db->commit();

                // Re-fetch updated row for notifications
                $stmt2 = $db->prepare("SELECT * FROM data_gadai WHERE id = ?");
                $stmt2->execute([$id]);
                $fresh = $stmt2->fetch(PDO::FETCH_ASSOC);
                try {
                    if (isset($whatsapp)) {
                        $whatsapp->notifyUserPelunasanVerified($fresh);
                    }
                } catch (Exception $e) {
                    error_log('WhatsApp notify failed: ' . $e->getMessage());
                }

                $message = 'Pelunasan manual berhasil. Total tebus: Rp ' . number_format($total_tebus, 0, ',', '.') . '.';
                $message_type = 'success';
            } catch (Exception $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                $message = 'Gagal memproses pelunasan: ' . $e->getMessage();
                $message_type = 'danger';
            }
        }
    } catch (Exception $e) {
        $message = 'Gagal memproses permintaan: ' . $e->getMessage();
        $message_type = 'danger';
    }
}

// Authentication handled by auth_check.php (included at top of file)



// Fetch pending submissions
$pending_sql = "SELECT * FROM data_gadai WHERE status = 'Pending' ORDER BY created_at DESC";
$pending_stmt = $db->query($pending_sql);
$pending_data = $pending_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch approved submissions
$approved_sql = "SELECT * FROM data_gadai WHERE status = 'Disetujui' ORDER BY updated_at DESC LIMIT 10";
$approved_stmt = $db->query($approved_sql);
$approved_data = $approved_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch rejected submissions
$rejected_sql = "SELECT * FROM data_gadai WHERE status = 'Ditolak' ORDER BY updated_at DESC LIMIT 10";
$rejected_stmt = $db->query($rejected_sql);
$rejected_data = $rejected_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all submissions (for list table) - include fields needed for the new list columns
$all_sql = "SELECT id, nama, nik, no_wa, merk_barang, spesifikasi_barang, kondisi_barang, jumlah_pinjaman, jumlah_disetujui, bunga, lama_gadai, denda_terakumulasi, total_tebus, tanggal_gadai, tanggal_jatuh_tempo, status, catatan_admin, created_at FROM data_gadai ORDER BY created_at DESC";
$all_stmt = $db->query($all_sql);
$all_data = $all_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch pelunasan pending submissions
$pelunasan_data = [];
$pelunasan_error = null;
try {
    // Note: aksi_jatuh_tempo column removed - pelunasan pending now based on transaksi with keterangan 'pelunasan'
    $pelunasan_sql = "SELECT dg.*, 
        (SELECT COUNT(*) FROM transaksi t WHERE t.barang_id = dg.id AND t.pelanggan_nik = dg.nik AND t.keterangan = 'pelunasan') AS bukti_count,
        (SELECT SUM(t.jumlah_bayar) FROM transaksi t WHERE t.barang_id = dg.id AND t.pelanggan_nik = dg.nik AND t.keterangan = 'pelunasan') AS bukti_total,
        (SELECT t.bukti FROM transaksi t WHERE t.barang_id = dg.id AND t.pelanggan_nik = dg.nik AND t.keterangan = 'pelunasan' ORDER BY t.id DESC LIMIT 1) AS bukti_latest
        FROM data_gadai dg
        WHERE dg.status IN ('Disetujui', 'Diperpanjang')
        AND EXISTS (SELECT 1 FROM transaksi t WHERE t.barang_id = dg.id AND t.pelanggan_nik = dg.nik AND t.keterangan = 'pelunasan')
        ORDER BY dg.updated_at DESC";
    $pelunasan_stmt = $db->query($pelunasan_sql);
    $pelunasan_data = $pelunasan_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pelunasan_error = "Data pelunasan belum tersedia (cek tabel transaksi).";
}

    // Fetch perpanjangan pending submissions
    $perpanjangan_data = [];
    $perpanjangan_error = null;
    try {
        // Note: aksi_jatuh_tempo column removed - perpanjangan pending now based on transaksi with keterangan 'perpanjangan'
        $perpanjangan_sql = "SELECT dg.*, 
            (SELECT COUNT(*) FROM transaksi t WHERE t.barang_id = dg.id AND t.pelanggan_nik = dg.nik AND t.keterangan = 'perpanjangan') AS bukti_count,
            (SELECT SUM(t.jumlah_bayar) FROM transaksi t WHERE t.barang_id = dg.id AND t.pelanggan_nik = dg.nik AND t.keterangan = 'perpanjangan') AS bukti_total,
            (SELECT t.bukti FROM transaksi t WHERE t.barang_id = dg.id AND t.pelanggan_nik = dg.nik AND t.keterangan = 'perpanjangan' ORDER BY t.id DESC LIMIT 1) AS bukti_latest
            FROM data_gadai dg
            WHERE dg.status IN ('Disetujui', 'Diperpanjang')
            AND EXISTS (SELECT 1 FROM transaksi t WHERE t.barang_id = dg.id AND t.pelanggan_nik = dg.nik AND t.keterangan = 'perpanjangan')
            ORDER BY dg.updated_at DESC";
        $perpanjangan_stmt = $db->query($perpanjangan_sql);
        $perpanjangan_data = $perpanjangan_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $perpanjangan_error = "Data perpanjangan belum tersedia (cek tabel transaksi).";
    }

// Fetch overdue submissions for manual reminders
$reminder_data = [];
$reminder_error = null;
try {
    $reminder_sql = "SELECT dg.*, DATEDIFF(CURDATE(), dg.tanggal_jatuh_tempo) AS days_overdue
        FROM data_gadai dg
        WHERE dg.status IN ('Disetujui', 'Diperpanjang')
          AND dg.tanggal_jatuh_tempo IS NOT NULL
          AND dg.tanggal_jatuh_tempo < CURDATE()
        ORDER BY dg.tanggal_jatuh_tempo ASC";
    $reminder_stmt = $db->query($reminder_sql);
    $reminder_data = $reminder_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $reminder_error = 'Data reminder belum tersedia.';
}

// --- Fetch transaksi records (used for admin view of bukti perpanjangan / pelunasan) ---
$transaksi_data = [];
$transaksi_error = null;
try {
    // Include item identity fields from data_gadai so we can show a friendly item name
    $transaksi_sql = "SELECT t.*, dg.nama AS nama_nasabah, dg.jenis_barang, dg.merk_barang AS merk, dg.spesifikasi_barang AS tipe FROM transaksi t LEFT JOIN data_gadai dg ON t.barang_id = dg.id ORDER BY t.created_at DESC";
    $transaksi_stmt = $db->query($transaksi_sql);
    $transaksi_data = $transaksi_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $transaksi_error = "Data transaksi belum tersedia (cek tabel transaksi).";
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
        /* Ensure modals appear above transformed/animated elements (stacking context fixes) */
        .modal {
            z-index: 20000 !important;
        }
        .modal-backdrop.show {
            z-index: 19999 !important;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>🔍 Panel Verifikasi Admin</h1>
        </div>
    </div>
    
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
                <button class="nav-link" id="perpanjangan-tab" data-bs-toggle="tab" data-bs-target="#perpanjangan" type="button">
                    🔁 Perpanjangan Pending (<?php echo count($perpanjangan_data); ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="reminder-tab" data-bs-toggle="tab" data-bs-target="#reminder" type="button">
                    ⏰ Reminder Manual (<?php echo count($reminder_data); ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="transaksi-tab" data-bs-toggle="tab" data-bs-target="#transaksi" type="button">
                    🧾 Transaksi (<?php echo !empty($transaksi_data) ? count($transaksi_data) : 0; ?>)
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
                                        <span class="info-value"><?php echo htmlspecialchars($row['nama']); ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">No. KTP:</span>
                                        <span class="info-value"><?php echo $row['nik']; ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">No. HP:</span>
                                        <span class="info-value"><?php echo $row['no_wa']; ?></span>
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
                                        <span class="info-value"><?php echo $row['merk_barang'] . ' ' . $row['spesifikasi_barang']; ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Kondisi:</span>
                                        <span class="info-value"><?php echo $row['kondisi_barang']; ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Spesifikasi:</span>
                                        <span class="info-value"><?php echo $row['spesifikasi_barang']; ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h5>💰 Data Pinjaman</h5>
                                    <div class="info-row">
                                        <span class="info-label">Harga Pasar:</span>
                                        <span class="info-value">Rp <?php echo number_format($row['nilai_taksiran'] ?? 0, 0, ',', '.'); ?></span>
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
                                                <label class="form-label"><strong>Harga Pasar (Estimasi oleh Admin):</strong> <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text">Rp</span>
                                                    <input type="number" class="form-control" id="harga_pasar_<?php echo $row['id']; ?>" name="harga_pasar" required 
                                                           value="<?php echo isset($row['nilai_taksiran']) ? $row['nilai_taksiran'] : ''; ?>" 
                                                           min="0" step="0.01"
                                                           placeholder="Masukkan estimasi harga pasar">
                                                </div>
                                                <small class="text-muted">Masukkan estimasi harga pasar; gunakan opsi persentase di bawah untuk menentukan nominal yang disetujui.</small>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label"><strong>Opsi Persentase Disetujui:</strong></label>
                                                <div class="d-flex gap-2 align-items-center">
                                                    <div class="form-check">
                                                        <input class="form-check-input percent-option" type="radio" name="percent_option" id="percent50_<?php echo $row['id']; ?>" value="50" checked>
                                                        <label class="form-check-label" for="percent50_<?php echo $row['id']; ?>">50%</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input percent-option" type="radio" name="percent_option" id="percent60_<?php echo $row['id']; ?>" value="60">
                                                        <label class="form-check-label" for="percent60_<?php echo $row['id']; ?>">60%</label>
                                                    </div>
                                                    <div class="form-check d-flex align-items-center">
                                                        <input class="form-check-input percent-option" type="radio" name="percent_option" id="percentCustom_<?php echo $row['id']; ?>" value="custom">
                                                        <label class="form-check-label me-2" for="percentCustom_<?php echo $row['id']; ?>">Custom</label>
                                                        <input type="number" class="form-control" id="custom_percent_<?php echo $row['id']; ?>" name="custom_percent" placeholder="%" min="1" max="100" style="width:100px; display:none;">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label"><strong>Nominal yang Disetujui:</strong> <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text">Rp</span>
                                                    <input type="number" class="form-control" id="jumlah_disetujui_<?php echo $row['id']; ?>" name="jumlah_disetujui" required 
                                                           value="<?php echo $row['jumlah_pinjaman']; ?>" 
                                                           min="100000" 
                                                           placeholder="Nominal yang disetujui" readonly>
                                                </div>
                                                <small class="text-muted">Nominal terisi otomatis sesuai persentase. Untuk custom, masukkan persen di field Custom.</small>
                                            </div>

                                            <!-- BRIVA generation removed per request -->

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
                        <script>
                            (function() {
                                var hargaInput = document.getElementById('harga_pasar_<?php echo $row['id']; ?>');
                                var jumlahInput = document.getElementById('jumlah_disetujui_<?php echo $row['id']; ?>');
                                var percentRadios = document.querySelectorAll('#approveModal<?php echo $row['id']; ?> input[name="percent_option"]');
                                var customPercent = document.getElementById('custom_percent_<?php echo $row['id']; ?>');
                                var generateBriva = document.getElementById('generate_briva_<?php echo $row['id']; ?>');

                                function getSelectedPercent() {
                                    // Query the checked radio inside this modal to avoid global collisions
                                    var sel = document.querySelector('#approveModal<?php echo $row['id']; ?> input[name="percent_option"]:checked');
                                    if (!sel) {
                                        // Fallback to default 50%
                                        return 50;
                                    }
                                    var selected = sel.value;
                                    if (selected === 'custom') {
                                        var v = parseFloat(customPercent.value) || 0;
                                        return v;
                                    }
                                    return parseFloat(selected) || 50;
                                }

                                function compute() {
                                    var harga = parseFloat(hargaInput.value) || 0;
                                    var pct = getSelectedPercent();
                                    var approved = Math.round(harga * (pct / 100) * 100) / 100;
                                    jumlahInput.value = approved;
                                }

                                // Show/hide custom percent input
                                percentRadios.forEach(function(radio) {
                                    radio.addEventListener('change', function() {
                                        if (this.value === 'custom') {
                                            customPercent.style.display = 'inline-block';
                                            customPercent.focus();
                                        } else {
                                            customPercent.style.display = 'none';
                                        }
                                        compute();
                                    });
                                });

                                hargaInput.addEventListener('input', compute);
                                customPercent.addEventListener('input', compute);

                                // Initialize
                                compute();
                            })();
                        </script>
                        
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

                        // Compute days late and penalty display (Rp30.000 per hari, cap 7 hari)
                        $days_late = 0;
                        if (!empty($row['tanggal_jatuh_tempo'])) {
                            $due_ts = strtotime($row['tanggal_jatuh_tempo']);
                            if ($due_ts !== false) {
                                $now_ts = time();
                                $diff_days = floor(($now_ts - $due_ts) / 86400);
                                if ($diff_days > 0) {
                                    $days_late = (int)$diff_days;
                                }
                            }
                        }
                        $daily_penalty = 30000;
                        $denda_calc = min($days_late, 7) * $daily_penalty; // only up to 7 days counted

                        // Prefer persisted denda_terakumulasi if present, otherwise show calculated value
                        $denda_display = $denda_admin > 0 ? $denda_admin : $denda_calc;

                        // Recompute visible total tebus for admin UI (pokok + bunga + admin fee + biaya asuransi + denda)
                        $admin_fee = round($pokok_admin * 0.01);
                        $biaya_asuransi = 10000;
                        $total_tebus_admin = !empty($row['total_tebus']) ? (float)$row['total_tebus'] : ($pokok_admin + $bunga_total_admin + $admin_fee + $biaya_asuransi + $denda_display);
                        ?>
                        <div class="data-card approved">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="no-transaksi">#<?php echo str_pad($row['id'], 6, '0', STR_PAD_LEFT); ?></div>
                                    <small class="text-muted">Disetujui: <?php echo !empty($row['verified_at']) ? date('d M Y H:i', strtotime($row['verified_at'])) : date('d M Y H:i', strtotime($row['created_at'])); ?></small>
                                </div>
                                <span class="badge-approved">✅ DISETUJUI</span>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <strong><?php echo htmlspecialchars($row['nama']); ?></strong><br>
                                    <small><?php echo $row['jenis_barang']; ?>: <?php echo $row['merk_barang'] . ' ' . $row['spesifikasi_barang']; ?></small>
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
                                    
                                    <!-- Detail Perhitungan Total Tebus -->
                                    <div class="mt-2 p-2" style="background-color: #f8f9fa; border-radius: 8px; border-left: 3px solid #28a745;">
                                        <small><strong>📊 Detail Perhitungan:</strong></small><br>
                                        <small>
                                            • Pokok: <strong>Rp <?php echo number_format($pokok_admin, 0, ',', '.'); ?></strong><br>
                                            • Bunga (<?php echo $bunga_admin; ?>% × <?php echo $lama_admin; ?> bulan): <strong>Rp <?php echo number_format($bunga_total_admin, 0, ',', '.'); ?></strong><br>
                                            • Admin Fee (1%): <strong>Rp <?php echo number_format($admin_fee, 0, ',', '.'); ?></strong><br>
                                            • Biaya Asuransi: <strong>Rp <?php echo number_format($biaya_asuransi, 0, ',', '.'); ?></strong><br>
                                            <?php if ($denda_display > 0): ?>
                                            • Denda Keterlambatan (<?php echo $days_late; ?> hari): <strong class="text-danger">Rp <?php echo number_format($denda_display, 0, ',', '.'); ?></strong><br>
                                            <?php endif; ?>
                                            <hr style="margin: 5px 0;">
                                            • <strong>Total Tebus: <span class="text-success">Rp <?php echo number_format($total_tebus_admin, 0, ',', '.'); ?></span></strong>
                                        </small>
                                    </div>
                                    
                                    <?php if ($days_late > 0 && $days_late < 8): ?>
                                        <div class="mt-2">
                                            <small class="text-muted">Telat: <?php echo $days_late; ?> hari &middot; Tarif: Rp <?php echo number_format($daily_penalty, 0, ',', '.'); ?>/hari (max 7 hari)</small>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($days_late >= 8 || $row['status'] === 'Gagal Tebus'): ?>
                                        <div class="alert alert-danger mt-2 mb-0" style="padding:8px; font-size:0.9rem;">
                                            <strong>Gagal Tebus:</strong> item telah melewati batas maksimal pelunasan (lebih dari 7 hari terlambat). Sistem akan (atau telah) mengubah status dan mengirim notifikasi WhatsApp.
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-3">
                                    <small>No. HP: <?php echo $row['no_wa']; ?></small>
                                </div>
                            </div>
                            
                            <?php if ($row['catatan_admin']): ?>
                                <div class="alert alert-info mt-3 mb-0" style="padding: 10px; font-size: 0.9rem;">
                                    <strong>📝 Catatan Admin:</strong> <?php echo nl2br(htmlspecialchars($row['catatan_admin'])); ?>
                                </div>
                            <?php endif; ?>
                            <div class="d-flex gap-2 justify-content-end mt-3">
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#lunaskanModal<?php echo $row['id']; ?>">
                                    💸 Lunaskan Sekarang
                                </button>
                            </div>

                            <!-- Modal: Lunaskan Sekarang -->
                            <div class="modal fade" id="lunaskanModal<?php echo $row['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header" style="background: linear-gradient(135deg, #28a745, #20c997); color: white;">
                                            <h5 class="modal-title">💸 Konfirmasi Pelunasan Manual</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                <input type="hidden" name="action" value="manual_lunas">
                                                <p>Anda akan menandai pengajuan ini sebagai <strong>lunas</strong>. Estimasi total tebus:</p>
                                                <p><strong>Rp <?php echo number_format($total_tebus_admin, 0, ',', '.'); ?></strong></p>

                                                <div class="mb-3">
                                                    <label class="form-label">Metode Pembayaran (opsional)</label>
                                                    <select name="metode" class="form-control">
                                                        <option value="tunai_admin">Tunai - Admin</option>
                                                        <option value="transfer_admin">Transfer - Admin</option>
                                                    </select>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Catatan Admin (opsional)</label>
                                                    <textarea name="keterangan_admin" class="form-control" rows="2"></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-approve">✅ Lunaskan Sekarang</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
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
                                    <small class="text-muted">Ditolak: <?php echo !empty($row['verified_at']) ? date('d M Y H:i', strtotime($row['verified_at'])) : date('d M Y H:i', strtotime($row['created_at'])); ?></small>
                                </div>
                                <span class="badge-rejected">❌ DITOLAK</span>
                            </div>
                            
                            <div class="row mb-2">
                                <div class="col-md-4">
                                    <strong><?php echo htmlspecialchars($row['nama']); ?></strong><br>
                                    <small><?php echo $row['jenis_barang']; ?>: <?php echo $row['merk_barang'] . ' ' . $row['spesifikasi_barang']; ?></small>
                                </div>
                                <div class="col-md-4">
                                    <strong>Pinjaman: Rp <?php echo number_format($row['jumlah_pinjaman'], 0, ',', '.'); ?></strong>
                                </div>
                                <div class="col-md-4">
                                    <small>No. HP: <?php echo $row['no_wa']; ?></small>
                                </div>
                            </div>
                            
                            <?php if ($row['alasan_penolakan']): ?>
                                <div class="alert alert-danger mb-0 mt-2">
                                    <strong>Alasan Penolakan:</strong> <?php echo htmlspecialchars($row['alasan_penolakan']); ?>
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
                        $bukti_path = $bukti_file ? 'payment/' . $row['nik'] . '/' . $bukti_file : null;

                        // Compute days late & denda for pelunasan view
                        $days_late_p = 0;
                        if (!empty($row['tanggal_jatuh_tempo'])) {
                            $due_ts_p = strtotime($row['tanggal_jatuh_tempo']);
                            if ($due_ts_p !== false) {
                                $diff_days_p = floor((time() - $due_ts_p) / 86400);
                                if ($diff_days_p > 0) $days_late_p = (int)$diff_days_p;
                            }
                        }
                        $daily_penalty_p = 30000;
                        $denda_calc_p = min($days_late_p, 7) * $daily_penalty_p;
                        $denda_display_p = !empty($row['denda_terakumulasi']) ? (float)$row['denda_terakumulasi'] : $denda_calc_p;
                        
                        // Compute breakdown for display
                        $pokok_p = !empty($row['jumlah_disetujui']) ? (float)$row['jumlah_disetujui'] : (float)$row['jumlah_pinjaman'];
                        $bunga_p = (float)$row['bunga'];
                        $lama_p = (int)$row['lama_gadai'];
                        $bunga_total_p = $pokok_p * ($bunga_p / 100) * $lama_p;
                        $admin_fee_p = round($pokok_p * 0.01);
                        $biaya_asuransi_p = 10000;
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
                                    <strong><?php echo htmlspecialchars($row['nama']); ?></strong><br>
                                    <small><?php echo $row['jenis_barang']; ?>: <?php echo $row['merk_barang'] . ' ' . $row['spesifikasi_barang']; ?></small>
                                </div>
                                <div class="col-md-4">
                                    <small>No. KTP: <?php echo $row['nik']; ?></small><br>
                                    <small>No. HP: <?php echo $row['no_wa']; ?></small>
                                </div>
                                <div class="col-md-4">
                                    <!-- Detail Perhitungan Total Tebus -->
                                    <div class="p-2" style="background-color: #f8f9fa; border-radius: 8px; border-left: 3px solid #28a745;">
                                        <small><strong>📊 Detail Total Tebus:</strong></small><br>
                                        <small>
                                            • Pokok: <strong>Rp <?php echo number_format($pokok_p, 0, ',', '.'); ?></strong><br>
                                            • Bunga (<?php echo $bunga_p; ?>% × <?php echo $lama_p; ?> bulan): <strong>Rp <?php echo number_format($bunga_total_p, 0, ',', '.'); ?></strong><br>
                                            • Admin Fee (1%): <strong>Rp <?php echo number_format($admin_fee_p, 0, ',', '.'); ?></strong><br>
                                            • Biaya Asuransi: <strong>Rp <?php echo number_format($biaya_asuransi_p, 0, ',', '.'); ?></strong><br>
                                            <?php if ($denda_display_p > 0): ?>
                                            • Denda (<?php echo $days_late_p; ?> hari): <strong class="text-danger">Rp <?php echo number_format($denda_display_p, 0, ',', '.'); ?></strong><br>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <small class="mt-2 d-block"><strong>Bukti:</strong> <?php echo $bukti_count; ?> file (Rp <?php echo number_format($bukti_total, 0, ',', '.'); ?>)</small>
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

            <!-- Perpanjangan Pending Tab -->
            <div class="tab-pane fade" id="perpanjangan" role="tabpanel">
                <?php if ($perpanjangan_error): ?>
                    <div class="alert alert-warning"><?php echo $perpanjangan_error; ?></div>
                <?php elseif (empty($perpanjangan_data)): ?>
                    <div class="alert alert-info">Tidak ada perpanjangan pending.</div>
                <?php else: ?>
                    <?php foreach ($perpanjangan_data as $row): ?>
                        <?php
                        $bukti_count = (int)($row['bukti_count'] ?? 0);
                        $bukti_total = !empty($row['bukti_total']) ? (float)$row['bukti_total'] : 0;
                        $bukti_file = $row['bukti_latest'] ?? null;
                        $bukti_path = $bukti_file ? 'payment/' . $row['nik'] . '/' . $bukti_file : null;

                        // Compute days late & denda for perpanjangan view
                        $days_late_p = 0;
                        if (!empty($row['tanggal_jatuh_tempo'])) {
                            $due_ts_p = strtotime($row['tanggal_jatuh_tempo']);
                            if ($due_ts_p !== false) {
                                $diff_days_p = floor((time() - $due_ts_p) / 86400);
                                if ($diff_days_p > 0) $days_late_p = (int)$diff_days_p;
                            }
                        }
                        $daily_penalty_p = 30000;
                        $denda_calc_p = min($days_late_p, 7) * $daily_penalty_p;
                        $denda_display_p = !empty($row['denda_terakumulasi']) ? (float)$row['denda_terakumulasi'] : $denda_calc_p;
                        
                        // Compute breakdown for display
                        $pokok_perp = !empty($row['jumlah_disetujui']) ? (float)$row['jumlah_disetujui'] : (float)$row['jumlah_pinjaman'];
                        $bunga_perp = (float)$row['bunga'];
                        $lama_perp = (int)$row['lama_gadai'];
                        $bunga_total_perp = $pokok_perp * ($bunga_perp / 100) * $lama_perp;
                        $admin_fee_perp = round($pokok_perp * 0.01);
                        $biaya_asuransi_perp = 10000;
                        ?>
                        <div class="data-card approved">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="no-transaksi">#<?php echo str_pad($row['id'], 6, '0', STR_PAD_LEFT); ?></div>
                                    <small class="text-muted">Permintaan: <?php echo date('d M Y H:i', strtotime($row['updated_at'])); ?></small>
                                </div>
                                <span class="badge-approved">🔁 PERPANJANGAN</span>
                            </div>

                            <div class="row mb-2">
                                <div class="col-md-4">
                                    <strong><?php echo htmlspecialchars($row['nama']); ?></strong><br>
                                    <small><?php echo $row['jenis_barang']; ?>: <?php echo $row['merk_barang'] . ' ' . $row['spesifikasi_barang']; ?></small>
                                </div>
                                <div class="col-md-4">
                                    <small>No. KTP: <?php echo $row['nik']; ?></small><br>
                                    <small>No. HP: <?php echo $row['no_wa']; ?></small>
                                </div>
                                <div class="col-md-4">
                                    <!-- Detail Perhitungan -->  
                                    <div class="p-2 mb-2" style="background-color: #f8f9fa; border-radius: 8px; border-left: 3px solid #20c997;">
                                        <small><strong>📊 Info Gadai:</strong></small><br>
                                        <small>
                                            • Pokok: <strong>Rp <?php echo number_format($pokok_perp, 0, ',', '.'); ?></strong><br>
                                            • Bunga: <?php echo $bunga_perp; ?>% × <?php echo $lama_perp; ?> bulan<br>
                                            • Admin + Asuransi: Rp <?php echo number_format($admin_fee_perp + $biaya_asuransi_perp, 0, ',', '.'); ?><br>
                                            <?php if ($denda_display_p > 0): ?>
                                            • Denda (<?php echo $days_late_p; ?> hari): <strong class="text-danger">Rp <?php echo number_format($denda_display_p, 0, ',', '.'); ?></strong><br>
                                            <?php endif; ?>
                                        </small>
                                    </div>
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
                                    <input type="hidden" name="action" value="acc_perpanjangan">
                                    <button type="submit" class="btn btn-approve" <?php echo $bukti_count <= 0 ? 'disabled' : ''; ?>>
                                        ✅ ACC Perpanjangan
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Reminder Manual Tab (Overdue) -->
            <div class="tab-pane fade" id="reminder" role="tabpanel">
                <?php if ($reminder_error): ?>
                    <div class="alert alert-warning"><?php echo $reminder_error; ?></div>
                <?php elseif (empty($reminder_data)): ?>
                    <div class="alert alert-info">Tidak ada data lewat jatuh tempo.</div>
                <?php else: ?>
                    <div class="table-responsive mt-3">
                        <table class="table table-striped table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama</th>
                                    <th>NIK</th>
                                    <th>No. HP</th>
                                    <th>Barang</th>
                                    <th>Jatuh Tempo</th>
                                    <th>Telat (hari)</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reminder_data as $r): ?>
                                    <?php
                                        $days_overdue = isset($r['days_overdue']) ? (int)$r['days_overdue'] : 0;
                                        if ($days_overdue < 0) $days_overdue = 0;
                                        $barang_label = trim(($r['jenis_barang'] ?? '') . ': ' . ($r['merk_barang'] ?? '') . ' ' . ($r['spesifikasi_barang'] ?? ''));
                                        $barang_label = trim(preg_replace('/\s+/', ' ', $barang_label));
                                    ?>
                                    <tr>
                                        <td>#<?php echo str_pad($r['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo htmlspecialchars($r['nama'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($r['nik'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($r['no_wa'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($barang_label !== '' ? $barang_label : ('ID: ' . ($r['id'] ?? ''))); ?></td>
                                        <td><?php echo !empty($r['tanggal_jatuh_tempo']) ? date('d M Y', strtotime($r['tanggal_jatuh_tempo'])) : '-'; ?></td>
                                        <td><span class="badge bg-danger"><?php echo (int)$days_overdue; ?></span></td>
                                        <td><?php echo htmlspecialchars($r['status'] ?? ''); ?></td>
                                        <td>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Kirim reminder WhatsApp untuk #' + <?php echo json_encode(str_pad($r['id'], 6, '0', STR_PAD_LEFT)); ?> + ' (telat ' + <?php echo json_encode((string)$days_overdue); ?> + ' hari)?');">
                                                <input type="hidden" name="action" value="manual_reminder_overdue">
                                                <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-warning">Kirim Reminder</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Transaksi Tab -->
            <div class="tab-pane fade" id="transaksi" role="tabpanel">
                <?php if ($transaksi_error): ?>
                    <div class="alert alert-warning"><?php echo $transaksi_error; ?></div>
                <?php elseif (empty($transaksi_data)): ?>
                    <div class="alert alert-info">Belum ada transaksi (bukti) tercatat.</div>
                <?php else: ?>
                    <?php
                        $tx_perpanjangan = array_filter($transaksi_data, function($t){ return stripos($t['keterangan'] ?? '', 'perpanjangan') !== false; });
                        $tx_pelunasan = array_filter($transaksi_data, function($t){ return stripos($t['keterangan'] ?? '', 'pelunasan') !== false; });
                    ?>

                    <h5>🔁 Perpanjangan (<?php echo count($tx_perpanjangan); ?>)</h5>
                    <?php if (empty($tx_perpanjangan)): ?>
                        <div class="alert alert-info">Tidak ada bukti perpanjangan.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Pelanggan NIK</th>
                                        <th>Nama</th>
                                        <th>Barang</th>
                                        <th>Serial</th>
                                        <th>Jumlah</th>
                                        <th>Metode</th>
                                        <th>Bukti</th>
                                        <th>Tanggal</th>
                                        <th>Keterangan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tx_perpanjangan as $t): ?>
                                        <?php $bukti = !empty($t['bukti']) ? 'payment/'.($t['pelanggan_nik'] ?? '').'/'.$t['bukti'] : null; ?>
                                                        <tr>
                                                            <td><?php echo $t['id']; ?></td>
                                                            <td><?php echo htmlspecialchars($t['pelanggan_nik']); ?></td>
                                                            <td><?php echo htmlspecialchars($t['nama_nasabah'] ?? ''); ?></td>
                                                            <?php
                                                                // Build a friendly item label from available data_gadai fields
                                                                $barang_label = '';
                                                                if (!empty($t['jenis_barang'])) {
                                                                    $barang_label .= $t['jenis_barang'] . ': ';
                                                                }
                                                                if (!empty($t['merk']) || !empty($t['tipe'])) {
                                                                    $barang_label .= trim(($t['merk'] ?? '') . ' ' . ($t['tipe'] ?? ''));
                                                                }
                                                                $barang_label = trim($barang_label);
                                                                if ($barang_label === '') {
                                                                    $barang_label = isset($t['barang_id']) ? 'ID: ' . $t['barang_id'] : '-';
                                                                }
                                                            ?>
                                                            <td><?php echo htmlspecialchars($barang_label); ?></td>
                                                            <td><?php echo htmlspecialchars($t['serial_number'] ?? ($t['imei'] ?? '-')); ?></td>
                                                    <td>Rp <?php echo number_format((float)$t['jumlah_bayar'],0,',','.'); ?></td>
                                                    <td><?php echo htmlspecialchars($t['metode_pembayaran'] ?? ''); ?></td>
                                                    <td><?php if ($bukti): ?><a href="<?php echo $bukti; ?>" target="_blank">Lihat Bukti</a><?php else: ?>-<?php endif; ?></td>
                                                    <td><?php echo htmlspecialchars($t['created_at'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($t['keterangan'] ?? ''); ?></td>
                                                    <td>
                                                        <?php if (!empty($t['barang_id']) && (($t['keterangan'] ?? '') === 'perpanjangan')): ?>
                                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Anda yakin ingin ACC perpanjangan untuk ' + <?php echo json_encode($barang_label); ?> + '?');">
                                                                <input type="hidden" name="action" value="acc_perpanjangan">
                                                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($t['barang_id']); ?>">
                                                                <button type="submit" class="btn btn-sm btn-primary">ACC Perpanjangan</button>
                                                            </form>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <hr>

                    <h5>💰 Pelunasan (<?php echo count($tx_pelunasan); ?>)</h5>
                    <?php if (empty($tx_pelunasan)): ?>
                        <div class="alert alert-info">Tidak ada bukti pelunasan.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Pelanggan NIK</th>
                                        <th>Nama</th>
                                        <th>Barang</th>
                                        <th>Serial</th>
                                        <th>Jumlah</th>
                                        <th>Metode</th>
                                        <th>Bukti</th>
                                        <th>Tanggal</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tx_pelunasan as $t): ?>
                                        <?php $bukti = !empty($t['bukti']) ? 'payment/'.($t['pelanggan_nik'] ?? '').'/'.$t['bukti'] : null; ?>
                                        <tr>
                                            <td><?php echo $t['id']; ?></td>
                                            <td><?php echo htmlspecialchars($t['pelanggan_nik']); ?></td>
                                            <td><?php echo htmlspecialchars($t['nama_nasabah'] ?? ''); ?></td>
                                            <?php
                                                // Friendly item label (fallback to barang_id)
                                                $barang_label = '';
                                                if (!empty($t['jenis_barang'])) {
                                                    $barang_label .= $t['jenis_barang'] . ': ';
                                                }
                                                if (!empty($t['merk']) || !empty($t['tipe'])) {
                                                    $barang_label .= trim(($t['merk'] ?? '') . ' ' . ($t['tipe'] ?? ''));
                                                }
                                                $barang_label = trim($barang_label);
                                                if ($barang_label === '') {
                                                    $barang_label = isset($t['barang_id']) ? 'ID: ' . $t['barang_id'] : '-';
                                                }
                                            ?>
                                            <td><?php echo htmlspecialchars($barang_label); ?></td>
                                            <td><?php echo htmlspecialchars($t['serial_number'] ?? ($t['imei'] ?? '-')); ?></td>
                                            <td>Rp <?php echo number_format((float)$t['jumlah_bayar'],0,',','.'); ?></td>
                                            <td><?php echo htmlspecialchars($t['metode_pembayaran'] ?? ''); ?></td>
                                            <td><?php if ($bukti): ?><a href="<?php echo $bukti; ?>" target="_blank">Lihat Bukti</a><?php else: ?>-<?php endif; ?></td>
                                            <td><?php echo htmlspecialchars($t['created_at'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($t['keterangan'] ?? ''); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- List Tab -->
            <div class="tab-pane fade" id="list" role="tabpanel">
                                <!-- CSV upload UI and instructions removed -->

                <div class="d-flex justify-content-end mt-3 mb-3">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGadaiModal">
                        ➕ Tambah Data Gadai
                    </button>
                </div>

                <!-- Modal: Input Manual Data Gadai (tanpa kirim WhatsApp) -->
                <div class="modal fade" id="addGadaiModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header" style="background: linear-gradient(135deg, #0056b3, #007bff); color: white;">
                                <h5 class="modal-title">➕ Input Manual Data Gadai</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form method="POST">
                                <div class="modal-body">
                                    <p class="text-muted mb-3">Form ini hanya menyimpan data ke sistem (tanpa mengirim WhatsApp).</p>
                                    <input type="hidden" name="action" value="manual_add_gadai">

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Nama <span class="text-danger">*</span></label>
                                            <input type="text" name="nama" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">NIK <span class="text-danger">*</span></label>
                                            <input type="text" name="nik" class="form-control" required maxlength="16">
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">No. WhatsApp <span class="text-danger">*</span></label>
                                            <input type="text" name="no_wa" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Jenis Barang <span class="text-danger">*</span></label>
                                            <input type="text" name="jenis_barang" class="form-control" required placeholder="Contoh: HP / Laptop">
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">Alamat <span class="text-danger">*</span></label>
                                            <textarea name="alamat" class="form-control" rows="2" required></textarea>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label">Merk Barang</label>
                                            <input type="text" name="merk_barang" class="form-control">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Spesifikasi / Tipe</label>
                                            <input type="text" name="spesifikasi_barang" class="form-control">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Kondisi</label>
                                            <select name="kondisi_barang" class="form-control">
                                                <option value="Baru">Baru</option>
                                                <option value="Bekas - Baik" selected>Bekas - Baik</option>
                                                <option value="Bekas - Cukup">Bekas - Cukup</option>
                                                <option value="Rusak Ringan">Rusak Ringan</option>
                                            </select>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">Kelengkapan Barang</label>
                                            <textarea name="kelengkapan_barang" class="form-control" rows="2" placeholder="Contoh: Dus, charger, kabel data, headset, nota, dll"></textarea>
                                            <small class="text-muted">Opsional. Akan tersimpan sebagai catatan admin.</small>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label">Nilai Taksiran</label>
                                            <input type="number" name="nilai_taksiran" class="form-control" min="0" step="0.01" placeholder="Opsional">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Jumlah Pinjaman <span class="text-danger">*</span></label>
                                            <input type="number" name="jumlah_pinjaman" class="form-control" min="0" step="0.01" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Bunga (%)</label>
                                            <input type="number" name="bunga" class="form-control" min="0" step="0.01" value="5.00">
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label">Lama Gadai</label>
                                            <input type="number" name="lama_gadai" class="form-control" min="1" step="1" value="30">
                                            <small class="text-muted">Sesuai konfigurasi sistem.</small>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Tanggal Gadai <span class="text-danger">*</span></label>
                                            <input type="date" name="tanggal_gadai" class="form-control" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Tanggal Jatuh Tempo <span class="text-danger">*</span></label>
                                            <input type="date" name="tanggal_jatuh_tempo" class="form-control" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-primary">Simpan Data</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <?php if (empty($all_data)): ?>
                    <div class="alert alert-info">Belum ada data gadai.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nik</th>
                                    <th>Nama</th>
                                    <th>No Hp</th>
                                    <th>Merek Hp</th>
                                    <th>Kelengkapan</th>
                                    <th>Kondisi hp</th>
                                    <th>Jumlah Gadai</th>
                                    <th>Bunga</th>
                                    <th>Total Yang Balik</th>
                                    <th>Tanggal Gadai</th>
                                    <th>Tanggal Jatuh Tempo</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_data as $index => $row): ?>
                                    <?php
                                        // Determine principal: approved amount if present, otherwise requested
                                        $pokok = !empty($row['jumlah_disetujui']) ? (float)$row['jumlah_disetujui'] : (float)$row['jumlah_pinjaman'];
                                        $bunga_pct = isset($row['bunga']) ? (float)$row['bunga'] : 0.0;
                                        $lama = isset($row['lama_gadai']) ? (int)$row['lama_gadai'] : 0;
                                        $denda = isset($row['denda_terakumulasi']) ? (float)$row['denda_terakumulasi'] : 0.0;
                                        if (!empty($row['total_tebus']) && (float)$row['total_tebus'] > 0) {
                                            $total_kembali = (float)$row['total_tebus'];
                                        } else {
                                            $bunga_total_list = $pokok * ($bunga_pct / 100) * $lama;
                                            $admin_fee_list = round($pokok * 0.01);
                                            $biaya_asuransi_list = 10000;
                                            $total_kembali = $pokok + $bunga_total_list + $admin_fee_list + $biaya_asuransi_list + $denda;
                                        }
                                    ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($row['nik']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nama']); ?></td>
                                        <td><?php echo htmlspecialchars($row['no_wa']); ?></td>
                                        <td><?php echo htmlspecialchars($row['merk_barang']); ?></td>
                                        <td>
                                            <?php
                                                $kelengkapan = '';
                                                if (!empty($row['catatan_admin'])) {
                                                    $note = (string)$row['catatan_admin'];
                                                    $prefix = 'Kelengkapan Barang:';
                                                    if (stripos($note, $prefix) === 0) {
                                                        $kelengkapan = trim(substr($note, strlen($prefix)));
                                                    } else {
                                                        $kelengkapan = $note;
                                                    }
                                                }
                                                echo htmlspecialchars($kelengkapan !== '' ? $kelengkapan : '-');
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['kondisi_barang']); ?></td>
                                        <td>Rp <?php echo number_format((float)$row['jumlah_pinjaman'], 0, ',', '.'); ?></td>
                                        <td><?php echo htmlspecialchars($bunga_pct) . '%'; ?></td>
                                        <td>Rp <?php echo number_format($total_kembali, 0, ',', '.'); ?></td>
                                        <td><?php echo $row['tanggal_gadai'] ? date('d M Y', strtotime($row['tanggal_gadai'])) : '-'; ?></td>
                                        <td><?php echo $row['tanggal_jatuh_tempo'] ? date('d M Y', strtotime($row['tanggal_jatuh_tempo'])) : '-'; ?></td>
                                        <td><?php echo htmlspecialchars($row['status']); ?></td>
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
    <script>
        // Move lunaskan modals to document.body when opened to avoid stacking-context issues
        (function () {
            try {
                document.querySelectorAll('button[data-bs-target^="#lunaskanModal"]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var targetSelector = btn.getAttribute('data-bs-target');
                        if (!targetSelector) return;
                        var modalEl = document.querySelector(targetSelector);
                        if (!modalEl) return;
                        // If modal is nested inside transformed container, move it to body before showing
                        if (modalEl.parentNode !== document.body) {
                            document.body.appendChild(modalEl);
                        }
                    });
                });
            } catch (e) {
                console.error('Error moving modals to body', e);
            }
        })();
    </script>
</body>
</html>
