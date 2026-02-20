# 🚀 PANDUAN SETUP DATABASE OTOMATIS

## Setup Database Lengkap - Satu Klik!

Dokumen ini menjelaskan cara menggunakan halaman **Auto Setup Database** untuk setup sistem Gadai Cepat Timika secara otomatis.

---

## 📋 Apa yang Akan Di-Setup?

Halaman `setup_database.php` akan otomatis membuat:

### 1. **Database**
- Database: `GadaiCepat`
- Character Set: UTF8MB4 (support emoji & unicode)

### 2. **Tabel-Tabel**
- ✅ `admin` - Data administrator sistem
- ✅ `data_gadai` - Data transaksi gadai
- ✅ `ulasan` - Review dari pelanggan
- ✅ `wa_log` - Log notifikasi WhatsApp
- ✅ `payments` - Tracking pembayaran
- ✅ `v_gadai_aktif` - View untuk monitoring

### 3. **Data Default**
- ✅ User admin (NIK: admin123, Password: admin123)
- ✅ Sample ulasan pelanggan (3 ulasan)
- ✅ Optimasi tabel

---

## 🎯 Cara Menggunakan

### Method 1: Via Admin Tools
1. Buka browser, akses: `http://localhost/GadaiHp/admin_tools.php`
2. Klik tombol **"Auto Setup Database"** (tombol kuning dengan icon 🚀)
3. Tunggu proses selesai (biasanya 2-5 detik)
4. Lihat hasilnya - jika semua ✅ hijau, setup berhasil!

### Method 2: Akses Langsung
```
http://localhost/GadaiHp/setup_database.php
```

---

## 📊 Memahami Hasil Setup

### Progress Bar
- **100% Hijau** = Semua berhasil (Perfect!)
- **50-99% Kuning** = Sebagian berhasil (perlu cek error)
- **< 50% Merah** = Banyak gagal (cek koneksi database)

### Step-by-Step Details
Setiap langkah akan menampilkan:
- ✅ **Success** (hijau) = Berhasil
- ❌ **Failed** (merah) = Gagal (lihat detail error)

---

## 🔑 Kredensial Login Default

Setelah setup berhasil, gunakan kredensial ini untuk login:

```
URL     : http://localhost/GadaiHp/login.php
NIK     : admin123
Password: admin123
Role    : superadmin
```

**⚠️ PENTING:** Segera ganti password setelah login pertama kali!

---

## 🛠️ Troubleshooting

### Problem 1: Koneksi MySQL Gagal
**Error:** "GAGAL koneksi MySQL"

**Solusi:**
1. Pastikan Laragon sudah running (Apache & MySQL)
2. Klik tombol "Start All" di Laragon
3. Cek konfigurasi di `database.php`:
   ```php
   $host = 'localhost';
   $user = 'root';
   $pass = '';  // Pastikan kosong untuk Laragon default
   ```

### Problem 2: Database Sudah Ada
**Error:** Tabel sudah ada atau data duplikat

**Solusi:**
1. Klik tombol **"Reset & Setup Ulang"** (merah)
2. Atau manual drop database via phpMyAdmin
3. Jalankan setup lagi

### Problem 3: Permission Denied
**Error:** "Access denied for user"

**Solusi:**
1. Cek user MySQL di `database.php`
2. Pastikan user `root` tidak memiliki password
3. Atau sesuaikan dengan konfigurasi MySQL Anda

### Problem 4: Beberapa Step Gagal
**Solusi:**
1. Lihat detail error pada step yang gagal
2. Klik "Coba Lagi" untuk retry
3. Jika tetap gagal, cek error log MySQL

---

## 🔄 Reset & Setup Ulang

Jika ingin menghapus semua data dan setup dari awal:

1. **Via Website:**
   - Klik tombol **"Reset & Setup Ulang"** (merah)
   - Konfirmasi "OK"
   - Setup akan otomatis berjalan ulang

2. **Via phpMyAdmin:**
   ```sql
   DROP DATABASE IF EXISTS GadaiCepat;
   ```
   Kemudian akses `setup_database.php` lagi

