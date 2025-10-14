<?php
include 'head.php';

// Tambahkan field foto_diri pada proses insert dan edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle upload foto
    $foto_diri = '';
    if (isset($_FILES['foto_diri']) && $_FILES['foto_diri']['error'] == 0) {
        $target_dir = "uploads/foto_diri/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_name = time() . '_' . basename($_FILES["foto_diri"]["name"]);
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES["foto_diri"]["tmp_name"], $target_file)) {
            $foto_diri = $file_name;
        }
    }

    if (isset($_POST['edit_user'])) {
        $id = $_POST['id'];
        $ktp = $_POST['ktp'];
        $nama = $_POST['nama'];
        $nomor_hp = $_POST['nomor_hp'];
        $alamat = $_POST['alamat'];
        $status = $_POST['status'];

        // Jika ada foto baru, update juga foto_diri
        if ($foto_diri != '') {
            $stmt = $conn->prepare("UPDATE pelanggan SET nik=?, nama=?, nomor_hp=?, alamat=?, status=?, foto_diri=? WHERE id=?");
            $stmt->bind_param("ssssssi", $ktp, $nama, $nomor_hp, $alamat, $status, $foto_diri, $id);
        } else {
            $stmt = $conn->prepare("UPDATE pelanggan SET nik=?, nama=?, nomor_hp=?, alamat=?, status=? WHERE id=?");
            $stmt->bind_param("sssssi", $ktp, $nama, $nomor_hp, $alamat, $status, $id);
        }

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

        $stmt = $conn->prepare("INSERT INTO pelanggan (nik, nama, nomor_hp, alamat, status, foto_diri) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $ktp, $nama, $nomor_hp, $alamat, $status, $foto_diri);

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
  // Ambil NIK user yang akan dihapus
  $stmtNik = $conn->prepare("SELECT nik FROM pelanggan WHERE id = ?");
  $stmtNik->bind_param("i", $id);
  $nik = '';
  if ($stmtNik->execute()) {
    $resultNik = $stmtNik->get_result();
    if ($rowNik = $resultNik->fetch_assoc()) {
      $nik = $rowNik['nik'];
    }
  }
  $stmtNik->close();

  // Hapus semua transaksi yang berhubungan dengan user
  if ($nik != '') {
    $stmtTrans = $conn->prepare("DELETE FROM transaksi WHERE pelanggan_nik = ?");
    $stmtTrans->bind_param("s", $nik);
    $stmtTrans->execute();
    $stmtTrans->close();

    // Hapus semua barang_gadai yang berhubungan dengan user
    $stmtBarang = $conn->prepare("DELETE FROM barang_gadai WHERE pelanggan_nik = ?");
    $stmtBarang->bind_param("s", $nik);
    $stmtBarang->execute();
    $stmtBarang->close();
  }

  // Hapus user
  $stmt = $conn->prepare("DELETE FROM pelanggan WHERE id = ?");
  $stmt->bind_param("i", $id);

  if ($stmt->execute()) {
    echo "<script>
      Swal.fire({
        icon: 'success',
        title: 'Berhasil',
        text: 'Data user dan semua data terkait berhasil dihapus!'
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
          <table id="userTable" class="table table-bordered table-striped align-middle">
            <thead class="table-dark">
              <tr class="text-center">
                <th>No</th>
                <th>KTP</th>
                <th>Nama</th>
                <th>Nomor HP</th>
                <th>Alamat</th>
                <th>Status</th>
                <th>Foto Diri</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              $i = 1;
              $ambil_data = mysqli_query($conn, "SELECT * FROM pelanggan");
              foreach ($ambil_data as $row) : ?>
              <tr class="text-center">
                <td><?= $i; ?></td>
                <td><?= htmlspecialchars($row['nik']); ?></td>
                <td><?= htmlspecialchars($row['nama']); ?></td>
                <td><?= htmlspecialchars($row['nomor_hp']); ?></td>
                <td><?= htmlspecialchars($row['alamat']); ?></td>
                <td>
                  <?php if ($row['status'] == 'Aktif'): ?>
                    <span class="badge bg-success">Aktif</span>
                  <?php else: ?>
                    <span class="badge bg-secondary">Tidak Aktif</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (!empty($row['foto_diri'])): ?>
                    <a href="#" data-toggle="modal" data-target="#fotoModal<?= $row['id']; ?>">
                      <img src="uploads/foto_diri/<?= htmlspecialchars($row['foto_diri']); ?>" alt="Foto Diri" width="50" height="50" class="rounded-circle border" style="object-fit:cover;">
                    </a>
                    <!-- Modal Foto Diri -->
                    <div class="modal fade" id="fotoModal<?= $row['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="fotoModalLabel<?= $row['id']; ?>" aria-hidden="true">
                      <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title" id="fotoModalLabel<?= $row['id']; ?>">Foto Diri - <?= htmlspecialchars($row['nama']); ?></h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                              <span aria-hidden="true">&times;</span>
                            </button>
                          </div>
                          <div class="modal-body text-center">
                            <img src="uploads/foto_diri/<?= htmlspecialchars($row['foto_diri']); ?>" alt="Foto Diri" class="img-fluid rounded shadow">
                          </div>
                        </div>
                      </div>
                    </div>
                  <?php else: ?>
                    <span class="text-muted">Belum Ada</span>
                  <?php endif; ?>
                </td>
                <td>
                  <a href="rw_user.php?nik=<?= urlencode($row['nik']); ?>" class="btn btn-info btn-sm mb-1">
                    <i class="fas fa-history"></i> Riwayat
                  </a>
                  <button type="button" class="btn btn-warning btn-sm mb-1" 
                          data-toggle="modal" 
                          data-target="#editUserModal" 
                          data-id="<?= $row['id']; ?>" 
                          data-nik="<?= htmlspecialchars($row['nik']); ?>" 
                          data-nama="<?= htmlspecialchars($row['nama']); ?>" 
                          data-nomor_hp="<?= htmlspecialchars($row['nomor_hp']); ?>" 
                          data-alamat="<?= htmlspecialchars($row['alamat']); ?>" 
                          data-status="<?= htmlspecialchars($row['status']); ?>">
                    <i class="fas fa-edit"></i> Edit
                  </button>
                  <button type="button" class="btn btn-danger btn-sm mb-1" onclick="hapusUser(<?= $row['id']; ?>)">
                    <i class="fas fa-trash"></i> Hapus
                  </button>
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
        <form action="user.php" method="POST" enctype="multipart/form-data">
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
          <div class="form-group">
            <label for="foto_diri">Foto Diri</label>
            <input type="file" class="form-control" id="foto_diri" name="foto_diri" accept="image/*">
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
        <form action="user.php" method="POST" enctype="multipart/form-data">
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
          <div class="form-group">
            <label for="edit_foto_diri">Foto Diri (Kosongkan jika tidak ingin mengubah)</label>
            <input type="file" class="form-control" id="edit_foto_diri" name="foto_diri" accept="image/*">
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
