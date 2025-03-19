<?php
include 'head.php';
include 'navbar.php';
include 'sidebar.php';
$id = $_GET['nik'];
$query = mysqli_query($conn, "SELECT barang_gadai.*, pelanggan.nama AS nama_pemilik FROM barang_gadai JOIN pelanggan ON barang_gadai.pelanggan_nik = pelanggan.nik WHERE pelanggan_nik = '$id'");

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
          <h1>Daftar riwayat Gadai HP</h1>
        </div>
        <div class="col-sm-6">
          <button type="button" class="btn btn-secondary float-right" onclick="history.back();">
            Kembali
          </button>
        </div>
      </div>
    </div><!-- /.container-fluid -->
  </section>

  <!-- Main content -->
  <section class="content">

    <!-- Default box -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Daftar Gadai</h3>

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
        <div style="overflow-x: auto;">
          <table id="userTable" class="table table-bordered table-striped">
            <thead>
              <tr>
                <th>ID</th>
                <th>Nama Barang</th>
                <th>Imei</th>
                <th>Pemilik</th>
                <th>Nilai Taksir</th>
                <th>Pinjaman</th>
                <th>Jatuh Tempo</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $no = 1;
              while ($gadai = mysqli_fetch_assoc($query)) {
              ?>
                <tr>
                  <td><?= $no++; ?></td>
                  <td><?= htmlspecialchars($gadai['nama_barang']); ?></td>
                  <td><?= htmlspecialchars($gadai['imei']); ?></td>
                  <td><?= htmlspecialchars($gadai['nama_pemilik']); ?></td>
                  <td><?= formatRupiah($gadai['nilai_taksir']); ?></td>
                  <td><?= formatRupiah($gadai['pinjaman'] + ($gadai['pinjaman'] * $gadai['bunga'] / 100)); ?></td>
                  <td><?= htmlspecialchars($gadai['jatuh_tempo']); ?></td>
                  <td><span class="badge bg-success"><?= htmlspecialchars($gadai['status']); ?></span></td>
                  <td>
                    <a href="transaksi.php?id=<?= $gadai['id']; ?>" class="btn btn-sm btn-primary">Lihat Pembayaran</a>
                  </td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
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


<?php
include 'script.php';
?>

<!-- Include DataTables CSS and JS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>

<script>
$(document).ready(function() {
    $('#userTable').DataTable();
});
</script>




