-- Setup tabel admin untuk sistem login yang aman
-- Gunakan file ini untuk membuat atau update tabel admin

-- Hapus tabel admin lama jika ada (hati-hati: ini akan menghapus semua data admin!)
-- DROP TABLE IF EXISTS admin;

-- Buat tabel admin baru dengan kolom password yang aman
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nik VARCHAR(20) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    telepon VARCHAR(20),
    role ENUM('super_admin', 'admin') DEFAULT 'admin',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_nik (nik),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Jika tabel admin sudah ada tapi belum punya kolom password, tambahkan:
-- ALTER TABLE admin ADD COLUMN password VARCHAR(255) NOT NULL AFTER nik;
-- ALTER TABLE admin ADD COLUMN nama VARCHAR(100) NOT NULL AFTER nik;

-- Insert admin default (NIK: admin123, Password: admin123)
-- PENTING: Ganti password ini setelah login pertama kali!
INSERT INTO admin (nik, nama, password, role) VALUES 
('admin123', 'Administrator', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin')
ON DUPLICATE KEY UPDATE password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

-- Password hash di atas adalah untuk password: admin123
-- Untuk keamanan, segera ganti password setelah login pertama menggunakan file generate_password.php

-- Contoh menambah admin baru:
-- INSERT INTO admin (nik, nama, password, email, telepon, role) VALUES 
-- ('1234567890123456', 'Nama Admin', 'HASH_PASSWORD_DI_SINI', 'admin@example.com', '081234567890', 'admin');
