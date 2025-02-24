<?php
include '../database.php';

session_start();
if (!isset($_SESSION['id'])) {
  echo "<script type='text/javascript'>
  alert('Anda harus login terlebih dahulu!');
  window.location = '../index.php'
</script>";
  exit();
} else {
  $id = $_SESSION['id'];
  $get_data = $conn->prepare("SELECT * FROM admin WHERE id=?");
  $get_data->bind_param("s", $id);
  $get_data->execute();
  $data = $get_data->get_result()->fetch_array(MYSQLI_ASSOC);

  if (isset($_POST['kirim'])) {
    // Ambil data dari form dan lakukan validasi
    $ktp = mysqli_real_escape_string($conn, trim($_POST['ktp']));
    $namalengkap = mysqli_real_escape_string($conn, trim($_POST['namalengkap']));
    $alamat = mysqli_real_escape_string($conn, trim($_POST['alamat']));
    $nohp = mysqli_real_escape_string($conn, trim($_POST['nohp']));

    // Tentukan folder berdasarkan KTP
    $target_dir = "uploads/{$ktp}/"; 
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Fungsi untuk upload file
    function uploadFile($file, $dir, $newName) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mime_type = mime_content_type($file['tmp_name']);

        if (!in_array($file_extension, $allowed) || !strstr($mime_type, 'image')) {
            echo "Format file tidak diizinkan.";
            return false;
        }

        $target_file = $dir . $newName . "." . $file_extension;
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            return $target_file;
        } else {
            return false;
        }
    }

    // Upload foto diri
    $target_fotodiri = uploadFile($_FILES['fotodiri'], $target_dir, $ktp);
    if (!$target_fotodiri) die("Gagal upload foto diri.");

    // Upload foto KTP
    $target_ftktp = uploadFile($_FILES['ftktp'], $target_dir, "ktp_scan_" . $ktp);
    if (!$target_ftktp) die("Gagal upload foto KTP.");

    // Upload foto HP
    $foto_hp_keys = ['depan', 'belakang', 'sampingkanan', 'sampingkiri', 'atas', 'bawa'];
    $foto_hp_paths = [];
    foreach ($foto_hp_keys as $key) {
        $foto_hp_paths[$key] = uploadFile($_FILES[$key], $target_dir, "hp_{$key}_{$ktp}");
        if (!$foto_hp_paths[$key]) die("Gagal upload foto $key.");
    }

    // Simpan Data ke Database menggunakan Prepared Statement
    $stmt = $conn->prepare("INSERT INTO `From_gadai`
        (`ktp_nasabah`, `nama`, `alamat`, `no_hp`, `foto_nasabah`, `merek_tipe_hp`, `imei_hp`, `kondisi_hp`, `akun_hp`, `kelengkapan_hp`, 
        `foto_depan_hp`, `foto_belakang_hp`, `foto_samping_kanan_hp`, `foto_samping_kiri_hp`, `foto_atas_hp`, `foto_bawa_hp`, 
        `jumlah_pinjaman`, `bunga`, `administrasi`, `asuransi`, `total_tebus_hp`, `tanggal_jatuh_tempo`, `kip_verifikasi_nasabah`, `foto_ktp`) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    // Memastikan format tanggal benar
    $waktuakhir = $_POST['jangkawaktu'];
    if (empty($waktuakhir)) {
        $waktuakhir = NULL;
    } else {
        $date = DateTime::createFromFormat('Y-m-d', $waktuakhir);
        if (!$date) {
            die("Format tanggal tidak valid! Harus dalam format YYYY-MM-DD.");
        }
        $waktuakhir = $date->format('Y-m-d');
    }

    // Pastikan semuanya ter-bind dengan benar, pastikan urutan dan tipe datanya sesuai
    $stmt->bind_param("ssssssssssssssssssssssss", 
    $ktp, $namalengkap, $alamat, $nohp, $target_fotodiri, 
    $_POST['mrkhp'], $_POST['imeihp'], $_POST['kondisihp'], $_POST['akun'], $_POST['Kelengkapan'],
    $foto_hp_paths['depan'], $foto_hp_paths['belakang'], $foto_hp_paths['sampingkanan'], 
    $foto_hp_paths['sampingkiri'], $foto_hp_paths['atas'], $foto_hp_paths['bawa'], 
    str_replace(['Rp ', '.'], '', $_POST['harga']), str_replace(['Rp ', '.'], '', $_POST['bunga']), str_replace(['Rp ', '.'], '', $_POST['biayaAdministrasi']), 
    str_replace(['Rp ', '.'], '', $_POST['biayaAsuransi']), str_replace(['Rp ', '.'], '', $_POST['totalPinjaman']), $waktuakhir, $ktp, $target_ftktp);

    // Eksekusi query
    if ($stmt->execute()) {
        echo "<script>
            alert('Data berhasil disimpan.');
        </script>";
    } else {
        echo "<script>
            alert('Terjadi kesalahan: " . addslashes($stmt->error) . "');
        </script>";
    }

    $stmt->close();
  }
}
?>


