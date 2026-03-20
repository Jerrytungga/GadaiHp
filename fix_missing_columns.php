<?php
/**
 * Script untuk menambahkan kolom yang hilang pada tabel data_gadai
 * Jalankan file ini sekali di browser: http://localhost/GadaiHp/fix_missing_columns.php
 */

require_once 'database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Perbaikan Database</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
        .success { color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; margin: 10px 0; }
        .info { color: blue; padding: 10px; background: #d1ecf1; border: 1px solid #bee5eb; margin: 10px 0; }
    </style>
</head>
<body>
<h1>Perbaikan Database - Tambah Kolom yang Hilang</h1>";

try {
    // Cek kolom imei_serial
    echo "<h3>Mengecek kolom imei_serial...</h3>";
    $check = $db->query("SHOW COLUMNS FROM data_gadai LIKE 'imei_serial'")->fetch();
    
    if (!$check) {
        echo "<p class='info'>Kolom imei_serial tidak ditemukan. Menambahkan...</p>";
        $db->exec("ALTER TABLE `data_gadai` ADD COLUMN `imei_serial` varchar(100) DEFAULT NULL AFTER `kondisi_barang`");
        echo "<p class='success'>✓ Kolom imei_serial berhasil ditambahkan!</p>";
    } else {
        echo "<p class='success'>✓ Kolom imei_serial sudah ada.</p>";
    }

    // Cek kolom kelengkapan_hp
    echo "<h3>Mengecek kolom kelengkapan_hp...</h3>";
    $check = $db->query("SHOW COLUMNS FROM data_gadai LIKE 'kelengkapan_hp'")->fetch();
    
    if (!$check) {
        echo "<p class='info'>Kolom kelengkapan_hp tidak ditemukan. Menambahkan...</p>";
        $db->exec("ALTER TABLE `data_gadai` ADD COLUMN `kelengkapan_hp` text DEFAULT NULL AFTER `imei_serial`");
        echo "<p class='success'>✓ Kolom kelengkapan_hp berhasil ditambahkan!</p>";
    } else {
        echo "<p class='success'>✓ Kolom kelengkapan_hp sudah ada.</p>";
    }

    echo "<h3>Semua pengecekan selesai!</h3>";
    echo "<p class='success'>Database sudah diperbarui. Silakan kembali ke <a href='admin_verifikasi.php'>halaman admin</a> atau <a href='form_gadai.php'>form gadai</a>.</p>";
    
} catch (Exception $e) {
    echo "<div class='error'>Error: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>
