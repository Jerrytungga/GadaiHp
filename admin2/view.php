<?php 
include 'header.php'; 
include '../database.php';

$id = $_GET['id'];
$query = mysqli_query($conn, "SELECT * FROM From_gadai WHERE id_form='$id'");
$data = mysqli_fetch_array($query);

function formatRupiah($number) {
    return 'Rp ' . number_format($number, 0, ',', '.');
}
?>


<div class="az-content az-content-dashboard">
  <div class="container">
    <div class="az-content-body">
      <?php include 'ds.php'; ?>

      <div class="row row-sm mg-b-20">
        <div class="col-lg-12 ht-lg-100p">
          <h2>Detail Nasabah</h2>
          <div class="row">
            <div class="col-lg-6">
              <div class="card mt-4">
                <div class="card-body text-center">
                  <img src="<?= htmlspecialchars($data['foto_nasabah']) ?>" class="rounded-circle mb-3 img-thumbnail" alt="Foto Nasabah" width="150" height="150" onclick="showImageModal(this)">
                  <h5 class="card-title"><?= htmlspecialchars($data['nama']) ?></h5>
                  <p class="card-text"><strong>Alamat:</strong> <?= htmlspecialchars($data['alamat']) ?></p>
                  <p class="card-text"><strong>No. Telepon:</strong> <?= htmlspecialchars($data['no_hp']) ?></p>
                  <p class="card-text">Foto KTP: <br>
                    <img src="<?= htmlspecialchars($data['foto_ktp']) ?>" class="rounded float-start img-thumbnail" alt="Foto KTP" width="100" height="100" onclick="showImageModal(this)">
                  </p>
                </div>
              </div>
            </div>
            <div class="col-lg-12">
              <div class="card mt-4">
                <div class="card-body">
                  <h5 class="card-title">Data HP yang Digadaikan</h5>
                  <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
  <table class="table table-striped table-bordered table-hover">
    <thead>
      <tr>
        <th scope="col">No</th>
        <th scope="col">Merek & Tipe HP</th>
        <th scope="col">Nomor IMEI</th>
        <th scope="col">Kondisi HP</th>
        <th scope="col">Akses Akun</th>
        <th scope="col">Kelengkapan HP</th>
        <th scope="col">Jumlah Pinjaman</th>
        <th scope="col">Bunga</th>
        <th scope="col">Biaya Administrasi</th>
        <th scope="col">Biaya Asuransi</th>
        <th scope="col">Total Bulanan</th>
        <th class="bg-danger text-light" scope="col">Total Yang Ditebus</th>
        <th scope="col">Status</th>
        <th scope="col">PB (pembayaran bulan k1)</th>
        <th scope="col">PB (pembayaran bulan k2)</th>
        <th scope="col">PB (pembayaran bulan k3)</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>1</td>
        <td><?= htmlspecialchars($data['merek_tipe_hp']) ?> <br> 
          <button type="button" class="btn btn-info btn-sm mt-2" data-toggle="modal" data-target="#lihatfotohp">
            Lihat Foto HP
          </button>
        </td>
        <td><?= htmlspecialchars($data['imei_hp']) ?></td>
        <td><?= htmlspecialchars($data['kondisi_hp']) ?></td>
        <td><?= htmlspecialchars($data['akun_hp']) ?></td>
        <td><?= htmlspecialchars($data['kelengkapan_hp']) ?></td>
        <td><?= formatRupiah($data['jumlah_pinjaman']) ?></td>
        <td><?= formatRupiah($data['bunga']) ?></td>
        <td><?= formatRupiah($data['administrasi']) ?></td>
        <td><?= formatRupiah($data['asuransi']) ?></td>
        <td><?= formatRupiah($data['asuransi'] + $data['administrasi'] + $data['bunga']) ?></td>
        <td><?= formatRupiah($data['asuransi'] + $data['administrasi'] + $data['bunga'] + $data['jumlah_pinjaman']) ?></td>
        <td></td>
      </tr>
    </tbody>
  </table>
</div>



<!-- Modal for showing photos -->
<div class="modal fade" id="lihatfotohp" tabindex="-1" aria-labelledby="lihatfotohpLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="lihatfotohpLabel">Foto-Foto HP</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-4">
            <img src="<?= $data['foto_depan_hp'] ?>" class="img-fluid mb-2 img-thumbnail" alt="Foto Depan HP" onclick="showImageModal(this)">
          </div>
          <div class="col-md-4">
            <img src="<?= $data['foto_belakang_hp'] ?>" class="img-fluid mb-2 img-thumbnail" alt="Foto Belakang HP" onclick="showImageModal(this)">
          </div>
          <div class="col-md-4">
            <img src="<?= $data['foto_samping_kanan_hp'] ?>" class="img-fluid mb-2 img-thumbnail" alt="Foto Samping Kanan HP" onclick="showImageModal(this)">
          </div>
          <div class="col-md-4">
            <img src="<?= $data['foto_samping_kiri_hp'] ?>" class="img-fluid mb-2 img-thumbnail" alt="Foto Samping Kiri HP" onclick="showImageModal(this)">
          </div>
          <div class="col-md-4">
            <img src="<?= $data['foto_atas_hp'] ?>" class="img-fluid mb-2 img-thumbnail" alt="Foto Atas HP" onclick="showImageModal(this)">
          </div>
          <div class="col-md-4">
            <img src="<?= $data['foto_bawa_hp'] ?>" class="img-fluid mb-2 img-thumbnail" alt="Foto Bawa HP" onclick="showImageModal(this)">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary">Save changes</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal for image preview -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="imageModalLabel">Image Preview</h5>
        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body text-center">
        <img id="modalImage" class="img-fluid" src="" alt="Image Preview">
      </div>
    </div>
  </div>
</div>


<!-- JavaScript for handling image modal -->
<script>
  function showImageModal(img) {
    var modalImage = document.getElementById('modalImage');
    modalImage.src = img.src;

    var imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
    imageModal.show();
  }
</script>


<!-- Include Bootstrap JavaScript files -->

</div>
</div>
</div>
</div>
</div>
<a href="index.php" class="btn btn-secondary mt-3">Kembali</a>
</div><!-- col -->
</div><!-- row -->
</div><!-- az-content-body -->
</div>
</div><!-- az-content -->


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/fixedheader/3.1.9/js/dataTables.fixedHeader.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
  $(document).ready(function() {
    var table = $('#example').DataTable({
      "scrollX": true, // Enables horizontal scrolling
      "scrollY": "600px", // Enables vertical scrolling with a fixed height
      "paging": true, // Enable pagination
      "searching": true, // Enable search
      "info": true, // Enable information display (e.g., "Showing 1 to 10 of 100 entries")
      "ordering": true, // Enable column ordering
      fixedHeader: true // Enable fixed header
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

  function showImageModal(img) {
    $('#imageModal').modal('show');
  }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<?php include 'footer.php'; ?>