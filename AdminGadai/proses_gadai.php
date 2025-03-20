<?php
include '../database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $merek_hp = $_POST['merek_hp'];
    $imei = $_POST['imei'];
    $nilai_taksir = $_POST['nilai_taksir'];
    $pinjaman = $_POST['pinjaman'];
    $bunga = $_POST['bunga'];
    $jatuh_tempo = $_POST['jatuh_tempo'];
    $keteranganhp = $_POST['keteranganhp'];

    // Hitung total tagihan (pinjaman + bunga)
    $sisa_tagihan = $pinjaman + ($pinjaman * $bunga / 100);

    // Masukkan ke database tanpa upload gambar
    $insert_query = "
        INSERT INTO barang_gadai (pelanggan_nik, nama_barang, imei, deskripsi, nilai_taksir, pinjaman, bunga, jatuh_tempo, status, created_at)
        VALUES ('$user_id', '$merek_hp', '$imei', '$keteranganhp', '$nilai_taksir', '$pinjaman', '$bunga', '$jatuh_tempo', 'Aktif', NOW())
    ";

    if (mysqli_query($conn, $insert_query)) {
        // Ambil nomor HP pelanggan
        $pelangganQuery = mysqli_query($conn, "SELECT nama, nomor_hp FROM pelanggan WHERE nik = '$user_id'");
        $pelanggan = mysqli_fetch_assoc($pelangganQuery);
        $namaPelanggan = htmlspecialchars($pelanggan['nama']);
        $nomorHp = htmlspecialchars($pelanggan['nomor_hp']); // Nomor tujuan (format internasional tanpa "+")

        // Hitung bunga bulanan
        $bungaBulanan = $pinjaman * ($bunga / 100);

        // Pesan WhatsApp
        $whatsappMessage = "Halo $namaPelanggan,\n\nTerima kasih telah melakukan gadai di layanan kami.\n\nDetail Gadai:\n- Nama Barang: $merek_hp\n- IMEI: $imei\n- Nilai Taksir: Rp " . number_format($nilai_taksir, 0, ',', '.') . "\n- Jumlah Pinjaman: Rp " . number_format($pinjaman, 0, ',', '.') . "\n- Bunga Bulanan: Rp " . number_format($bungaBulanan, 0, ',', '.') . "\n- Jatuh Tempo: $jatuh_tempo\n\nSilakan simpan informasi ini sebagai referensi. Terima kasih.";

        // Kirim pesan menggunakan Fonnte API
        $url = "https://api.fonnte.com/send";
        $data = [
            'target' => $nomorHp, // Nomor tujuan
            'message' => $whatsappMessage, // Isi pesan
            'countryCode' => '62', // Kode negara (62 untuk Indonesia)
        ];

        $headers = [
            "Authorization: g6i1PFe8Zcu8AvLjidiw", // Ganti dengan API Key Fonnte Anda
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
            error_log("Pesan WhatsApp berhasil dikirim ke $nomorHp: $whatsappMessage");
        } else {
            error_log("Gagal mengirim pesan WhatsApp ke $nomorHp. Response: $response");
        }

        // Redirect kembali ke halaman utama dengan pesan sukses
        header("Location: vg.php?message=success");
        exit();
    } else {
        echo "Gagal menambahkan gadai: " . mysqli_error($conn);
    }
}
?>