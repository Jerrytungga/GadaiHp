<?php
include '../database.php';
session_start();
if (!isset($_SESSION['id'])) {
  echo "<script type='text/javascript'>
  alert('Anda harus login terlebih dahulu!');
  window.location = '../index.php'
</script>";
} else {
  $id = $_SESSION['id'];
  $get_data = mysqli_query($conn, "SELECT * FROM admin WHERE id='$id'");
  $data = mysqli_fetch_array($get_data);

  $ambil_data = mysqli_query($conn, "SELECT * FROM From_gadai WHERE status IS NULL");

  // Query untuk mendapatkan data jatuh tempo yang mendekati tanggal sekarang
  $tanggal_sekarang = date('Y-m-d');
  $jatuh_tempo_data = mysqli_query($conn, "SELECT *, (jumlah_pinjaman + bunga + administrasi + asuransi) AS total_tebus_hp FROM From_gadai WHERE tanggal_jatuh_tempo >= '$tanggal_sekarang' AND status IS NULL ORDER BY tanggal_jatuh_tempo ASC");

  // Query untuk mendapatkan data gadai selesai dan pembayaran lunas
  $selesai_data = mysqli_query($conn, "SELECT * FROM From_gadai WHERE status = 'selesai' ORDER BY tanggal_jatuh_tempo ASC");
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-90680653-2"></script>
  

    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Meta -->
    <meta name="description" content="Responsive Bootstrap 4 Dashboard Template">
    <meta name="author" content="BootstrapDash">

    <title>Gadai Cepat Timika</title>

    <!-- vendor css -->
    <link href="lib/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="lib/ionicons/css/ionicons.min.css" rel="stylesheet">
    <link href="lib/typicons.font/typicons.css" rel="stylesheet">
    <link href="lib/flag-icon-css/css/flag-icon.min.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">

    <!-- azia CSS -->
    <link rel="stylesheet" href="css/azia.css">

  </head>
  <body>

    <div class="az-header">
      <div class="container">
        <div class="az-header-left">
          <a href="index.php" class="az-logo text-uppercase "><span>Gadai Cepat Timika</span></a>
          <a href="" id="azMenuShow" class="az-header-menu-icon d-lg-none"><span></span></a>
        </div><!-- az-header-left -->
        <div class="az-header-menu">
          <div class="az-header-menu-header">
            <a href="index.html" class="az-logo"><span></span> azia</a>
            <a href="" class="close">&times;</a>
          </div><!-- az-header-menu-header -->
          <ul class="nav">
            <li class="nav-item active show">
              <a href="index.html" class="nav-link"><i class="typcn typcn-chart-area-outline"></i> Dashboard</a>
            </li>
            <li class="nav-item">
              <a href="chart-chartjs.html" class="nav-link"><i class="typcn typcn-chart-bar-outline"></i>Daftar Gadai</a>
            </li>
            <li class="nav-item">
              <a href="chart-chartjs.html" class="nav-link"><i class="typcn typcn-chart-bar-outline"></i>Jatuh Tempo</a>
            </li>
          </ul>
        </div><!-- az-header-menu -->

        <div class="az-header-right">
          <a href="" class="btn btn-danger">Log Out</a>
        </div><!-- az-header-right -->
      </div><!-- container -->
    </div><!-- az-header -->