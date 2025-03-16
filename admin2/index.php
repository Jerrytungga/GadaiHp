<?php include 'header.php'; ?>
<div class="az-content az-content-dashboard">
  <div class="container">
    <div class="az-content-body">
      <?php include 'ds.php'; ?>

      <div class="row row-sm mg-b-20">
        <div class="col-lg-7 ht-lg-100p">
          <h2>Data Nasabah</h2>
          <div class="table-responsive">
            <table id="example" class="display table table-striped table-bordered table-hover" style="width:100%">
              <thead>
                <tr>
                  <th scope="col">No</th>
                  <th scope="col">Ktp</th>
                  <th scope="col">Nama</th>
                  <th scope="col">Barang</th>
                  <th scope="col">Tanggal Pinjaman</th>
                  <th scope="col">Pengaturan</th>
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
                  <td><?= $row['merek_tipe_hp'];?></td>
                  <td><?= $row['tanggal_pinjaman'];?></td>
                  <td>
                    <a href="view.php?id=<?= $row['id_form']; ?>" class="btn btn-primary btn-sm mb-2">Detail</a>
                    <button class="btn btn-success btn-sm selesai-btn" data-idform="<?= $row['id_form']; ?>">Gadai Selesai</button>
                  </td>
                </tr>
                <?php 
                $i++; 
                endforeach ;
                ?>
              </tbody>
            </table>
          </div>
        </div><!-- col -->
        <div class="col-lg-5 mg-t-20 mg-lg-t-0">
          <div class="row row-sm">
         
            <div class="col-sm-12 mg-t-50">
              <div class="card card-dashboard-three">
                <div class="card-header">
                  <p>Profile</p>
                  <hr>
                  <img src="" alt="" sizes="" srcset="">
                  <h6>16,869 <small class="tx-success"><i class="icon ion-md-arrow-up"></i> 2.87%</small></h6>
                  <small>The total number of sessions within the date range. It is the period time a user is actively engaged with your website, page or app, etc.</small>
                </div><!-- card-header -->
                <div class="card-body">
                  <div class="chart"><canvas id="chartBar5"></canvas></div>
                </div>
              </div>
            </div>
          </div><!-- row -->
        </div><!--col -->
      </div><!-- row -->
    </div><!-- az-content-body -->
  </div>
</div><!-- az-content -->



<!-- Modal Konfirmasi Gadai Selesai -->
<div class="modal fade" id="selesaiModal" tabindex="-1" aria-labelledby="selesaiModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="selesaiModalLabel">Konfirmasi Gadai Selesai</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Apakah Anda yakin ingin menandai gadai ini sebagai selesai?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-success" id="confirmSelesaiBtn">Ya, Selesai</button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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

    // Handle selesai button click
    $('.selesai-btn').on('click', function() {
      var id = $(this).data('idform');
      $('#confirmSelesaiBtn').data('idform', id);
      $('#selesaiModal').modal('show');
    });

    // Handle confirm selesai button click
    $('#confirmSelesaiBtn').on('click', function() {
      var id = $(this).data('idform');
      $.ajax({
        url: 'selesai.php', // URL to handle selesai action
        type: 'POST',
        data: { id: id },
        success: function(response) {
          if (response == 'success') {
            alert('Gadai berhasil ditandai sebagai selesai.');
            location.reload();
          } else {
            alert('Terjadi kesalahan saat menandai gadai sebagai selesai.');
          }
        },
        error: function() {
          alert('Terjadi kesalahan saat mengirim permintaan.');
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
<?php include 'footer.php'; ?>