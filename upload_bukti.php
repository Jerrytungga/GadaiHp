<?php
include 'database.php'; // Pastikan file koneksi database benar
$id_gadai = $_GET['id_gadai'];

if (isset($_POST['byrcicilan'])) {
    $pelanggan = $_POST['ktp'];
    $payment = str_replace(['Rp', '.', ','], '', $_POST['amount']); // Hapus format Rupiah
    $metode = $_POST['method'];
    $bukti = $_FILES['receipt'];

    // Set the target directory for file uploads
    $base_dir = "payment/";
    $target_dir = $base_dir . $pelanggan . "/"; // Buat folder berdasarkan KTP

    // Periksa apakah folder sudah ada, jika tidak buat folder
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true); // Buat folder dengan izin 0755
    }

    // Nama file bukti berdasarkan nominal pembayaran
    $file_extension = pathinfo($bukti['name'], PATHINFO_EXTENSION); // Dapatkan ekstensi file
    $new_file_name = $payment . '.' . $file_extension; // Nama file baru berupa nominal pembayaran
    $target_file = $target_dir . $new_file_name;

    // Periksa jumlah pengiriman sebelumnya
    $checkAttempts = mysqli_query($conn, "SELECT COUNT(*) AS total FROM transaksi WHERE pelanggan_nik = '$pelanggan' AND barang_id = '$id_gadai'");
    $attempts = mysqli_fetch_assoc($checkAttempts)['total'];

    if ($attempts >= 3) {
        echo "<script>
            alert('Anda hanya dapat mengirim data maksimal 3 kali.');
            window.location.href = 'index.php';
        </script>";
        exit(); // Pastikan tidak ada kode lain yang dieksekusi
    } else {
        // Check if pelanggan_nik exists in pelanggan table
        $checkPelanggan = mysqli_query($conn, "SELECT nik FROM pelanggan WHERE nik = '$pelanggan'");
        if (mysqli_num_rows($checkPelanggan) > 0) {
            $query = mysqli_query($conn, "INSERT INTO `transaksi`(`pelanggan_nik`, `barang_id`, `jumlah_bayar`, `keterangan`, `metode_pembayaran`, `bukti`) VALUES ('$pelanggan', '$id_gadai', '$payment', 'cicilan', '$metode', '$new_file_name')");
            if ($query) {
                // Move the uploaded file to the target directory
                if (move_uploaded_file($bukti["tmp_name"], $target_file)) {
                    echo "<script>
                        alert('Bukti pembayaran berhasil diunggah.');
                        window.location.href = 'index.php';
                    </script>";
                    exit(); // Pastikan tidak ada kode lain yang dieksekusi
                } else {
                    echo "<script>alert('Terjadi kesalahan saat mengunggah bukti pembayaran.');</script>";
                }
            } else {
                echo "<script>alert('Gagal mengirim data pembayaran.');</script>";
            }
        } else {
            echo "<script>alert('NIK tidak ditemukan. Silakan periksa kembali.');</script>";
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
            let value = input.value.replace(/[^,\d]/g, ""); // Hanya angka dan koma
            let split = value.split(",");
            let sisa = split[0].length % 3;
            let rupiah = split[0].substr(0, sisa);
            let ribuan = split[0].substr(sisa).match(/\d{3}/gi);

            if (ribuan) {
                let separator = sisa ? "." : "";
                rupiah += separator + ribuan.join(".");
            }

            rupiah = split[1] !== undefined ? rupiah + "," + split[1] : rupiah;
            input.value = "Rp " + rupiah;
        }
    </script>
</head>
<body>
    <div class="container">
        <!-- Logo -->
        <img src="image/logo.ico" alt="Logo" class="logo">
        
        <h2>Form Bukti Pembayaran</h2>
        <form action="#" method="post" enctype="multipart/form-data">
            <label for="nik">Masukan Nomor KTP</label>
            <input type="text" id="ktp" name="ktp" placeholder="KTP yang terdaftar pada saat gadai" required>
            
            <label for="amount">Jumlah Pembayaran</label>
            <input type="text" id="amount" name="amount" placeholder="Masukkan jumlah pembayaran" oninput="formatRupiah(this)" required>
            
            <label for="payment-method">Metode Pembayaran</label>
            <select id="payment-method" name="method" required>
                <option value="Transfer bank">Transfer Bank</option>
            </select>
            
            <label for="receipt">Unggah Bukti Pembayaran</label>
            <input type="file" id="receipt" name="receipt" accept="image/*" required>
            
            <button type="submit" name="byrcicilan">Kirim</button>
            
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
