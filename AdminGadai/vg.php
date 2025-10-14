<?php
// =======================
// INCLUDE HEADER & NAVBAR
// =======================
include 'head.php';
include 'navbar.php';
include 'sidebar.php';
?>

<!-- ======================= -->
<!-- SweetAlert & FontAwesome -->
<!-- ======================= -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

<?php
// =======================
// ALERT HANDLING
// =======================
if (isset($_GET['status']) && isset($_GET['message'])) {
  $status = htmlspecialchars($_GET['status']);
  $message = htmlspecialchars($_GET['message']);
  echo "<script>
    Swal.fire({
      icon: '$status',
      title: '" . ucfirst($status) . "',
      text: '$message'
    });
  </script>";
}

// =======================
// PROSES BAYAR LUNAS
// =======================
if (isset($_POST['lunas'])) {
  $idbarang = $_POST['lunas'];
  $pelanggan = $_POST['pelanggan'];
  $jumlah_tebus = $_POST['jumlah_tebus'];
  $status = 'lunas';
  mysqli_begin_transaction($conn);
  try {
    $insertTransaksiQuery = "INSERT INTO `transaksi`(`pelanggan_nik`, `barang_id`, `jumlah_bayar`, `keterangan`) VALUES ('$pelanggan', '$idbarang', '$jumlah_tebus', '$status')";
    mysqli_query($conn, $insertTransaksiQuery);
    $updateBarangQuery = "UPDATE barang_gadai SET status='ditebus' WHERE id='$idbarang'";
    mysqli_query($conn, $updateBarangQuery);
    mysqli_commit($conn);
    echo "<script>
      Swal.fire({
        icon: 'success',
        title: 'Berhasil',
        text: 'Barang berhasil ditebus dan modal telah dikurangi!'
      }).then(() => {
        window.location.href = 'vg.php';
      });
    </script>";
  } catch (Exception $e) {
    mysqli_rollback($conn);
    echo "<script>
      Swal.fire({
        icon: 'error',
        title: 'Gagal',
        text: 'Terjadi kesalahan saat memproses pelunasan: " . $e->getMessage() . "'
      }).then(() => {
        window.location.href = 'vg.php';
      });
    </script>";
  }
}



$result_pinjaman = mysqli_query($conn, "SELECT SUM(pinjaman) AS total_pinjaman FROM barang_gadai WHERE status = 'aktif'");
$row_pinjaman = mysqli_fetch_assoc($result_pinjaman);
$totalPinjaman = $row_pinjaman['total_pinjaman'] ?? 0; // Jika null, set ke 0

$result_bunga = mysqli_query($conn, "SELECT SUM(bunga) AS total_bunga FROM barang_gadai WHERE status = 'ditebus'");
$row_bunga = mysqli_fetch_assoc($result_bunga);
$totalbunga = $row_bunga['total_bunga'] ?? 0; // Jika null, set ke 0

$result_pinjaman_ditebus = mysqli_query($conn, "SELECT SUM(pinjaman) AS total_pinjaman_tebus FROM barang_gadai WHERE status = 'ditebus'");
$row_pinjaman_ditebus = mysqli_fetch_assoc($result_pinjaman_ditebus);
$totalPinjamantebus = $row_pinjaman_ditebus['total_pinjaman_tebus'] ?? 0; // Jika null, set ke 0




$modalQuery = "SELECT SUM(jumlah) AS total_modal FROM modal";
$modalResult = mysqli_query($conn, $modalQuery);
$modalRow = mysqli_fetch_assoc($modalResult);
$totalModal = $modalRow['total_modal'] ?? 0; // Jika null, set ke 0

$sisa_modal = $totalModal - $totalPinjaman + $totalbunga;

if (isset($_POST['edit_gadai'])) {
  $id_gadai = $_POST['id_gadai'];
  $user_id = $_POST['user_id'];
  $merek_hp = $_POST['merek_hp'];
  $imei = $_POST['imei'];
  $keteranganhp = $_POST['keteranganhp'];
  $nilai_taksir = $_POST['nilai_taksir'];
  $pinjaman = $_POST['pinjaman'];
  $bunga = $_POST['bunga'];
  $jatuh_tempo = $_POST['jatuh_tempo'];

  $query = mysqli_query($conn, "UPDATE barang_gadai SET pelanggan_nik='$user_id', nama_barang='$merek_hp', imei='$imei', deskripsi='$keteranganhp', nilai_taksir='$nilai_taksir', pinjaman='$pinjaman', bunga='$bunga', jatuh_tempo='$jatuh_tempo' WHERE id='$id_gadai'");

  if ($query) {
    echo "<script>
      Swal.fire({
        icon: 'success',
        title: 'Berhasil',
        text: 'Data gadai berhasil diupdate'
      });
    </script>";
  } else {
    echo "<script>
      Swal.fire({
        icon: 'error',
        title: 'Gagal',
        text: 'Gagal mengupdate data gadai'
      });
    </script>";
  }
}

