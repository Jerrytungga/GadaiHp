<?php
include 'head.php';
include 'navbar.php';
include 'sidebar.php';



if (isset($_POST['lunas'])) {
  $idbarang = $_POST['lunas'];
  $pelanggan = $_POST['pelanggan'];
  $jumlah_tebus = $_POST['jumlah_tebus'];
  $status = 'lunas';
  mysqli_query($conn, "INSERT INTO `transaksi`(`pelanggan_nik`, `barang_id`, `jumlah_bayar`, `keterangan`) VALUES ('$pelanggan','$idbarang','$jumlah_tebus','$status')");
  mysqli_query($conn, "UPDATE barang_gadai SET status='ditebus' WHERE id='$idbarang'");
  echo "<script>
    Swal.fire({
      icon: 'success',
      title: 'Berhasil',
      text: 'Barang berhasil ditebus'
    });
  </script>";
}

if (isset($_POST['edit_gadai'])) {
  $id_gadai = $_POST['id_gadai'];
  $user_id = $_POST['user_id'];
  $merek_hp = $_POST['merek_hp'];
  $imei = $_POST['imei'];
  $keteranganhp = $_POST['keteranganhp'];
  $nilai_taksir = $_POST['nilai_taksir'];
  $pinjaman = $_POST['pinjaman'];
  $bunga = $_POST['bunga'];
  $jatuh_tempo = $_POST['jatuh_tempo'];

  $query = mysqli_query($conn, "UPDATE barang_gadai SET pelanggan_nik='$user_id', nama_barang='$merek_hp', imei='$imei', deskripsi='$keteranganhp', nilai_taksir='$nilai_taksir', pinjaman='$pinjaman', bunga='$bunga', jatuh_tempo='$jatuh_tempo' WHERE id='$id_gadai'");

  if ($query) {
    echo "<script>
      Swal.fire({
        icon: 'success',
        title: 'Berhasil',
        text: 'Data gadai berhasil diupdate'
      });
    </script>";
  } else {
    echo "<script>
      Swal.fire({
        icon: 'error',
        title: 'Gagal',
        text: 'Gagal mengupdate data gadai'
      });
    </script>";
  }
}

if (isset($_POST['reminder_cicilan'])) {
    $idGadai = $_POST['id_gadai'];
    $namaPemilik = $_POST['nama_pemilik'];
    $namaBarang = $_POST['nama_barang'];
    $jatuhTempo = $_POST['jatuh_tempo'];
    $bungaBulanan = $_POST['bunga_bulanan'];
    $nomorHp = $_POST['nomor_hp']; // Nomor tujuan (format internasional tanpa "+")

    // Ambil 3 angka terakhir dari nomor telepon
    $lastThreeDigits = substr($nomorHp, -3);

    // Tambahkan 3 angka terakhir ke jumlah pembayaran
    $totalPembayaran = $bungaBulanan + (int)$lastThreeDigits;

    // URL ke halaman upload bukti pembayaran
    $uploadUrl = "http://localhost:8888/Gadaihp/upload_bukti.php?id_gadai=$idGadai";

    // Pesan WhatsApp
    $whatsappMessage = "Halo $namaPemilik,\n\nIni adalah pengingat bahwa jatuh tempo pembayaran cicilan gadai untuk barang '$namaBarang' adalah pada $jatuhTempo.\n\nJumlah yang harus dibayar: Rp " . number_format($totalPembayaran, 0, ',', '.') . "\n\nSilakan lakukan pembayaran melalui transfer ke Rekening BRI 305101007702502 a/n JERRI CHRISTIAN GEDEON TUNGGA.\n\nSetelah melakukan pembayaran, Anda dapat mengunggah bukti pembayaran melalui tautan berikut:\n$uploadUrl\n\nTerima kasih.";

    // Kirim pesan menggunakan Fonnte API
    $url = "https://api.fonnte.com/send";
    $data = [
        'target' => $nomorHp, // Nomor tujuan
        'message' => $whatsappMessage, // Isi pesan
        'countryCode' => '62', // Kode negara (62 untuk Indonesia)
    ];

    $headers = [
        "Authorization: g6i1PFe8Zcu8AvLjidiw", // Ganti dengan API Key Fonnte Anda
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Log hasil pengiriman
    if ($httpCode == 200) {
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Pesan WhatsApp berhasil dikirim ke $nomorHp'
            });
        </script>";
    } else {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: 'Gagal mengirim pesan WhatsApp. Response: $response'
            });
        </script>";
    }
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

// Query to count the number of installments paid for each item
$cicilanQuery = mysqli_query($conn, "SELECT barang_id, COUNT(*) as cicilan_count FROM transaksi WHERE keterangan = 'cicilan' GROUP BY barang_id");

