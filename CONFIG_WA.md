## 🔧 KONFIGURASI WHATSAPP - QUICK START

Pilih salah satu opsi di bawah, copy-paste ke whatsapp_helper.php (baris 10-12)

---

### OPSI 1: FONNTE (Recommended)
```php
private $api_provider = 'fonnte';
private $api_key = 'PASTE_API_KEY_FONNTE_ANDA_DISINI';
private $sender_number = '6285823091908'; // Ganti dengan nomor admin Anda
```

**Cara dapat API Key Fonnte:**
1. Daftar: https://fonnte.com
2. Scan QR Code untuk connect WhatsApp
3. Dashboard → Menu API → Copy Token
4. Paste token ke $api_key

---

### OPSI 2: WABLAS
```php
private $api_provider = 'wablas';
private $api_key = 'PASTE_TOKEN_WABLAS_ANDA_DISINI';
private $sender_number = '6285823091908'; // Ganti dengan nomor admin Anda
```

**Cara dapat Token Wablas:**
1. Daftar: https://wablas.com
2. Tambah device → Scan QR Code
3. Menu API/Development → Copy Token
4. Paste token ke $api_key

---

### OPSI 3: MANUAL MODE (GRATIS - TANPA KIRIM OTOMATIS)
```php
private $api_provider = 'manual';
private $api_key = ''; // Kosongkan
private $sender_number = '6285823091908'; // Nomor admin
```

**Cara kerja:**
- Sistem tidak kirim otomatis
- Generate link wa.me → disimpan di log_wa.txt
- Buka log_wa.txt → klik link untuk kirim manual

---

## 📝 CONTOH LENGKAP

### File: whatsapp_helper.php (line 10-12)

**SEBELUM (default):**
```php
private $api_provider = 'fonnte'; // Options: 'fonnte', 'wablas', 'manual'
private $api_key = ''; // API Key dari provider
private $sender_number = '6285823091908'; // Nomor pengirim (format: 62xxx)
```

**SESUDAH (contoh pakai Fonnte):**
```php
private $api_provider = 'fonnte';
private $api_key = 'abc123def456ghi789jkl0mnop';
private $sender_number = '6281234567890';
```

---

## ✅ TESTING

### Test 1: Pengajuan Baru
1. Buka: http://localhost/GadaiHp/form_gadai.php
2. Isi form → Submit
3. Admin WA harus terima notif "PENGAJUAN GADAI BARU"

### Test 2: Approve
1. Buka: http://localhost/GadaiHp/admin_verifikasi.php
2. Login: admin / admin123
3. Approve pengajuan
4. User harus terima notif "PENGAJUAN DISETUJUI"

### Test 3: Reject
1. Di admin panel, tolak pengajuan
2. User harus terima notif "PENGAJUAN DITOLAK"

**Jika mode MANUAL:**
- Buka file: log_wa.txt
- Copy link wa.me
- Paste di browser untuk kirim

---

## 🐛 TROUBLESHOOTING

**Problem: Pesan tidak terkirim**
✅ Cek log_wa.txt untuk lihat error
✅ Pastikan API key benar (tidak ada spasi)
✅ Pastikan WA terhubung di dashboard provider
✅ Cek saldo/kuota di Fonnte/Wablas

**Problem: Format nomor salah**
✅ Gunakan format 62xxx (bukan 08xxx)
✅ Sistem auto-convert, tapi pastikan mulai dari 08 atau 62

**Problem: API Key invalid**
✅ Login ke dashboard → regenerate API key
✅ Copy paste ulang (hati-hati spasi)

---

## 💰 HARGA

### Fonnte:
- Gratis: 100 pesan/bulan
- Starter: Rp 150.000/bulan (1.000 pesan)
- Business: Rp 500.000/bulan (5.000 pesan)

### Wablas:
- Trial: Gratis (terbatas)
- Starter: Rp 199.000/bulan
- Pro: Rp 499.000/bulan

### Manual Mode:
- GRATIS 100% (tapi harus kirim manual)

---

**📌 QUICK TIP:**
Mulai dengan **Manual Mode** untuk testing gratis.
Setelah yakin sistem berjalan, upgrade ke Fonnte/Wablas.

---

Baca panduan lengkap: PANDUAN_WHATSAPP_BUSINESS.md
