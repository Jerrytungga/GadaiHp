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
    SELECT SUM(pinjaman * (bunga / 100)) AS total 
    FROM barang_gadai 
    WHERE status = 'ditebus'
"))['total'] ?? 0;

// Query untuk grafik barang digadai per bulan
$gadaiPerBulan = mysqli_query($conn, "
    SELECT MONTHNAME(jatuh_tempo) AS bulan, COUNT(*) AS total 
    FROM barang_gadai 
    WHERE YEAR(jatuh_tempo) = YEAR(CURDATE()) 
    GROUP BY MONTH(jatuh_tempo), MONTHNAME(jatuh_tempo)
");

// Query untuk aktivitas terbaru
$aktivitasTerbaru = mysqli_query($conn, "
    SELECT transaksi.*, pelanggan.nama AS nama_pelanggan, barang_gadai.nama_barang 
    FROM transaksi 
    JOIN pelanggan ON transaksi.pelanggan_nik = pelanggan.nik 
    JOIN barang_gadai ON transaksi.barang_id = barang_gadai.id 
    ORDER BY transaksi.tanggal_bayar DESC 
    LIMIT 5
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

        <!-- Aktivitas Terbaru -->
        <div class="col-lg-4">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Aktivitas Terbaru</h3>
            </div>
            <div class="card-body">
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