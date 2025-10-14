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

    // Proses tambah data
    if (!isset($_POST['edit_gadai'])) {
        $user_id = mysqli_real_escape_string($conn, $_POST['user_id'] ?? '');
        $merek_hp = mysqli_real_escape_string($conn, $_POST['merek_hp'] ?? '');
        $imei = mysqli_real_escape_string($conn, $_POST['imei'] ?? '');
        $nilai_taksir = mysqli_real_escape_string($conn, $_POST['nilai_taksir'] ?? '');
        $pinjaman = mysqli_real_escape_string($conn, $_POST['pinjaman'] ?? '');
        $bunga = mysqli_real_escape_string($conn, $_POST['bunga'] ?? '');
        $jatuh_tempo = mysqli_real_escape_string($conn, $_POST['jatuh_tempo'] ?? '');
        $keteranganhp = mysqli_real_escape_string($conn, $_POST['keteranganhp'] ?? '');

        // Validasi hanya untuk tambah data
        if (empty($user_id) || empty($merek_hp) || empty($imei) || empty($nilai_taksir) || empty($pinjaman) || empty($bunga) || empty($jatuh_tempo) || empty($keteranganhp)) {
            header("Location: vg.php?status=warning&message=Semua field harus diisi!");
            exit();
        }

        // Periksa apakah jumlah pinjaman lebih besar dari modal yang tersedia
        if ($pinjaman > $totalModal) {
            header("Location: vg.php?status=warning&message=Jumlah pinjaman melebihi modal yang tersedia!");
            exit();
        }

        // Cek data gadai sudah ada (berdasarkan pelanggan, nama barang, dan IMEI)
        $cek_query = "SELECT id FROM barang_gadai WHERE pelanggan_nik='$user_id' AND nama_barang='$merek_hp' AND imei='$imei'";
        $cek_result = mysqli_query($conn, $cek_query);
        if (mysqli_num_rows($cek_result) > 0) {
            header("Location: vg.php?status=error&message=Data gadai sudah ada, input gagal!");
            exit();
        }

        // Proses upload gambar HP
        $gambar_hp_json = '';
        if (!empty($_FILES['gambar_hp']['name'][0])) {
            $uploadedFiles = [];
            $target_dir = "uploads/gambar_hp/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            foreach ($_FILES['gambar_hp']['name'] as $key => $name) {
                $tmp_name = $_FILES['gambar_hp']['tmp_name'][$key];
                $file_name = time() . '_' . $key . '_' . basename($name);
                $target_file = $target_dir . $file_name;
                if (move_uploaded_file($tmp_name, $target_file)) {
                    $uploadedFiles[] = $file_name;
                }
            }
            $gambar_hp_json = json_encode($uploadedFiles);
        }

        // Masukkan data gadai ke database, termasuk gambar_hp
        $insert_query = "
            INSERT INTO barang_gadai (pelanggan_nik, nama_barang, imei, deskripsi, nilai_taksir, pinjaman, bunga, jatuh_tempo, status, gambar_hp, created_at)
            VALUES ('$user_id', '$merek_hp', '$imei', '$keteranganhp', '$nilai_taksir', '$pinjaman', '$bunga', '$jatuh_tempo', 'Aktif', '$gambar_hp_json', NOW())
        ";

        if (mysqli_query($conn, $insert_query)) {
            // Ambil nomor WA pelanggan
            $pelanggan_query = mysqli_query($conn, "SELECT nama, nomor_hp FROM pelanggan WHERE nik='$user_id'");
            $pelanggan = mysqli_fetch_assoc($pelanggan_query);
            $namaPemilik = $pelanggan['nama'] ?? '';
            $nomorHp = $pelanggan['nomor_hp'] ?? '';
            // Format nomor HP ke 62
            $nomorHp = preg_replace('/[^0-9]/', '', $nomorHp);
            if (substr($nomorHp, 0, 1) == '0') {
                $nomorHp = '62' . substr($nomorHp, 1);
            }
            // Data untuk pesan WA
            $namaBarang = $merek_hp;
            $jatuhTempo = $jatuh_tempo;
            $totalPembayaran = $pinjaman + $bunga;
            $uploadUrl = 'https://yourdomain.com/upload_bukti.php'; // Ganti dengan URL upload bukti pembayaran
            $whatsappMessage = "*Halo $namaPemilik,*\n\nTerima kasih telah melakukan gadai HP di Gadai Cepat Timika. Berikut detail informasi gadai Anda:\n\n*Nama Barang:* $namaBarang\n*IMEI:* $imei\n*Nilai Taksir:* Rp " . number_format($nilai_taksir, 0, ',', '.') . "\n*Pinjaman:* Rp " . number_format($pinjaman, 0, ',', '.') . "\n*Bunga:* Rp " . number_format($bunga, 0, ',', '.') . "\n*Jatuh Tempo:* $jatuhTempo\n\n*Total pembayaran:* Rp " . number_format($totalPembayaran, 0, ',', '.') . "\n\n*Transfer ke BRI 305101007702502 a/n JERRI CHRISTIAN GEDEON TUNGGA.*\n\nJika ada pertanyaan, silakan hubungi admin. Terima kasih.";
            // Kirim pesan menggunakan Fonnte API
            $url = "https://api.fonnte.com/send";
            $data = [
                'target' => $nomorHp,
                'message' => $whatsappMessage,
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
            // Log response WA untuk debug
            file_put_contents(__DIR__ . '/log_wa.txt', date('Y-m-d H:i:s') . "\n" . $response . "\n", FILE_APPEND);
            header("Location: vg.php?status=success&message=Data gadai dan gambar HP berhasil ditambahkan!");
            exit();
        } else {
            header("Location: vg.php?status=error&message=Gagal menambahkan gadai: " . mysqli_error($conn));
            exit();
        }
    }
}

