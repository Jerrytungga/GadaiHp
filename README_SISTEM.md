# 📱 Sistem Gadai HP & Laptop - Gadai Cepat Timika Papua

Sistem manajemen gadai HP dan Laptop berbasis web dengan fitur verifikasi admin dan **notifikasi WhatsApp otomatis**.

---

## 🌟 Fitur Utama

### **User/Nasabah:**
- ✅ Form pengajuan gadai online (HP & Laptop)
- ✅ Upload foto KTP & barang
- ✅ Cek status pengajuan dengan nomor registrasi
- ✅ **Notifikasi WhatsApp saat disetujui/ditolak** ⭐

### **Admin:**
- ✅ Panel verifikasi pengajuan
- ✅ Dashboard statistik real-time
- ✅ Approve/Reject dengan penyesuaian nominal
- ✅ **Notifikasi WhatsApp saat ada pengajuan baru** ⭐
- ✅ History lengkap semua transaksi

### **Integrasi:**
- ✅ WhatsApp Business API (Fonnte/Wablas)
- ✅ Database MySQL/MariaDB
- ✅ Upload file dengan validasi
- ✅ Responsive design (mobile-friendly)

---

## 🚀 Quick Start

### **1. Setup Database**
```bash
1. Buka phpMyAdmin
2. Buat database: GadaiCepat
3. Import file: create_data_gadai_table.sql
```

### **2. Setup WhatsApp** ⭐ **PENTING!**
Baca panduan lengkap:
- **Quick Start:** [CONFIG_WA.md](CONFIG_WA.md) ⚡ (5 menit)
- **Lengkap:** [PANDUAN_WHATSAPP_BUSINESS.md](PANDUAN_WHATSAPP_BUSINESS.md) 📚

**TL;DR:**
1. Pilih provider: **Fonnte** (recommended) atau Wablas atau Manual
2. Daftar & dapat API key
3. Edit `whatsapp_helper.php` (line 10-12):
   ```php
   private $api_provider = 'fonnte';
   private $api_key = 'YOUR_API_KEY';
   private $sender_number = '6285823091908';
   ```

### **3. Akses Website**
```
User - Form Pengajuan:
http://localhost/GadaiHp/form_gadai.php

User - Cek Status:
http://localhost/GadaiHp/cek_status.php

Admin - Panel Verifikasi:
http://localhost/GadaiHp/admin_verifikasi.php
(Login: admin / admin123)
```

---

## 📚 Dokumentasi Lengkap

### **📖 Panduan Sistem:**
1. [PANDUAN_SISTEM_VERIFIKASI.md](PANDUAN_SISTEM_VERIFIKASI.md)
   - Alur sistem lengkap
   - Struktur database
   - Cara menggunakan
   - Troubleshooting

### **📱 Panduan WhatsApp:** ⭐
2. [PANDUAN_WHATSAPP_BUSINESS.md](PANDUAN_WHATSAPP_BUSINESS.md)
   - Setup Fonnte (step-by-step)
   - Setup Wablas (step-by-step)
   - Manual mode (gratis)
   - Customize template pesan

3. [CONFIG_WA.md](CONFIG_WA.md)
   - Quick start setup (5 menit)
   - Copy-paste konfigurasi
   - Testing

---

## 🔄 Alur Kerja dengan WhatsApp

```
User Submit Form
      ↓
📱 Notif WA ke Admin ← Otomatis!
      ↓  
Data Pending di Database
      ↓
Admin Review & Verifikasi
      ↓
   Approve / Reject
      ↓
📱 Notif WA ke User ← Otomatis!
      ↓
User Cek Status
      ↓
✅ Disetujui → Datang ke kantor
❌ Ditolak → Lihat alasan
```

---

## 🎯 Contoh Notifikasi WhatsApp

### **1. Notif ke Admin (Pengajuan Baru):**
```
🔔 PENGAJUAN GADAI BARU

📋 No. Registrasi: #000001
👤 Nama: John Doe
📱 Barang: HP Samsung Galaxy S21
💰 Pengajuan: Rp 3.000.000
📞 HP: 081234567890

⏳ Status: Menunggu Verifikasi

Silakan verifikasi di:
http://localhost/GadaiHp/admin_verifikasi.php
```

### **2. Notif ke User (Disetujui):**
```
✅ PENGAJUAN DISETUJUI

Halo John Doe,

Pengajuan gadai Anda telah DISETUJUI!

📋 No. Registrasi: #000001
📱 Barang: HP Samsung Galaxy S21
💰 Pengajuan: Rp 3.000.000
✅ Disetujui: Rp 2.800.000
ℹ️ Penyesuaian: -Rp 200.000
📅 Jatuh Tempo: 11 Mei 2026

📝 Catatan Admin:
Disesuaikan berdasarkan kondisi barang

Silakan datang ke kantor kami untuk 
proses pencairan dana.

📍 Gadai Cepat Timika Papua
📞 WA: 0858-2309-1908
```

