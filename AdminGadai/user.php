<?php
include 'head.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  if (isset($_POST['edit_user'])) {
    $ktp = $_POST['ktp'];
    $nama = $_POST['nama'];
    $nomor_hp = $_POST['nomor_hp'];
    $alamat = $_POST['alamat'];
    $status = $_POST['status'];

    // Handle file uploads
    $foto_ktp = $_FILES['foto_ktp']['name'];
    $foto_diri = $_FILES['foto_diri']['name'];

    // Set the target directory for file uploads
    $target_dir = "uploads/";

    // Set the target file paths
    $target_file_ktp = $target_dir . basename($foto_ktp);
    $target_file_diri = $target_dir . basename($foto_diri);

    // Move the uploaded files to the target directory
    if ($foto_ktp) {
      move_uploaded_file($_FILES['foto_ktp']['tmp_name'], $target_file_ktp);
      $update_ktp = ", foto_ktp='$target_file_ktp'";
    } else {
      $update_ktp = "";
    }

    if ($foto_diri) {
      move_uploaded_file($_FILES['foto_diri']['tmp_name'], $target_file_diri);
      $update_diri = ", foto_diri='$target_file_diri'";
    } else {
      $update_diri = "";
    }

    // Update the data in the database
    $sql = "UPDATE pelanggan SET nik='$ktp', nama='$nama', nomor_hp='$nomor_hp', alamat='$alamat', status='$status' $update_ktp $update_diri WHERE nik='$ktp'";

    if (mysqli_query($conn, $sql)) {
      echo "<script>
        Swal.fire({
          icon: 'success',
          title: 'Berhasil',
          text: 'Data user berhasil diperbarui!'
        }).then(() => {
          window.location = 'user.php';
        });
      </script>";
    } else {
      echo "<script>
        Swal.fire({
          icon: 'error',
          title: 'Gagal',
          text: 'Terjadi kesalahan saat memperbarui data!'
        });
      </script>";
    }
    exit();
  } else {
    $ktp = $_POST['ktp'];
    $nama = $_POST['nama'];
    $nomor_hp = $_POST['nomor_hp'];
    $alamat = $_POST['alamat'];
    $status = $_POST['status'];

    // Handle file uploads
    $foto_ktp = $_FILES['foto_ktp']['name'];
    $foto_diri = $_FILES['foto_diri']['name'];

    // Set the target directory for file uploads
    $target_dir = "uploads/";

    // Set the target file paths
    $target_file_ktp = $target_dir . basename($foto_ktp);
    $target_file_diri = $target_dir . basename($foto_diri);

    // Move the uploaded files to the target directory
    move_uploaded_file($_FILES['foto_ktp']['tmp_name'], $target_file_ktp);
    move_uploaded_file($_FILES['foto_diri']['tmp_name'], $target_file_diri);

    // Insert the data into the database
    $sql = "INSERT INTO pelanggan (nik, foto_ktp, foto_diri, nama, nomor_hp, alamat, status) VALUES ('$ktp', '$target_file_ktp', '$target_file_diri', '$nama', '$nomor_hp', '$alamat', '$status')";

    if (mysqli_query($conn, $sql)) {
      echo "<script>
        Swal.fire({
          icon: 'success',
          title: 'Berhasil',
          text: 'Data user berhasil ditambahkan!'
        }).then(() => {
          window.location = 'user.php';
        });
      </script>";
    } else {
      echo "<script>
        Swal.fire({
          icon: 'error',
          title: 'Gagal',
          text: 'Terjadi kesalahan saat menambahkan data!'
        });
      </script>";
    }
    exit();
  }
}

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['hapus_nik'])) {
    $nik = $_GET['hapus_nik'];

    // Query untuk menghapus data berdasarkan NIK
    $sql = "DELETE FROM pelanggan WHERE nik = '$nik'";

    if (mysqli_query($conn, $sql)) {
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Data user berhasil dihapus!'
            }).then(() => {
                window.location = 'user.php';
            });
        </script>";
    } else {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: 'Terjadi kesalahan saat menghapus data!'
            }).then(() => {
                window.location = 'user.php';
            });
        </script>";
    }
}

include 'navbar.php';
include 'sidebar.php';
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1>User</h1>
        </div>
        <div class="col-sm-6">
          <button type="button" class="btn btn-primary float-right" data-toggle="modal" data-target="#addUserModal">
            Tambah User
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
        <h3 class="card-title">User List</h3>

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
        <table id="userTable" class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>No</th>
              <th>KTP</th>
              <th>Foto</th>
              <th>Nama</th>
              <th>Nomor HP</th>
              <th>Alamat</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
          <?php 
                $i = 1;
                $ambil_data = mysqli_query($conn, "SELECT * FROM pelanggan ");
                foreach ($ambil_data as $row) :
                ?>
            <tr>
            <td scope="row"><?= $i; ?></td>
            <td><?= $row['nik'];?></td>
            <td>
              <img src="<?= $row['foto_ktp'] ?>" class="mb-3 img-thumbnail" alt="Foto Nasabah" width="50" height="50" data-toggle="modal" data-target="#viewPhotoModal" data-photo="<?= $row['foto_ktp'] ?>">
              <img src="<?= $row['foto_diri'] ?>" class="mb-3 img-thumbnail" alt="Foto Nasabah" width="50" height="50" data-toggle="modal" data-target="#viewPhotoModal" data-photo="<?= $row['foto_diri'] ?>">
            </td>
            <td><?= $row['nama'];?></td>
            <td><?= $row['nomor_hp'];?></td>
            <td><?= $row['alamat'];?></td>
            <td><?= $row['status'];?></td>
            <td>
              <a href="rw_user.php?nik=<?= $row['nik'];?>" type="button" class="btn btn-info btn-sm">Lihat Riwayat</a>
              <button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editUserModal" data-ktp="<?= $row['nik']; ?>" data-nama="<?= $row['nama']; ?>" data-nomor_hp="<?= $row['nomor_hp']; ?>" data-alamat="<?= $row['alamat']; ?>" data-status="<?= $row['status']; ?>">Edit</button>
              <button type="button" class="btn btn-danger btn-sm" onclick="hapusUser(<?= $row['nik']; ?>)">Hapus</button>
            </td>
            </tr>
            <?php 
                $i++; 
                endforeach ;
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

