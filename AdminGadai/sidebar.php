<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-light-primary elevation-4" style="background: #ffffff;">
  <!-- Brand Logo -->
  <a href="index.php" class="brand-link text-center">
  <!-- Tambahkan logo aplikasi -->
  <img src="../image/GC.png" alt="Logo" class="brand-image " style=" width: 50px; height: 50px;">
  <span class="brand-text font-weight-bold text-dark">
    GC TIMIKA</span>
</a>

  <!-- Sidebar -->
  <div class="sidebar">
    <!-- Sidebar Menu -->
    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column" role="menu">
        <!-- Menu Header -->
        <li class="nav-header text-dark">Menu</li>

        <!-- User Menu -->
        <li class="nav-item">
          <a href="index.php" class="nav-link text-dark">
            <i class="nav-icon fas fa-home me-2"></i> <!-- Ganti ikon menjadi 'fa-home' -->
            <p>Dashboard</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="user.php" class="nav-link text-dark">
            <i class="nav-icon fas fa-users me-2"></i>
            <p>User</p>
          </a>
        </li>

        <!-- Daftar Barang Gadai -->
        <li class="nav-item">
          <a href="vg.php" class="nav-link text-dark">
            <i class="nav-icon fas fa-box-open me-2"></i>
            <p>Daftar Barang Gadai</p>
          </a>
        </li>

        <!-- Modal -->
        <li class="nav-item">
          <a href="modal.php" class="nav-link text-dark">
            <i class="nav-icon fas fa-coins me-2"></i>
            <p>Modal</p>
          </a>
        </li>

        <!-- Transaksi -->
        <li class="nav-item">
          <a href="tr1.php" class="nav-link text-dark">
            <i class="nav-icon fas fa-exchange-alt me-2"></i>
            <p>Transaksi</p>
          </a>
        </li>

        <!-- Settings -->
        <!-- <li class="nav-item">
          <a href="settings.php" class="nav-link text-dark">
            <i class="nav-icon fas fa-tools me-2"></i>
            <p>Settings</p>
          </a>
        </li> -->

        <!-- Logout -->
        <li class="nav-item">
          <a href="../logout.php" class="nav-link text-danger">
            <i class="nav-icon fas fa-sign-out-alt me-2"></i>
            <p>Logout</p>
          </a>
        </li>
      </ul>
    </nav>
    <!-- /.sidebar-menu -->
  </div>
  <!-- /.sidebar -->
</aside>