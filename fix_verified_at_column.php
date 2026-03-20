<?php
/**
 * Script untuk menambahkan kolom verified_at pada tabel data_gadai
 * Jalankan file ini sekali di browser: http://localhost/GadaiHp/fix_verified_at_column.php
 */

require_once 'database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Perbaikan Database - verified_at</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
        .success { color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; margin: 10px 0; }
        .info { color: blue; padding: 10px; background: #d1ecf1; border: 1px solid #bee5eb; margin: 10px 0; }
    </style>
</head>
<body>
<h1>Perbaikan Database - Kolom verified_at</h1>";

try {
    // Cek kolom verified_at
    echo "<h3>Mengecek kolom verified_at...</h3>";
    $check = $db->query("SHOW COLUMNS FROM data_gadai LIKE 'verified_at'")->fetch();
    
    if (!$check) {
        echo "<p class='info'>Kolom verified_at tidak ditemukan. Menambahkan...</p>";
        $db->exec("ALTER TABLE `data_gadai` ADD COLUMN `verified_at` timestamp NULL DEFAULT NULL AFTER `reminder_telat_last_day`");
        echo "<p class='success'>✓ Kolom verified_at berhasil ditambahkan!</p>";
    } else {
        echo "<p class='success'>✓ Kolom verified_at sudah ada.</p>";
    }

    // Cek kolom verified_by
    echo "<h3>Mengecek kolom verified_by...</h3>";
    $check = $db->query("SHOW COLUMNS FROM data_gadai LIKE 'verified_by'")->fetch();
    
    if (!$check) {
        echo "<p class='info'>Kolom verified_by tidak ditemukan. Menambahkan...</p>";
        $db->exec("ALTER TABLE `data_gadai` ADD COLUMN `verified_by` int(11) DEFAULT NULL AFTER `verified_at`");
        echo "<p class='success'>✓ Kolom verified_by berhasil ditambahkan!</p>";
    } else {
        echo "<p class='success'>✓ Kolom verified_by sudah ada.</p>";
        
        // Cek apakah tipe data benar (INT bukan VARCHAR)
        $column_info = $db->query("SHOW COLUMNS FROM data_gadai LIKE 'verified_by'")->fetch(PDO::FETCH_ASSOC);
        if (strpos($column_info['Type'], 'varchar') !== false) {
            echo "<p class='info'>Kolom verified_by bertipe VARCHAR, mengubah ke INT...</p>";
            $db->exec("ALTER TABLE `data_gadai` MODIFY COLUMN `verified_by` int(11) DEFAULT NULL");
            echo "<p class='success'>✓ Tipe data verified_by berhasil diubah ke INT!</p>";
        }
    }

    // Cek kolom alasan_penolakan
    echo "<h3>Mengecek kolom alasan_penolakan...</h3>";
    $check = $db->query("SHOW COLUMNS FROM data_gadai LIKE 'alasan_penolakan'")->fetch();
    
    if (!$check) {
        echo "<p class='info'>Kolom alasan_penolakan tidak ditemukan. Menambahkan...</p>";
        $db->exec("ALTER TABLE `data_gadai` ADD COLUMN `alasan_penolakan` text DEFAULT NULL COMMENT 'Alasan penolakan' AFTER `verified_by`");
        echo "<p class='success'>✓ Kolom alasan_penolakan berhasil ditambahkan!</p>";
    } else {
        echo "<p class='success'>✓ Kolom alasan_penolakan sudah ada.</p>";
    }

    // Cek kolom catatan_admin
    echo "<h3>Mengecek kolom catatan_admin...</h3>";
    $check = $db->query("SHOW COLUMNS FROM data_gadai LIKE 'catatan_admin'")->fetch();
    
    if (!$check) {
        echo "<p class='info'>Kolom catatan_admin tidak ditemukan. Menambahkan...</p>";
        $db->exec("ALTER TABLE `data_gadai` ADD COLUMN `catatan_admin` text DEFAULT NULL COMMENT 'Catatan admin saat menyetujui' AFTER `alasan_penolakan`");
        echo "<p class='success'>✓ Kolom catatan_admin berhasil ditambahkan!</p>";
    } else {
        echo "<p class='success'>✓ Kolom catatan_admin sudah ada.</p>";
    }

    echo "<h3>Semua pengecekan selesai!</h3>";
    echo "<p class='success'>Database sudah diperbarui. Silakan kembali ke <a href='admin_verifikasi.php'>halaman admin</a>.</p>";
    
} catch (Exception $e) {
    echo "<div class='error'>Error: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>
