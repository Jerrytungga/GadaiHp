<?php
include 'head.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['edit_user'])) {
        $id = $_POST['id'];
        $ktp = $_POST['ktp'];
        $nama = $_POST['nama'];
        $nomor_hp = $_POST['nomor_hp'];
        $alamat = $_POST['alamat'];
        $status = $_POST['status'];

        // Gunakan prepared statement untuk update
        $stmt = $conn->prepare("UPDATE pelanggan SET nik=?, nama=?, nomor_hp=?, alamat=?, status=? WHERE id=?");
        $stmt->bind_param("sssssi", $ktp, $nama, $nomor_hp, $alamat, $status, $id);

        if ($stmt->execute()) {
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
        $stmt->close();
        exit();
    } else {
        $ktp = $_POST['ktp'];
        $nama = $_POST['nama'];
        $nomor_hp = $_POST['nomor_hp'];
        $alamat = $_POST['alamat'];
        $status = $_POST['status'];

        // Gunakan prepared statement untuk insert
        $stmt = $conn->prepare("INSERT INTO pelanggan (nik, nama, nomor_hp, alamat, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $ktp, $nama, $nomor_hp, $alamat, $status);

        if ($stmt->execute()) {
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
        $stmt->close();
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['hapus_nik'])) {
    $id = $_GET['hapus_nik'];

    // Gunakan prepared statement untuk delete
    $stmt = $conn->prepare("DELETE FROM pelanggan WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
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
    $stmt->close();
}

include 'navbar.php';
include 'sidebar.php';
?>

<!-- Content Wrapper -->
<div class="content-wrapper">
  <!-- Content Header -->
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
    </div>
  </section>

  <!-- Main Content -->
  <section class="content">
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
        <div class="table-responsive">
          <table id="userTable" class="table table-bordered table-striped">
            <thead>
              <tr>
                <th>No</th>
                <th>KTP</th>
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
              $ambil_data = mysqli_query($conn, "SELECT * FROM pelanggan");
              foreach ($ambil_data as $row) : ?>
              <tr>
                <td><?= $i; ?></td>
                <td><?= $row['nik']; ?></td>
                <td><?= $row['nama']; ?></td>
                <td><?= $row['nomor_hp']; ?></td>
                <td><?= $row['alamat']; ?></td>
                <td><?= $row['status']; ?></td>
                <td>
                  <a href="rw_user.php?nik=<?= $row['nik']; ?>" class="btn btn-info btn-sm">Lihat Riwayat</a>
                  <button type="button" class="btn btn-warning btn-sm" 
                          data-toggle="modal" 
                          data-target="#editUserModal" 
                          data-id="<?= $row['id']; ?>" 
                          data-nik="<?= $row['nik']; ?>" 
                          data-nama="<?= $row['nama']; ?>" 
                          data-nomor_hp="<?= $row['nomor_hp']; ?>" 
                          data-alamat="<?= $row['alamat']; ?>" 
                          data-status="<?= $row['status']; ?>">
                    Edit
                  </button>
                  <button type="button" class="btn btn-danger btn-sm" onclick="hapusUser(<?= $row['id']; ?>)">Hapus</button>
                </td>
              </tr>
              <?php $i++; endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer">Footer</div>
    </div>
  </section>
</div>

<!-- Modal for Adding User -->
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
        <form action="user.php" method="POST">
          <div class="form-group">
            <label for="ktp">KTP</label>
            <input type="text" class="form-control" id="ktp" name="ktp" required>
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

<!-- Modal for Editing User -->
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
        <form action="user.php" method="POST">
          <input type="hidden" id="edit_id" name="id">
          <div class="form-group">
            <label for="edit_nik">KTP</label>
            <input type="text" class="form-control" id="edit_nik" name="ktp" required>
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

<?php include 'script.php'; ?>

<!-- Include DataTables CSS and JS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>

<script>
$(document).ready(function() {
    $('#userTable').DataTable({
        responsive: true
    });

    // Handle edit button click to show edit modal with pre-filled data
    $('#editUserModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var id = button.data('id');
        var nik = button.data('nik');
        var nama = button.data('nama');
        var nomor_hp = button.data('nomor_hp');
        var alamat = button.data('alamat');
        var status = button.data('status');

        var modal = $(this);
        modal.find('#edit_id').val(id);
        modal.find('#edit_nik').val(nik);
        modal.find('#edit_nama').val(nama);
        modal.find('#edit_nomor_hp').val(nomor_hp);
        modal.find('#edit_alamat').val(alamat);
        modal.find('#edit_status').val(status);
    });
});

function hapusUser(id) {
    if (confirm('Apakah Anda yakin ingin menghapus user ini?')) {
        window.location.href = 'user.php?hapus_nik=' + id;
    }
}
</script>
