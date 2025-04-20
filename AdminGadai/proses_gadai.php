<?php
include '../database.php';

// Periksa apakah tabel modal memiliki data
$modalCountQuery = "SELECT SUM(jumlah) AS total_modal FROM modal";
$modalCountResult = mysqli_query($conn, $modalCountQuery);
$modalCountRow = mysqli_fetch_assoc($modalCountResult);
$totalModal = $modalCountRow['total_modal'] ?? 0; // Jika null, set ke 0

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Jika tabel modal kosong, hentikan proses dan kembali ke halaman vg.php
    if ($totalModal == 0) {
        header("Location: vg.php?status=warning&message=Tidak dapat menambahkan data gadai karena modal kosong!");
        exit();
    }

    // Validasi input
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id'] ?? '');
    $merek_hp = mysqli_real_escape_string($conn, $_POST['merek_hp'] ?? '');
    $imei = mysqli_real_escape_string($conn, $_POST['imei'] ?? '');
    $nilai_taksir = mysqli_real_escape_string($conn, $_POST['nilai_taksir'] ?? '');
    $pinjaman = mysqli_real_escape_string($conn, $_POST['pinjaman'] ?? '');
    $bunga = mysqli_real_escape_string($conn, $_POST['bunga'] ?? '');
    $jatuh_tempo = mysqli_real_escape_string($conn, $_POST['jatuh_tempo'] ?? '');
    $keteranganhp = mysqli_real_escape_string($conn, $_POST['keteranganhp'] ?? '');

    // Pastikan semua input tidak kosong
    if (empty($user_id) || empty($merek_hp) || empty($imei) || empty($nilai_taksir) || empty($pinjaman) || empty($bunga) || empty($jatuh_tempo) || empty($keteranganhp)) {
        header("Location: vg.php?status=warning&message=Semua field harus diisi!");
        exit();
    }

    // Periksa apakah jumlah pinjaman lebih besar dari modal yang tersedia
    if ($pinjaman > $totalModal) {
        header("Location: vg.php?status=warning&message=Jumlah pinjaman melebihi modal yang tersedia!");
        exit();
    }

    // Masukkan data gadai ke database
    $insert_query = "
        INSERT INTO barang_gadai (pelanggan_nik, nama_barang, imei, deskripsi, nilai_taksir, pinjaman, bunga, jatuh_tempo, status, created_at)
        VALUES ('$user_id', '$merek_hp', '$imei', '$keteranganhp', '$nilai_taksir', '$pinjaman', '$bunga', '$jatuh_tempo', 'Aktif', NOW())
    ";

    if (mysqli_query($conn, $insert_query)) {
        // Kurangi jumlah modal
        header("Location: vg.php?status=success&message=Data gadai berhasil ditambahkan dan modal telah dikurangi!");
        exit();
    } else {
        header("Location: vg.php?status=error&message=Gagal menambahkan gadai: " . mysqli_error($conn));
        exit();
    }
}
?>