<!-- Modal for viewing photo -->
<div class="modal fade" id="viewPhotoModal" tabindex="-1" role="dialog" aria-labelledby="viewPhotoModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewPhotoModalLabel">View Photo</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <img id="modalPhoto" src="" class="img-fluid" alt="Foto Nasabah">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal for adding user -->
<div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addUserModalLabel">Tambah User</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form action="user.php" method="POST" enctype="multipart/form-data">
          <div class="form-group">
            <label for="ktp">KTP</label>
            <input type="text" class="form-control" id="ktp" name="ktp" required>
          </div>
          <div class="form-group">
            <label for="foto_ktp">Foto KTP</label>
            <input type="file" class="form-control" id="foto_ktp" name="foto_ktp" required>
          </div>
          <div class="form-group">
            <label for="foto_diri">Foto Diri</label>
            <input type="file" class="form-control" id="foto_diri" name="foto_diri" required>
          </div>
          <div class="form-group">
            <label for="nama">Nama</label>
            <input type="text" class="form-control" id="nama" name="nama" required>
          </div>
          <div class="form-group">
            <label for="nomor_hp">Nomor HP</label>
            <input type="text" class="form-control" id="nomor_hp" name="nomor_hp" required>
          </div>
          <div class="form-group">
            <label for="alamat">Alamat</label>
            <input type="text" class="form-control" id="alamat" name="alamat" required>
          </div>
          <div class="form-group">
            <label for="status">Status</label>
            <select class="form-control" id="status" name="status" required>
              <option value="Aktif">Aktif</option>
              <option value="Tidak Aktif">Tidak Aktif</option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary">Tambah</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal for editing user -->
<div class="modal fade" id="editUserModal" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form action="user.php" method="POST" enctype="multipart/form-data">
          <input type="hidden" id="edit_id" name="id">
          <div class="form-group">
            <label for="edit_ktp">KTP</label>
            <input type="text" class="form-control" id="edit_ktp" name="ktp" required>
          </div>
          <div class="form-group">
            <label for="edit_foto_ktp">Foto KTP</label>
            <input type="file" class="form-control" id="edit_foto_ktp" name="foto_ktp">
          </div>
          <div class="form-group">
            <label for="edit_foto_diri">Foto Diri</label>
            <input type="file" class="form-control" id="edit_foto_diri" name="foto_diri">
          </div>
          <div class="form-group">
            <label for="edit_nama">Nama</label>
            <input type="text" class="form-control" id="edit_nama" name="nama" required>
          </div>
          <div class="form-group">
            <label for="edit_nomor_hp">Nomor HP</label>
            <input type="text" class="form-control" id="edit_nomor_hp" name="nomor_hp" required>
          </div>
          <div class="form-group">
            <label for="edit_alamat">Alamat</label>
            <input type="text" class="form-control" id="edit_alamat" name="alamat" required>
          </div>
          <div class="form-group">
            <label for="edit_status">Status</label>
            <select class="form-control" id="edit_status" name="status" required>
              <option value="Aktif">Aktif</option>
              <option value="Tidak Aktif">Tidak Aktif</option>
            </select>
          </div>
          <button type="submit" name="edit_user" class="btn btn-primary">Update</button>
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

    // Handle photo click to show in modal
    $('#viewPhotoModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var photo = button.data('photo');
        var modal = $(this);
        modal.find('#modalPhoto').attr('src', photo);
    });

    // Handle edit button click to show edit modal with pre-filled data
    $('#editUserModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var id = button.data('id');
        var ktp = button.data('ktp');
        var nama = button.data('nama');
        var nomor_hp = button.data('nomor_hp');
        var alamat = button.data('alamat');
        var status = button.data('status');
        var modal = $(this);
        modal.find('#edit_id').val(id);
        modal.find('#edit_ktp').val(ktp);
        modal.find('#edit_nama').val(nama);
        modal.find('#edit_nomor_hp').val(nomor_hp);
        modal.find('#edit_alamat').val(alamat);
        modal.find('#edit_status').val(status);
    });
});

function hapusUser(id) {
    // Implement the logic to delete the user
    if (confirm('Apakah Anda yakin ingin menghapus user ini?')) {
        window.location.href = 'user.php?hapus_nik=' + id;
    }
}
</script>
