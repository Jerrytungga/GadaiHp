<?php
include '../database.php';
session_start();
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Query untuk mendapatkan data gadai berdasarkan ID
    $query = mysqli_query($conn, "
        SELECT barang_gadai.*, pelanggan.nama AS nama_pemilik, pelanggan.nomor_hp AS telepon_pemilik 
        FROM barang_gadai 
        JOIN pelanggan ON barang_gadai.pelanggan_nik = pelanggan.nik 
        WHERE barang_gadai.id = '$id'
    ");

    $gadai = mysqli_fetch_assoc($query);

    if (!$gadai) {
        echo "Data tidak ditemukan!";
        exit;
    }

    // Hitung bunga bulanan
    $bungaBulanan = $gadai['pinjaman'] * ($gadai['bunga'] / 100);
    $totalTebusan = $gadai['pinjaman'] + $bungaBulanan;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Gadai</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .invoice-box {
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 10px;
            background-color: #f9f9f9;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .invoice-header {
            border-bottom: 2px solid #007bff;
            margin-bottom: 20px;
            padding-bottom: 10px;
        }
        .invoice-header img {
            max-width: 100px;
        }
        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
        .invoice-footer {
            margin-top: 20px;
            text-align: center;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="invoice-box">
        <!-- Header -->
        <div class="row invoice-header">
            <div class="col-md-6">
                <img src="../image/logo.ico" alt="Logo Perusahaan">
            </div>
            <div class="col-md-6 text-end">
                <h1 class="invoice-title">Invoice Gadai</h1>
                <p><strong>Tanggal:</strong> <?= date('d-m-Y'); ?></p>
            </div>
        </div>

        <!-- Informasi Pemilik -->
        <div class="row">
            <div class="col-md-6">
                <h5>Informasi Pemilik</h5>
                <p><strong>Nama:</strong> <?= htmlspecialchars($gadai['nama_pemilik']); ?></p>
                <p><strong>Telepon:</strong> <?= htmlspecialchars($gadai['telepon_pemilik']); ?></p>
                <p><strong>NIK:</strong> <?= htmlspecialchars($gadai['pelanggan_nik']); ?></p>
            </div>
            <div class="col-md-6">
                <h5>Informasi Barang</h5>
                <p><strong>Nama Barang:</strong> <?= htmlspecialchars($gadai['nama_barang']); ?></p>
                <p><strong>IMEI:</strong> <?= htmlspecialchars($gadai['imei']); ?></p>
                <p><strong>Deskripsi:</strong> <?= htmlspecialchars($gadai['deskripsi']); ?></p>
            </div>
        </div>

        <!-- Detail Peminjaman -->
        <hr>
        <h5>Detail Peminjaman</h5>
        <table class="table table-bordered">
            <tr>
                <th>Nilai Taksir</th>
                <td>Rp <?= number_format($gadai['nilai_taksir'], 0, ',', '.'); ?></td>
            </tr>
            <tr>
                <th>Jumlah Pinjaman</th>
                <td>Rp <?= number_format($gadai['pinjaman'], 0, ',', '.'); ?></td>
            </tr>
            <tr>
                <th>Bunga Bulanan</th>
                <td>Rp <?= number_format($bungaBulanan, 0, ',', '.'); ?> (<?= $gadai['bunga']; ?>%)</td>
            </tr>
            <tr>
                <th>Total Tebusan</th>
                <td>Rp <?= number_format($totalTebusan, 0, ',', '.'); ?></td>
            </tr>
            <tr>
                <th>Jatuh Tempo</th>
                <td><?= htmlspecialchars($gadai['jatuh_tempo']); ?></td>
            </tr>
        </table>

        <!-- Footer -->
        <div class="invoice-footer">
            <p>Terima kasih telah menggunakan layanan kami.</p>
            <p><strong>Gadai HP Cepat & Terpercaya</strong></p>
        </div>

        <!-- Tombol Cetak -->
        <div class="text-center mt-4">
            <button class="btn btn-primary" onclick="window.print()">Cetak Invoice</button>
            <a href="vg.php" class="btn btn-secondary">Kembali</a>
        </div>
    </div>
</div>
</body>
</html>