if (isset($_POST['reminder_cicilan'])) {
  $idGadai = $_POST['id_gadai'];
  $namaPemilik = $_POST['nama_pemilik'];
  $namaBarang = $_POST['nama_barang'];
  $jatuhTempo = $_POST['jatuh_tempo'];
  $bungaBulanan = $_POST['bunga_bulanan'];
  $nomorHp = $_POST['nomor_hp']; // Nomor tujuan (format internasional tanpa "+")

  // URL ke halaman upload bukti pembayaran
  $uploadUrl = "https://gadaicepat.online/upload_bukti.php?id_gadai=$idGadai";

  // Hitung denda jika terlambat
  $today = date('Y-m-d');
  $jatuhTempoDate = date('Y-m-d', strtotime($jatuhTempo));
  $denda = 0;
  $pesanDenda = '';
  if ($today > $jatuhTempoDate) {
    $selisihHari = ceil((strtotime($today) - strtotime($jatuhTempoDate)) / (60 * 60 * 24));
    $denda = $selisihHari * 30000;
    $pesanDenda = "\n\n*Denda keterlambatan: Rp " . number_format($denda, 0, ',', '.') . " (" . $selisihHari . " hari x Rp 30.000)*";
  }

  // Pesan WhatsApp fokus ke cicilan

  $whatsappMessage = "*Halo $namaPemilik*,\n\nIni adalah pengingat pembayaran cicilan gadai untuk barang *$namaBarang*.\n\nJatuh tempo cicilan: $jatuhTempo\n*Jumlah cicilan bulan ini: Rp " . number_format($bungaBulanan + $denda, 0, ',', '.') . "*$pesanDenda\n\nSilakan transfer ke Rekening *BRI 305101007702502 a/n JERRI CHRISTIAN GEDEON TUNGGA.*\n\nSetelah transfer, kirim bukti transaksi ke WhatsApp ini.\n\nTerima kasih telah membayar cicilan tepat waktu.";

    // Kirim pesan menggunakan Fonnte API
    $url = "https://api.fonnte.com/send";
    $data = [
        'target' => $nomorHp, // Nomor tujuan
        'message' => $whatsappMessage, // Isi pesan
        'countryCode' => '62', // Kode negara (62 untuk Indonesia)
    ];

    $headers = [
        "Authorization: t7JRhRozh7NF1rp1dsdF", // Ganti dengan API Key Fonnte Anda
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Log hasil pengiriman
    if ($httpCode == 200) {
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Pesan WhatsApp berhasil dikirim ke $nomorHp'
            });
        </script>";
    } else {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: 'Gagal mengirim pesan WhatsApp. Response: $response'
            });
        </script>";
    }
}

if (isset($_POST['reminder_pelunasan'])) {
    $idGadai = $_POST['id_gadai'];
    $namaPemilik = $_POST['nama_pemilik'];
    $namaBarang = $_POST['nama_barang'];
    $jatuhTempo = $_POST['jatuh_tempo'];
    $totalPelunasan = $_POST['total_pelunasan'];
    $nomorHp = $_POST['nomor_hp']; // Nomor tujuan (format internasional tanpa "+")

    // URL ke halaman upload bukti pembayaran
    $uploadUrl = "https://gadaicepat.online/upload_bukti.php?id_gadai=$idGadai";

    // Pesan WhatsApp
    $today = date('Y-m-d');
    $jatuhTempoDate = date('Y-m-d', strtotime($jatuhTempo));
    $denda = 0;
    $pesanDenda = '';
    $pesanJual = '';

    if ($today > $jatuhTempoDate) {
        // Hitung selisih hari keterlambatan
        $selisihHari = ceil((strtotime($today) - strtotime($jatuhTempoDate)) / (60 * 60 * 24));
        $denda = $selisihHari * 30000;
        $pesanDenda = "\n\n*Denda keterlambatan: Rp " . number_format($denda, 0, ',', '.') . " (" . $selisihHari . " hari x Rp 30.000)*";
        if ($selisihHari > 7) {
            $pesanJual = "\n\n*PERHATIAN: KETERLAMBATAN SUDAH LEBIH DARI 7 HARI. BARANG AKAN DIJUAL SESUAI KETENTUAN!*";
        } else {
            $pesanJual = "\n\n*PERHATIAN: Maksimal keterlambatan adalah 7 hari. Jika lewat dari itu, barang akan dijual sesuai ketentuan!*";
        }
    }

    $whatsappMessage = "*Halo $namaPemilik*,\n\nIni adalah pengingat bahwa jatuh tempo pembayaran pelunasan gadai untuk barang *$namaBarang* adalah pada *$jatuhTempo*.\n\n*Jumlah yang harus dibayar: Rp " . number_format($totalPelunasan + $denda, 0, ',', '.') . "*$pesanDenda$pesanJual\n\nSilakan lakukan pembayaran melalui transfer ke Rekening *BRI 305101007702502 a/n JERRI CHRISTIAN GEDEON TUNGGA.*\n\nSetelah melakukan pembayaran, Anda dapat mengirim bukti pembayaran melalui whatsapp ini. \n\nTerima kasih.";

    // Kirim pesan menggunakan Fonnte API
    $url = "https://api.fonnte.com/send";
    $data = [
        'target' => $nomorHp, // Nomor tujuan
        'message' => $whatsappMessage, // Isi pesan
        'countryCode' => '62', // Kode negara (62 untuk Indonesia)
    ];

    $headers = [
        "Authorization: t7JRhRozh7NF1rp1dsdF", // Ganti dengan API Key Fonnte Anda
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Log hasil pengiriman
    if ($httpCode == 200) {
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Pesan WhatsApp berhasil dikirim ke $nomorHp'
            });
        </script>";
    } else {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: 'Gagal mengirim pesan WhatsApp. Response: $response'
            });
        </script>";
    }
}

