<?php
include 'head.php';
include 'navbar.php';
include 'sidebar.php';

// Query untuk mendapatkan data statistik
$totalPelanggan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM pelanggan"))['total'];
$totalBarangGadai = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM barang_gadai WHERE status != 'ditebus'"))['total'];
$totalPinjaman = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(pinjaman) AS total FROM barang_gadai WHERE status != 'ditebus'"))['total'] ?? 0;
$totalBarangTebus = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM barang_gadai WHERE status = 'ditebus'"))['total'];

// Query untuk menghitung total keuntungan dari bunga
$totalKeuntungan = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT SUM(bunga) AS total 
    FROM barang_gadai 
    WHERE status = 'ditebus'
"))['total'] ?? 0;

// Barang yang akan jatuh tempo dalam 7 hari ke depan
$barangJatuhTempo = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM barang_gadai 
    WHERE status != 'ditebus' AND jatuh_tempo BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
"))['total'];

// Barang yang sudah lewat jatuh tempo
$barangLewatTempo = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM barang_gadai 
    WHERE status != 'ditebus' AND jatuh_tempo < CURDATE()
"))['total'];

// Query untuk grafik barang digadai per bulan
$gadaiPerBulan = mysqli_query($conn, "
    SELECT MONTHNAME(jatuh_tempo) AS bulan, COUNT(*) AS total 
    FROM barang_gadai 
    WHERE YEAR(jatuh_tempo) = YEAR(CURDATE()) 
    GROUP BY MONTH(jatuh_tempo), MONTHNAME(jatuh_tempo)
");

// Query untuk total keuntungan per bulan
$keuntunganPerBulan = mysqli_query($conn, "
    SELECT MONTHNAME(tanggal_bayar) AS bulan, SUM(jumlah_bayar) AS total 
    FROM transaksi 
    WHERE keterangan = 'lunas' AND YEAR(tanggal_bayar) = YEAR(CURDATE())
    GROUP BY MONTH(tanggal_bayar), MONTHNAME(tanggal_bayar)
");

// Ambil input pencarian dari form
$search = mysqli_real_escape_string($conn, $_GET['search'] ?? '');

// Query untuk aktivitas terbaru dengan pencarian
$aktivitasTerbaruQuery = "
    SELECT transaksi.*, pelanggan.nama AS nama_pelanggan, barang_gadai.nama_barang 
    FROM transaksi 
    JOIN pelanggan ON transaksi.pelanggan_nik = pelanggan.nik 
    JOIN barang_gadai ON transaksi.barang_id = barang_gadai.id 
    WHERE pelanggan.nama LIKE '%$search%' 
       OR barang_gadai.nama_barang LIKE '%$search%' 
       OR transaksi.tanggal_bayar LIKE '%$search%'
    ORDER BY transaksi.tanggal_bayar DESC 
    LIMIT 5
";

$aktivitasTerbaru = mysqli_query($conn, $aktivitasTerbaruQuery);

// Query untuk mendapatkan daftar barang yang akan jatuh tempo dalam 7 hari ke depan
$barangJatuhTempoList = mysqli_query($conn, "
    SELECT barang_gadai.nama_barang, pelanggan.nama AS nama_pelanggan, barang_gadai.jatuh_tempo 
    FROM barang_gadai 
    JOIN pelanggan ON barang_gadai.pelanggan_nik = pelanggan.nik 
    WHERE barang_gadai.status != 'ditebus' AND barang_gadai.jatuh_tempo BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
");
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1>Dashboard</h1>
        </div>
      </div>
    </div><!-- /.container-fluid -->
  </section>

  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">
      <!-- Statistik Utama -->
      <div class="row">
        <div class="col-lg-3 col-6">
          <div class="small-box bg-info">
            <div class="inner">
              <h3><?= $totalPelanggan; ?></h3>
              <p>Total Pelanggan</p>
            </div>
            <div class="icon">
              <i class="fas fa-users"></i>
            </div>
            <a href="user.php" class="small-box-footer">Lihat Detail <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>
        <div class="col-lg-3 col-6">
          <div class="small-box bg-success">
            <div class="inner">
              <h3><?= $totalBarangGadai; ?></h3>
              <p>Barang yang Digadai</p>
            </div>
            <div class="icon">
              <i class="fas fa-box"></i>
            </div>
            <a href="vg.php" class="small-box-footer">Lihat Detail <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>
        <div class="col-lg-3 col-6">
          <div class="small-box bg-warning">
            <div class="inner">
              <h3>Rp <?= number_format($totalPinjaman, 0, ',', '.'); ?></h3>
              <p>Total Pinjaman</p>
            </div>
            <div class="icon">
              <i class="fas fa-money-bill-wave"></i>
            </div>
            <a href="vg.php" class="small-box-footer">Lihat Detail <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>
        <div class="col-lg-3 col-6">
          <div class="small-box bg-danger">
            <div class="inner">
              <h3><?= $totalBarangTebus; ?></h3>
              <p>Barang yang Ditebus</p>
            </div>
            <div class="icon">
              <i class="fas fa-check-circle"></i>
            </div>
            <a href="vg.php" class="small-box-footer">Lihat Detail <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>
        <div class="col-lg-3 col-6">
          <div class="small-box bg-primary">
            <div class="inner">
              <h3>Rp <?= number_format($totalKeuntungan, 0, ',', '.'); ?></h3>
              <p>Total Keuntungan (Bunga)</p>
            </div>
            <div class="icon">
              <i class="fas fa-coins"></i>
            </div>
            <a href="vg.php" class="small-box-footer">Lihat Detail <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>
        <div class="col-lg-3 col-6">
          <div class="small-box bg-warning">
            <div class="inner">
              <h3><?= $barangJatuhTempo; ?></h3>
              <p>Barang Akan Jatuh Tempo</p>
            </div>
            <div class="icon">
              <i class="fas fa-clock"></i>
            </div>
            <a href="jatuh_tempo.php" class="small-box-footer">Lihat Detail <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>
        <div class="col-lg-3 col-6">
          <div class="small-box bg-danger">
            <div class="inner">
              <h3><?= $barangLewatTempo; ?></h3>
              <p>Barang Lewat Jatuh Tempo</p>
            </div>
            <div class="icon">
              <i class="fas fa-exclamation-triangle"></i>
            </div>
            <a href="lewat_tempo.php" class="small-box-footer">Lihat Detail <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>
      </div>

      <!-- Grafik dan Aktivitas Terbaru -->
      <div class="row">
        <!-- Grafik -->
        <div class="col-lg-8">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Grafik Barang Digadai per Bulan</h3>
            </div>
            <div class="card-body">
              <canvas id="gadaiChart"></canvas>
            </div>
          </div>
        </div>

        <!-- Grafik Pie -->
        <div class="col-lg-4">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Status Barang</h3>
            </div>
            <div class="card-body">
              <canvas id="statusBarangChart"></canvas>
            </div>
          </div>
        </div>

        <!-- Grafik Total Keuntungan per Bulan -->
        <div class="col-lg-8">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Grafik Total Keuntungan per Bulan</h3>
            </div>
            <div class="card-body">
              <canvas id="keuntunganChart"></canvas>
            </div>
          </div>
        </div>

        <!-- Aktivitas Terbaru -->
        <div class="col-lg-4">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Aktivitas Terbaru</h3>
            </div>
            <div class="card-body">
              <form method="GET" action="">
                <div class="input-group mb-3">
                  <input type="text" name="search" class="form-control" placeholder="Cari aktivitas..." value="<?= htmlspecialchars($_GET['search'] ?? ''); ?>">
                  <button class="btn btn-primary" type="submit">Cari</button>
                </div>
              </form>
              <ul class="list-group">
                <?php while ($aktivitas = mysqli_fetch_assoc($aktivitasTerbaru)) { ?>
                  <li class="list-group-item">
                    <strong><?= htmlspecialchars($aktivitas['nama_pelanggan']); ?></strong> membayar <strong><?= htmlspecialchars($aktivitas['nama_barang']); ?></strong>
                    <br>
                    <small><?= htmlspecialchars($aktivitas['tanggal_bayar']); ?></small>
                  </li>
                <?php } ?>
              </ul>
            </div>
          </div>
        </div>

        <!-- Barang Akan Jatuh Tempo -->
        <div class="col-lg-4">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Barang Akan Jatuh Tempo</h3>
            </div>
            <div class="card-body">
              <ul class="list-group">
                <?php while ($barang = mysqli_fetch_assoc($barangJatuhTempoList)) { ?>
                  <li class="list-group-item">
                    <strong><?= htmlspecialchars($barang['nama_barang']); ?></strong> milik <strong><?= htmlspecialchars($barang['nama_pelanggan']); ?></strong>
                    <br>
                    <small>Jatuh Tempo: <?= htmlspecialchars($barang['jatuh_tempo']); ?></small>
                  </li>
                <?php } ?>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div><!-- /.container-fluid -->
  </section>
  <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<?php
include 'script.php';
?>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Data untuk grafik
    const gadaiData = {
        labels: [
            <?php while ($row = mysqli_fetch_assoc($gadaiPerBulan)) {
                echo "'" . $row['bulan'] . "',";
            } ?>
        ],
        datasets: [{
            label: 'Barang Digadai',
            data: [
                <?php
                mysqli_data_seek($gadaiPerBulan, 0); // Reset pointer query
                while ($row = mysqli_fetch_assoc($gadaiPerBulan)) {
                    echo $row['total'] . ",";
                }
                ?>
            ],
            backgroundColor: 'rgba(54, 162, 235, 0.5)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    };

    // Konfigurasi grafik
    const config = {
        type: 'bar',
        data: gadaiData,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    };

    // Render grafik
    const gadaiChart = new Chart(
        document.getElementById('gadaiChart'),
        config
    );
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Data untuk grafik pie
    const statusBarangData = {
        labels: ['Digadai', 'Ditebus'],
        datasets: [{
            data: [<?= $totalBarangGadai; ?>, <?= $totalBarangTebus; ?>],
            backgroundColor: ['rgba(255, 99, 132, 0.5)', 'rgba(75, 192, 192, 0.5)'],
            borderColor: ['rgba(255, 99, 132, 1)', 'rgba(75, 192, 192, 1)'],
            borderWidth: 1
        }]
    };

    // Konfigurasi grafik pie
    const pieConfig = {
        type: 'pie',
        data: statusBarangData,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                }
            }
        }
    };

    // Render grafik pie
    const statusBarangChart = new Chart(
        document.getElementById('statusBarangChart'),
        pieConfig
    );
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Data untuk grafik keuntungan per bulan
    const keuntunganData = {
        labels: [
            <?php while ($row = mysqli_fetch_assoc($keuntunganPerBulan)) {
                echo "'" . $row['bulan'] . "',";
            } ?>
        ],
        datasets: [{
            label: 'Total Keuntungan',
            data: [
                <?php
                mysqli_data_seek($keuntunganPerBulan, 0); // Reset pointer query
                while ($row = mysqli_fetch_assoc($keuntunganPerBulan)) {
                    echo $row['total'] . ",";
                }
                ?>
            ],
            backgroundColor: 'rgba(255, 206, 86, 0.5)',
            borderColor: 'rgba(255, 206, 86, 1)',
            borderWidth: 1
        }]
    };

    // Konfigurasi grafik
    const keuntunganConfig = {
        type: 'line',
        data: keuntunganData,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            }
        }
    };

    // Render grafik
    const keuntunganChart = new Chart(
        document.getElementById('keuntunganChart'),
        keuntunganConfig
    );
});
</script>