### **3. Notif ke User (Ditolak):**
```
❌ PENGAJUAN DITOLAK

Halo John Doe,

Mohon maaf, pengajuan gadai Anda DITOLAK.

📋 No. Registrasi: #000001
📱 Barang: HP Samsung Galaxy S21

📝 Alasan Penolakan:
Barang terkunci akun Google

Anda dapat mengajukan kembali setelah 
memenuhi persyaratan.

Hubungi kami untuk informasi lebih lanjut:
📞 WA: 0858-2309-1908
```

---

## 📂 File-file Penting

### **Core System:**
- `form_gadai.php` - Form pengajuan user
- `admin_verifikasi.php` - Panel verifikasi admin
- `cek_status.php` - Cek status pengajuan
- `database.php` - Koneksi database

### **WhatsApp Integration:** ⭐
- `whatsapp_helper.php` - Helper kirim notifikasi
- `log_wa.txt` - Log semua pesan WA (auto-generate)

### **Database:**
- `create_data_gadai_table.sql` - SQL tabel baru
- `update_table_verifikasi.sql` - SQL update tabel

### **Dokumentasi:**
- `PANDUAN_SISTEM_VERIFIKASI.md` - Panduan sistem
- `PANDUAN_WHATSAPP_BUSINESS.md` - Panduan WA lengkap
- `CONFIG_WA.md` - Quick start WA

---

## 💡 Setup WhatsApp - 3 Opsi

### **Opsi 1: Fonnte (Recommended)** ⭐
- **Pro:** Mudah setup, stabil, dokumentasi lengkap
- **Harga:** Rp 150K/bln (1000 pesan) + Free 100 pesan/bln
- **Setup:** 5 menit (daftar → scan QR → copy API key)
- **Link:** https://fonnte.com

### **Opsi 2: Wablas**
- **Pro:** Fitur lengkap, dashboard bagus
- **Harga:** Rp 199K/bln
- **Setup:** 5 menit
- **Link:** https://wablas.com

### **Opsi 3: Manual Mode (Gratis)**
- **Pro:** Gratis 100%, no API needed
- **Cons:** Harus kirim manual (klik link wa.me)
- **Setup:** 1 menit (edit config aja)
- **Best for:** Testing/development

**Panduan lengkap:** [PANDUAN_WHATSAPP_BUSINESS.md](PANDUAN_WHATSAPP_BUSINESS.md)

---

## 🧪 Testing

### **Test Notifikasi WhatsApp:**

**1. Test Pengajuan Baru:**
```bash
1. Buka: form_gadai.php
2. Isi form lengkap → Submit
3. CEK: Admin WA harus terima notif "PENGAJUAN GADAI BARU"
```

**2. Test Approve:**
```bash
1. Login: admin_verifikasi.php
2. Approve pengajuan
3. CEK: User harus terima notif "PENGAJUAN DISETUJUI"
```

**3. Test Reject:**
```bash
1. Reject pengajuan dengan alasan
2. CEK: User harus terima notif "PENGAJUAN DITOLAK"
```

**Jika Mode Manual:**
- Buka `log_wa.txt`
- Copy link wa.me → paste di browser

---

## 🛠️ Troubleshooting WhatsApp

### **Pesan tidak terkirim?**
✅ Cek `log_wa.txt` untuk lihat error  
✅ Pastikan API key valid (login dashboard provider)  
✅ Cek HP terhubung di dashboard  
✅ Cek saldo/kuota provider  

### **Nomor format salah?**
✅ Sistem auto-convert 08xxx → 62xxx  
✅ Pastikan nomor aktif WhatsApp  

### **API Key invalid?**
✅ Login dashboard → regenerate API key  
✅ Copy paste ulang (hati-hati spasi)  

**Panduan lengkap:** [PANDUAN_WHATSAPP_BUSINESS.md](PANDUAN_WHATSAPP_BUSINESS.md)

---

## 📝 Changelog

### **v2.0 - 11 Feb 2026** ⭐ **WhatsApp Integration**
- ✅ Notifikasi otomatis via WhatsApp
- ✅ Support Fonnte, Wablas, Manual mode
- ✅ Template pesan (Pengajuan baru, Approve, Reject)
- ✅ Logger ke log_wa.txt
- ✅ Dokumentasi lengkap WhatsApp Business

### **v1.5 - 11 Feb 2026**
- ✅ Penyesuaian nominal saat approve
- ✅ Keterangan admin untuk nasabah
- ✅ Perbandingan nominal diajukan vs disetujui

### **v1.0 - 10 Feb 2026**
- ✅ Form pengajuan gadai
- ✅ Panel admin verifikasi
- ✅ Sistem approve/reject
- ✅ Upload foto KTP & barang

---

## 🚀 Next Steps

**Untuk Production:**
1. ✅ Import database
2. ✅ **Setup WhatsApp** (baca [CONFIG_WA.md](CONFIG_WA.md))
3. ✅ Test notifikasi
4. ✅ Ganti password admin
5. ✅ Setup SSL/HTTPS
6. 🚀 **Go Live!**

**Selamat menggunakan! 🎉**

---

**© 2026 Gadai Cepat Timika Papua**  
📱 WA: 0858-2309-1908
