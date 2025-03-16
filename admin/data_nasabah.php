<?php
include '../database.php';

function formatRupiah($number) {
    return 'Rp ' . number_format($number, 0, ',', '.');
}

$ktp = $_POST['ktp'] ?? '';
$data = null;
$error = '';

if (!empty($ktp)) {
    $query = mysqli_query($conn, "SELECT * FROM From_gadai WHERE ktp_nasabah='$ktp'");
    $data = mysqli_fetch_array($query);

    if (!$data) {
        $error = "Data tidak ditemukan.";
    }
} else {
    $error = "KTP tidak boleh kosong.";
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Nasabah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  </head>
  <body>
    <div class="container mt-5">
      <h1 class="text-center">Data Nasabah</h1>
      <form action="data_nasabah.php" method="post">
        <div class="mb-3">
          <label for="ktp" class="form-label">Masukkan Nomor KTP</label>
          <input type="number" class="form-control" id="ktp" name="ktp" required>
        </div>
        <button type="submit" class="btn btn-primary">Cek Data</button>
      </form>
      <?php if ($error): ?>
        <div class="alert alert-danger mt-4"><?= htmlspecialchars($error) ?></div>
      <?php elseif ($data): ?>
        <div class="card mt-4">
          <div class="card-body">
            <h5 class="card-title">Detail Nasabah</h5>
            <p class="card-text"><strong>Nama:</strong> <?= htmlspecialchars($data['nama']) ?></p>
            <p class="card-text"><strong>Alamat:</strong> <?= htmlspecialchars($data['alamat']) ?></p>
            <p class="card-text"><strong>No. Telepon:</strong> <?= htmlspecialchars($data['no_hp']) ?></p>
            <h5 class="card-title mt-4">Data HP yang Digadaikan</h5>
            <p class="card-text"><strong>Merek & Tipe HP:</strong> <?= htmlspecialchars($data['merek_tipe_hp']) ?></p>
            <p class="card-text"><strong>Nomor IMEI:</strong> <?= htmlspecialchars($data['imei_hp']) ?></p>
            <p class="card-text"><strong>Kondisi HP:</strong> <?= htmlspecialchars($data['kondisi_hp']) ?></p>
            <p class="card-text"><strong>Akses Akun:</strong> <?= htmlspecialchars($data['akun_hp']) ?></p>
            <p class="card-text"><strong>Kelengkapan HP:</strong> <?= htmlspecialchars($data['kelengkapan_hp']) ?></p>
            <h5 class="card-title mt-4">Ketentuan Gadai</h5>
            <p class="card-text"><strong>Jumlah Pinjaman:</strong> <?= formatRupiah($data['jumlah_pinjaman']) ?></p>
            <p class="card-text"><strong>Bunga:</strong> <?= formatRupiah($data['bunga']) ?></p>
            <p class="card-text"><strong>Biaya Administrasi:</strong> <?= formatRupiah($data['administrasi']) ?></p>
            <p class="card-text"><strong>Biaya Asuransi:</strong> <?= formatRupiah($data['asuransi']) ?></p>
            <p class="card-text"><strong>Total yang Harus Ditebus:</strong> <?= formatRupiah($data['total_tebus_hp']) ?></p>
            <p class="card-text"><strong>Tanggal Jatuh Tempo:</strong> <?= htmlspecialchars($data['tanggal_jatuh_tempo']) ?></p>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </body>
</html>