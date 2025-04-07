<?php
include 'head.php';
include 'navbar.php';
include 'sidebar.php';
$query = mysqli_query($conn, "SELECT transaksi.*, barang_gadai.nama_barang, barang_gadai.pinjaman, barang_gadai.bunga, pelanggan.nama AS nama_pemilik 
                              FROM transaksi 
                              JOIN barang_gadai ON transaksi.barang_id = barang_gadai.id 
                              JOIN pelanggan ON transaksi.pelanggan_nik = pelanggan.nik 
                              ");

// Function to format numbers as Rupiah
function formatRupiah($number) {
    return 'Rp ' . number_format($number, 0, ',', '.');
}
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1>Daftar transaksi Gadai HP</h1>
        </div>
        <div class="col-sm-6">
         
        </div>
      </div>
    </div><!-- /.container-fluid -->
  </section>

  <!-- Main content -->
  <section class="content">

    <!-- Default box -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Transaksi</h3>

        <div class="card-tools">
          <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
            <i class="fas fa-minus"></i>
          </button>
          <button type="button" class="btn btn-tool" data-card-widget="remove" title="Remove">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>
      <div class="card-body">
        <?php if (isset($_GET['message']) && $_GET['message'] == 'success') { ?>
          <div class="alert alert-success">
            Data gadai berhasil ditambahkan!
          </div>
        <?php } ?>
        <div class="mb-3">
    <button class="btn btn-primary filter-btn" data-filter="">Semua</button>
    <button class="btn btn-success filter-btn" data-filter="lunas">Lunas</button>
    <button class="btn btn-warning filter-btn" data-filter="cicilan">Cicilan</button>
</div>
        <div class="table-responsive"> <!-- Tambahkan div ini untuk membuat tabel responsif -->
          <table id="userTable" class="table table-bordered table-striped">
            <thead>
              <tr>
                <th>No</th>
                <th>Nama Pemilik</th>
                <th>Nama Barang</th>
                <th>Jumlah Gadai</th>
                <th>Bunga (Profit)</th>
                <th>Jumlah Pembayaran</th>
                <th>Metode Pembayaran</th>
                <th>Tanggal Bayar</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $no = 1;
              while ($gadai = mysqli_fetch_assoc($query)) {

                $pinjaman = $gadai['pinjaman'];
                // Menghitung biaya administrasi 1%
                $satupersen = 1;  // Persentase yang diinginkan (1%)
                $Administrasi = ($pinjaman * $satupersen) / 100;

                // Biaya admin tetap 10.000
                $biaya_admin = 10000; 
               $totalbunga = $Administrasi + $biaya_admin + $gadai['pinjaman'] * ($gadai['bunga'] / 100);
              ?>
                <tr>
                  <td><?= $no++; ?></td>
                  <td><?= htmlspecialchars($gadai['nama_pemilik']); ?></td>
                  <td><?= htmlspecialchars($gadai['nama_barang']); ?></td>
                  <td><?= formatRupiah($gadai['pinjaman']); ?></td>
                  <td><?= formatRupiah($totalbunga); ?></td>
                  <td><?= formatRupiah($gadai['jumlah_bayar']); ?></td>
                  <td>
                    <?= $gadai['metode_pembayaran']; ?> <br>
                    <a href="#" data-toggle="modal" data-target="#viewBuktiModal" data-bukti="../payment/<?= $gadai['bukti']; ?>">Lihat bukti transfer</a>
                  </td>
                  <td><?= htmlspecialchars($gadai['tanggal_bayar']); ?></td>
                  <td>
                    <?php if ($gadai['keterangan'] == 'cicilan') { ?>
                      <span class="badge bg-warning"><?= htmlspecialchars($gadai['keterangan']); ?></span>
                    <?php } elseif ($gadai['keterangan'] == 'lunas') { ?>
                      <span class="badge bg-success"><?= htmlspecialchars($gadai['keterangan']); ?></span>
                    <?php } else { ?>
                      <span class="badge bg-secondary"><?= htmlspecialchars($gadai['keterangan']); ?></span>
                    <?php } ?>
                  </td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div> <!-- Tutup div table-responsive -->
      </div>
      <!-- /.card-body -->
      <div class="card-footer">
        Footer
      </div>
      <!-- /.card-footer-->
    </div>
    <!-- /.card -->

  </section>
  <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<!-- Modal untuk melihat bukti transfer -->
<div class="modal fade" id="viewBuktiModal" tabindex="-1" aria-labelledby="viewBuktiModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewBuktiModalLabel">Bukti Transfer</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="buktiImage" src="" alt="Bukti Transfer" class="img-fluid">
            </div>
        </div>
    </div>
</div>

<?php
include 'script.php';
?>

<!-- Include DataTables CSS and JS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>

<script>
$(document).ready(function() {
    var table = $('#userTable').DataTable({
        responsive: true
    });

    // Filter berdasarkan keterangan (Lunas / Cicilan)
    $('.filter-btn').on('click', function() {
        var filterValue = $(this).data('filter');
        if (filterValue) {
            table.column(8).search(filterValue).draw(); // Kolom ke-8 adalah kolom Keterangan
        } else {
            table.column(8).search('').draw(); // Reset filter jika tombol "Semua" ditekan
        }
    });
});
</script>




