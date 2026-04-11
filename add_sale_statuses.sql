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
