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

    // Handle file upload
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["gambarhp"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if image file is a actual image or fake image
    $check = getimagesize($_FILES["gambarhp"]["tmp_name"]);
    if ($check !== false) {
        $uploadOk = 1;
    } else {
        echo "File is not an image.";
        $uploadOk = 0;
    }

    // Check file size
    if ($_FILES["gambarhp"]["size"] > 500000) {
        echo "Sorry, your file is too large.";
        $uploadOk = 0;
    }

    // Allow certain file formats
    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
        echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        $uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        echo "Sorry, your file was not uploaded.";
    // if everything is ok, try to upload file
    } else {
        if (move_uploaded_file($_FILES["gambarhp"]["tmp_name"], $target_file)) {
            // File is uploaded, proceed with database insertion
            $foto = $target_file;

            // Hitung total tagihan (pinjaman + bunga)
            $sisa_tagihan = $pinjaman + ($pinjaman * $bunga / 100);

            // Masukkan ke database
            $insert_query = "
                INSERT INTO barang_gadai (pelanggan_nik, nama_barang, imei, deskripsi, foto, nilai_taksir, pinjaman, bunga, jatuh_tempo, status, created_at)
                VALUES ('$user_id', '$merek_hp','$imei', '$keteranganhp', '$foto', '$nilai_taksir', '$pinjaman', '$bunga', '$jatuh_tempo', 'Aktif', NOW())
            ";

            if (mysqli_query($conn, $insert_query)) {
                // Redirect to vg.php with success message
                header("Location: vg.php?message=success");
                exit();
            } else {
                echo "Gagal menambahkan gadai: " . mysqli_error($conn);
            }
        } else {
            echo "Sorry, there was an error uploading your file.";
        }
    }
}
?>