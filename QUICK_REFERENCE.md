# 🎯 QUICK REFERENCE - Sistem Gadai dengan WhatsApp

## 📱 Setup WhatsApp - 5 Menit

### **Step 1: Pilih Provider**
- **Fonnte** (Recommended): https://fonnte.com - Rp 150K/bln
- **Wablas**: https://wablas.com - Rp 199K/bln
- **Manual** (Gratis): Tidak otomatis, harus klik link

### **Step 2: Daftar & Connect**
1. Daftar akun
2. Scan QR Code dengan WhatsApp HP
3. Copy API Key/Token

### **Step 3: Konfigurasi**
Edit file: `whatsapp_helper.php` (line 10-12)

**Fonnte:**
```php
private $api_provider = 'fonnte';
private $api_key = 'PASTE_API_KEY_DISINI';
private $sender_number = '6285823091908';
```

**Wablas:**
```php
private $api_provider = 'wablas';
private $api_key = 'PASTE_TOKEN_DISINI';
private $sender_number = '6285823091908';
```

**Manual (Gratis):**
```php
private $api_provider = 'manual';
private $api_key = '';
private $sender_number = '6285823091908';
```

### **Step 4: Test**
1. Submit form gadai → Admin harus terima notif WA
2. Approve pengajuan → User harus terima notif WA
3. Cek `log_wa.txt` untuk lihat log

---

## 🔄 Alur Sistem

```
USER SUBMIT FORM
      ↓
📱 Notif WA → Admin ✅
      ↓
ADMIN REVIEW
      ↓
APPROVE / REJECT
      ↓
📱 Notif WA → User ✅
```

---

## 🔗 Link Penting

**User:**
- Form: `http://localhost/GadaiHp/form_gadai.php`
- Cek Status: `http://localhost/GadaiHp/cek_status.php`

**Admin:**
- Panel: `http://localhost/GadaiHp/admin_verifikasi.php`
- Login: `admin` / `admin123`

**File:**
- Config WA: `whatsapp_helper.php`
- Log WA: `log_wa.txt`

---

## 📝 Template Notif WhatsApp

### **Ke Admin (Pengajuan Baru):**
```
🔔 PENGAJUAN GADAI BARU
No: #000001
Nama: John Doe
Barang: HP Samsung S21
Pengajuan: Rp 3.000.000
```

### **Ke User (Disetujui):**
```
✅ PENGAJUAN DISETUJUI
No: #000001
Disetujui: Rp 2.800.000
Jatuh Tempo: 11 Mei 2026
Catatan: ...
```

### **Ke User (Ditolak):**
```
❌ PENGAJUAN DITOLAK
No: #000001
Alasan: Barang terkunci akun
```

---

## 🧪 Checklist Testing

- [ ] User submit form → Admin terima notif WA
- [ ] Admin approve → User terima notif disetujui
- [ ] Admin reject → User terima notif ditolak
- [ ] Cek `log_wa.txt` ada record pesan
- [ ] Format nomor correct (62xxx)

---

## 🛠️ Troubleshooting

**Pesan tidak terkirim:**
- Cek `log_wa.txt`
- Pastikan API key benar
- Cek HP connected di dashboard
- Cek saldo provider

**Nomor salah:**
- Gunakan 62xxx (bukan 08xxx)
- Sistem auto-convert

**API Key invalid:**
- Login dashboard → regenerate
- Copy paste ulang (no space)

---

## 💰 Harga Provider

| Provider | Gratis | Berbayar |
|----------|--------|----------|
| Fonnte | 100 msg/bln | Rp 150K (1000 msg) |
| Wablas | Trial | Rp 199K/bln |
| Manual | ✅ Unlimited | - |

---

## 📚 Dokumentasi Lengkap

1. **Quick:** [CONFIG_WA.md](CONFIG_WA.md)
2. **Lengkap:** [PANDUAN_WHATSAPP_BUSINESS.md](PANDUAN_WHATSAPP_BUSINESS.md)
3. **Sistem:** [PANDUAN_SISTEM_VERIFIKASI.md](PANDUAN_SISTEM_VERIFIKASI.md)
4. **Overview:** [README_SISTEM.md](README_SISTEM.md)

---

**© 2026 Gadai Cepat Timika Papua**
