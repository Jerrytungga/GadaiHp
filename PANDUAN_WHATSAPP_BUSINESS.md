# 📱 Panduan Integrasi WhatsApp Business

## 🎯 Tujuan
Mengintegrasikan sistem gadai dengan WhatsApp Business untuk mengirim notifikasi otomatis kepada:
- **Admin** → Saat ada pengajuan baru dari user
- **User/Nasabah** → Saat pengajuan disetujui atau ditolak

---

## 📋 Fitur Notifikasi Otomatis

### 1️⃣ **Notifikasi ke Admin**
Dikirim saat user submit form pengajuan gadai:
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

### 2️⃣ **Notifikasi ke User - DISETUJUI**
Dikirim saat admin approve pengajuan:
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

Silakan datang ke kantor kami untuk proses pencairan dana.

📍 Gadai Cepat Timika Papua
📞 WA: 0858-2309-1908
```

### 3️⃣ **Notifikasi ke User - DITOLAK**
Dikirim saat admin reject pengajuan:
```
❌ PENGAJUAN DITOLAK

Halo John Doe,

Mohon maaf, pengajuan gadai Anda DITOLAK.

📋 No. Registrasi: #000001
📱 Barang: HP Samsung Galaxy S21

📝 Alasan Penolakan:
Barang terkunci akun Google

Anda dapat mengajukan kembali setelah memenuhi persyaratan.

