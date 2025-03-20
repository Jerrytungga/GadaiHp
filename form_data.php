<!-- filepath: /Applications/MAMP/htdocs/GadaiHp/form_data.php -->
<?php
include 'database.php'; // Pastikan file koneksi database Anda sudah diatur
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Peminjaman</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2 class="text-center">Form Peminjaman</h2>
    <form action="proses_peminjaman.php" method="POST" enctype="multipart/form-data">
        <!-- Data Pelanggan -->
        <div class="mb-3">
            <label for="pelanggan_nik" class="form-label">NIK Pelanggan</label>
            <input type="text" class="form-control" id="pelanggan_nik" name="pelanggan_nik" placeholder="Masukkan NIK pelanggan" required>
        </div>
        <div class="mb-3">
            <label for="nama_pemilik" class="form-label">Nama Pelanggan</label>
            <input type="text" class="form-control" id="nama_pemilik" name="nama_pemilik" placeholder="Masukkan nama pelanggan" required>
        </div>

        <!-- Data Barang -->
        <div class="mb-3">
            <label for="nama_barang" class="form-label">Nama Barang</label>
            <input type="text" class="form-control" id="nama_barang" name="nama_barang" placeholder="Masukkan nama barang" required>
        </div>
        <div class="mb-3">
            <label for="imei" class="form-label">IMEI Barang</label>
            <input type="text" class="form-control" id="imei" name="imei" placeholder="Masukkan IMEI barang (opsional)">
        </div>
        <div class="mb-3">
            <label for="deskripsi" class="form-label">Deskripsi Barang</label>
            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3" placeholder="Masukkan deskripsi barang" required></textarea>
        </div>

        <!-- Data Peminjaman -->
        <div class="mb-3">
            <label for="nilai_taksir" class="form-label">Nilai Taksir Barang</label>
            <input type="number" class="form-control" id="nilai_taksir" name="nilai_taksir" placeholder="Masukkan nilai taksir barang" required>
        </div>
        <div class="mb-3">
            <label for="pinjaman" class="form-label">Jumlah Pinjaman</label>
            <input type="number" class="form-control" id="pinjaman" name="pinjaman" placeholder="Masukkan jumlah pinjaman" required>
        </div>
        <div class="mb-3">
            <label for="bunga" class="form-label">Bunga (%)</label>
            <input type="number" class="form-control" id="bunga" name="bunga" placeholder="Masukkan persentase bunga" required>
        </div>
        <div class="mb-3">
            <label for="jatuh_tempo" class="form-label">Tanggal Jatuh Tempo</label>
            <input type="date" class="form-control" id="jatuh_tempo" name="jatuh_tempo" required>
        </div>

        <!-- Tombol Submit -->
        <button type="submit" class="btn btn-primary">Ajukan Peminjaman</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>