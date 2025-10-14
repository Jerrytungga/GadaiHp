

<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    
    <!-- jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <title>Gadai Cepat Timika Papua</title>

    <link href="css/style.css" rel="stylesheet" type="text/css">
    <style>
      /* Flash screen styles */
      #flash-screen {
        position: fixed;
        width: 100%;
        height: 100%;
        background: #fff;
        z-index: 9999;
        display: flex;
        justify-content: center;
        align-items: center;
        transition: opacity 0.5s ease-out;
      }
      #flash-screen.hidden {
        opacity: 0;
        visibility: hidden;
      }
      .logo {
  width: 85px;
  height: auto;
  margin: 1px 0;
  padding: 0; 
}
      .ulasan .card {
  background-color: #e3f2fd; /* Biru muda */
  border: 1px solid #0056b3;
  border-radius: 8px;
  padding: 15px;
  margin-bottom: 15px;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.ulasan .card:hover {
  transform: scale(1.05);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
}
.ulasan h2 {
  font-weight: bold;
}

.btn-primary {
  background-color: #0056b3; /* Biru gelap */
  border-color: #004494;
}

.btn-primary:hover {
  background-color: #004494; /* Biru lebih gelap */
  border-color: #003366;
}

.btn-success {
  background-color: #007bff; /* Biru terang */
  border-color: #0056b3;
}

.btn-success:hover {
  background-color: #0056b3; /* Biru gelap */
  border-color: #003f7f;
}

.btn-danger {
  background-color: #003366; /* Biru sangat gelap */
  border-color: #002244;
}

.btn-danger:hover {
  background-color: #002244; /* Biru lebih gelap */
  border-color: #001122;
}

#kontakkami {
  background-color: #e3f2fd; /* Biru muda */
  color: #003f7f; /* Biru gelap */
}

#kontakkami h2 {
  color: #0056b3; /* Biru gelap */
}

#tentangkami {
  background-color: #f0f8ff; /* Biru sangat muda */
  color: #003f7f; /* Biru gelap */
}

#tentangkami h2 {
  color: #0056b3; /* Biru gelap */
}
#layanan {
  background-color: #f0f8ff; /* Biru sangat muda */
  color: #003f7f; /* Biru gelap */
}

#layanan h2 {
  color: #0056b3; /* Biru gelap */
}
#ulasan {
  background-color: #f0f8ff; /* Biru sangat muda */
  color: #003f7f; /* Biru gelap */
}

#ulasan h2 {
  color: #0056b3; /* Biru gelap */
}

.navbar {
  background-color: #f0f8ff; /* Biru gelap */
}

.navbar .nav-link {
  color: #ffffff; /* Putih */
}

.navbar .nav-link:hover {
  color: #007bff; /* Biru terang */
}

.navbar-text {
  color: #ffffff; /* Putih */
}

#flash-screen {
  background-color: #0056b3; /* Biru gelap */
}

#flash-screen img {
  filter: brightness(0) invert(1); /* Membuat logo menjadi putih */
}

.modal-content {
  background-color: #e3f2fd; /* Biru muda */
  color: #003f7f; /* Biru gelap */
}

.modal-header {
  background-color: #0056b3; /* Biru gelap */
  color: #ffffff; /* Putih */
}

.modal-body {
  background-color: #ffffff; /* Putih */
  color: #003f7f; /* Biru gelap */
}
#ulasan {
  background-color:rgb(255, 255, 255); /* Biru sangat muda */
  padding: 60px 20px; /* Tambahkan padding */
  margin-bottom: 50px; /* Tambahkan jarak bawah */
  text-align: center; /* Pusatkan teks */
}

#ulasan h2 {
  color: #0056b3; /* Biru gelap */
  margin-bottom: 20px; /* Tambahkan jarak bawah */
}

#ulasan p {
  color: #003f7f; /* Biru gelap */
  margin-bottom: 30px; /* Tambahkan jarak bawah */
}

