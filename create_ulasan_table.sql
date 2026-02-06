-- Membuat tabel ulasan untuk database GadaiCepat

CREATE TABLE IF NOT EXISTS `ulasan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Nama` varchar(100) NOT NULL,
  `Ulasan` text NOT NULL,
  `rating` int(1) NOT NULL DEFAULT 5,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Menambahkan data contoh ulasan
INSERT INTO `ulasan` (`Nama`, `Ulasan`, `rating`) VALUES
('Ahmad Wijaya', 'Pelayanan sangat cepat dan ramah! Prosesnya benar-benar hanya 5 menit. Sangat membantu saat butuh dana mendesak.', 5),
('Sarah Putri', 'Recommended banget! Sistem COD nya juga aman dan terpercaya. Tim nya profesional.', 5),
('Budi Santoso', 'Proses gadai nya mudah dan tidak ribet. Harga taksiran juga fair sesuai pasaran.', 4),
('Linda Sari', 'Sangat terbantu dengan layanan ini. Response cepat via WA dan prosedur jelas.', 5),
('Reza Firmansyah', 'Pelayanan memuaskan, barang juga dijaga dengan baik. Terima kasih Gadai Cepat!', 5);
