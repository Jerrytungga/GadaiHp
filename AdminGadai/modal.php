<?php
include 'head.php';
include 'navbar.php';
include 'sidebar.php';

// Function to format numbers as Rupiah
function formatRupiah($number) {
    return 'Rp ' . number_format($number, 0, ',', '.');
}

// Proses form tambah modal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tanggal'], $_POST['keterangan'], $_POST['jumlah'])) {
    $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $jumlah = floatval($_POST['jumlah']); // Pastikan jumlah berupa angka

    // Validasi input
    if (!empty($tanggal) && !empty($keterangan) && $jumlah > 0) {
        $query = "INSERT INTO modal (tanggal, keterangan, jumlah) VALUES ('$tanggal', '$keterangan', '$jumlah')";
        if (mysqli_query($conn, $query)) {
            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'Data berhasil ditambahkan!'
                }).then(() => {
                    window.location.href = 'modal.php';
                });
            </script>";
        } else {
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: 'Gagal menambahkan data: " . mysqli_error($conn) . "'
                });
            </script>";
        }
    } else {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: 'Semua field harus diisi dan jumlah harus lebih dari 0!'
            });
        </script>";
    }
}

// Proses Edit
if (isset($_POST['update'])) {
    $id = intval($_POST['id']);
    $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $jumlah = floatval($_POST['jumlah']); // Pastikan jumlah berupa angka

    // Validasi input
    if (!empty($tanggal) && !empty($keterangan) && $jumlah > 0) {
        $query = "UPDATE modal SET tanggal = '$tanggal', keterangan = '$keterangan', jumlah = '$jumlah' WHERE id = $id";
        if (mysqli_query($conn, $query)) {
            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'Data berhasil diperbarui!'
                }).then(() => {
                    window.location.href = 'modal.php';
                });
            </script>";
        } else {
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: 'Gagal memperbarui data: " . mysqli_error($conn) . "'
                });
            </script>";
        }
    } else {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: 'Semua field harus diisi dan jumlah harus lebih dari 0!'
            });
        </script>";
    }
}

// Proses Hapus
if (isset($_POST['delete'])) {
    $id = intval($_POST['id']);
    $query = "DELETE FROM modal WHERE id = $id";

    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Data berhasil dihapus!'); window.location.href='modal.php';</script>";
    } else {
        echo "<script>alert('Gagal menghapus data: " . mysqli_error($conn) . "');</script>";
    }
}

// Fetch data from the database
$query = "SELECT * FROM modal ORDER BY tanggal DESC";
$result = mysqli_query($conn, $query);

// Hitung total jumlah modal
$totalQuery = "SELECT SUM(jumlah) AS total_modal FROM modal";
$totalResult = mysqli_query($conn, $totalQuery);
$totalRow = mysqli_fetch_assoc($totalResult);
$totalModal = $totalRow['total_modal'] ?? 0; // Jika null, set ke 0
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1>Modal Gadai HP</h1>
        </div>
      </div>
    </div><!-- /.container-fluid -->
  </section>

  <!-- Main content -->
  <section class="content">

    <!-- Form Tambah Modal -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Tambah Modal</h3>
      </div>
      <div class="card-body">
        <form action="" method="POST">
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label for="tanggal">Tanggal</label>
                <input type="date" class="form-control" id="tanggal" name="tanggal" required>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label for="keterangan">Keterangan</label>
                <input type="text" class="form-control" id="keterangan" name="keterangan" placeholder="Masukkan keterangan" required>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label for="jumlah">Jumlah</label>
                <input type="number" class="form-control" id="jumlah" name="jumlah" placeholder="Masukkan jumlah modal" required>
              </div>
            </div>
          </div>
          <button type="submit" class="btn btn-primary">Simpan</button>
        </form>
      </div>
    </div>
    <!-- /.Form Tambah Modal -->

    <!-- Tabel Data Modal -->
    <div class="card">
      <div class="card-body">
        <div style="overflow-x: auto;">
          <table id="userTable" class="table table-bordered table-striped">
            <thead>
              <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Keterangan</th>
                <th>Jumlah</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php
              if (mysqli_num_rows($result) > 0) {
                  $no = 1;
                  while ($row = mysqli_fetch_assoc($result)) {
                      echo "<tr>";
                      echo "<td>" . $no++ . "</td>";
                      echo "<td>" . htmlspecialchars($row['tanggal']) . "</td>";
                      echo "<td>" . htmlspecialchars($row['keterangan']) . "</td>";
                      echo "<td>" . formatRupiah($row['jumlah']) . "</td>";
                      echo "<td>
                              <button class='btn btn-warning btn-sm' data-toggle='modal' data-target='#editModal' 
                                      data-id='" . $row['id'] . "' 
                                      data-tanggal='" . $row['tanggal'] . "' 
                                      data-keterangan='" . htmlspecialchars($row['keterangan']) . "' 
                                      data-jumlah='" . $row['jumlah'] . "'>Edit</button>
                              <button class='btn btn-danger btn-sm' data-toggle='modal' data-target='#deleteModal' 
                                      data-id='" . $row['id'] . "'>Hapus</button>
                            </td>";
                      echo "</tr>";
                  }
              } else {
                  echo "<tr><td colspan='5' class='text-center'>Tidak ada data</td></tr>";
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>
      <!-- /.card-body -->
      <div class="card-footer">
        <h5>Total Modal: <?php echo formatRupiah($totalModal); ?></h5>
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

    // Isi data ke modal edit
    $('#editModal').on('show.bs.modal', function(event) {
      var button = $(event.relatedTarget);
      var id = button.data('id');
      var tanggal = button.data('tanggal');
      var keterangan = button.data('keterangan');
      var jumlah = button.data('jumlah');

      var modal = $(this);
      modal.find('#edit-id').val(id);
      modal.find('#edit-tanggal').val(tanggal);
      modal.find('#edit-keterangan').val(keterangan);
      modal.find('#edit-jumlah').val(jumlah);
    });

    // Isi data ke modal hapus
    $('#deleteModal').on('show.bs.modal', function(event) {
      var button = $(event.relatedTarget);
      var id = button.data('id');

      var modal = $(this);
      modal.find('#delete-id').val(id);
    });
  });
</script>

<!-- Modal Edit -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form action="modal.php" method="POST">
        <div class="modal-header">
          <h5 class="modal-title" id="editModalLabel">Edit Modal</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="edit-id">
          <div class="form-group">
            <label for="edit-tanggal">Tanggal</label>
            <input type="date" class="form-control" id="edit-tanggal" name="tanggal" required>
          </div>
          <div class="form-group">
            <label for="edit-keterangan">Keterangan</label>
            <input type="text" class="form-control" id="edit-keterangan" name="keterangan" required>
          </div>
          <div class="form-group">
            <label for="edit-jumlah">Jumlah</label>
            <input type="number" class="form-control" id="edit-jumlah" name="jumlah" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary" name="update">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Hapus -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form action="modal.php" method="POST">
        <div class="modal-header">
          <h5 class="modal-title" id="deleteModalLabel">Hapus Modal</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="delete-id">
          <p>Apakah Anda yakin ingin menghapus data ini?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger" name="delete">Hapus</button>
        </div>
      </form>
    </div>
  </div>
</div>