#ulasanCarousel .card {
  margin: 0 auto; /* Pusatkan kartu */
  max-width: 600px; /* Batasi lebar kartu */
}
  </style>
  </head>
  <body>
    <!-- Flash screen -->



     <!-- Modal -->
     <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">Welcome to Gadai Cepat Timika Papua</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            Selamat datang di layanan Gadai Cepat Timika Papua. Kami siap membantu Anda dengan proses gadai yang cepat dan mudah.
            <hr>
            <h2 class="text-center">SYARAT & KETENTUAN</h2>
            1️⃣ Nasabah wajib berusia minimal 18 tahun dan membawa KTP asli. <br>
            2️⃣ HP yang digadaikan harus dalam kondisi baik dan tidak terkunci akun Google/iCloud. <br>
            3️⃣ Pinjaman maksimal 70% dari harga pasar HP.  <br>
            4️⃣ Bunga gadai : 30% per bulan, tergantung kondisi HP. <br>
            5️⃣ Masa gadai maksimal 3 bulan (dapat diperpanjang dengan syarat tertentu). <br>
            6️⃣ Denda keterlambatan Rp 30.000/hari jika pembayaran melewati jatuh tempo. <br>
            7️⃣ Jika HP tidak ditebus dalam 7 hari setelah jatuh tempo, HP akan dijual oleh penyedia gadai.  <br>
            8️⃣ Nasabah wajib mencadangkan data pribadi sebelum gadai, karena penyedia gadai tidak bertanggung jawab atas kehilangan data. <br>
            9️⃣ Penyedia gadai berhak menolak HP yang dicurigai hasil curian. <br><p></p>
          </div>
        
        </div>
      </div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-light bg">
        <div class="container">
          <a class="navbar-brand" href="index.php"></a>
            <img src="image/GC.png" class="logo" alt="Logo">
          </a>
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
          <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
              <li class="nav-item">
                <a class="nav-link" href="#layanan">Layanan</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="#prosesgadai">Proses Gadai</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="#tentangkami">Tentang Kami</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="#kontakkami">Kontak Kami</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="#simulasi">Simulasi</a>
              </li>
            </ul>
            <div class="d-flex">
              <span class="navbar-text">
                <b>GADAI CEPAT TIMIKA PAPUA</b> | 
                <a href="login.php" rel="noopener noreferrer">Login</a>
              </span>
            </div>
          </div>
        </div>
      </nav>
      

    <div class="container">
      <section>
        <div class="row m-4 mt-5">
          <div class="col-sm-6 flex-column">
            <h2 class="headtext">Selamat Datang di Gadai Cepat, Timika Papua</h2>
            <p class="">Cair dalam 5 Menit.</p>
            <a href="https://wa.me/6285823091908?text=Halo%2C%20saya%20ingin%20bertanya%20tentang%20layanan%20Gadai%20Cepat." target="_blank" class="btn btn-primary btn-lg">HUBUNGI KAMI</a><br>
            <sub>Dengan Sistem <span class="badge bg-danger">COD</span></sub>
          </div>
              
          <div class="col-sm-6 d-flex align-items-stretch">
            <img src="./image/rb_51019.png" class="gambar" alt="Logo">
          </div>
        </div>
      </section>
    </div>


  



    <section class="layanan" id="layanan">
      <div class="container">
        <div class="row text-center m-4">
          <h2 class="mt-5">LAYANAN</h2>
          <p class="fst-italic">Gadai ponsel Anda dengan proses cepat dan mudah, dari berbagai merek dan model terbaru. <br> Dapatkan pinjaman sesuai dengan nilai ponsel Anda!</p>
          <p class="fst-italic">Saat ini kami hanya menerima berbagai merek ponsel anda, Kami memberikan <br> nominal yang sesuai dengan harga pasaran ponsel anda.</p>
        </div>
      </div>
    </section>

    <section id="prosesgadai">
      <div class="container">
        <div class="row text-center m-4">
          <h2 class="mt-5 mb-4">CARA GADAI MUDAH & INSTAN</h2>
          <p class="des">Gadai Cepat mengerti bahwa kemudahan dan kecepatan mencairkan dana adalah hal terpenting <br>bagi pelanggan; karena itu Gadai Cepat siap membantu Anda dengan proses yang simpel dan <br>pelayanan yang ramah.</p>

          <div class="col-sm-4 d-flex align-items-stretch">
            <div class="card">
              <img src="./image/rb_43377.png" class="icon" alt="...">
              <div class="card-body">
                <h1 class="badge rounded-pill bg-primary">01</h1>
                <h5 class="card-title">Buat Janji Ketemuan</h5>
                <p class="card-text">Buat janjian ketemuan yang disepakati oleh kedua pihak.</p>
              </div>
            </div>
          </div>

          <div class="col-sm-4 d-flex align-items-stretch">
            <div class="card">
              <img src="./image/Audit-pana.png" class="icon" alt="...">
              <div class="card-body">
                <h1 class="badge rounded-pill bg-primary">02</h5>
                <h5 class="card-title">Pengecekan Barang & Identitas</h5>
                <p class="card-text">Periksa dan catat kondisi barang, kelengkapan, serta harga barang. Pastikan identitas dan spesifikasi barang sesuai dengan dokumen yang tertera.</p>
              </div>
            </div>
          </div>

          <div class="col-sm-4 d-flex align-items-stretch">
            <div class="card">
              <img src="./image/verifikasi.png" class="icon" alt="...">
              <div class="card-body">
                <h1 class="badge rounded-pill bg-primary">03</h1>
                <h5 class="card-title">Verifikasi Data</h5>
                <p class="card-text">Barang Anda akan diverifikasi, dan kami akan segera memproses pinjaman Anda.</p>
              </div>
            </div>
          </div>

        </div>
        <center>
          <a href="https://wa.me/6285823091908?text=Halo%2C%20saya%20ingin%20bertanya%20tentang%20layanan%20Gadai%20Cepat." target="_blank" class="btn thubungikami btn-success rounded-pill btn-lg">HUBUNGI KAMI</a>
        </center>
      </div>
    </section>

    <section class="tentangkami" id="tentangkami">
      <div class="container">
        <div class="row text-center m-4">
          <h2 class="mt-5">TENTANG KAMI</h2>
          <p class="fst-italic">Gadai Cepat adalah sebuah usaha yang baru merintis untuk memudahkan <br> anda dalam memenuhi kebutuhan keuangan anda yang mendesak. <br><br> Kami akan menjaga barang anda dengan sangat aman.</p>
        </div>
      </div>
    </section>

    <section id="simulasi">
  <div class="container">
    <div class="row text-center m-4">
      <div class="col-sm-6 d-flex flex-column justify-content-center align-items-center">
        <h2 class="mt-5">Simulasi Gadai</h2>
        <p>Masukan Harga barang :</p>
        <form id="simulasiForm" method="post" class="w-100">
          <input type="text" name="harga" id="harga" class="form-control col-sm-2" required autocomplete="off">
          <button type="submit" class="btn btn-lg mt-2 btn-info">Simulasi</button>
          <a href="#" id="resetButton" class="btn btn-danger mt-2 btn-lg">Reset</a>
        </form>
      </div>
      <div class="col-sm-6 d-flex flex-column justify-content-center align-items-center">
        <h2 class="mt-5">Hasil Simulasi Gadai</h2>
        <div id="result" class="w-100"></div>
      </div>
    </div>
  </div>