if (isset($_POST['bayar_cicilan'])) {
    $idGadai = mysqli_real_escape_string($conn, $_POST['id_gadai']);
    $pelanggan = mysqli_real_escape_string($conn, $_POST['pelanggan']);
    $jumlahCicilan = floatval($_POST['jumlah_cicilan']); // Pastikan jumlah cicilan berupa angka
    $metodePembayaran = 'transfer'; // Contoh metode pembayaran

    // Validasi jumlah cicilan
    if ($jumlahCicilan <= 0) {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: 'Jumlah cicilan tidak valid.'
            });
        </script>";
        exit;
    }

  // Cek cicilan terakhir di bulan apa
  $lastCicilanQuery = mysqli_query($conn, "SELECT tanggal_bayar FROM transaksi WHERE barang_id='$idGadai' AND keterangan='cicilan' ORDER BY tanggal_bayar DESC LIMIT 1");
  $canPay = true;
  if ($lastCicilan = mysqli_fetch_assoc($lastCicilanQuery)) {
    $lastMonth = date('Y-m', strtotime($lastCicilan['tanggal_bayar']));
    $currentMonth = date('Y-m');
    if ($lastMonth == $currentMonth) {
      $canPay = false;
    }
  }

  if (!$canPay) {
    echo "<script>
      Swal.fire({
        icon: 'warning',
        title: 'Cicilan Bulan Ini Sudah Dibayar',
        text: 'Pembayaran cicilan hanya bisa dilakukan satu kali per bulan.'
      });
    </script>";
    exit;
  }

  // Masukkan data cicilan ke tabel transaksi
  $insertCicilanQuery = "INSERT INTO transaksi (pelanggan_nik, barang_id, jumlah_bayar, keterangan, metode_pembayaran) 
               VALUES ('$pelanggan', '$idGadai', '$jumlahCicilan', 'cicilan', '$metodePembayaran')";
  $result = mysqli_query($conn, $insertCicilanQuery);

  // Update jatuh_tempo ke bulan berikutnya jika cicilan berhasil
  if ($result) {
    // Ambil jatuh tempo lama
    $jatuhTempoQuery = mysqli_query($conn, "SELECT jatuh_tempo FROM barang_gadai WHERE id='$idGadai'");
    $jatuhTempoRow = mysqli_fetch_assoc($jatuhTempoQuery);
    $jatuhTempoLama = $jatuhTempoRow['jatuh_tempo'] ?? date('Y-m-d');
    // Tambah 1 bulan
    $jatuhTempoBaru = date('Y-m-d', strtotime("+1 month", strtotime($jatuhTempoLama)));
    // Update jatuh_tempo di barang_gadai
    mysqli_query($conn, "UPDATE barang_gadai SET jatuh_tempo='$jatuhTempoBaru' WHERE id='$idGadai'");

    // Ambil data pemilik dan barang
    $queryGadai = mysqli_query($conn, "SELECT bg.nama_barang, p.nama AS nama_pemilik, p.nomor_hp AS telepon_pemilik FROM barang_gadai bg JOIN pelanggan p ON bg.pelanggan_nik = p.nik WHERE bg.id='$idGadai'");
    $dataGadai = mysqli_fetch_assoc($queryGadai);
    $namaPemilik = $dataGadai['nama_pemilik'] ?? '';
    $namaBarang = $dataGadai['nama_barang'] ?? '';
    $nomorHp = $dataGadai['telepon_pemilik'] ?? '';
    // Format nomor HP ke 62
    $nomorHp = preg_replace('/[^0-9]/', '', $nomorHp);
    if (substr($nomorHp, 0, 1) == '0') {
      $nomorHp = '62' . substr($nomorHp, 1);
    }

    // Kirim pesan WhatsApp konfirmasi cicilan
    $whatsappMessage = "Halo $namaPemilik,\n\nPembayaran cicilan untuk barang '$namaBarang' telah diterima. Terima kasih telah membayar cicilan tepat waktu.\n\nCicilan selanjutnya dapat dibayar di bulan berikutnya.\n\nJatuh tempo cicilan berikutnya: $jatuhTempoBaru.\n\nJika ada pertanyaan, silakan hubungi admin.\n\nTerima kasih telah menggunakan layanan GadaiCepat!";
    $url = "https://api.fonnte.com/send";
    $data = [
      'target' => $nomorHp,
      'message' => $whatsappMessage,
      'countryCode' => '62',
    ];
    $headers = ["Authorization: t7JRhRozh7NF1rp1dsdF"];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200) {
      echo "<script>
        Swal.fire({
          icon: 'success',
          title: 'Berhasil',
          text: 'Pembayaran cicilan berhasil diproses & WA terkirim!'
        }).then(() => {
          window.location.href = 'vg.php';
        });
      </script>";
    } else {
      echo "<script>
        Swal.fire({
          icon: 'warning',
          title: 'Cicilan Berhasil',
          text: 'Cicilan berhasil, tapi WA gagal: $response'
        }).then(() => {
          window.location.href = 'vg.php';
        });
      </script>";
    }
  } else {
    echo "<script>
      Swal.fire({
        icon: 'error',
        title: 'Gagal',
        text: 'Terjadi kesalahan saat memproses pembayaran cicilan.'
      });
    </script>";
  }
}

