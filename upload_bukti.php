<?php
include 'database.php'; // Pastikan file koneksi database benar

// Validasi parameter id_gadai
if (!isset($_GET['id_gadai']) || empty($_GET['id_gadai'])) {
    echo "<script>
        alert('ID gadai tidak valid.');
        window.location.href = 'index.php';
    </script>";
    exit();
}

$id_gadai = mysqli_real_escape_string($conn, $_GET['id_gadai']);

if (isset($_POST['byrcicilan'])) {
    // Sanitasi input
    $pelanggan = mysqli_real_escape_string($conn, trim($_POST['ktp']));
    $payment_input = $_POST['amount'];
    $metode = mysqli_real_escape_string($conn, $_POST['method']);
    $bukti = $_FILES['receipt'];

    // Validasi input tidak kosong
    if (empty($pelanggan) || empty($payment_input) || empty($metode)) {
        echo "<script>alert('Semua field harus diisi.');</script>";
    } else {
        // Bersihkan format Rupiah untuk mendapatkan angka murni
        $payment = preg_replace('/[^0-9]/', '', $payment_input);
        
        // Validasi nominal pembayaran
        if ($payment <= 0) {
            echo "<script>alert('Jumlah pembayaran tidak valid.');</script>";
        } else {
            // Validasi file upload
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $max_file_size = 5 * 1024 * 1024; // 5MB
            
            if ($bukti['error'] !== UPLOAD_ERR_OK) {
                echo "<script>alert('Terjadi kesalahan saat mengupload file.');</script>";
            } elseif ($bukti['size'] > $max_file_size) {
                echo "<script>alert('Ukuran file terlalu besar. Maksimal 5MB.');</script>";
            } elseif (!in_array($bukti['type'], $allowed_types)) {
                echo "<script>alert('Format file tidak didukung. Gunakan JPG, PNG, atau GIF.');</script>";
            } else {
                // Set the target directory for file uploads
                $base_dir = "payment/";
                $target_dir = $base_dir . $pelanggan . "/";

                // Periksa apakah folder sudah ada, jika tidak buat folder
                if (!is_dir($target_dir)) {
                    if (!mkdir($target_dir, 0755, true)) {
                        echo "<script>alert('Gagal membuat direktori untuk menyimpan file.');</script>";
                        exit();
                    }
                }

                // Periksa jumlah pengiriman sebelumnya
                $checkAttempts = mysqli_query($conn, "SELECT COUNT(*) AS total FROM transaksi WHERE pelanggan_nik = '$pelanggan' AND barang_id = '$id_gadai'");
                if (!$checkAttempts) {
                    echo "<script>alert('Gagal memeriksa data transaksi.');</script>";
                } else {
                    $attempts = mysqli_fetch_assoc($checkAttempts)['total'];

                    if ($attempts >= 3) {
                        echo "<script>
                            alert('Anda hanya dapat mengirim data maksimal 3 kali.');
                            window.location.href = 'index.php';
                        </script>";
                        exit();
                    } else {
                        // Check if pelanggan_nik exists in pelanggan table
                        $checkPelanggan = mysqli_query($conn, "SELECT nik FROM pelanggan WHERE nik = '$pelanggan'");
                        if (!$checkPelanggan) {
                            echo "<script>alert('Gagal memeriksa data pelanggan.');</script>";
                        } elseif (mysqli_num_rows($checkPelanggan) == 0) {
                            echo "<script>alert('NIK tidak ditemukan. Silakan periksa kembali.');</script>";
                        } else {
                            // Buat nama file unik untuk menghindari duplikasi
                            $file_extension = strtolower(pathinfo($bukti['name'], PATHINFO_EXTENSION));
                            $timestamp = date('YmdHis');
                            $new_file_name = $payment . '_' . $timestamp . '.' . $file_extension;
                            $target_file = $target_dir . $new_file_name;

                            // Mulai transaksi database
                            mysqli_begin_transaction($conn);
                            
                            try {
                                // Insert data ke database
                                $query = mysqli_query($conn, "INSERT INTO `transaksi`(`pelanggan_nik`, `barang_id`, `jumlah_bayar`, `keterangan`, `metode_pembayaran`, `bukti`) VALUES ('$pelanggan', '$id_gadai', '$payment', 'cicilan', '$metode', '$new_file_name')");
                                
                                if (!$query) {
                                    throw new Exception('Gagal menyimpan data pembayaran.');
                                }

                                // Move uploaded file
                                if (!move_uploaded_file($bukti["tmp_name"], $target_file)) {
                                    throw new Exception('Gagal menyimpan file bukti pembayaran.');
                                }

                                // Commit transaksi
                                mysqli_commit($conn);
                                
                                echo "<script>
                                    alert('Bukti pembayaran berhasil diunggah.');
                                    window.location.href = 'index.php';
                                </script>";
                                exit();
                                
                            } catch (Exception $e) {
                                // Rollback transaksi
                                mysqli_rollback($conn);
                                
                                // Hapus file jika sudah terupload tapi database gagal
                                if (file_exists($target_file)) {
                                    unlink($target_file);
                                }
                                
                                echo "<script>alert('" . $e->getMessage() . "');</script>";
                            }
                        }
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Bukti Pembayaran</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 10px;
            box-sizing: border-box;
        }
        .container {
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            box-sizing: border-box;
            text-align: center;
        }
        .logo {
            max-width: 80px;
            margin-bottom: 20px;
        }
        h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
        }
        label {
            font-weight: bold;
            display: block;
            text-align: left;
            margin-bottom: 5px;
            color: #555;
        }
        input, select {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
        }
        input:focus, select:focus {
            border-color: #28a745;
            outline: none;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #28a745;
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        button:hover {
            background: #218838;
        }
        .info {
            font-size: 12px;
            color: #777;
            text-align: left;
            margin-top: 10px;
            line-height: 1.5;
        }
        .error {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }
        .file-info {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
            line-height: 1.3;
        }
        .loading {
            display: none;
            text-align: center;
            margin-top: 10px;
        }
        .progress-bar {
            width: 100%;
            background-color: #f0f0f0;
            border-radius: 4px;
            margin-top: 10px;
            display: none;
        }
        .progress {
            background-color: #28a745;
            height: 4px;
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            h2 {
                font-size: 20px;
            }
            button {
                font-size: 14px;
                padding: 10px;
            }
        }
        @media (max-width: 480px) {
            h2 {
                font-size: 18px;
            }
            input, select {
                padding: 10px;
                font-size: 14px;
            }
            button {
                font-size: 12px;
                padding: 8px;
            }
        }
    </style>
    <script>
        function formatRupiah(input) {
            let value = input.value.replace(/[^0-9]/g, ""); // Hanya angka
            if (value === "") {
                input.value = "";
                return;
            }
            
            let number = parseInt(value);
            let rupiah = number.toLocaleString('id-ID');
            input.value = "Rp " + rupiah;
        }

        function validateForm() {
            let isValid = true;
            
            // Validasi KTP
            const ktp = document.getElementById('ktp');
            const ktpError = document.getElementById('ktp-error');
            if (ktp.value.trim().length < 16) {
                ktpError.textContent = 'NIK harus 16 digit';
                ktpError.style.display = 'block';
                isValid = false;
            } else {
                ktpError.style.display = 'none';
            }
            
            // Validasi jumlah pembayaran
            const amount = document.getElementById('amount');
            const amountError = document.getElementById('amount-error');
            const cleanAmount = amount.value.replace(/[^0-9]/g, '');
            if (cleanAmount === '' || parseInt(cleanAmount) <= 0) {
                amountError.textContent = 'Jumlah pembayaran harus lebih dari 0';
                amountError.style.display = 'block';
                isValid = false;
            } else {
                amountError.style.display = 'none';
            }
            
            // Validasi file
            const receipt = document.getElementById('receipt');
            const fileError = document.getElementById('file-error');
            if (receipt.files.length > 0) {
                const file = receipt.files[0];
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                const maxSize = 5 * 1024 * 1024; // 5MB
                
                if (!allowedTypes.includes(file.type)) {
                    fileError.textContent = 'Format file tidak didukung. Gunakan JPG, PNG, atau GIF';
                    fileError.style.display = 'block';
                    isValid = false;
                } else if (file.size > maxSize) {
                    fileError.textContent = 'Ukuran file terlalu besar. Maksimal 5MB';
                    fileError.style.display = 'block';
                    isValid = false;
                } else {
                    fileError.style.display = 'none';
                }
            }
            
            return isValid;
        }

        function showLoading() {
            document.getElementById('submit-btn').disabled = true;
            document.getElementById('submit-btn').textContent = 'Sedang mengirim...';
            document.querySelector('.loading').style.display = 'block';
        }

        function previewFile() {
            const file = document.getElementById('receipt').files[0];
            const preview = document.getElementById('file-preview');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" style="max-width: 100px; max-height: 100px; border-radius: 5px;">`;
                };
                reader.readAsDataURL(file);
            } else {
                preview.innerHTML = '';
            }
        }

        // Format input KTP hanya angka
        function formatKTP(input) {
            input.value = input.value.replace(/[^0-9]/g, '').substring(0, 16);
        }
    </script>
</head>
<body>
    <div class="container">
        <!-- Logo -->
        <img src="image/logo.ico" alt="Logo" class="logo">
        
        <h2>Form Bukti Pembayaran</h2>
        <form action="#" method="post" enctype="multipart/form-data" onsubmit="return validateForm() && showLoading()">
            <label for="ktp">Masukan Nomor KTP</label>
            <input type="text" id="ktp" name="ktp" placeholder="16 digit NIK yang terdaftar saat gadai" maxlength="16" oninput="formatKTP(this)" required>
            <div id="ktp-error" class="error"></div>
            
            <label for="amount">Jumlah Pembayaran</label>
            <input type="text" id="amount" name="amount" placeholder="Masukkan jumlah pembayaran" oninput="formatRupiah(this)" required>
            <div id="amount-error" class="error"></div>
            
            <label for="payment-method">Metode Pembayaran</label>
            <select id="payment-method" name="method" required>
                <option value="">Pilih metode pembayaran</option>
                <option value="Transfer Bank">Transfer Bank</option>
                <option value="E-Wallet">E-Wallet</option>
                <option value="Tunai">Tunai</option>
            </select>
            
            <label for="receipt">Unggah Bukti Pembayaran</label>
            <input type="file" id="receipt" name="receipt" accept="image/jpeg,image/jpg,image/png,image/gif" onchange="previewFile()" required>
            <div class="file-info">
                Format: JPG, PNG, GIF | Maksimal: 5MB
            </div>
            <div id="file-error" class="error"></div>
            <div id="file-preview" style="margin-top: 10px;"></div>
            
            <button type="submit" id="submit-btn" name="byrcicilan">Kirim</button>
            
            <div class="loading">
                <p>Sedang memproses...</p>
                <div class="progress-bar">
                    <div class="progress" style="width: 0%"></div>
                </div>
            </div>
            
            <div class="info">
                <p>Silakan upload bukti pembayaran yang jelas dan dapat dibaca.</p>
                <p>Pastikan jumlah pembayaran sesuai dengan yang tertera pada tagihan.</p>
                <p>Jika ada kesalahan, silakan hubungi customer service kami.</p>
                <p>Jika Anda tidak menerima konfirmasi dalam waktu 24 jam, silakan hubungi kami.</p>
                <p>Pastikan Anda mengisi semua informasi dengan benar.</p>
            </div>
        </form>
    </div>
</body>
</html>