Hubungi kami untuk informasi lebih lanjut:
📞 WA: 0858-2309-1908
```

---

## 🚀 Cara Setup - 3 Opsi

### **OPSI 1: Fonnte (Recommended - Paling Mudah)** ⭐

#### **a. Daftar Akun:**
1. Buka [https://fonnte.com](https://fonnte.com)
2. Klik **"Daftar Gratis"**
3. Masukkan email dan password
4. Verifikasi email

#### **b. Koneksi WhatsApp:**
1. Login ke dashboard Fonnte
2. Klik **"Connect Device"** atau **"Tambah Device"**
3. Scan QR Code dengan WhatsApp di HP Anda
4. Tunggu sampai status **"Connected"**

#### **c. Dapatkan API Key:**
1. Di dashboard, klik menu **"API"** atau **"Settings"**
2. Copy **API Token** Anda
3. Simpan token ini (contoh: `abc123def456ghi789jkl`)

#### **d. Konfigurasi di Sistem:**
Buka file `whatsapp_helper.php` dan edit:
```php
private $api_provider = 'fonnte'; // Jangan diubah
private $api_key = 'abc123def456ghi789jkl'; // Paste API Token Anda
private $sender_number = '6285823091908'; // Nomor WA Admin (format 62xxx)
```

#### **e. Harga Fonnte:**
- **Gratis**: 100 pesan per bulan
- **Starter**: Rp 150.000/bulan - 1.000 pesan
- **Business**: Rp 500.000/bulan - 5.000 pesan
- **Enterprise**: Custom pricing

**Link**: [https://fonnte.com/pricing](https://fonnte.com/pricing)

---

### **OPSI 2: Wablas** ⭐

#### **a. Daftar Akun:**
1. Buka [https://wablas.com](https://wablas.com)
2. Klik **"Daftar"**
3. Isi form registrasi
4. Verifikasi email

#### **b. Koneksi WhatsApp:**
1. Login ke dashboard Wablas
2. Pilih **"Perangkat Saya"**
3. Klik **"Tambah Perangkat"**
4. Scan QR Code dengan WhatsApp
5. Tunggu status **"Aktif"**

#### **c. Dapatkan API Key:**
1. Klik menu **"API"** atau **"Development"**
2. Copy **Token** Anda
3. Simpan token

#### **d. Konfigurasi di Sistem:**
Buka file `whatsapp_helper.php` dan edit:
```php
private $api_provider = 'wablas'; // Ubah ke wablas
private $api_key = 'your_wablas_token_here'; // Paste Token Anda
private $sender_number = '6285823091908'; // Nomor WA Admin
```

#### **e. Harga Wablas:**
- **Trial**: Gratis (terbatas)
- **Starter**: Rp 199.000/bulan
- **Professional**: Rp 499.000/bulan
- **Enterprise**: Rp 999.000/bulan

**Link**: [https://wablas.com/pricing](https://wablas.com/pricing)

---

### **OPSI 3: Manual Mode (Gratis - Tanpa Kirim Otomatis)** 💡

Jika tidak ingin pakai layanan berbayar, gunakan mode manual:

#### **Konfigurasi:**
Buka file `whatsapp_helper.php` dan edit:
```php
private $api_provider = 'manual'; // Ubah ke manual
private $api_key = ''; // Kosongkan
private $sender_number = '6285823091908'; // Nomor WA Admin
```

#### **Cara Kerja:**
- Sistem **TIDAK** mengirim pesan otomatis
- Sistem hanya **generate link wa.me**
- Link tersimpan di file `log_wa.txt`
- Admin bisa buka link untuk kirim pesan manual

#### **Contoh Link di log_wa.txt:**
```
https://wa.me/6281234567890?text=%F0%9F%94%94+PENGAJUAN+GADAI+BARU...
```

#### **Keuntungan:**
- ✅ Gratis 100%
- ✅ Tidak butuh API key
- ✅ Tetap ada log pesan

#### **Kekurangan:**
- ❌ Tidak otomatis (harus manual klik link)
- ❌ Admin harus rajin cek `log_wa.txt`

---

## 📁 File yang Terkait

### **1. whatsapp_helper.php**
File utama untuk integrasi WhatsApp. Berisi:
- Konfigurasi API provider (Fonnte, Wablas, Manual)
- Fungsi kirim pesan
- Template pesan (Pengajuan baru, Disetujui, Ditolak)
- Logger ke file `log_wa.txt`

**Konfigurasi Penting:**
```php
private $api_provider = 'fonnte'; // atau 'wablas' atau 'manual'
private $api_key = ''; // API Key dari provider
private $sender_number = '6285823091908'; // Nomor WA Admin
```

### **2. form_gadai.php**
Mengirim notifikasi ke **Admin** saat user submit pengajuan:
```php
require_once 'whatsapp_helper.php';
// ... setelah insert data ...
$whatsapp->notifyAdminNewSubmission($data_pengajuan);
```

### **3. admin_verifikasi.php**
Mengirim notifikasi ke **User** saat admin approve/reject:
```php
require_once 'whatsapp_helper.php';
// ... setelah approve ...
$whatsapp->notifyUserApproved($data);
// ... setelah reject ...
$whatsapp->notifyUserRejected($data);
```

### **4. log_wa.txt**
File log otomatis yang mencatat semua pesan WhatsApp:
- Timestamp
- Nomor tujuan
- Isi pesan
- Response dari API

**Lokasi:** `c:\laragon\www\GadaiHp\log_wa.txt`

---

## 🧪 Testing

### **Test 1: Pengajuan Baru**
1. Buka `http://localhost/GadaiHp/form_gadai.php`
2. Isi form dan submit
3. **Cek:** Admin WA (`6285823091908`) harus terima notifikasi

### **Test 2: Approve Pengajuan**
1. Buka `http://localhost/GadaiHp/admin_verifikasi.php`
2. Login (admin/admin123)
3. Approve pengajuan
4. **Cek:** User yang ajukan harus terima notifikasi disetujui

### **Test 3: Reject Pengajuan**
1. Di admin panel, reject pengajuan
2. **Cek:** User harus terima notifikasi ditolak

### **Jika Mode Manual:**
- Buka `log_wa.txt`
- Copy link wa.me
- Paste di browser untuk kirim manual

---

## ⚙️ Troubleshooting

### **Problem: Pesan tidak terkirim**

**Solusi:**
1. Cek `log_wa.txt` untuk lihat error
2. Pastikan API key sudah benar
3. Pastikan nomor WhatsApp dalam format `62xxx` (bukan `08xxx`)
4. Cek koneksi internet
5. Cek saldo/kuota di provider (Fonnte/Wablas)