if (isset($_POST['bayar_pelunasan'])) {
  $idGadai = mysqli_real_escape_string($conn, $_POST['id_gadai']);
  $pelanggan = mysqli_real_escape_string($conn, $_POST['pelanggan']);
  $jumlahPelunasan = floatval($_POST['jumlah_pelunasan']);
  $metodePembayaran = 'transfer';

  // Validasi jumlah pelunasan
  if ($jumlahPelunasan <= 0) {
    echo "<script>Swal.fire({icon: 'error',title: 'Gagal',text: 'Jumlah pelunasan tidak valid.'});</script>";
    exit;
  }

  mysqli_begin_transaction($conn);
  try {
    // Proses pelunasan
    $insertPelunasanQuery = "INSERT INTO transaksi (pelanggan_nik, barang_id, jumlah_bayar, keterangan, metode_pembayaran) VALUES ('$pelanggan', '$idGadai', '$jumlahPelunasan', 'lunas', '$metodePembayaran')";
    mysqli_query($conn, $insertPelunasanQuery);
    $updateBarangQuery = "UPDATE barang_gadai SET status='ditebus' WHERE id='$idGadai'";
    mysqli_query($conn, $updateBarangQuery);

    // Ambil data pemilik dan barang
    $queryGadai = mysqli_query($conn, "SELECT bg.nama_barang, p.nama AS nama_pemilik, p.nomor_hp AS telepon_pemilik FROM barang_gadai bg JOIN pelanggan p ON bg.pelanggan_nik = p.nik WHERE bg.id='$idGadai'");
    $dataGadai = mysqli_fetch_assoc($queryGadai);
    $namaPemilik = $dataGadai['nama_pemilik'] ?? '';
    $namaBarang = $dataGadai['nama_barang'] ?? '';
    $nomorHp = $dataGadai['telepon_pemilik'] ?? '';
    // Format nomor HP ke 62
    $nomorHp = preg_replace('/[^0-9]/', '', $nomorHp);
    if (substr($nomorHp, 0, 1) == '0') {
      $nomorHp = '62' . substr($nomorHp, 1);
    }

    // Kirim pesan WhatsApp pelunasan
    $whatsappMessage = "Halo $namaPemilik,\n\nTerima kasih telah melakukan pelunasan gadai untuk barang '$namaBarang'. Barang Anda sudah lunas dan dapat diambil di kantor kami.\n\nJika ada pertanyaan, silakan hubungi admin.\n\nTerima kasih telah menggunakan layanan GadaiCepat!";
    $url = "https://api.fonnte.com/send";
    $data = [
      'target' => $nomorHp,
      'message' => $whatsappMessage,
      'countryCode' => '62',
    ];
    $headers = ["Authorization: t7JRhRozh7NF1rp1dsdF"];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    mysqli_commit($conn);

    // Tampilkan response API Fonnte
    if ($httpCode == 200) {
      echo "<script>Swal.fire({icon: 'success',title: 'Berhasil',text: 'Pelunasan berhasil & WA terkirim!'}).then(()=>{window.location.href='vg.php';});</script>";
    } else {
      echo "<script>Swal.fire({icon: 'warning',title: 'Pelunasan Berhasil',text: 'Pelunasan berhasil, tapi WA gagal: $response'}).then(()=>{window.location.href='vg.php';});</script>";
    }
  } catch (Exception $e) {
    mysqli_rollback($conn);
    echo "<script>Swal.fire({icon: 'error',title: 'Gagal',text: 'Terjadi kesalahan saat memproses pelunasan.'});</script>";
  }
}

