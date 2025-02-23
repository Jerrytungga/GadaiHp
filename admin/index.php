<?php
include '../database.php';
session_start();
if (!isset($_SESSION['id'])) {
  echo "<script type='text/javascript'>
  alert('Anda harus login terlebih dahulu!');
  window.location = '../index.php'
</script>";
} else {
  $id = $_SESSION['id'];
  $get_data = mysqli_query($conn, "SELECT * FROM admin WHERE id='$id'");
  $data = mysqli_fetch_array($get_data);

  $ambil_data = mysqli_query($conn, "SELECT * FROM From_gadai");
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        .logo {
            width: 10%;
        }
        .bg {
            background-color: #FBF8EF;
        }
    </style>
  </head>
  <body>
    <nav class="navbar bg">
      <div class="container">
        <img src="../image/logo.ico" class="logo" alt="Logo">
        <a href="../logout.php" class="btn btn-outline-danger">Keluar</a>
      </div>
    </nav>

    <div class="container">
      <div class="row mt-5">
        <ul class="nav nav-tabs" id="myTab" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="DataNasabah-tab" data-bs-toggle="tab" data-bs-target="#DataNasabah-tab-pane" type="button" role="tab" aria-controls="DataNasabah-tab-pane" aria-selected="true">Data Nasabah</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-tab-pane" type="button" role="tab" aria-controls="profile-tab-pane" aria-selected="false">Daftar Jatuh Tempo</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="contact-tab" data-bs-toggle="tab" data-bs-target="#contact-tab-pane" type="button" role="tab" aria-controls="contact-tab-pane" aria-selected="false">Contact</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="disabled-tab" data-bs-toggle="tab" data-bs-target="#disabled-tab-pane" type="button" role="tab" aria-controls="disabled-tab-pane" aria-selected="false" disabled>Disabled</button>
          </li>
        </ul>
        <div class="tab-content" id="myTabContent">
          <div class="tab-pane fade show active" id="DataNasabah-tab-pane" role="tabpanel" aria-labelledby="DataNasabah-tab" tabindex="0">
            <div class="container mt-5">
            <a href="form_peminjaman.php" class="btn btn-info">Form Peminjaman</a>
              <h2>Data Nasabah</h2>
              <table id="example" class="table table-striped table-bordered" style="width:100%">
                <thead>
                  <tr>
                    <th scope="col">No</th>
                    <th scope="col">Ktp</th>
                    <th scope="col">Nama</th>
                    <th scope="col">Tanggal Gadai</th>
                    <th scope="col">Jumlah Pinjaman</th>
                    <th scope="col">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  $i = 1;
                  foreach ($ambil_data as $row) :
                  ?>
                  <tr>
                    <td scope="row"><?= $i; ?></td>
                    <td><?= $row['ktp_nasabah'];?></td>
                    <td><?= $row['nama'];?></td>
                    <td><?= $row['tanggal_pinjaman'];?></td>
                    <td class="rupiah"><?= $row['jumlah_pinjaman']; ?></td>
                    <td>
                      <button class="btn btn-primary detail-btn" data-idform="<?= $row['id_form']; ?>">Detail</button>
                    </td>
                  </tr>
                  <?php 
                  $i++; 
                  endforeach ;
                  ?>
                </tbody>
              </table>
            </div>
          </div>
          <div class="tab-pane fade" id="profile-tab-pane" role="tabpanel" aria-labelledby="profile-tab" tabindex="0">
            <!-- Content for Daftar Jatuh Tempo -->
          </div>
          <div class="tab-pane fade" id="contact-tab-pane" role="tabpanel" aria-labelledby="contact-tab" tabindex="0">
            <!-- Content for Contact -->
          </div>
          <div class="tab-pane fade" id="disabled-tab-pane" role="tabpanel" aria-labelledby="disabled-tab" tabindex="0">
            <!-- Content for Disabled -->
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
           
            <h5 class="modal-title" id="detailModalLabel">Detail Nasabah</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="detail-content">
            <!-- Detail content will be loaded here -->
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
      $(document).ready(function() {
        $('#example').DataTable({
          "scrollX": true, // Enables horizontal scrolling
          "scrollY": "600px", // Enables vertical scrolling with a fixed height
          "paging": true, // Enable pagination
          "searching": true, // Enable search
          "info": true, // Enable information display (e.g., "Showing 1 to 10 of 100 entries")
          "ordering": true // Enable column ordering
        });

        // Handle detail button click
        $('.detail-btn').on('click', function() {
          var id = $(this).data('idform');
          $.ajax({
            url: 'nasabah.php', // URL to fetch detail data
            type: 'GET',
            data: { id: id },
            success: function(response) {
              $('#detail-content').html(response);
              $('#detailModal').modal('show');
            },
            error: function() {
              alert('Terjadi kesalahan saat mengambil data detail.');
            }
          });
        });

        // Format rupiah
        $('.rupiah').each(function() {
          var angka = $(this).text();
          $(this).text(formatRupiah(angka, 'Rp. '));
        });

        function formatRupiah(angka, prefix) {
          var number_string = angka.replace(/[^,\d]/g, '').toString(),
              split = number_string.split(','),
              sisa = split[0].length % 3,
              rupiah = split[0].substr(0, sisa),
              ribuan = split[0].substr(sisa).match(/\d{3}/gi);

          if (ribuan) {
            separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
          }

          rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
          return prefix == undefined ? rupiah : (rupiah ? 'Rp. ' + rupiah : '');
        }
      });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  </body>
</html>