// Create an associative array to store the count of installments paid for each item
$cicilanCounts = [];
while ($cicilan = mysqli_fetch_assoc($cicilanQuery)) {
    $cicilanCounts[$cicilan['barang_id']] = $cicilan['cicilan_count'];
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
        <div style="overflow-x: auto;">
          <table id="userTable" class="table table-bordered table-striped">
            <thead>
              <tr>
                <th>ID</th>
                <th>Nama Barang</th>
                <th>Imei</th>
                <th>Keterangan</th>
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
                  <td><?= htmlspecialchars($gadai['deskripsi']); ?></td>
                  <td><?= htmlspecialchars($gadai['nama_pemilik']); ?></td>
                  <td><?= formatRupiah($gadai['nilai_taksir']); ?></td>
                  <td><?= formatRupiah($gadai['pinjaman']); ?></td>
                  <td><?= formatRupiah($bungaBulanan); ?></td>
                  <td><?= htmlspecialchars($gadai['jatuh_tempo']); ?></td>
                  <td><span class="badge bg-success"><?= htmlspecialchars($gadai['status']); ?></span></td>
                  <td>
                    <?php if ($gadai['status'] != 'ditebus') { ?>
                      <div class="d-none d-md-block">
                        <button type="button" class="btn btn-light btn-sm btn-edit m-1" data-gadai='<?= json_encode($gadai); ?>'>Edit</button>
                        <form action="" method="post" style="display: inline;" class="mb-1">
                          <input type="text" name="pelanggan" value="<?= $gadai['pelanggan_nik']; ?>" hidden>
                          <input type="text" name="id_gadai" value="<?= $gadai['id']; ?>" hidden>
                          <input type="text" name="status" value="cicilan" hidden>
                          <input type="text" name="bunga" value="<?= $gadai['bunga']; ?>" hidden>
                          <button type="submit" name="byrcicilan" class="btn btn-info m-1 btn-sm">
                            Bayar Cicilan <span class="badge badge-light"><?= isset($cicilanCounts[$gadai['id']]) ? $cicilanCounts[$gadai['id']] : 0; ?></span>
                          </button>
                        </form>
                        <form action="" method="post" style="display: inline;" class="mb-1">
                          <input type="text" name="lunas" value="<?= $gadai['id']; ?>" hidden>
                          <input type="text" name="pelanggan" value="<?= $gadai['pelanggan_nik']; ?>" hidden>
                          <input type="text" name="jumlah_tebus" value="<?= $gadai['pinjaman'] + ($gadai['pinjaman'] * $gadai['bunga'] / 100); ?>" hidden>
                          <button type="submit" name="lunas" value="<?= $gadai['id']; ?>" class="btn m-1 btn-dark btn-sm">Lunasin</button>
                        </form>
                        <form action="" method="post" style="display: inline;" class="mb-1">
                            <input type="hidden" name="id_gadai" value="<?= $gadai['id']; ?>">
                            <input type="hidden" name="pelanggan" value="<?= $gadai['pelanggan_nik']; ?>">
                            <input type="hidden" name="nama_pemilik" value="<?= htmlspecialchars($gadai['nama_pemilik']); ?>">
                            <input type="hidden" name="nama_barang" value="<?= htmlspecialchars($gadai['nama_barang']); ?>">
                            <input type="hidden" name="jatuh_tempo" value="<?= htmlspecialchars($gadai['jatuh_tempo']); ?>">
                            <input type="hidden" name="bunga_bulanan" value="<?= $gadai['pinjaman'] * ($gadai['bunga'] / 100); ?>">
                            <input type="hidden" name="nomor_hp" value="<?= htmlspecialchars($gadai['telepon_pemilik']); ?>">
                            <button type="submit" name="reminder_cicilan" class="btn m-1 btn-success btn-sm">Reminder Cicilan WA</button>
                        </form>
                        <a href="https://wa.me/<?= htmlspecialchars($gadai['telepon_pemilik']); ?>?text=<?= urlencode($whatsappMessageLunas); ?>" target="_blank" class="btn btn-primary m-1 btn-sm">Reminder Lunas WA</a>
                      </div>
                      <div class="d-block d-md-none">
                        <div class="dropdown">
                          <button class="btn btn-secondary btn-sm dropdown-toggle mb-1" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            Aksi
                          </button>
                          <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item btn-edit" href="#" data-gadai='<?= json_encode($gadai); ?>'>Edit</a>
                            <form action="" method="post" style="display: inline;" class="mb-1">
                              <input type="text" name="pelanggan" value="<?= $gadai['pelanggan_nik']; ?>" hidden>
                              <input type="text" name="id_gadai" value="<?= $gadai['id']; ?>" hidden>
                              <input type="text" name="status" value="cicilan" hidden>
                              <input type="text" name="bunga" value="<?= $gadai['bunga']; ?>" hidden>
                              <button type="submit" name="byrcicilan" class="dropdown-item">Bayar Cicilan <span class="badge badge-light"><?= isset($cicilanCounts[$gadai['id']]) ? $cicilanCounts[$gadai['id']] : 0; ?></span></button>
                            </form>
                            <form action="" method="post" style="display: inline;" class="mb-1">
                              <input type="text" name="lunas" value="<?= $gadai['id']; ?>" hidden>
                              <input type="text" name="pelanggan" value="<?= $gadai['pelanggan_nik']; ?>" hidden>
                              <input type="text" name="jumlah_tebus" value="<?= $gadai['pinjaman'] + ($gadai['pinjaman'] * $gadai['bunga'] / 100); ?>" hidden>
                              <button type="submit" name="lunas" value="<?= $gadai['id']; ?>" class="dropdown-item">Lunasin</button>
                            </form>
                            <form action="" method="post" style="display: inline;" class="mb-1">
                                <input type="hidden" name="id_gadai" value="<?= $gadai['id']; ?>">
                                <input type="hidden" name="pelanggan" value="<?= $gadai['pelanggan_nik']; ?>">
                                <input type="hidden" name="nama_pemilik" value="<?= htmlspecialchars($gadai['nama_pemilik']); ?>">
                                <input type="hidden" name="nama_barang" value="<?= htmlspecialchars($gadai['nama_barang']); ?>">
                                <input type="hidden" name="jatuh_tempo" value="<?= htmlspecialchars($gadai['jatuh_tempo']); ?>">
                                <input type="hidden" name="bunga_bulanan" value="<?= $gadai['pinjaman'] * ($gadai['bunga'] / 100); ?>">
                                <input type="hidden" name="nomor_hp" value="<?= htmlspecialchars($gadai['telepon_pemilik']); ?>">
                                <button type="submit" name="reminder_cicilan" class="btn btn-success btn-sm">Reminder Cicilan WA</button>
                            </form>
                            <a class="dropdown-item" href="https://wa.me/<?= htmlspecialchars($gadai['telepon_pemilik']); ?>?text=<?= urlencode($whatsappMessageLunas); ?>" target="_blank">Reminder Lunas WA</a>
                          </div>
                        </div>
                      </div>
                    <?php } ?>
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

<!-- Modal for editing gadai -->
<div class="modal fade" id="editGadaiModal" tabindex="-1" role="dialog" aria-labelledby="editGadaiModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editGadaiModalLabel">Edit Gadai</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="editGadaiForm" action="" method="POST">
          <input type="hidden" name="id_gadai" id="edit_id_gadai">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="edit_user_id">Nama Pelanggan:</label>
                <select name="user_id" id="edit_user_id" class="form-control" required>
                  <option value="">Pilih Pelanggan</option>
                  <?php
                  $userQuery1 = mysqli_query($conn, "SELECT nik, nama FROM pelanggan");
                   while ($users = mysqli_fetch_assoc($userQuery1)) { ?>
                    <option value="<?= $users['nik']; ?>"><?= htmlspecialchars($users['nama']); ?></option>
                  <?php } ?>
                </select>
              </div>
              <div class="form-group">
                <label for="edit_merek_hp">Merek HP:</label>
                <input type="text" name="merek_hp" id="edit_merek_hp" class="form-control" required>
              </div>
              <div class="form-group">
                <label for="edit_imei">IMEI HP:</label>
                <input type="text" name="imei" id="edit_imei" class="form-control" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="edit_keteranganhp">Keterangan HP:</label>
                <textarea name="keteranganhp" id="edit_keteranganhp" class="form-control" required></textarea>
              </div>
              <div class="form-group">
                <label for="edit_nilai_taksir">Nilai Taksir (Rp):</label>
                <input type="number" name="nilai_taksir" id="edit_nilai_taksir" class="form-control" required>
              </div>
              <div class="form-group">
                <label for="edit_pinjaman">Jumlah Pinjaman (Rp):</label>
                <input type="number" name="pinjaman" id="edit_pinjaman" class="form-control" required>
              </div>
              <div class="form-group">
                <label for="edit_bunga">Bunga (%):</label>
                <input type="number" name="bunga" id="edit_bunga" class="form-control" step="0.01" required>
              </div>
              <div class="form-group">
                <label for="edit_jatuh_tempo">Jatuh Tempo:</label>
                <input type="date" name="jatuh_tempo" id="edit_jatuh_tempo" class="form-control" required>
              </div>
            </div>
          </div>
          <button type="submit" class="btn btn-primary" name="edit_gadai">Simpan Perubahan</button>
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

<!-- Include SweetAlert CSS and JS -->


<script>
$(document).ready(function() {
    $('#userTable').DataTable();

    // Fill the edit form with data when the edit button is clicked
    $('.btn-edit').on('click', function() {
        var gadai = $(this).data('gadai');
        $('#edit_id_gadai').val(gadai.id);
        $('#edit_user_id').val(gadai.pelanggan_nik);
        $('#edit_merek_hp').val(gadai.nama_barang);
        $('#edit_imei').val(gadai.imei);
        $('#edit_keteranganhp').val(gadai.deskripsi);
        $('#edit_nilai_taksir').val(gadai.nilai_taksir);
        $('#edit_pinjaman').val(gadai.pinjaman);
        $('#edit_bunga').val(gadai.bunga);
        $('#edit_jatuh_tempo').val(gadai.jatuh_tempo);
        $('#editGadaiModal').modal('show');
    });
});
</script>