</section>
    


    <section id="ulasan" class="ulasan">
      <div class="container">
        <div class="row text-center m-4">
          <h2 class="mt-5">ULASAN PENGGUNA</h2>
          <p class="fst-italic">Berikut adalah ulasan dari pengguna layanan kami:</p>
        </div>
        <?php
        include 'database.php';
        $ulasan = [];
        $sql = "SELECT Nama, Ulasan, rating FROM ulasan ORDER BY id DESC LIMIT 30";
        if ($result = $conn->query($sql)) {
          while ($row = $result->fetch_assoc()) {
            $ulasan[] = $row;
          }
        }
        ?>
        <div id="ulasanCarousel" class="carousel slide" data-bs-ride="carousel">
          <div class="carousel-inner">
            <?php if (count($ulasan) > 0): ?>
              <?php foreach ($ulasan as $i => $u): ?>
                <div class="carousel-item<?php echo $i === 0 ? ' active' : ''; ?>">
                  <div class="card mb-3">
                    <div class="card-body">
                      <h5 class="card-title"><?php echo htmlspecialchars($u['Nama']); ?></h5>
                      <p class="card-text"><?php echo htmlspecialchars($u['Ulasan']); ?></p>
                      <p class="text-muted">
                        <?php
                          $stars = intval($u['rating']);
                          for ($s = 0; $s < $stars; $s++) echo '⭐';
                        ?>
                      </p>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="carousel-item active">
                <div class="card mb-3">
                  <div class="card-body">
                    <h5 class="card-title">Belum ada ulasan</h5>
                    <p class="card-text">Jadilah yang pertama memberikan ulasan!</p>
                  </div>
                </div>
              </div>
            <?php endif; ?>
          </div>
          <button class="carousel-control-prev" type="button" data-bs-target="#ulasanCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#ulasanCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
          </button>
        </div>
      </div>
    </section>


    <section id="kontakkami" class="kontakkami">
      <div class="container">
        <div class="row mb-5">
          <div class="col-sm-12 flex-column">
            <h2 class="mt-5">KONTAK KAMI</h2>
            <p class="">Telepon : 085823091908 <br> Alamat : Jl Irigasi, Perumahan Residence 5 <br> Mimika Papua<br><b>NIB : 1110250029885</b><br><span class="text-muted">Usaha ini sedang diawasi oleh instansi terkait untuk memastikan keamanan dan legalitas.</span></p>
            <a href="https://wa.me/6285823091908?text=Halo%2C%20saya%20ingin%20bertanya%20tentang%20layanan%20Gadai%20Cepat." class="btn btn-primary btn-lg" target="_blank">HUBUNGI KAMI</a>

          </div>
        </div>
      </div>
    </section>
 



    <script>
      // Fungsi untuk memformat angka ke format Rupiah
      function formatRupiah(value) {
        var numberString = value.replace(/[^,\d]/g, '').toString(),
            split = numberString.split(','),
            remainder = split[0].length % 3,
            rupiah = split[0].substr(0, remainder),
            thousands = split[0].substr(remainder).match(/\d{3}/gi);

        if (thousands) {
          separator = remainder ? '.' : '';
          rupiah += separator + thousands.join('.');
        }

        rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
        return rupiah ? 'Rp ' + rupiah : '';
      }

      // Saat user mengetik
      $('#harga').on('input', function() {
        var rawValue = $(this).val();
        var formattedValue = formatRupiah(rawValue);
        $(this).val(formattedValue);
      });

      // Menghandle submit form menggunakan AJAX
      $(document).ready(function(){
        $('#simulasiForm').on('submit', function(e){
          e.preventDefault(); // Mencegah form submit normal

          // Mengambil harga yang sudah diformat menjadi angka tanpa format Rupiah
          var harga = $('#harga').val().replace(/[^0-9]/g, ''); // Menghapus 'Rp' dan tanda titik

          $.ajax({
            url: 'simulasi.php', // File PHP yang akan menangani request
            type: 'POST',
            data: { harga: harga }, // Mengirimkan data harga ke server
            success: function(response){
              $('#result').html(response); // Menampilkan hasil simulasi tanpa reload halaman
            },
            error: function(){
              alert('Terjadi kesalahan saat memproses data');
            }
          });
        });

        // Menangani tombol reset
        $('#resetButton').on('click', function(e){
          e.preventDefault(); // Mencegah link default behavior (reload halaman)

          // Mengosongkan nilai input harga
          $('#harga').val('');

          // Mengosongkan hasil simulasi
          $('#result').html('');
        });
      });

      // Menghilangkan flash screen setelah halaman dimuat
      $(window).on('load', function() {
        $('#flash-screen').addClass('hidden');
        $('#exampleModal').modal('show'); // Menampilkan modal secara otomatis
      });
    </script>

   

    <!-- Include Bootstrap JS and Popper JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

  </body>
</html>
