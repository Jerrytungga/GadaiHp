# 🔐 Panduan Setup Login Admin

Sistem login telah diperbaiki dengan fitur keamanan yang lebih baik. Ikuti langkah-langkah di bawah ini untuk setup.

## ✨ Fitur Keamanan Baru

1. **Prepared Statements** - Mencegah SQL Injection
2. **Password Hashing** - Password disimpan dengan bcrypt hash
3. **Session Security** - Session regeneration dan validation
4. **Error Handling** - Pesan error yang informatif tanpa expose detail sistem
5. **Input Validation** - Validasi input user

## 📋 Langkah Setup

### 1. Setup Database

Jalankan file SQL untuk membuat/update tabel admin:

```bash
# Di MySQL command line atau phpMyAdmin, import file:
mysql -u root -p GadaiCepat < setup_admin.sql
```

Atau copy-paste isi file `setup_admin.sql` ke phpMyAdmin > SQL.

### 2. Login dengan Akun Default

Setelah setup database selesai, login dengan:
- **NIK:** `admin123`
- **Password:** `admin123`

### 3. Ganti Password Default (PENTING!)

Setelah login pertama, segera ganti password untuk keamanan:

1. Buka `generate_password.php` di browser
2. Masukkan password baru
3. Copy hash yang dihasilkan
4. Update di database:
   ```sql
   UPDATE admin SET password = 'HASH_BARU_ANDA' WHERE nik = 'admin123';
   ```

## 🛠️ Menambah Admin Baru

### Cara 1: Menggunakan generate_password.php (Mudah)

1. Buka http://localhost/GadaiHp/generate_password.php
2. Masukkan password yang diinginkan
3. Copy hasil hash
4. Jalankan query SQL:
   ```sql
   INSERT INTO admin (nik, nama, password, role) VALUES 
   ('NIK_BARU', 'Nama Admin', 'HASH_DARI_GENERATE_PASSWORD', 'admin');
   ```

### Cara 2: Menggunakan PHP Script

```php
<?php
// Buat password hash
$password = 'password_anda';
$hash = password_hash($password, PASSWORD_DEFAULT);

// Insert ke database
include 'database.php';
$stmt = $conn->prepare("INSERT INTO admin (nik, nama, password, role) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $nik, $nama, $hash, $role);

$nik = '1234567890';
$nama = 'Admin Baru';
$role = 'admin';

$stmt->execute();
?>
```

## 🔄 Migrasi dari Sistem Lama

Jika Anda sudah punya data admin tanpa password hash:

1. Backup data admin lama terlebih dahulu
2. Jalankan `setup_admin.sql` (tabel akan di-update)
3. Generate password hash untuk setiap admin
4. Update password di database

## 🚨 Troubleshooting

### Error: "Kolom password tidak ada"
```sql
ALTER TABLE admin ADD COLUMN password VARCHAR(255) NOT NULL AFTER nik;
```

### Error: "NIK atau password salah" padahal sudah benar
- Pastikan password sudah di-hash dengan benar
- Cek apakah NIK benar-benar ada di database
- Pastikan kolom password cukup panjang (VARCHAR 255)

### Lupa Password Admin
1. Generate password hash baru dengan `generate_password.php`
2. Update di database:
   ```sql
   UPDATE admin SET password = 'HASH_BARU' WHERE nik = 'NIK_ADMIN';
   ```

## 📁 File-File Terkait

- `login.php` - Form login admin (sudah diperbaiki)
- `setup_admin.sql` - Script SQL untuk setup tabel admin
- `generate_password.php` - Tool untuk generate password hash
- `admin_verifikasi.php` - Panel admin (memerlukan login)

## 🔒 Best Practices

1. **Jangan pernah commit password atau hash ke Git**
2. **Ganti password default segera setelah setup**
3. **Gunakan password yang kuat** (min 8 karakter, kombinasi huruf, angka, simbol)
4. **Backup database secara berkala**
5. **Batasi akses login** (pertimbangkan IP whitelist atau 2FA di masa depan)

## 📞 Support

Jika ada masalah, hubungi developer atau buka issue di repository.

---

**Terakhir diupdate:** <?php echo date('Y-m-d'); ?>