### **Problem: Nomor format salah**

File `whatsapp_helper.php` sudah auto-format:
- `0858-2309-1908` → `6285823091908` ✅
- `+62 858 2309 1908` → `6285823091908` ✅
- `858-2309-1908` → `6285823091908` ✅

### **Problem: API Key invalid**

**Cek:**
1. Login ke dashboard provider
2. Generate ulang API key
3. Copy paste ulang ke `whatsapp_helper.php`
4. Pastikan tidak ada spasi di awal/akhir

### **Problem: WhatsApp disconnect**

**Fonnte/Wablas:**
1. Login ke dashboard
2. Cek status device
3. Jika disconnect, scan QR code ulang

---

## 💰 Perbandingan Provider

| Fitur | Fonnte | Wablas | Manual |
|-------|--------|--------|--------|
| **Harga Mulai** | Rp 150K/bln | Rp 199K/bln | Gratis |
| **Pesan Gratis** | 100/bln | Trial terbatas | Unlimited (manual) |
| **Setup** | Mudah (Scan QR) | Mudah (Scan QR) | Sangat Mudah |
| **Kirim Otomatis** | ✅ Ya | ✅ Ya | ❌ Tidak |
| **Support Media** | ✅ Ya | ✅ Ya | ❌ Tidak |
| **API Stabil** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐ |
| **Dokumentasi** | Lengkap | Lengkap | N/A |
| **Recommended** | ✅ Ya | ✅ Ya | Hanya test |

---

## 📊 Template Pesan yang Tersedia

### **Di whatsapp_helper.php:**

1. **notifyAdminNewSubmission()** - Pengajuan baru ke Admin
2. **notifyUserApproved()** - Pengajuan disetujui ke User
3. **notifyUserRejected()** - Pengajuan ditolak ke User

### **Customize Template:**

Edit di file `whatsapp_helper.php` bagian:
```php
public function notifyUserApproved($data) {
    $message = "✅ *PENGAJUAN DISETUJUI*\n\n";
    // ... edit sesuai kebutuhan ...
}
```

**Tips:**
- Gunakan `*teks*` untuk bold
- Gunakan `_teks_` untuk italic
- Gunakan emoji untuk lebih menarik
- Jangan terlalu panjang (max 4096 karakter)

---

## 🔐 Keamanan

### **Jangan Expose API Key:**
- ❌ Jangan commit API key ke Git
- ❌ Jangan share API key ke orang lain
- ✅ Simpan API key di file `.env` (production)
- ✅ Regenerate API key secara berkala

### **Format Nomor:**
Sistem auto-format nomor, tapi pastikan:
- Gunakan nomor valid WhatsApp aktif
- Nomor admin harus selalu available
- Test dengan nomor sendiri dulu

---

## 📞 Support

### **Fonnte:**
- Website: [https://fonnte.com](https://fonnte.com)
- Chat: WhatsApp support di website
- Dokumentasi: [https://docs.fonnte.com](https://docs.fonnte.com)

### **Wablas:**
- Website: [https://wablas.com](https://wablas.com)
- Chat: Live chat di dashboard
- Dokumentasi: [https://wablas.com/api](https://wablas.com/api)

---

## ✅ Checklist Setup

- [ ] Pilih provider (Fonnte / Wablas / Manual)
- [ ] Daftar akun di provider
- [ ] Scan QR Code untuk koneksi WhatsApp
- [ ] Copy API Key/Token
- [ ] Edit `whatsapp_helper.php`:
  - [ ] Set `$api_provider`
  - [ ] Set `$api_key`
  - [ ] Set `$sender_number`
- [ ] Test kirim pesan dari form pengajuan
- [ ] Test approve/reject dari admin panel
- [ ] Cek `log_wa.txt` untuk memastikan log berjalan
- [ ] Top up saldo jika perlu (Fonnte/Wablas)

---

**🎉 Selesai! Sistem WhatsApp terintegrasi sempurna.**

**Update:** 11 Februari 2026  
**© Gadai Cepat Timika Papua**
