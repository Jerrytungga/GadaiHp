<?php
include '../database.php';

function formatRupiah($number) {
    return 'Rp ' . number_format($number, 0, ',', '.');
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $query = mysqli_query($conn, "SELECT * FROM From_gadai WHERE id_form='$id'");
    $data = mysqli_fetch_array($query);

    if (!$data) {
        echo "Data tidak ditemukan.";
        exit;
    }
} else {
    echo "ID tidak ditemukan.";
    exit;
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detail Nasabah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
      .card {
        margin-top: 20px;
      }
      .card img {
        width: 100px;
        height: 100px;
        object-fit: cover;
        cursor: pointer;
      }
      .card-title {
        font-size: 1.5rem;
        font-weight: bold;
      }
      .card-text {
        font-size: 1rem;
      }
      .badge-custom {
        background-color: #17a2b8;
        color: white;
      }
    </style>
  </head>
  <body>
    <div class="container mt-5">
      <div class="row">
        <div class="col-md-4">
          <div class="card bg-light">
            <div class="card-body text-center">
              <img src="<?= $data['foto_nasabah'] ?>" class="rounded-circle mb-3 img-thumbnail" alt="Foto Nasabah" width="150" height="150" onclick="showImageModal(this)">
              <h5 class="card-title"><?= $data['nama'] ?></h5>
              <p class="card-text"><span class="badge badge-custom">KTP:</span> <?= $data['ktp_nasabah'] ?></p>
              <p class="card-text">Foto KTP: <br>
                <img src="<?= $data['foto_ktp'] ?>" class="rounded float-start img-thumbnail" alt="Foto KTP" width="100" height="100" onclick="showImageModal(this)">
              </p>
            </div>
          </div>
        </div>
        <div class="col-md-8">
          <div class="card bg-light">
            <div class="card-body">
              <h5 class="card-title">Informasi Nasabah</h5>
              <p class="card-text"><span class="badge badge-custom">Alamat:</span> <?= $data['alamat'] ?></p>
              <p class="card-text"><span class="badge badge-custom">No. Telepon:</span> <?= $data['no_hp'] ?></p>
              <h5 class="card-title mt-4">Data HP yang Digadaikan</h5>
              <p class="card-text"><span class="badge badge-custom">Merek & Tipe HP:</span> <?= $data['merek_tipe_hp'] ?></p>
              <p class="card-text"><span class="badge badge-custom">Nomor IMEI:</span> <?= $data['imei_hp'] ?></p>
              <p class="card-text"><span class="badge badge-custom">Kondisi HP:</span> <?= $data['kondisi_hp'] ?></p>
              <p class="card-text"><span class="badge badge-custom">Akses Akun:</span> <?= $data['akun_hp'] ?></p>
              <p class="card-text"><span class="badge badge-custom">Kelengkapan HP:</span> <?= $data['kelengkapan_hp'] ?></p>
              <div class="row">
                <div class="col-md-4">
                  <img src="<?= $data['foto_depan_hp'] ?>" class="img-fluid mb-2 img-thumbnail" alt="Foto Depan HP" onclick="showImageModal(this)">
                </div>
                <div class="col-md-4">
                  <img src="<?= $data['foto_belakang_hp'] ?>" class="img-fluid mb-2 img-thumbnail" alt="Foto Belakang HP" onclick="showImageModal(this)">
                </div>
                <div class="col-md-4">
                  <img src="<?= $data['foto_samping_kanan_hp'] ?>" class="img-fluid mb-2 img-thumbnail" alt="Foto Samping Kanan HP" onclick="showImageModal(this)">
                </div>
                <div class="col-md-4">
                  <img src="<?= $data['foto_samping_kiri_hp'] ?>" class="img-fluid mb-2 img-thumbnail" alt="Foto Samping Kiri HP" onclick="showImageModal(this)">
                </div>
                <div class="col-md-4">
                  <img src="<?= $data['foto_atas_hp'] ?>" class="img-fluid mb-2 img-thumbnail" alt="Foto Atas HP" onclick="showImageModal(this)">
                </div>
                <div class="col-md-4">
                  <img src="<?= $data['foto_bawa_hp'] ?>" class="img-fluid mb-2 img-thumbnail" alt="Foto Bawa HP" onclick="showImageModal(this)">
                </div>
              </div>
              <h5 class="card-title mt-4">Ketentuan Gadai</h5>
              <p class="card-text"><span class="badge badge-custom">Jumlah Pinjaman:</span> <?= formatRupiah($data['jumlah_pinjaman']) ?></p>
              <p class="card-text"><span class="badge badge-custom">Bunga:</span> <?= formatRupiah($data['bunga']) ?></p>
              <p class="card-text"><span class="badge badge-custom">Biaya Administrasi:</span> <?= formatRupiah($data['administrasi']) ?></p>
              <p class="card-text"><span class="badge badge-custom">Biaya Asuransi:</span> <?= formatRupiah($data['asuransi']) ?></p>
              <p class="card-text"><span class="badge badge-custom">Total yang Harus Ditebus:</span> <?= formatRupiah($data['total_tebus_hp']) ?></p>
              <p class="card-text"><span class="badge badge-custom">Tanggal Jatuh Tempo:</span> <?= $data['tanggal_jatuh_tempo'] ?></p>
              <h5 class="card-title mt-4">Pernyataan Nasabah</h5>
              <p>Saya, yang bertanda tangan di bawah ini, menyatakan bahwa HP yang saya gadaikan bukan hasil curian dan saya bersedia memenuhi kewajiban sesuai perjanjian. Jika saya tidak menebus HP dalam waktu yang ditentukan, saya memahami bahwa HP akan dijual oleh pihak penyedia gadai.</p>
              <h5 class="card-title mt-4">Syarat & Ketentuan</h5>
              <p>
                1️⃣ Nasabah wajib berusia minimal 18 tahun dan membawa KTP asli. <br>
                2️⃣ HP yang digadaikan harus dalam kondisi baik dan tidak terkunci akun Google/iCloud. <br>
                3️⃣ Pinjaman maksimal 70% dari harga pasar HP. <br>
                4️⃣ Bunga gadai: 10% per bulan, tergantung kondisi HP. <br>
                5️⃣ Masa gadai maksimal 3 bulan (dapat diperpanjang dengan syarat tertentu). <br>
                6️⃣ Denda keterlambatan Rp 10.000/hari jika pembayaran melewati jatuh tempo. <br>
                7️⃣ Jika HP tidak ditebus dalam 7 hari setelah jatuh tempo, HP akan dijual oleh penyedia gadai. <br>
                8️⃣ Nasabah wajib mencadangkan data pribadi sebelum gadai, karena penyedia gadai tidak bertanggung jawab atas kehilangan data. <br>
                9️⃣ Penyedia gadai berhak menolak HP yang dicurigai hasil curian.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="imageModalLabel">Gambar</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body text-center">
            <img id="modalImage" src="" class="img-fluid" alt="Gambar">
          </div>
        </div>
      </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
      function showImageModal(img) {
        $('#modalImage').attr('src', img.src);
        $('#imageModal').modal('show');
      }
    </script>
  </body>
</html>