if (isset($_POST['reminder_lunas'])) {
    $idGadai = $_POST['id_gadai'];
    $namaPemilik = $_POST['nama_pemilik'];
    $namaBarang = $_POST['nama_barang'];
    $nomorHp = $_POST['nomor_hp']; // Nomor tujuan (format internasional tanpa "+")

    // URL ke halaman upload bukti pembayaran
    $uploadUrl = "https://gadaicepat.online/upload_bukti.php?id_gadai=$idGadai";

    // Pesan WhatsApp untuk barang yang sudah dilunasi
    $whatsappMessage = "Halo $namaPemilik,\n\nTerima kasih telah melakukan pelunasan gadai untuk barang '$namaBarang'. Barang Anda sudah lunas dan dapat diambil di kantor kami.\n\nJika ada pertanyaan, silakan hubungi admin.\n\nTerima kasih telah menggunakan layanan GadaiCepat!";

    // Kirim pesan menggunakan Fonnte API
    $url = "https://api.fonnte.com/send";
    $data = [
        'target' => $nomorHp, // Nomor tujuan
        'message' => $whatsappMessage, // Isi pesan
        'countryCode' => '62', // Kode negara (62 untuk Indonesia)
    ];

    $headers = [
        "Authorization: t7JRhRozh7NF1rp1dsdF", // Ganti dengan API Key Fonnte Anda
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Log hasil pengiriman
    if ($httpCode == 200) {
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Pesan WhatsApp pelunasan berhasil dikirim ke $nomorHp'
            });
        </script>";
    } else {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: 'Gagal mengirim pesan WhatsApp. Response: $response'
            });
        </script>";
    }
}