---

## 📁 Struktur Tabel yang Dibuat

### Tabel: `admin`
```sql
- id (Primary Key)
- nik (Unique)
- nama
- password (Hashed)
- role (superadmin/admin/staff)
- email
- telepon
- created_at
- last_login
- is_active
```

### Tabel: `data_gadai`
```sql
- id (Primary Key)
- nama, nik, alamat, no_wa
- jenis_barang, merk_barang, spesifikasi
- nilai_taksiran, jumlah_pinjaman
- bunga, lama_gadai
- tanggal_gadai, tanggal_jatuh_tempo
- status (Pending/Disetujui/Ditolak/Lunas/dll)
- foto_ktp, foto_barang
- catatan_admin
- ... dan lainnya
```

### Tabel: `ulasan`
```sql
- id (Primary Key)
- nama
- rating (1-5)
- ulasan
- is_approved
- tanggal
```

### Tabel: `wa_log`
```sql
- id (Primary Key)
- gadai_id
- phone_number
- message_type
- status (pending/sent/failed)
- sent_at
```

### Tabel: `payments`
```sql
- id (Primary Key)
- gadai_id
- payment_type (pelunasan/perpanjangan/denda)
- amount
- payment_date
- payment_method
- bukti_transfer
```

---

## ✨ Fitur Auto Setup

### 1. **Intelligent Checking**
- Cek apakah database sudah ada
- Cek apakah tabel sudah ada (CREATE IF NOT EXISTS)
- Cek apakah admin sudah ada (tidak duplikat)

### 2. **Beautiful UI**
- Progress bar real-time
- Animated steps (slide-in animation)
- Color-coded status (hijau/merah)
- Responsive design

### 3. **Auto Redirect**
- Setelah sukses, auto confirm redirect ke login
- Atau klik manual "Login Sekarang"

### 4. **Safety Features**
- Konfirmasi sebelum reset database
- Tidak overwrite data existing
- Error handling comprehensive

---

## 📝 Checklist Setelah Setup

- [ ] Setup database berhasil (100% hijau)
- [ ] Login dengan admin123/admin123
- [ ] Ganti password default
- [ ] Test buat data gadai baru
- [ ] Test approval/rejection
- [ ] Cek notifikasi WhatsApp (jika sudah setup)
- [ ] Backup database pertama kali

---

## 🔐 Keamanan

### Password Hashing
Password di-hash menggunakan `password_hash()` dengan algoritma bcrypt:
```php
$hashed = password_hash('admin123', PASSWORD_DEFAULT);
// Hasil: $2y$10$...
```

### Session Security
- Session regeneration setelah login
- Timeout 30 menit
- Cek authentication di setiap halaman admin

---

## 📞 Support

Jika mengalami masalah:

1. **Cek Error Details** - Lihat pesan error di setiap step
2. **Check Logs** - Buka browser console (F12)
3. **MySQL Logs** - Cek error log MySQL di Laragon
4. **Test Connection** - Gunakan `test_login.php` untuk test

---

## 🎓 Tips & Best Practices

1. **Jalankan Setup PERTAMA KALI saja**
   - Setup ini hanya perlu dijalankan sekali
   - Setelah itu gunakan menu admin untuk manage data

2. **Backup Database Berkala**
   ```bash
   # Via Laragon Menu -> MySQL -> Export
   # Atau via phpMyAdmin
   ```

3. **Jangan Share Kredensial Default**
   - Segera ganti password admin123
   - Buat admin baru untuk user lain

4. **Monitor Database Size**
   - Tabel `wa_log` bisa membesar cepat
   - Periodic cleanup data lama

---

## 🚀 Next Steps

Setelah setup selesai:

1. ✅ Login ke sistem
2. ✅ Ganti password
3. ✅ Setup WhatsApp API (lihat PANDUAN_WHATSAPP_BUSINESS.md)
4. ✅ Test form gadai
5. ✅ Configure notifikasi
6. ✅ Mulai input data nasabah

---

**Happy Coding! 🎉**

*Last Updated: 2026-02-20*
*Version: 1.0*
