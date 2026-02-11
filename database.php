<?php

// Koneksi MySQLi (untuk file lama yang masih menggunakan mysqli)
$conn = mysqli_connect("localhost", "root", "", "GadaiCepat") or die("Gagal koneksi MySQLi");

// Koneksi PDO (untuk file baru yang menggunakan PDO)
try {
    $db = new PDO("mysql:host=localhost;dbname=GadaiCepat", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Gagal koneksi PDO: " . $e->getMessage());
}

?>