$userQuery = mysqli_query($conn, "SELECT nik, nama FROM pelanggan");
$query = mysqli_query($conn, "SELECT barang_gadai.*, pelanggan.nama AS nama_pemilik, pelanggan.nomor_hp AS telepon_pemilik, 
                              DATEDIFF(jatuh_tempo, CURDATE()) AS days_to_due 
                              FROM barang_gadai 
                              JOIN pelanggan ON barang_gadai.pelanggan_nik = pelanggan.nik 
                              ORDER BY days_to_due ASC");

// =======================
// HELPER FUNCTIONS
// =======================
function formatRupiah($number) {
  return 'Rp ' . number_format($number, 0, ',', '.');
}
function isDueSoon($daysToDue) {
  return $daysToDue <= 3 && $daysToDue >= 0;
}
function isOverdue($daysToDue) {
  return $daysToDue < 0;
}

// Query to count the number of installments paid for each item
$cicilanQuery = mysqli_query($conn, "SELECT barang_id, COUNT(*) as cicilan_count FROM transaksi WHERE keterangan = 'cicilan' GROUP BY barang_id");

// Create an associative array to store the count of installments paid for each item
$cicilanCounts = [];
while ($cicilan = mysqli_fetch_assoc($cicilanQuery)) {
    $cicilanCounts[$cicilan['barang_id']] = $cicilan['cicilan_count'];
}
?>

<!-- ======================= -->
<!-- CONTENT WRAPPER & HEADER -->
<!-- ======================= -->
<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1>Daftar Gadai HP</h1>
        </div>
        <div class="col-sm-6">
          <button type="button" class="btn btn-primary float-right" data-toggle="modal" data-target="#addGadaiModal">
            Tambah Gadai
          </button>
        </div>
      </div>
      <div class="row">
        <!-- Card Sisa Modal -->
        <div class="col-lg-6 col-md-6 col-sm-12">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3><?= 'Rp ' . number_format($sisa_modal, 0, ',', '.'); ?></h3>
                    <p>Sisa Modal</p>
                </div>
                <div class="icon">
                    <i class="fas fa-wallet"></i>
                </div>
            </div>
        </div>
        <!-- Card Jumlah Bunga -->
        <div class="col-lg-6 col-md-6 col-sm-12">
            <div class="small-box bg-success">
                <div class="inner">
                    <?php
                    // Hitung total bunga
                    $bungaQuery = mysqli_query($conn, "SELECT SUM(bunga) AS total_bunga FROM barang_gadai");
                    $bungaRow = mysqli_fetch_assoc($bungaQuery);
                    $totalBunga = $bungaRow['total_bunga'] ?? 0;
                    ?>
                    <h3><?= 'Rp ' . number_format($totalBunga, 0, ',', '.'); ?></h3>
                    <p>Total Profit</p>
                </div>
                <div class="icon">
                    <i class="fas fa-percentage"></i>
                </div>
            </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ======================= -->
  <!-- MAIN CONTENT & TABLE -->
  <!-- ======================= -->
  <section class="content">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Gadai List</h3>
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
        <?php if (isset($_GET['message']) && $_GET['message'] == 'success') { ?>
          <div class="alert alert-success">Data gadai berhasil ditambahkan!</div>
        <?php } ?>
        <div style="overflow-x: auto;">
          <table id="userTable" class="table table-bordered table-striped align-middle">
            <thead class="table-dark">
              <tr class="text-center">
                <th>No</th>
                <th>Nama Barang</th>
                <th>Imei</th>
                <th>Keterangan</th>
                <th>Pemilik</th>
                <th>Nilai Taksir</th>
                <th>Pinjaman</th>
                <th>Bunga Bulanan (Rp)</th>
                <th>Jatuh Tempo</th>
                <th>Status</th>
                <th>Gambar HP</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $no = 1;
              while ($gadai = mysqli_fetch_assoc($query)) {
                if ($gadai['status'] == 'ditebus') continue;
                // Cek apakah cicilan bulan ini sudah dibayar
                $cicilanBulanIni = false;
                $cicilanCheckQuery = mysqli_query($conn, "SELECT tanggal_bayar FROM transaksi WHERE barang_id='{$gadai['id']}' AND keterangan='cicilan' ORDER BY tanggal_bayar DESC LIMIT 1");
                if ($cicilanCheck = mysqli_fetch_assoc($cicilanCheckQuery)) {
                  $lastMonth = date('Y-m', strtotime($cicilanCheck['tanggal_bayar']));
                  $currentMonth = date('Y-m');
                  if ($lastMonth == $currentMonth) {
                    $cicilanBulanIni = true;
                  }
                }
                $rowClass = '';
                if (!$cicilanBulanIni) {
                  if (isOverdue($gadai['days_to_due'])) $rowClass = 'bg-danger text-white';
                  elseif (isDueSoon($gadai['days_to_due'])) $rowClass = 'bg-warning';
                }
                $gambar_hp = [];
                if (!empty($gadai['gambar_hp'])) $gambar_hp = json_decode($gadai['gambar_hp'], true);
              ?>
                <tr class="<?= $rowClass; ?> text-center">
                  <td><?= $no++; ?></td>
                  <td><?= htmlspecialchars($gadai['nama_barang']); ?></td>
                  <td><?= htmlspecialchars($gadai['imei']); ?></td>
                  <td><?= htmlspecialchars($gadai['deskripsi']); ?></td>
                  <td><?= htmlspecialchars($gadai['nama_pemilik']); ?></td>
                  <td><?= formatRupiah($gadai['nilai_taksir']); ?></td>
                  <td><?= formatRupiah($gadai['pinjaman']); ?></td>
                  <td><?= formatRupiah($gadai['bunga']); ?></td>
                  <td><?= htmlspecialchars($gadai['jatuh_tempo']); ?></td>
                  <td>
                    <span class="badge <?= $gadai['status'] == 'aktif' ? 'bg-success' : 'bg-secondary'; ?>">
                      <?= htmlspecialchars($gadai['status']); ?>
                    </span>
                  </td>
                  <td>
                    <?php if ($gambar_hp): ?>
                      <?php foreach ($gambar_hp as $img): ?>
                        <a href="uploads/gambar_hp/<?= htmlspecialchars($img); ?>" target="_blank">
                          <img src="uploads/gambar_hp/<?= htmlspecialchars($img); ?>" width="40" height="40" class="rounded border m-1" style="object-fit:cover;">
                        </a>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <span class="text-muted">Belum Ada</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <!-- Tombol Edit -->
                    <button type="button" class="btn btn-light btn-sm btn-edit m-1" data-gadai='<?= json_encode($gadai); ?>'>
                      <i class="fas fa-edit"></i> Edit
                    </button>
                    <!-- Reminder Cicilan WA -->
                    <form action="" method="post" style="display: inline;" class="mb-1">
                        <input type="hidden" name="id_gadai" value="<?= $gadai['id']; ?>">
                        <input type="hidden" name="pelanggan" value="<?= $gadai['pelanggan_nik']; ?>">
                        <input type="hidden" name="nama_pemilik" value="<?= htmlspecialchars($gadai['nama_pemilik']); ?>">
                        <input type="hidden" name="nama_barang" value="<?= htmlspecialchars($gadai['nama_barang']); ?>">
                        <input type="hidden" name="jatuh_tempo" value="<?= htmlspecialchars($gadai['jatuh_tempo']); ?>">
                        <input type="hidden" name="bunga_bulanan" value="<?= $gadai['bunga']; ?>">
                        <input type="hidden" name="nomor_hp" value="<?= htmlspecialchars($gadai['telepon_pemilik']); ?>">
                        <button type="submit" name="reminder_cicilan" class="btn m-1 btn-success btn-sm">
                            <i class="fab fa-whatsapp"></i> Reminder Cicilan WA
                        </button>
                    </form>
                    <!-- Reminder Pelunasan WA -->
                    <form action="" method="post" style="display: inline;" class="mb-1">
                        <input type="hidden" name="id_gadai" value="<?= $gadai['id']; ?>">
                        <input type="hidden" name="nama_pemilik" value="<?= htmlspecialchars($gadai['nama_pemilik']); ?>">
                        <input type="hidden" name="nama_barang" value="<?= htmlspecialchars($gadai['nama_barang']); ?>">
                        <input type="hidden" name="jatuh_tempo" value="<?= htmlspecialchars($gadai['jatuh_tempo']); ?>">
                        <input type="hidden" name="total_pelunasan" value="<?= $gadai['pinjaman'] + $gadai['bunga']; ?>">
                        <input type="hidden" name="nomor_hp" value="<?= htmlspecialchars($gadai['telepon_pemilik']); ?>">
                        <button type="submit" name="reminder_pelunasan" class="btn m-1 btn-info btn-sm">
                            <i class="fab fa-whatsapp"></i> Reminder Pelunasan WA
                        </button>
                    </form>
          <!-- Bayar Cicilan -->
          <?php
          // Cek apakah cicilan bulan ini sudah dibayar
          $cicilanBulanIni = false;
          $cicilanCheckQuery = mysqli_query($conn, "SELECT tanggal_bayar FROM transaksi WHERE barang_id='{$gadai['id']}' AND keterangan='cicilan' ORDER BY tanggal_bayar DESC LIMIT 1");
          if ($cicilanCheck = mysqli_fetch_assoc($cicilanCheckQuery)) {
            $lastMonth = date('Y-m', strtotime($cicilanCheck['tanggal_bayar']));
            $currentMonth = date('Y-m');
            if ($lastMonth == $currentMonth) {
              $cicilanBulanIni = true;
            }
          }
          ?>
          <?php if (!$cicilanBulanIni): ?>
          <form action="" method="post" style="display: inline;" class="mb-1">
            <input type="hidden" name="id_gadai" value="<?= $gadai['id']; ?>">
            <input type="hidden" name="pelanggan" value="<?= $gadai['pelanggan_nik']; ?>">
            <input type="hidden" name="jumlah_cicilan" value="<?= $gadai['bunga']; ?>">
            <button type="submit" name="bayar_cicilan" class="btn m-1 btn-success btn-sm">
              Bayar Cicilan
            </button>
          </form>
          <?php endif; ?>
                    <!-- Bayar Pelunasan -->
                    <form action="" method="post" style="display: inline;" class="mb-1">
                        <input type="hidden" name="id_gadai" value="<?= $gadai['id']; ?>">
                        <input type="hidden" name="pelanggan" value="<?= $gadai['pelanggan_nik']; ?>">
                        <input type="hidden" name="jumlah_pelunasan" value="<?= $gadai['pinjaman'] + $gadai['bunga']; ?>">
                        <input type="hidden" name="kde" value="<?= $gadai['kde'] ?? ''; ?>">
                        <button type="submit" name="bayar_pelunasan" class="btn m-1 btn-dark btn-sm">
                            Bayar Pelunasan
                        </button>
                    </form>
                  </td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer">Footer</div>
    </div>
  </section>
</div>

<!-- /.content-wrapper -->

<!-- Modal for adding gadai -->
<div class="modal fade" id="addGadaiModal" tabindex="-1" role="dialog" aria-labelledby="addGadaiModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addGadaiModalLabel">Tambah Gadai</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form action="proses_gadai.php" method="POST" enctype="multipart/form-data">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="user_id">Nama Pelanggan:</label>
                <select name="user_id" id="user_id" class="form-control" required>
                  <option value="">Pilih Pelanggan</option>
                  <?php while ($user = mysqli_fetch_assoc($userQuery)) { ?>
                    <option value="<?= $user['nik']; ?>"><?= htmlspecialchars($user['nama']); ?></option>
                  <?php } ?>
                </select>
              </div>
              <div class="form-group">
                <label for="merek_hp">Merek HP:</label>
                <input type="text" name="merek_hp" id="merek_hp" class="form-control" required>
              </div>
              <div class="form-group">
                <label for="imei">IMEI HP:</label>
                <input type="text" name="imei" id="imei" class="form-control" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="keteranganhp">Keterangan HP:</label>
                <textarea name="keteranganhp" id="keteranganhp" class="form-control" required></textarea>
              </div>
              <div class="form-group">
                <label for="nilai_taksir">Nilai Taksir (Rp):</label>
                <input type="number" name="nilai_taksir" id="nilai_taksir" class="form-control" required>
              </div>
              <div class="form-group">
                <label for="pinjaman">Jumlah Pinjaman (Rp):</label>
                <input type="number" name="pinjaman" id="pinjaman" class="form-control" required>
              </div>
              <div class="form-group">
                <label for="bunga">Bunga (Rp):</label>
                <input type="number" name="bunga" id="bunga" class="form-control"  required>
              </div>
              <div class="form-group">
                <label for="jatuh_tempo">Jatuh Tempo:</label>
                <input type="date" name="jatuh_tempo" id="jatuh_tempo" class="form-control" required>
              </div>
              <div class="form-group">
                <label for="gambar_hp[]">Upload Gambar HP (maksimal 10 gambar):</label>
                <input type="file" name="gambar_hp[]" id="gambar_hp" class="form-control" accept="image/*" multiple required>
                <small class="text-muted">Pilih hingga 10 gambar. Minimal 4 gambar.</small>
              </div>
            </div>
          </div>
          <button type="submit" class="btn btn-primary">Tambah Gadai</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal for editing gadai -->
<div class="modal fade" id="editGadaiModal" tabindex="-1" role="dialog" aria-labelledby="editGadaiModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form action="proses_gadai.php" method="POST" enctype="multipart/form-data">
        <div class="modal-header">
          <h5 class="modal-title" id="editGadaiModalLabel">Edit Data Gadai</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="edit_gadai_id" id="edit_gadai_id">
          <div class="form-group">
            <label for="edit_merek_hp">Nama Barang</label>
            <input type="text" class="form-control" id="edit_merek_hp" name="merek_hp">
          </div>
          <div class="form-group">
            <label for="edit_imei">IMEI</label>
            <input type="text" class="form-control" id="edit_imei" name="imei">
          </div>
          <div class="form-group">
            <label for="edit_nilai_taksir">Nilai Taksir</label>
            <input type="number" class="form-control" id="edit_nilai_taksir" name="nilai_taksir">
          </div>
          <div class="form-group">
            <label for="edit_pinjaman">Pinjaman</label>
            <input type="number" class="form-control" id="edit_pinjaman" name="pinjaman">
          </div>
          <div class="form-group">
            <label for="edit_bunga">Bunga</label>
            <input type="number" class="form-control" id="edit_bunga" name="bunga">
          </div>
          <div class="form-group">
            <label for="edit_jatuh_tempo">Jatuh Tempo</label>
            <input type="date" class="form-control" id="edit_jatuh_tempo" name="jatuh_tempo">
          </div>
          <div class="form-group">
            <label for="edit_keteranganhp">Keterangan</label>
            <textarea class="form-control" id="edit_keteranganhp" name="keteranganhp"></textarea>
          </div>
          <div class="form-group">
            <label>Gambar HP Saat Ini:</label>
            <div id="edit_gambar_hp_preview"></div>
          </div>
          <div class="form-group">
            <label for="edit_gambar_hp">Upload Gambar HP Baru (bisa lebih dari 4 gambar):</label>
            <input type="file" name="gambar_hp[]" id="edit_gambar_hp" class="form-control" accept="image/*" multiple>
            <small class="text-muted">Kosongkan jika tidak ingin mengganti gambar. Pilih maksimal 10 gambar.</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="edit_gadai" class="btn btn-primary">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
</div>



<?php
include 'script.php';
?>

<!-- Include DataTables CSS and JS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>

<!-- Include SweetAlert CSS and JS -->


<script>
$(document).ready(function() {
    $('#userTable').DataTable();

    // Fill the edit form with data when the edit button is clicked
    $('.btn-edit').on('click', function() {
        var gadai = $(this).data('gadai');
        $('#edit_gadai_id').val(gadai.id);
        $('#edit_merek_hp').val(gadai.nama_barang);
        $('#edit_imei').val(gadai.imei);
        $('#edit_keteranganhp').val(gadai.deskripsi);
        $('#edit_nilai_taksir').val(gadai.nilai_taksir);
        $('#edit_pinjaman').val(gadai.pinjaman);
        $('#edit_bunga').val(gadai.bunga);
        $('#edit_jatuh_tempo').val(gadai.jatuh_tempo);

        // Tampilkan gambar HP lama di preview
        var gambar_hp = [];
        try { gambar_hp = JSON.parse(gadai.gambar_hp); } catch(e) {}
        var preview = '';
        if (gambar_hp && gambar_hp.length) {
            gambar_hp.forEach(function(img) {
                preview += '<img src="uploads/gambar_hp/' + img + '" width="50" class="rounded m-1 border">';
            });
        } else {
            preview = '<span class="text-muted">Belum Ada</span>';
        }
        $('#edit_gambar_hp_preview').html(preview);

        $('#editGadaiModal').modal('show');
    });

    // Handle tombol Aksi
    $('.btn-action').on('click', function() {
        var gadai = $(this).data('gadai');
        // Isi data untuk Reminder Pelunasan
        $('#pelunasan_id_gadai').val(gadai.id);
        $('#pelunasan_nama_pemilik').val(gadai.nama_pemilik);
        $('#pelunasan_nama_barang').val(gadai.nama_barang);
        $('#pelunasan_jatuh_tempo').val(gadai.jatuh_tempo);
        $('#pelunasan_total_pelunasan').val(gadai.pinjaman + (gadai.pinjaman * (gadai.bunga / 100)));
        $('#pelunasan_nomor_hp').val(gadai.telepon_pemilik);

        // Tampilkan modal
        $('#actionModal').modal('show');
    });

    // Handle tombol Reminder Pelunasan
    $('#btn-reminder-pelunasan').on('click', function() {
        $('#reminderPelunasanForm').show();
    });
});
</script>





