-- Tambah status penjualan internal untuk alur gagal tebus
ALTER TABLE data_gadai
MODIFY COLUMN status ENUM(
    'Pending',
    'Disetujui',
    'Ditolak',
    'Lunas',
    'Diperpanjang',
    'Jatuh Tempo',
    'Gagal Tebus',
    'Barang Dijual',
    'Siap Dijual',
    'Terjual'
) DEFAULT 'Pending';

-- Samakan nama kolom verifikasi yang dipakai kode aplikasi
ALTER TABLE data_gadai
ADD COLUMN IF NOT EXISTS verified_at TIMESTAMP NULL DEFAULT NULL AFTER tanggal_jatuh_tempo;
