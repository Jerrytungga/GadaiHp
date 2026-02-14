# Panduan SEO & Sitemap - Gadai Cepat Timika Papua

## File yang Dibuat untuk SEO

### 1. sitemap.xml
File XML yang memberitahu Google tentang semua halaman di website Anda.

**Lokasi:** `sitemap.xml` (di root folder)

**Isi:**
- Homepage (index.php) - priority 1.0
- Form Gadai (form_gadai.php) - priority 0.9
- Cek Status (cek_status.php) - priority 0.8
- Simulasi (simulasi.php) - priority 0.8
- Ulasan (ulasan.php) - priority 0.7
- Cek Data Gadai (cek_gadai.php) - priority 0.6

### 2. robots.txt
File yang memberitahu search engine apa yang boleh dan tidak boleh di-crawl.

**Lokasi:** `robots.txt` (di root folder)

**Pengaturan:**
- ✅ Allow: Semua halaman publik
- ❌ Disallow: Admin, database, file sensitif
- 📍 Sitemap: Link ke sitemap.xml

---

## Cara Submit ke Google

### Langkah 1: Domain Sudah Diatur ✅

**Domain yang digunakan:** `gadaicepat.site`

Semua file sudah diupdate dengan domain:
- ✅ sitemap.xml
- ✅ robots.txt
- ✅ index.php (canonical & og:url & JSON-LD)

### Langkah 2: Upload ke Hosting

Upload 3 file ini ke root folder (`public_html/`):
- ✅ sitemap.xml
- ✅ robots.txt
- ✅ index.php (yang sudah diupdate)

### Langkah 3: Test Sitemap

Buka di browser:
```
https://gadaicepat.site/sitemap.xml
```

Jika berhasil, Anda akan melihat XML dengan daftar URL.

### Langkah 4: Submit ke Google Search Console

1. Buka: https://search.google.com/search-console
2. Klik "Add Property" → pilih "URL prefix"
3. Masukkan URL: `https://gadaicepat.site`
4. Verifikasi kepemilikan (pilih metode HTML file atau DNS)
5. Setelah verified:
   - Klik menu "Sitemaps" (kiri)
   - Masukkan: `sitemap.xml`
   - Klik "Submit"

### Langkah 5: Test Robots.txt

Buka di browser:
```
https://gadaicepat.site/robots.txt
```

Di Google Search Console:
- Klik "Settings" → "Test robots.txt"
- Pastikan tidak ada error

---

## Cara Memeriksa Indexing Google

### Cek apakah halaman sudah terindex:

Ketik di Google:
```
site:gadaicepat.site
```

Hasil akan menampilkan semua halaman yang sudah di-index Google.

### Cek halaman spesifik:
```
site:gadaicepat.site/form_gadai.php
```

---

## Update Sitemap (Jika Ada Halaman Baru)

Edit file `sitemap.xml`, tambahkan URL baru:

```xml
<url>
  <loc>https://gadaicepat.site/halaman-baru.php</loc>
  <lastmod>2026-02-14</lastmod>
  <changefreq>weekly</changefreq>
  <priority>0.7</priority>
</url>
```

Lalu submit ulang di Google Search Console.

---

## Tips SEO Tambahan

### 1. Konsisten Update Konten
- Update blog/ulasan minimal 1x per bulan
- Tambahkan foto HP/Laptop yang digadaikan (dengan izin)

### 2. Backlink
- Daftar di direktori bisnis lokal Papua/Timika
- Minta review di Google Business Profile

### 3. Kecepatan Website
- Compress gambar (gunakan TinyPNG)
- Enable caching di .htaccess
- Gunakan CDN jika traffic tinggi

### 4. Mobile-Friendly
- Test di: https://search.google.com/test/mobile-friendly
- Website sudah responsive (Bootstrap 5)

### 5. HTTPS
- Pastikan SSL aktif (https://)
- Rumahweb biasanya menyediakan SSL gratis

---

## Monitoring

### Google Search Console (setiap minggu):
- Cek "Performance" → lihat impressions & clicks
- Cek "Coverage" → lihat error indexing
- Cek "Enhancements" → mobile usability

### Google Analytics (opsional):
- Daftar di: https://analytics.google.com
- Tambahkan tracking code di index.php
- Monitor traffic & user behavior

---

## Troubleshooting

### Sitemap tidak kebaca:
1. Cek format XML (harus valid)
2. Pastikan file di root folder
3. Cek permission file: 644

### Google tidak index:
1. Submit manual di Search Console: "URL Inspection"
2. Tunggu 1-2 minggu (Google butuh waktu)
3. Pastikan robots.txt tidak block

### Keyword tidak ranking:
1. Kompetisi tinggi → tambah long-tail keyword
2. Buat konten berkualitas → artikel/blog
3. Backlink dari website lokal Papua

---

## Kontak Support

Jika ada pertanyaan tentang SEO:
- Google Search Console Help: https://support.google.com/webmasters
- Web.dev (Google): https://web.dev

---

**Catatan:** Indexing Google butuh waktu 1-4 minggu. Bersabar dan konsisten update konten!
