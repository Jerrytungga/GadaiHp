<?php
session_start();
require_once 'database.php';
require_once 'whatsapp_helper.php';

// Proses form jika ada submit
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Ambil data dari form
        $nama_nasabah = $_POST['nama_nasabah'];
        $no_ktp = $_POST['no_ktp'];
        $no_hp = $_POST['no_hp'];
        $alamat = $_POST['alamat'];
        $jenis_barang = $_POST['jenis_barang'];
        $merk = $_POST['merk'];
        $tipe = $_POST['tipe'];
        $kondisi = $_POST['kondisi'];
        $imei_serial = $_POST['imei_serial'];
        $kelengkapan_hp = $_POST['kelengkapan_hp'] ?? '';
        $harga_pasar = $_POST['harga_pasar'];
        $jumlah_pinjaman = $_POST['jumlah_pinjaman'];
        $bunga = $_POST['bunga'];
        $lama_gadai = $_POST['lama_gadai'];
        
        // Hitung tanggal jatuh tempo
        $tanggal_gadai = date('Y-m-d');
        $tanggal_jatuh_tempo = date('Y-m-d', strtotime("+$lama_gadai months"));
        
        // Handle upload foto
        $foto_barang = '';
        $foto_ktp = '';
        
        if (isset($_FILES['foto_barang']) && $_FILES['foto_barang']['error'] == 0) {
            $target_dir = "uploads/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $foto_barang = $target_dir . time() . '_' . basename($_FILES["foto_barang"]["name"]);
            move_uploaded_file($_FILES["foto_barang"]["tmp_name"], $foto_barang);
        }
        
        if (isset($_FILES['foto_ktp']) && $_FILES['foto_ktp']['error'] == 0) {
            $target_dir = "uploads/";
            $foto_ktp = $target_dir . time() . '_ktp_' . basename($_FILES["foto_ktp"]["name"]);
            move_uploaded_file($_FILES["foto_ktp"]["tmp_name"], $foto_ktp);
        }
        
        // Insert ke database
        $sql = "INSERT INTO data_gadai (
            nama_nasabah, no_ktp, no_hp, alamat, jenis_barang, merk, tipe, 
            kondisi, imei_serial, kelengkapan_hp, harga_pasar, jumlah_pinjaman, bunga, 
            lama_gadai, tanggal_gadai, tanggal_jatuh_tempo, foto_barang, foto_ktp, status
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending'
        )";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $nama_nasabah, $no_ktp, $no_hp, $alamat, $jenis_barang, $merk, $tipe,
            $kondisi, $imei_serial, $kelengkapan_hp, $harga_pasar, $jumlah_pinjaman, $bunga,
            $lama_gadai, $tanggal_gadai, $tanggal_jatuh_tempo, $foto_barang, $foto_ktp
        ]);
        
        $no_transaksi = $db->lastInsertId();
        $success_message = "Pengajuan gadai berhasil dikirim! Nomor Registrasi: #" . str_pad($no_transaksi, 6, '0', STR_PAD_LEFT) . ". Mohon tunggu verifikasi admin.";
        
        // Kirim notifikasi WhatsApp ke Admin
        try {
            $data_pengajuan = [
                'id' => $no_transaksi,
                'nama_nasabah' => $nama_nasabah,
                'jenis_barang' => $jenis_barang,
                'merk' => $merk,
                'tipe' => $tipe,
                'jumlah_pinjaman' => $jumlah_pinjaman,
                'no_hp' => $no_hp
            ];
            $whatsapp->notifyAdminNewSubmission($data_pengajuan);
        } catch(Exception $e) {
            // Jika gagal kirim WA, abaikan (data sudah tersimpan)
            error_log("WhatsApp notification failed: " . $e->getMessage());
        }
        
    } catch(PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Data Gadai - Gadai Cepat Timika</title>
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
        
        .form-container {
            background: #ffffff;
            border-radius: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 50px;
            max-width: 900px;
            margin: 0 auto;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .form-title {
            font-family: 'Raleway', sans-serif;
            font-weight: 800;
            font-size: 2.5rem;
            background: linear-gradient(135deg, #0056b3, #007bff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }
        
        .form-subtitle {
            color: #666;
            font-size: 1.1rem;
            font-weight: 400;
        }
        
        .section-title {
            font-family: 'Raleway', sans-serif;
            font-weight: 700;
            color: #0056b3;
            font-size: 1.4rem;
            margin-top: 30px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #e3f2fd;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .form-control,
        .form-select,
        textarea {
            border: 2px solid #e3f2fd;
            border-radius: 15px;
            padding: 12px 20px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus,
        .form-select:focus,
        textarea:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15);
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #0056b3, #007bff);
            border: none;
            color: white;
            font-weight: 600;
            padding: 15px 50px;
            border-radius: 50px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 30px;
        }
        
        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 86, 179, 0.4);
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
        
        .alert {
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            font-weight: 500;
        }
        
        .input-group-text {
            background: linear-gradient(135deg, #0056b3, #007bff);
            color: white;
            border: none;
            border-radius: 15px 0 0 15px;
            font-weight: 600;
        }
        
        .file-upload-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }
        
        .file-upload-wrapper input[type=file] {
            font-size: 100px;
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
        }
        
        .file-upload-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 12px 20px;
            border-radius: 15px;
            display: inline-block;
            width: 100%;
            text-align: center;
            font-weight: 600;
            border: 2px dashed #28a745;
            transition: all 0.3s ease;
        }
        
        .file-upload-btn:hover {
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }
        
        .required {
            color: #dc3545;
        }
        
        .info-box {
            background: linear-gradient(135deg, #e3f2fd, #f0f8ff);
            border-left: 4px solid #007bff;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        
        .info-box p {
            margin: 0;
            color: #0056b3;
            font-size: 0.95rem;
        }
        
        @media (max-width: 768px) {
            .form-container {
                padding: 30px 20px;
            }
            
            .form-title {
                font-size: 1.8rem;
            }
            
            .section-title {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <div class="form-header">
                <h1 class="form-title">📱 FORM DATA GADAI</h1>
                <p class="form-subtitle">Gadai Cepat Timika Papua - HP & Laptop</p>
            </div>
            
            <a href="index.php" class="btn-back">← Kembali ke Beranda</a>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success mt-3" role="alert">
                    <strong>✅ Berhasil!</strong> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger mt-3" role="alert">
                    <strong>❌ Error!</strong> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" id="gadaiForm">
                <!-- Data Nasabah -->
                <h3 class="section-title">👤 Data Nasabah</h3>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama Lengkap <span class="required">*</span></label>
                        <input type="text" class="form-control" name="nama_nasabah" required placeholder="Masukkan nama lengkap">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">No. KTP <span class="required">*</span></label>
                        <input type="text" class="form-control" name="no_ktp" required placeholder="16 digit nomor KTP" maxlength="16">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">No. HP/WhatsApp <span class="required">*</span></label>
                        <input type="tel" class="form-control" name="no_hp" required placeholder="08xxxxxxxxxx">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Foto KTP <span class="required">*</span></label>
                        <div class="file-upload-wrapper">
                            <div class="file-upload-btn">📷 Upload Foto KTP</div>
                            <input type="file" name="foto_ktp" accept="image/*" required>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Alamat Lengkap <span class="required">*</span></label>
                    <textarea class="form-control" name="alamat" rows="3" required placeholder="Masukkan alamat lengkap"></textarea>
                </div>
                
                <!-- Data Barang -->
                <h3 class="section-title">📱 Data Barang</h3>
                
                <div class="info-box">
                    <p><strong>💡 Info:</strong> Pastikan barang dalam kondisi baik, menyala normal, dan tidak terkunci akun (Google/iCloud/BitLocker)</p>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Jenis Barang <span class="required">*</span></label>
                        <select class="form-select" name="jenis_barang" required>
                            <option value="">-- Pilih Jenis --</option>
                            <option value="HP">📱 HP/Smartphone</option>
                            <option value="Laptop">💻 Laptop/Notebook</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Merk <span class="required">*</span></label>
                        <input type="text" class="form-control" name="merk" required placeholder="Contoh: Samsung, iPhone, Asus">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Tipe/Model <span class="required">*</span></label>
                        <input type="text" class="form-control" name="tipe" required placeholder="Contoh: Galaxy S21, MacBook Pro">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Kondisi Barang <span class="required">*</span></label>
                        <select class="form-select" name="kondisi" required>
                            <option value="">-- Pilih Kondisi --</option>
                            <option value="Sangat Baik">⭐⭐⭐⭐⭐ Sangat Baik (Seperti Baru)</option>
                            <option value="Baik">⭐⭐⭐⭐ Baik (Normal)</option>
                            <option value="Cukup">⭐⭐⭐ Cukup (Ada Lecet)</option>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">IMEI/Serial Number <span class="required">*</span></label>
                        <input type="text" class="form-control" name="imei_serial" required placeholder="Nomor IMEI atau Serial">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Foto Barang <span class="required">*</span></label>
                        <div class="file-upload-wrapper">
                            <div class="file-upload-btn">📸 Upload Foto Barang</div>
                            <input type="file" name="foto_barang" accept="image/*" required>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Keterangan Kelengkapan HP</label>
                    <textarea class="form-control" name="kelengkapan_hp" rows="2" placeholder="Contoh: box, charger, headset, kabel data"></textarea>
                    <div class="form-text">Isi jika ada kelengkapan tambahan untuk HP.</div>
                </div>
                
                <!-- Data Pinjaman -->
                <h3 class="section-title">💰 Data Pinjaman</h3>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Harga Pasar (Estimasi) <span class="required">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control" name="harga_pasar" required placeholder="0" min="0">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Jumlah Pinjaman (Max 70%) <span class="required">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control" name="jumlah_pinjaman" required placeholder="0" min="0">
                        </div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Bunga per Bulan (%) <span class="required">*</span></label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="bunga" required value="30" readonly style="background-color: #f8f9fa; cursor: not-allowed;">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Lama Gadai <span class="required">*</span></label>
                        <select class="form-select" name="lama_gadai" required>
                            <option value="">-- Pilih Durasi --</option>
                            <option value="1">1 Bulan</option>
                            <option value="2">2 Bulan</option>
                            <option value="3">3 Bulan</option>
                        </select>
                    </div>
                </div>
                
                <div class="info-box">
                    <p><strong>📌 Catatan:</strong> Bunga tetap 30% per bulan (tidak dapat diubah). Denda keterlambatan Rp 30.000/hari setelah jatuh tempo.</p>
                </div>
                
                <button type="submit" class="btn-submit">💾 Simpan Data Gadai</button>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // File upload preview
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function(e) {
                const fileName = e.target.files[0]?.name;
                const btn = this.previousElementSibling;
                if (fileName) {
                    btn.textContent = '✅ ' + fileName;
                    btn.style.background = 'linear-gradient(135deg, #28a745, #20c997)';
                }
            });
        });
        
        // Validasi jumlah pinjaman max 70% dari harga pasar
        const hargaPasar = document.querySelector('input[name="harga_pasar"]');
        const jumlahPinjaman = document.querySelector('input[name="jumlah_pinjaman"]');
        
        jumlahPinjaman.addEventListener('input', function() {
            const maxPinjaman = hargaPasar.value * 0.7;
            if (this.value > maxPinjaman) {
                alert('⚠️ Jumlah pinjaman maksimal 70% dari harga pasar (Rp ' + maxPinjaman.toLocaleString('id-ID') + ')');
                this.value = Math.floor(maxPinjaman);
            }
        });
        
        // Format currency
        function formatRupiah(input) {
            let value = input.value.replace(/[^0-9]/g, '');
            input.value = value;
        }
        
        document.querySelectorAll('input[type="number"]').forEach(input => {
            if (input.name === 'harga_pasar' || input.name === 'jumlah_pinjaman') {
                input.addEventListener('input', function() {
                    formatRupiah(this);
                });
            }
        });
    </script>
</body>
</html>
