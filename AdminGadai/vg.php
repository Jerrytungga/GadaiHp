<?php
include 'head.php';
include 'navbar.php';
include 'sidebar.php';

if (isset($_POST['byrcicilan'])) {
  $pelanggan = $_POST['pelanggan'];
  $id_gadai = $_POST['id_gadai'];
  $status = $_POST['status'];
  $bunga = $_POST['bunga'];

  // Check if pelanggan_nik exists in pelanggan table
  $checkPelanggan = mysqli_query($conn, "SELECT nik FROM pelanggan WHERE nik = '$pelanggan'");
  if (mysqli_num_rows($checkPelanggan) > 0) {
    $query = mysqli_query($conn, "INSERT INTO `transaksi`(`pelanggan_nik`, `barang_id`, `jumlah_bayar`, `keterangan`) VALUES ('$pelanggan','$id_gadai','$bunga','$status')");
    if ($query) {
      echo "<script>alert('Berhasil membayar cicilan')</script>";
    } else {
      echo "<script>alert('Gagal membayar cicilan')</script>";
    }
  } else {
    echo "<script>alert('Pelanggan tidak ditemukan')</script>";
  }
}

if (isset($_POST['lunas'])) {
  $idbarang = $_POST['lunas'];
  $pelanggan = $_POST['pelanggan'];
  $jumlah_tebus = $_POST['jumlah_tebus'];
  $status = 'lunas';
  mysqli_query($conn, "INSERT INTO `transaksi`(`pelanggan_nik`, `barang_id`, `jumlah_bayar`, `keterangan`) VALUES ('$pelanggan','$idbarang','$jumlah_tebus','$status')");
  mysqli_query($conn, "UPDATE barang_gadai SET status='ditebus' WHERE id='$idbarang'");
}

