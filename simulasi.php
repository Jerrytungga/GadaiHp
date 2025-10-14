<?php
if(isset($_POST['harga'])){
    // Mengambil harga dari input form
    $harga = $_POST['harga'];

    // Menghitung 30% dari harga (biaya jasa)
    $persentase = 30;  // Persentase bunga bulanan
    $diskon = ($harga * $persentase) / 100;

    // Menghitung biaya administrasi 1%
    $satupersen = 1;  // Persentase yang diinginkan (1%)
    $Administrasi = ($harga * $satupersen) / 100;

    // Biaya admin tetap 10.000
    $biaya_admin = 10000; 

    // Total biaya di muka (biaya jasa, administrasi, dan biaya admin)
    $total_biaya = $diskon + $Administrasi + $biaya_admin;

    // Menghitung uang yang diterima peminjam
    $uang_diterima = $harga + $total_biaya;
    
    // Menampilkan hasil simulasi dalam bentuk HTML

    echo '<table class="table">';
    echo '<thead><tr><th scope="col">Rincian Simulasi Gadai</th></tr></thead>';
    echo '<tbody>';
    echo '<tr><th scope="row">Harga Barang: <span class="fw-normal">Rp ' . number_format($harga, 0, ',', '.') . '</span></th></tr>';
    echo '<tr><th scope="row">Bunga Gadai per Bulan (30%): <span class="fw-normal">Rp ' . number_format($diskon, 0, ',', '.') . '</span></th></tr>';
    echo '<tr><th scope="row">Biaya Administrasi (1%): <span class="fw-normal">Rp ' . number_format($Administrasi, 0, ',', '.') . '</span></th></tr>';
    echo '<tr><th scope="row">Biaya Asuransi: <span class="fw-normal">Rp ' . number_format($biaya_admin, 0, ',', '.') . '</span></th></tr>';
    echo '<tr><th scope="row">Total Bunga : <span class="fw-normal">Rp ' . number_format($total_biaya, 0, ',', '.') . '</span></th></tr>';
    echo '<tr class="bg-success text-light"><th scope="row">Dana yang Anda Kembalikan: <span class="fw-normal">Rp ' . number_format($uang_diterima, 0, ',', '.') . '</span></th></tr>';
    echo '</tbody>';
    echo '</table>';
}
?>