// Proses edit data
if (isset($_POST['edit_gadai'])) {
    $id_gadai = $_POST['edit_gadai_id'];

    // Ambil data lama
    $result = mysqli_query($conn, "SELECT * FROM barang_gadai WHERE id='$id_gadai'");
    $lama = mysqli_fetch_assoc($result);

    // Ambil data baru, jika kosong pakai data lama
    $merek_hp = isset($_POST['merek_hp']) && $_POST['merek_hp'] !== '' ? mysqli_real_escape_string($conn, $_POST['merek_hp']) : $lama['nama_barang'];
    $imei = isset($_POST['imei']) && $_POST['imei'] !== '' ? mysqli_real_escape_string($conn, $_POST['imei']) : $lama['imei'];
    $keteranganhp = isset($_POST['keteranganhp']) && $_POST['keteranganhp'] !== '' ? mysqli_real_escape_string($conn, $_POST['keteranganhp']) : $lama['deskripsi'];
    $nilai_taksir = isset($_POST['nilai_taksir']) && $_POST['nilai_taksir'] !== '' ? mysqli_real_escape_string($conn, $_POST['nilai_taksir']) : $lama['nilai_taksir'];
    $pinjaman = isset($_POST['pinjaman']) && $_POST['pinjaman'] !== '' ? mysqli_real_escape_string($conn, $_POST['pinjaman']) : $lama['pinjaman'];
    $bunga = isset($_POST['bunga']) && $_POST['bunga'] !== '' ? mysqli_real_escape_string($conn, $_POST['bunga']) : $lama['bunga'];
    $jatuh_tempo = isset($_POST['jatuh_tempo']) && $_POST['jatuh_tempo'] !== '' ? mysqli_real_escape_string($conn, $_POST['jatuh_tempo']) : $lama['jatuh_tempo'];

    // Proses upload gambar baru jika ada
    $gambar_hp_json = $lama['gambar_hp'];
    $gambar_lama_dihapus = false;
    if (!empty($_FILES['gambar_hp']['name'][0])) {
        $uploadedFiles = [];
        $target_dir = "uploads/gambar_hp/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        foreach ($_FILES['gambar_hp']['name'] as $key => $name) {
            $tmp_name = $_FILES['gambar_hp']['tmp_name'][$key];
            $file_name = time() . '_' . $key . '_' . basename($name);
            $target_file = $target_dir . $file_name;
            if (move_uploaded_file($tmp_name, $target_file)) {
                $uploadedFiles[] = $file_name;
            }
        }
        $gambar_hp_json = json_encode($uploadedFiles);

        // Hapus file gambar lama
        $gambar_lama = json_decode($lama['gambar_hp'], true);
        if ($gambar_lama && is_array($gambar_lama)) {
            foreach ($gambar_lama as $file) {
                $file_path = $target_dir . $file;
                if (file_exists($file_path)) {
                    @unlink($file_path);
                }
            }
        }
        $gambar_lama_dihapus = true;
    }

    $query = mysqli_query($conn, "UPDATE barang_gadai SET 
        nama_barang='$merek_hp', 
        imei='$imei', 
        deskripsi='$keteranganhp', 
        nilai_taksir='$nilai_taksir', 
        pinjaman='$pinjaman', 
        bunga='$bunga', 
        jatuh_tempo='$jatuh_tempo', 
        gambar_hp='$gambar_hp_json'
        WHERE id='$id_gadai'");

    if ($query) {
        header("Location: vg.php?status=success&message=Data gadai berhasil diupdate" . ($gambar_lama_dihapus ? " dan gambar lama dihapus" : ""));
        exit();
    } else {
        header("Location: vg.php?status=error&message=Gagal mengupdate data gadai");
        exit();
    }
}

// Format nomor HP ke 62 (Indonesia)
$nomorHp = preg_replace('/[^0-9]/', '', $nomorHp);
if (substr($nomorHp, 0, 1) == '0') {
    $nomorHp = '62' . substr($nomorHp, 1);
}

// Pesan WhatsApp
$whatsappMessage = "Halo $namaPemilik,\n\nTerima kasih telah melakukan gadai HP di Gadai HP. Berikut detail informasi gadai Anda:\n\nNama Barang: $namaBarang\nIMEI: $imei\nNilai Taksir: Rp " . number_format($nilai_taksir, 0, ',', '.') . "\nPinjaman: Rp " . number_format($pinjaman, 0, ',', '.') . "\nBunga: Rp " . number_format($bunga, 0, ',', '.') . "\nJatuh Tempo: $jatuhTempo\n\nTotal pembayaran: Rp " . number_format($totalPembayaran, 0, ',', '.') . "\n\nTransfer ke BRI 305101007702502 a/n JERRI CHRISTIAN GEDEON TUNGGA.\n\nUpload bukti pembayaran di:\n$uploadUrl\n\nJika ada pertanyaan, silakan hubungi admin. Terima kasih.";

// Kirim pesan menggunakan Fonnte API
$url = "https://api.fonnte.com/send";
$data = [
    'target' => $nomorHp,
    'message' => $whatsappMessage,
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

// Optional: log response untuk debug
file_put_contents(__DIR__ . '/AdminGadai/log_wa.txt', date('Y-m-d H:i:s') . "\n" . $response . "\n", FILE_APPEND);
?>