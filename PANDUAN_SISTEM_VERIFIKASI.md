# 📱 Sistem Gadai HP & Laptop - Gadai Cepat Timika Papua

## 🔄 Alur Sistem Verifikasi

### 1️⃣ **User Mengajukan Gadai**
- User mengunjungi website dan klik tombol untuk pengajuan gadai
- Mengisi form di `form_gadai.php`:
  - Data Nasabah (Nama, KTP, No HP, Alamat, Foto KTP)
  - Data Barang (Jenis: HP/Laptop, Merk, Tipe, Kondisi, IMEI/Serial, Foto Barang)
  - Data Pinjaman (Harga Pasar, Jumlah Pinjaman max 70%, Bunga 30% tetap, Durasi 1-3 bulan)
- Setelah submit, data tersimpan dengan **status "Pending"**
- User mendapat **Nomor Registrasi** (contoh: #000001)
- **📱 Admin otomatis terima notifikasi WhatsApp** tentang pengajuan baru

### 2️⃣ **Admin Melakukan Verifikasi**
- Admin login ke `admin_verifikasi.php`
  - Default login: **username: admin** / **password: admin123**
- Admin melihat semua pengajuan dengan status Pending
- Admin melihat detail lengkap:
  - Data nasabah, foto KTP
  - Data barang, foto barang
  - Jumlah pinjaman yang diajukan
- Admin memutuskan:
  - **✅ Setujui**:
    - Modal muncul dengan form persetujuan
    - Admin bisa **menyesuaikan nominal** yang disetujui (bisa lebih rendah/tinggi dari pengajuan)
    - Default nominal = nominal yang diajukan user
    - Max nominal = 70% dari harga pasar barang
    - Admin bisa tambahkan keterangan/catatan untuk nasabah (opsional)
    - Status berubah jadi "Disetujui"
  - **❌ Tolak**:
    - Modal muncul untuk input alasan penolakan
    - Status berubah jadi "Ditolak" + alasan penolakan tersimpan

### 3️⃣ **User Cek Status Pengajuan**
- User bisa cek status di `cek_status.php`
- Masukkan **Nomor Registrasi**
- Melihat status:
  - **⏳ Menunggu Verifikasi** - Pengajuan sedang diproses admin
  - **✅ Disetujui**:
    - Menampilkan nominal yang diajukan
    - Menampilkan nominal yang disetujui (jika berbeda, ada badge "Disesuaikan")
    - Menampilkan selisih (ditambah/dikurangi)
    - Menampilkan catatan dari admin (jika ada)
    - Bisa datang ke kantor untuk pencairan
  - **❌ Ditolak** - Pengajuan ditolak dengan alasan yang ditampilkan

---

## 📂 File-file Penting

### **Halaman User:**
1. `form_gadai.php` - Form pengajuan gadai
2. `cek_status.php` - Cek status pengajuan

### **Halaman Admin:**
1. `admin_verifikasi.php` - Panel verifikasi pengajuan (perlu login)

### **WhatsApp Integration:**
1. `whatsapp_helper.php` - Helper untuk kirim notifikasi WhatsApp
2. `log_wa.txt` - Log semua pesan WhatsApp (auto-generate)
3. `PANDUAN_WHATSAPP_BUSINESS.md` - Panduan lengkap setup WhatsApp
4. `CONFIG_WA.md` - Quick start konfigurasi WhatsApp

### **Database:**
1. `create_data_gadai_table.sql` - SQL untuk membuat tabel baru
2. `update_table_verifikasi.sql` - SQL untuk update tabel yang sudah ada

---

## 🗄️ Setup Database

### **Opsi 1: Buat Tabel Baru**
Jika belum ada tabel `data_gadai`, jalankan:
```sql
-- Import file: create_data_gadai_table.sql
```

### **Opsi 2: Update Tabel yang Sudah Ada**
Jika sudah punya tabel `data_gadai` sebelumnya, jalankan:
```sql
-- Import file: update_table_verifikasi.sql
```

### **Struktur Tabel `data_gadai`:**
- **Data Nasabah:** nama_nasabah, no_ktp, no_hp, alamat, foto_ktp
- **Data Barang:** jenis_barang (HP/Laptop), merk, tipe, kondisi, imei_serial, foto_barang
- **Data Pinjaman:** 
  - harga_pasar - Estimasi harga pasar barang
  - jumlah_pinjaman - Nominal yang **diajukan user**
  - jumlah_disetujui - Nominal yang **disetujui admin** (bisa berbeda dari pengajuan)
  - bunga (30% tetap)
  - lama_gadai (1-3 bulan)
  - tanggal_gadai, tanggal_jatuh_tempo
- **Data Verifikasi:** 
  - status - Status pengajuan
  - verified_at - Waktu verifikasi
  - verified_by - Admin yang verifikasi
  - reject_reason - Alasan penolakan (jika ditolak)
  - keterangan_admin - Catatan admin (jika disetujui)
- **Timestamp:** created_at, updated_at

### **Status Enum:**
- `Pending` - Menunggu verifikasi admin
- `Disetujui` - Pengajuan diterima
- `Ditolak` - Pengajuan ditolak
- `Ditebus` - Barang sudah ditebus
- `Dijual` - Barang dijual karena lewat tempo
- `Diperpanjang` - Gadai diperpanjang

---

## 🔐 Login Admin

**Default Credentials:**
- Username: `admin`
- Password: `admin123`

⚠️ **PENTING:** Ganti dengan sistem login yang lebih aman untuk production!

---

## 🚀 Cara Menggunakan

### **1. Setup Awal:**
```bash
1. Import SQL ke database (pilih salah satu):
   - create_data_gadai_table.sql (tabel baru)
   - update_table_verifikasi.sql (update tabel lama)

2. Pastikan folder "uploads/" ada dan writable (chmod 777)

3. Pastikan database.php terkoneksi dengan benar

4. Setup WhatsApp Business (PENTING untuk notifikasi):
   - Baca: PANDUAN_WHATSAPP_BUSINESS.md (panduan lengkap)
   - Atau: CONFIG_WA.md (quick start)
   - Edit whatsapp_helper.php:
     * Pilih provider (fonnte/wablas/manual)
     * Isi API key
     * Set nomor admin
```

### **2. Akses Website:**
```
User - Pengajuan Gadai:
http://localhost/GadaiHp/form_gadai.php

User - Cek Status:
http://localhost/GadaiHp/cek_status.php

Admin - Verifikasi:
http://localhost/GadaiHp/admin_verifikasi.php
```

### **3. Flow Lengkap:**

**Step 1:** User mengisi form di `form_gadai.php`
- Upload foto KTP & foto barang
- Isi data lengkap
- Submit → dapat Nomor Registrasi

**Step 2:** Admin login ke `admin_verifikasi.php`
- Lihat list pengajuan Pending
- Review detail pengajuan
- Approve atau Reject dengan alasan

**Step 3:** User cek status di `cek_status.php`
- Input nomor registrasi
- Lihat status: Pending/Disetujui/Ditolak
- Jika ditolak, lihat alasan penolakan

---

## 📊 Fitur Panel Admin

### **Dashboard Statistik:**
- Total Pengajuan
- Menunggu Verifikasi
- Disetujui
- Ditolak

### **Tab Navigasi:**
1. **Menunggu Verifikasi** - Pengajuan yang perlu di-review
2. **Disetujui** - History pengajuan yang diterima
3. **Ditolak** - History pengajuan yang ditolak

### **Detail Pengajuan:**
- Data nasabah lengkap
- Preview foto KTP & barang (klik untuk zoom)
- Kalkulasi pinjaman vs harga pasar
- Tanggal jatuh tempo

### **Aksi Verifikasi:**
- ✅ **Setujui**:
  - Modal popup dengan form persetujuan
  - Menampilkan nominal yang diajukan user
  - Input nominal yang disetujui (bisa disesuaikan)
  - Validasi max 70% dari harga pasar
  - Input keterangan/catatan untuk nasabah (opsional)
  - Submit untuk approve dengan nominal final
  
- ❌ **Tolak**:
  - Modal popup untuk input alasan penolakan
  - Alasan wajib diisi
  - Submit untuk reject dengan alasan

### **History Pengajuan:**
- **Tab Disetujui:**
  - Menampilkan nominal diajukan vs nominal disetujui
  - Badge "Disesuaikan" jika ada perbedaan nominal
  - Catatan admin ditampilkan (jika ada)
  
- **Tab Ditolak:**
  - Menampilkan alasan penolakan
  - Data lengkap pengajuan yang ditolak

---

## ⚙️ Ketentuan Sistem

- **Bunga:** Tetap 30% per bulan (tidak bisa diubah)
- **Pinjaman Max:** 70% dari harga pasar barang
- **Durasi:** 1-3 bulan
- **Denda:** Rp 30.000/hari setelah jatuh tempo
- **Jenis Barang:** HP dan Laptop saja

---

## 🎨 Fitur UI/UX

- ✅ Desain responsive (mobile-friendly)
- ✅ Gradient theme biru konsisten
- ✅ Validasi form real-time
- ✅ Upload file dengan preview
- ✅ Status badge dengan warna (Pending=Kuning, Approved=Hijau, Rejected=Merah)
- ✅ Animasi smooth transitions
- ✅ Rating kondisi barang dengan bintang

---

## 📞 Kontak

WhatsApp: **+62 858-2309-1908**

---

## 🔒 Catatan Keamanan

⚠️ **Untuk Production, lakukan:**
1. Ganti login admin dengan sistem authentication proper
2. Gunakan prepared statements (sudah diterapkan)
3. Validasi upload file (tipe, ukuran)
4. HTTPS untuk keamanan data
5. Backup database secara berkala
6. Enkripsi data sensitif (KTP, dll)

---

**© 2026 Gadai Cepat Timika Papua**