<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gadai Cepat Timika Papua | Form Peminjaman</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Fungsi untuk menghitung bunga otomatis dan format rupiah
        function hitungBiaya() {
          var jumlahPinjaman = parseFloat(document.getElementById("harga").value.replace(/[^\d]/g, ''));
          var bunga = 10 / 100;  // Bunga 10%
          var biayaAdministrasi = 1 / 100; // Biaya Administrasi 1%
          var biayaAsuransi = 10000; // Biaya Asuransi 10.000
    
          if (!isNaN(jumlahPinjaman)) {
            // Menghitung bunga, biaya administrasi, dan total pinjaman
            var bungaTotal = jumlahPinjaman * bunga;
            var biayaAdministrasiTotal = jumlahPinjaman * biayaAdministrasi;
            var totalBiaya = bungaTotal + biayaAdministrasiTotal + biayaAsuransi;
    
            // Menampilkan hasil di input dengan format rupiah
            document.getElementById("bunga").value = formatRupiah(bungaTotal); // Set nilai bunga
            document.getElementById("biayaAdministrasi").value = formatRupiah(biayaAdministrasiTotal); // Set biaya administrasi
            document.getElementById("biayaAsuransi").value = formatRupiah(biayaAsuransi); // Set biaya asuransi
            document.getElementById("totalPinjaman").value = formatRupiah(jumlahPinjaman + totalBiaya); // Set total pinjaman termasuk biaya
          }
        }
    
        // Fungsi untuk format angka ke format Rupiah
        function formatRupiah(angka) {
          var number_string = angka.toString().replace(/[^,\d]/g, '').toString(),
              split = number_string.split(','),
              sisa = split[0].length % 3,
              rupiah = split[0].substr(0, sisa),
              ribuan = split[0].substr(sisa).match(/\d{3}/gi);
    
          if (ribuan) {
            separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
          }
    
          rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
          return 'Rp ' + rupiah;
        }
      </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

  </head>
    <style>
        .logo {
            width: 200px;
            height: 200px;
            margin-bottom: -80px;
        }
    </style>
  <body>
    <div class="container">
        <center>
            <img src="../image/logo.ico" class="logo" alt="Logo">
        </center>
      <h1 class="text-center mt-5">Gadai Cepat Timika Papua</h1>


      <div class="card">
        <h5 class="card-header">FORMULIR GADAI HP</h5>
        <div class="card-body">
            <form action="" method="post" enctype="multipart/form-data">
            <div class="row">
                <div class="col-sm-6 mb-3 mb-sm-0">
                  <div class="card">
                    <div class="card-body">

                      <h5 class="card-title">A. Data Nasabah</h5>
                      <div class="mb-3 row">
                        <label for="KTP" class="col-sm-2">KTP :</label>
                        <div class="col-sm-10">
                          <input type="number" name="ktp"  class="form-control" required>
                        </div>
                      </div>
                      <div class="mb-3 row">
                        <label for="nama" class="col-sm-2">Nama :</label>
                        <div class="col-sm-10">
                          <input type="text" name="namalengkap"  class="form-control" required>
                        </div>
                      </div>
                      <div class="mb-3 row">
                        <label for="alamat" class="col-sm-2">Alamat :</label>
                        <div class="col-sm-10">
                          <textarea name="alamat" required class="form-control" id=""></textarea>
                        </div>
                      </div>
                      <div class="mb-3 row">
                        <label for="hp" class="col-sm-2">No Hp :</label>
                        <div class="col-sm-10">
                            <input type="number" name="nohp" required  class="form-control">
                        </div>
                      </div>
                      <div class="mb-3 row">
                        <label for="hp" class="col-sm-2">Foto Diri :</label>
                        <div class="col-sm-10">
                            <input type="file" required name="fotodiri"  class="form-control">
                        </div>
                      </div>
                     
                      <h5 class="card-title">B. Data HP yang Digadaikan</h5>
                      <div class="mb-3 row">
                        <label for="Merek & Tipe Hp" class="col-sm-2">Merek & Tipe Hp :</label>
                        <div class="col-sm-10">
                          <input type="text" name="mrkhp"  class="form-control" placeholder="Samsung Galaxy s21 Ultra" required>
                        </div>
                      </div>
                      <div class="mb-3 row">
                        <label for="Nomor IMEI" class="col-sm-2">Nomor IMEI : </label>
                        <div class="col-sm-10">
                          <input type="text" name="imeihp"  class="form-control" placeholder="Masukan Imei Hp anda" required>
                        </div>
                      </div>
                      <div class="mb-3 row">
                        <label for="Kondisi HP" class="col-sm-2">Kondisi HP : </label>
                        <div class="col-sm-10">
                          <input type="text" name="kondisihp" class="form-control" placeholder="Masukan kondisi Hp anda" required>
                        </div>
                      </div>
                      <div class="mb-3 row">
                        <label for="Akses Akun" class="col-sm-2">Akses Akun : </label>
                        <div class="col-sm-10">
                          <select name="akun" id="" class="form-control" required>
                            <option value="Terkunci">Terkunci</option>
                            <option value="Tidak Terkunci">Tidak Terkunci</option>
                          </select>
                        </div>
                      </div>

                      <div class="mb-3 row">
                        <label for="Kelengkapan" name="kelengkapanhp" class="col-sm-2">Kelengkapan Hp :</label>
                        <div class="col-sm-10">
                          <textarea name="Kelengkapan" required class="form-control" id=""></textarea>
                        </div>
                      </div>

                        <div class="mb-3 row">
                            <label for="Harga" class="col-sm-2">Foto Hp :</label>
                            <div class="col-sm-10">
                            <input type="file" class="form-control"  name="depan" id="" required>
                            <p class="text-danger">Foto depan Hp</p>
                            <input type="file" class="form-control" name="belakang" id="" required>
                            <p class="text-danger">Foto belakang Hp</p>
                            <input type="file" class="form-control" name="sampingkanan" id="" required>
                            <p class="text-danger">Foto Samping Kanan Hp</p>
                            <input type="file" class="form-control" name="sampingkiri" id="" required>
                            <p class="text-danger">Foto Samping Kiri Hp</p>
                            <input type="file" class="form-control" name="atas" id="" required>
                            <p class="text-danger">Foto Atas Hp</p>
                            <input type="file" class="form-control" name="bawa" id="" required>
                            <p class="text-danger">Foto Bawa Hp</p>
                            </div>
                        </div>

                    </div>
                  </div>
                </div>

                <div class="col-sm-6 mb-3 mb-sm-0">
                  <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">C. Ketentuan Gadai</h5>
                        <div class="mb-3 row">
                            <label for="Jumlah Pinjaman" class="col-sm-2">Jumlah Pinjaman : </label>
                            <div class="col-sm-10">
                              <input type="text" name="harga" id="harga" class="form-control col-sm-2" required oninput="hitungBiaya()" placeholder="Masukkan jumlah pinjaman">
                            </div>
                          </div>
                          
                          <div class="mb-3 row">
                            <label for="Bunga" class="col-sm-2">Bunga : 10% </label>
                            <div class="col-sm-10">
                              <input type="text" name="bunga" id="bunga" class="form-control col-sm-2" readonly placeholder="Bunga otomatis dihitung">
                            </div>
                          </div>
                        
                          <div class="mb-3 row">
                            <label for="Biaya Administrasi" class="col-sm-2">Biaya Administrasi (1%) : </label>
                            <div class="col-sm-10">
                              <input type="text" name="biayaAdministrasi" id="biayaAdministrasi" class="form-control col-sm-2" readonly placeholder="Biaya administrasi otomatis dihitung">
                            </div>
                          </div>
                        
                          <div class="mb-3 row">
                            <label for="Biaya Asuransi" class="col-sm-2">Biaya Asuransi : </label>
                            <div class="col-sm-10">
                              <input type="text" name="biayaAsuransi" id="biayaAsuransi" class="form-control col-sm-2" readonly placeholder="Biaya asuransi tetap 10.000">
                            </div>
                          </div>
                        
                          <div class="mb-3 row">
                            <label for="Total Pinjaman" class="col-sm-2">Total yang Harus Ditebus: </label>
                            <div class="col-sm-10">
                              <input type="text" name="totalPinjaman" id="totalPinjaman" class="form-control col-sm-2" readonly placeholder="Total yang Harus Ditebus">
                            </div>
                          </div>

                          <div class="mb-3 row">
                            <label for="jangkah waktu" class="col-sm-2">Tanggal Jatuh Tempo : </label>
                            <div class="col-sm-10">
                            <input type="date" name="jangkawaktu" class="form-control" name="" id="">
                            </div>
                          </div>

                          <h5 class="card-title ">D. Pernyataan Nasabah</h5>
                          <p>Saya, yang bertanda tangan di bawah ini, menyatakan bahwa HP yang saya gadaikan
                            bukan hasil curian dan saya bersedia memenuhi kewajiban sesuai perjanjian. Jika saya
                            tidak menebus HP dalam waktu yang ditentukan, saya memahami bahwa HP akan dijual
                            oleh pihak penyedia gadai.</p>

                      
                          <h2 class="text-center">SYARAT & KETENTUAN</h2>
                          1️⃣ Nasabah wajib berusia minimal 18 tahun dan membawa KTP asli. <br>
                          2️⃣ HP yang digadaikan harus dalam kondisi baik dan tidak terkunci akun Google/iCloud. <br>
                          3️⃣ Pinjaman maksimal 70% dari harga pasar HP.  <br>
                          4️⃣ Bunga gadai : 10% per bulan, tergantung kondisi HP. <br>
                          5️⃣ Masa gadai maksimal 3 bulan (dapat diperpanjang dengan syarat tertentu). <br>
                          6️⃣ Denda keterlambatan Rp 10.000/hari jika pembayaran melewati jatuh tempo. <br>
                          7️⃣ Jika HP tidak ditebus dalam 7 hari setelah jatuh tempo, HP akan dijual oleh penyedia gadai.  <br>
                          8️⃣ Nasabah wajib mencadangkan data pribadi sebelum gadai, karena penyedia gadai tidak bertanggung jawab atas kehilangan data. <br>
                          9️⃣ Penyedia gadai berhak menolak HP yang dicurigai hasil curian. <br><p></p>
                          <div class="mb-2 row">
                            <label for="KTP" class="col-sm-2">KTP :</label>
                            <div class="col-sm-10">
                              <input type="file" class="form-control" name="ftktp" id="" required>
                            </div>
                          </div>

                     <button type="submit" name="kirim" class="btn btn-primary mb-5 ">Kirim Data</button>
                    </div>
                  </div>
                </div>
              </div>
            </form>
        </div>
      </div>






      
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  </body>
</html>