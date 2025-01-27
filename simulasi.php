<?php
if(isset($_POST['harga'])){
    // Mengambil harga dari input form
    $harga = $_POST['harga'];

    // Menghitung 10% dari harga (biaya jasa)
    $persentase = 10;  // Persentase yang diinginkan (10%)
    $diskon = ($harga * $persentase) / 100;

    // Menghitung biaya administrasi 1%
    $satupersen = 1;  // Persentase yang diinginkan (1%)
    $Administrasi = ($harga * $satupersen) / 100;

    // Biaya admin tetap 10.000
    $biaya_admin = 10000; 

    // Total biaya di muka (biaya jasa, administrasi, dan biaya admin)
    $total_biaya = $diskon + $Administrasi + $biaya_admin;

    // Menghitung uang yang diterima peminjam
    $uang_diterima = $harga - $total_biaya;
    
    // Menampilkan hasil simulasi dalam bentuk HTML

    echo '<table class="table">';
    echo '<thead><tr><th scope="col">Harga Barang</th></tr></thead>';
    echo '<tbody>';
    echo '<tr><th scope="row">Rp ' . number_format($harga, 0, ',', '.') . '</th></tr>';
    echo '<tr><th scope="row">Biaya Jasa Bulanan 10%: Rp ' . number_format($diskon, 0, ',', '.') . '</th></tr>';
    echo '<tr><th scope="row">Biaya Administrasi 1%: Rp ' . number_format($Administrasi, 0, ',', '.') . '</th></tr>';
    echo '<tr><th scope="row">Biaya Asuransi: Rp ' . number_format($biaya_admin, 0, ',', '.') . '</th></tr>';
    echo '<tr><th scope="row">Total Biaya di Muka: Rp ' . number_format($total_biaya, 0, ',', '.') . '</th></tr>';
    echo '<tr class="bg-success text-light"><th scope="row">Uang yang diterima peminjam: Rp ' . number_format($uang_diterima, 0, ',', '.') . '</th></tr>';
    echo '</tbody>';
    echo '</table>';
}
?>