$userQuery = mysqli_query($conn, "SELECT nik, nama FROM pelanggan");
$query = mysqli_query($conn, "SELECT barang_gadai.*, pelanggan.nama AS nama_pemilik, pelanggan.nomor_hp AS telepon_pemilik, 
                              DATEDIFF(jatuh_tempo, CURDATE()) AS days_to_due 
                              FROM barang_gadai 
                              JOIN pelanggan ON barang_gadai.pelanggan_nik = pelanggan.nik 
                              ORDER BY days_to_due ASC");

// Function to format numbers as Rupiah
function formatRupiah($number) {
    return 'Rp ' . number_format($number, 0, ',', '.');
}

// Function to check if the due date is within 3 days
function isDueSoon($daysToDue) {
    return $daysToDue <= 3 && $daysToDue >= 0;
}

// Function to check if the due date has passed
function isOverdue($daysToDue) {
    return $daysToDue < 0;
}
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1>Daftar Gadai HP</h1>
        </div>
        <div class="col-sm-6">
          <button type="button" class="btn btn-primary float-right" data-toggle="modal" data-target="#addGadaiModal">
            Tambah Gadai
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
        <h3 class="card-title">Gadai List</h3>

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
        <table id="userTable" class="table table-bordered table-striped">
          <thead>
          <tr>
                <th>ID</th>
                <th>Nama Barang</th>
                <th>Imei</th>
                <th>Pemilik</th>
                <th>Nilai Taksir</th>
                <th>Pinjaman</th>
                <th>Bunga Bulanan (Rp)</th>
                <th>Jatuh Tempo</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>

          </thead>
          <tbody>
            <?php
            
            $no = 1;
            while ($gadai = mysqli_fetch_assoc($query)) {
              if ($gadai['status'] == 'ditebus') {
                continue;
              }
              $rowClass = '';
              if (isOverdue($gadai['days_to_due'])) {
                $rowClass = 'bg-danger';
              } elseif (isDueSoon($gadai['days_to_due'])) {
                $rowClass = 'bg-warning';
              }
              $bungaBulanan = $gadai['pinjaman'] * ($gadai['bunga'] / 100);
              $whatsappMessageCicilan = "Halo " . htmlspecialchars($gadai['nama_pemilik']) . ",\n\nIni adalah pengingat bahwa jatuh tempo pembayaran cicilan gadai untuk barang " . htmlspecialchars($gadai['nama_barang']) . " adalah pada " . htmlspecialchars($gadai['jatuh_tempo']) . ".\n\nJumlah yang harus dibayar: " . formatRupiah($bungaBulanan) . "\n\nSilakan lakukan pembayaran melalui transfer ke Rekening BRI 305101007702502 a/n JERRI CHRISTIAN GEDEON TUNGGA.\n\nTerima kasih.";
              $whatsappMessageLunas = "Halo " . htmlspecialchars($gadai['nama_pemilik']) . ",\n\nIni adalah pengingat bahwa jatuh tempo pembayaran lunas gadai untuk barang " . htmlspecialchars($gadai['nama_barang']) . " adalah pada " . htmlspecialchars($gadai['jatuh_tempo']) . ".\n\nJumlah yang harus dibayar: " . formatRupiah($gadai['pinjaman'] + $bungaBulanan) . "\n\nSilakan lakukan pembayaran melalui transfer ke Rekening BRI 305101007702502 a/n JERRI CHRISTIAN GEDEON TUNGGA.\n\nTerima kasih.";
            ?>
              <tr class="<?= $rowClass; ?>">
                <td><?= $no++; ?></td>
                <td><?= htmlspecialchars($gadai['nama_barang']); ?></td>
                <td><?= htmlspecialchars($gadai['imei']); ?></td>
                <td><?= htmlspecialchars($gadai['nama_pemilik']); ?></td>
                <td><?= formatRupiah($gadai['nilai_taksir']); ?></td>
                <td><?= formatRupiah($gadai['pinjaman']); ?></td>
                <td><?= formatRupiah($bungaBulanan); ?></td>
                <td><?= htmlspecialchars($gadai['jatuh_tempo']); ?></td>
                <td><span class="badge bg-success"><?= htmlspecialchars($gadai['status']); ?></span></td>
                <td>
                  <?php if ($gadai['status'] != 'ditebus') { ?>
                    <a href="edit_barang.html" class="btn btn-warning btn-sm">Edit</a>
                
                    <form action="" method="post" style="display: inline;">
                      <input type="text" name="pelanggan" value="<?= $gadai['pelanggan_nik']; ?>" hidden>
                      <input type="text" name="id_gadai" value="<?= $gadai['id']; ?>" hidden>
                      <input type="text" name="status" value="cicilan" hidden>
                      <input type="text" name="bunga" value="<?= $gadai['bunga']; ?>" hidden>
                      <button type="submit" name="byrcicilan" class="btn btn-info btn-sm">Bayar Cicilan</button>
                    </form>
                    
                    <form action="" method="post" style="display: inline;">
                      <input type="text" name="lunas" value="<?= $gadai['id']; ?>" hidden>
                      <input type="text" name="pelanggan" value="<?= $gadai['pelanggan_nik']; ?>" hidden>
                      <input type="text" name="jumlah_tebus" value="<?= $gadai['pinjaman'] + ($gadai['pinjaman'] * $gadai['bunga'] / 100); ?>" hidden>
                      <button type="submit" name="lunas" value="<?= $gadai['id']; ?>" class="btn btn-dark btn-sm">Lunasin</button>
                    </form>

                    <a href="https://wa.me/<?= htmlspecialchars($gadai['telepon_pemilik']); ?>?text=<?= urlencode($whatsappMessageCicilan); ?>" target="_blank" class="btn btn-success btn-sm">Reminder Cicilan WA</a>
                    <a href="https://wa.me/<?= htmlspecialchars($gadai['telepon_pemilik']); ?>?text=<?= urlencode($whatsappMessageLunas); ?>" target="_blank" class="btn btn-primary btn-sm">Reminder Lunas WA</a>

                    <?php } ?>

                </td>
            </tr>
            <?php } 

            ?>
        
          </tbody>
        </table>
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

<!-- Modal for adding gadai -->
<div class="modal fade" id="addGadaiModal" tabindex="-1" role="dialog" aria-labelledby="addGadaiModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addGadaiModalLabel">Tambah Gadai</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form action="proses_gadai.php" method="POST" enctype="multipart/form-data">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="user_id">Nama Pelanggan:</label>
                <select name="user_id" id="user_id" class="form-control" required>
                  <option value="">Pilih Pelanggan</option>
                  <?php while ($user = mysqli_fetch_assoc($userQuery)) { ?>
                    <option value="<?= $user['nik']; ?>"><?= htmlspecialchars($user['nama']); ?></option>
                  <?php } ?>
                </select>
              </div>
              <div class="form-group">
                <label for="merek_hp">Merek HP:</label>
                <input type="text" name="merek_hp" id="merek_hp" class="form-control" required>
              </div>
              <div class="form-group">
                <label for="imei">IMEI HP:</label>
                <input type="text" name="imei" id="imei" class="form-control" required>
              </div>
              <div class="form-group">
                <label for="gambarhp">Foto HP:</label>
                <input type="file" name="gambarhp" id="gambarhp" class="form-control" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="keteranganhp">Keterangan HP:</label>
                <textarea name="keteranganhp" id="keteranganhp" class="form-control" required></textarea>
              </div>
              <div class="form-group">
                <label for="nilai_taksir">Nilai Taksir (Rp):</label>
                <input type="number" name="nilai_taksir" id="nilai_taksir" class="form-control" required>
              </div>
              <div class="form-group">
                <label for="pinjaman">Jumlah Pinjaman (Rp):</label>
                <input type="number" name="pinjaman" id="pinjaman" class="form-control" required>
              </div>
              <div class="form-group">
                <label for="bunga">Bunga (%):</label>
                <input type="number" name="bunga" id="bunga" class="form-control" step="0.01" required>
              </div>
              <div class="form-group">
                <label for="jatuh_tempo">Jatuh Tempo:</label>
                <input type="date" name="jatuh_tempo" id="jatuh_tempo" class="form-control" required>
              </div>
            </div>
          </div>
          <button type="submit" class="btn btn-primary">Tambah Gadai</button>
        </form>
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
    $('#userTable').DataTable();
});
</script>




