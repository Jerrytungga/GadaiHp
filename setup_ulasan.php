<?php
// Script untuk membuat database dan tabel ulasan

// Koneksi tanpa memilih database terlebih dahulu
$conn = mysqli_connect("localhost", "root", "");

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Membuat database jika belum ada
$createDb = "CREATE DATABASE IF NOT EXISTS `GadaiCepat` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
if ($conn->query($createDb) === TRUE) {
    echo "✓ Database GadaiCepat berhasil dibuat/sudah ada!<br>";
} else {
    echo "✗ Error membuat database: " . $conn->error . "<br>";
}

// Pilih database
$conn->select_db("GadaiCepat");

// SQL untuk membuat tabel ulasan
$sql = "CREATE TABLE IF NOT EXISTS `ulasan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Nama` varchar(100) NOT NULL,
  `Ulasan` text NOT NULL,
  `rating` int(1) NOT NULL DEFAULT 5,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql) === TRUE) {
    echo "✓ Tabel ulasan berhasil dibuat!<br>";
} else {
    echo "✗ Error membuat tabel: " . $conn->error . "<br>";
}

// Cek apakah tabel sudah ada data
$checkData = "SELECT COUNT(*) as total FROM ulasan";
$result = $conn->query($checkData);
$row = $result->fetch_assoc();

if ($row['total'] == 0) {
    // Menambahkan data contoh ulasan
    $insertData = "INSERT INTO `ulasan` (`Nama`, `Ulasan`, `rating`) VALUES
    ('Ahmad Wijaya', 'Pelayanan sangat cepat dan ramah! Prosesnya benar-benar hanya 5 menit. Sangat membantu saat butuh dana mendesak.', 5),
    ('Sarah Putri', 'Recommended banget! Sistem COD nya juga aman dan terpercaya. Tim nya profesional.', 5),
    ('Budi Santoso', 'Proses gadai nya mudah dan tidak ribet. Harga taksiran juga fair sesuai pasaran.', 4),
    ('Linda Sari', 'Sangat terbantu dengan layanan ini. Response cepat via WA dan prosedur jelas.', 5),
    ('Reza Firmansyah', 'Pelayanan memuaskan, barang juga dijaga dengan baik. Terima kasih Gadai Cepat!', 5)";

    if ($conn->query($insertData) === TRUE) {
        echo "✓ Data contoh berhasil ditambahkan!<br>";
    } else {
        echo "✗ Error menambahkan data: " . $conn->error . "<br>";
    }
} else {
    echo "✓ Tabel ulasan sudah berisi " . $row['total'] . " data.<br>";
}

echo "<br><h3>Setup selesai!</h3>";
echo "<a href='index.php' class='btn btn-primary'>Kembali ke halaman utama</a>";

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; font-family: Arial, sans-serif; }
        .btn { margin-top: 20px; }
    </style>
</head>
<body>
</body>
</html>
