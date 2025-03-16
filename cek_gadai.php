<?php
include 'database.php';

function formatRupiah($number) {
    return 'Rp ' . number_format($number, 0, ',', '.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ktp = $_POST['ktp'];
    $response = [];

    if (!empty($ktp)) {
        $query = mysqli_query($conn, "SELECT * FROM From_gadai WHERE ktp_nasabah='$ktp'");
        $data = mysqli_fetch_array($query);

        if ($data) {
            $response['status'] = 'success';
            $response['data'] = $data;
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Data tidak ditemukan.';
        }
    } else {
        $response['status'] = 'error';
        $response['message'] = 'KTP tidak boleh kosong.';
    }

    echo json_encode($response);
    exit;
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cek Data Gadai HP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
      .spinner-border {
        display: none;
      }
      .tagihan-card {
        background-color: #f8f9fa;
        border: 2px solid #007bff;
      }
    </style>
  </head>
  <body>
    <div class="container mt-5">
      <h1 class="text-center">Cek Data Gadai HP</h1>
      <div class="card mt-4">
        <div class="card-body">
          <form id="cekGadaiForm">
            <div class="mb-3">
              <label for="ktp" class="form-label">Masukkan Nomor KTP</label>
              <input type="number" class="form-control" id="ktp" name="ktp" required>
            </div>
            <button type="submit" class="btn btn-primary">Cek Data</button>
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </form>
        </div>
      </div>

      <div id="result" class="mt-4"></div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="detailModalLabel">Detail Nasabah</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <!-- Dynamic content will be loaded here -->
          </div>
        </div>
      </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
      $(document).ready(function() {
        $('#cekGadaiForm').on('submit', function(e) {
          e.preventDefault();
          var ktp = $('#ktp').val();
          $('.spinner-border').show();
          $.ajax({
            type: 'POST',
            url: '',
            data: { ktp: ktp },
            dataType: 'json',
            success: function(response) {
              $('.spinner-border').hide();
              if (response.status === 'success') {
                var data = response.data;
                var resultHtml = `
                  <div class="card">
                    <div class="card-body">
                      <h5 class="card-title">Detail Nasabah</h5>
                      <p class="card-text"><strong>Nama:</strong> ${data.nama}</p>
                      <p class="card-text"><strong>Alamat:</strong> ${data.alamat}</p>
                      <p class="card-text"><strong>No. Telepon:</strong> ${data.no_hp}</p>
                      <h5 class="card-title mt-4">Data HP yang Digadaikan</h5>
                      <p class="card-text"><strong>Merek & Tipe HP:</strong> ${data.merek_tipe_hp}</p>
                      <p class="card-text"><strong>Nomor IMEI:</strong> ${data.imei_hp}</p>
                      <p class="card-text"><strong>Kondisi HP:</strong> ${data.kondisi_hp}</p>
                      <p class="card-text"><strong>Akses Akun:</strong> ${data.akun_hp}</p>
                      <p class="card-text"><strong>Kelengkapan HP:</strong> ${data.kelengkapan_hp}</p>
                      <h5 class="card-title mt-4">Ketentuan Gadai</h5>
                      <p class="card-text"><strong>Jumlah Pinjaman:</strong> ${formatRupiah(data.jumlah_pinjaman)}</p>
                      <p class="card-text"><strong>Bunga:</strong> ${formatRupiah(data.bunga)}</p>
                      <p class="card-text"><strong>Biaya Administrasi:</strong> ${formatRupiah(data.administrasi)}</p>
                      <p class="card-text"><strong>Biaya Asuransi:</strong> ${formatRupiah(data.asuransi)}</p>
                      <p class="card-text"><strong>Total yang Harus Ditebus:</strong> ${formatRupiah(data.total_tebus_hp)}</p>
                      <p class="card-text"><strong>Tanggal Jatuh Tempo:</strong> ${data.tanggal_jatuh_tempo}</p>
                    </div>
                  </div>
                  <div class="card tagihan-card mt-4">
                    <div class="card-body">
                      <h5 class="card-title">Tagihan Pelunasan</h5>
                      <p class="card-text"><strong>Bunga Bulanan:</strong> ${formatRupiah(data.bunga)}</p>
                    </div>
                  </div>
                `;
                $('#result').html(resultHtml);
              } else {
                $('#result').html(`<div class="alert alert-danger">${response.message}</div>`);
              }
            }
          });
        });

        function formatRupiah(number) {
          return 'Rp ' + new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(number).replace('Rp', '').trim();
        }
      });
    </script>
  </body>
</html>