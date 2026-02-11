<?php
/**
 * WhatsApp Helper - Integration with WhatsApp Business API
 * Support: Fonnte, Wablas, atau WhatsApp Web API lainnya
 */

class WhatsAppHelper {
    
    // Konfigurasi - Ganti dengan API key Anda
    private $api_provider = 'fonnte'; // Options: 'fonnte', 'wablas', 'manual'
    private $api_key = 't7JRhRozh7NF1rp1dsdF'; // API Key dari provider
    private $sender_number = '6285823091908'; // Nomor pengirim (format: 62xxx)
    private $base_url = 'auto'; // Base URL: 'auto' (detect otomatis), atau set manual: 'http://192.168.1.100' atau 'https://yourdomain.com'
    
    /**
     * Kirim notifikasi WhatsApp
     * 
     * @param string $phone Nomor tujuan (format: 62xxx atau 08xxx)
     * @param string $message Pesan yang akan dikirim
     * @return array Response dari API
     */
    public function sendMessage($phone, $message) {
        // Format nomor telepon
        $phone = $this->formatPhoneNumber($phone);
        
        // Pilih provider
        switch($this->api_provider) {
            case 'fonnte':
                return $this->sendViaFonnte($phone, $message);
            case 'wablas':
                return $this->sendViaWablas($phone, $message);
            case 'manual':
            default:
                return $this->sendManual($phone, $message);
        }
    }
    
    /**
     * Format nomor telepon ke format internasional
     */
    private function formatPhoneNumber($phone) {
        // Hapus karakter non-numeric
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Jika diawali 0, ganti dengan 62
        if (substr($phone, 0, 1) == '0') {
            $phone = '62' . substr($phone, 1);
        }
        
        // Jika belum ada 62, tambahkan
        if (substr($phone, 0, 2) != '62') {
            $phone = '62' . $phone;
        }
        
        return $phone;
    }
    
    /**
     * Get base URL untuk link di pesan WhatsApp
     */
    private function getBaseUrl() {
        // Jika sudah di-set manual, gunakan yang manual
        if ($this->base_url !== 'auto') {
            return rtrim($this->base_url, '/');
        }
        
        // Auto-detect
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $base_url = "{$scheme}://{$host}";
        
        // Jika localhost, coba gunakan IP lokal
        if ($host === 'localhost' || $host === '127.0.0.1') {
            $local_ip = gethostbyname(gethostname());
            if ($local_ip && $local_ip !== '127.0.0.1' && filter_var($local_ip, FILTER_VALIDATE_IP)) {
                $base_url = "http://{$local_ip}";
            }
        }
        
        return $base_url;
    }
    
    /**
     * Kirim via Fonnte API
     * Daftar: https://fonnte.com
     */
    private function sendViaFonnte($phone, $message) {
        if (empty($this->api_key)) {
            return ['success' => false, 'message' => 'API Key Fonnte belum diset'];
        }
        
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.fonnte.com/send',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array(
                'target' => $phone,
                'message' => $message,
                'countryCode' => '62'
            ),
            CURLOPT_HTTPHEADER => array(
                'Authorization: ' . $this->api_key
            ),
        ));
        
        $response = curl_exec($curl);
        curl_close($curl);
        
        // Log response
        $this->logMessage($phone, $message, $response);
        
        return json_decode($response, true);
    }
    
    /**
     * Kirim via Wablas API
     * Daftar: https://wablas.com
     */
    private function sendViaWablas($phone, $message) {
        if (empty($this->api_key)) {
            return ['success' => false, 'message' => 'API Key Wablas belum diset'];
        }
        
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://console.wablas.com/api/send-message',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array(
                'phone' => $phone,
                'message' => $message
            ),
            CURLOPT_HTTPHEADER => array(
                'Authorization: ' . $this->api_key
            ),
        ));
        
        $response = curl_exec($curl);
        curl_close($curl);
        
        // Log response
        $this->logMessage($phone, $message, $response);
        
        return json_decode($response, true);
    }
    
    /**
     * Manual mode - Generate wa.me link
     * Tidak mengirim otomatis, hanya generate link
     */
    private function sendManual($phone, $message) {
        $encoded_message = urlencode($message);
        $link = "https://wa.me/{$phone}?text={$encoded_message}";
        
        // Log link
        $this->logMessage($phone, $message, "Manual link: " . $link);
        
        return [
            'success' => true,
            'message' => 'Manual mode - Link generated',
            'link' => $link
        ];
    }
    
    /**
     * Log pesan ke file
     */
    private function logMessage($phone, $message, $response) {
        $log_file = __DIR__ . '/log_wa.txt';
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] To: {$phone}\nMessage: {$message}\nResponse: " . print_r($response, true) . "\n\n";
        file_put_contents($log_file, $log_entry, FILE_APPEND);
    }
    
    /**
     * Template pesan untuk pengajuan baru (ke Admin)
     */
    public function notifyAdminNewSubmission($data) {
        $message = "🔔 *PENGAJUAN GADAI BARU*\n\n";
        $message .= "📋 No. Registrasi: #" . str_pad($data['id'], 6, '0', STR_PAD_LEFT) . "\n";
        $message .= "👤 Nama: {$data['nama_nasabah']}\n";
        $message .= "📱 Barang: {$data['jenis_barang']} {$data['merk']} {$data['tipe']}\n";
        $message .= "💰 Pengajuan: Rp " . number_format($data['jumlah_pinjaman'], 0, ',', '.') . "\n";
        $message .= "📞 HP: {$data['no_hp']}\n\n";
        $message .= "⏳ Status: Menunggu Verifikasi\n\n";
        $message .= "Klik link untuk verifikasi:\n";
        $message .= $this->getBaseUrl() . "/GadaiHp/admin_verifikasi.php";
        
        return $this->sendMessage($this->sender_number, $message);
    }
    
    /**
     * Template pesan untuk pengajuan disetujui (ke User)
     */
    public function notifyUserApproved($data) {
        $message = "✅ *PENGAJUAN DISETUJUI*\n\n";
        $message .= "Halo {$data['nama_nasabah']},\n\n";
        $message .= "Pengajuan gadai Anda telah *DISETUJUI*!\n\n";
        $message .= "📋 No. Registrasi: #" . str_pad($data['id'], 6, '0', STR_PAD_LEFT) . "\n";
        $message .= "📱 Barang: {$data['jenis_barang']} {$data['merk']} {$data['tipe']}\n";
        
        if ($data['jumlah_disetujui']) {
            $message .= "💰 Pengajuan: Rp " . number_format($data['jumlah_pinjaman'], 0, ',', '.') . "\n";
            $message .= "✅ *Disetujui: Rp " . number_format($data['jumlah_disetujui'], 0, ',', '.') . "*\n";
            
            $selisih = $data['jumlah_disetujui'] - $data['jumlah_pinjaman'];
            if ($selisih != 0) {
                $message .= "ℹ️ Penyesuaian: ";
                $message .= $selisih > 0 ? "+" : "";
                $message .= "Rp " . number_format(abs($selisih), 0, ',', '.') . "\n";
            }
        } else {
            $message .= "💰 Pinjaman: Rp " . number_format($data['jumlah_pinjaman'], 0, ',', '.') . "\n";
        }
        
        $message .= "📅 Jatuh Tempo: " . date('d F Y', strtotime($data['tanggal_jatuh_tempo'])) . "\n\n";
        
        if (!empty($data['keterangan_admin'])) {
            $message .= "📝 Catatan Admin:\n";
            $message .= $data['keterangan_admin'] . "\n\n";
        }
        
        $message .= "Silakan datang ke kantor kami untuk proses pencairan dana.\n\n";
        $message .= "📍 Gadai Cepat Timika Papua\n";
        $message .= "📞 WA: 0858-2309-1908";
        
        return $this->sendMessage($data['no_hp'], $message);
    }
    
    /**
     * Template pesan untuk pengajuan ditolak (ke User)
     */
    public function notifyUserRejected($data) {
        $message = "❌ *PENGAJUAN DITOLAK*\n\n";
        $message .= "Halo {$data['nama_nasabah']},\n\n";
        $message .= "Mohon maaf, pengajuan gadai Anda *DITOLAK*.\n\n";
        $message .= "📋 No. Registrasi: #" . str_pad($data['id'], 6, '0', STR_PAD_LEFT) . "\n";
        $message .= "📱 Barang: {$data['jenis_barang']} {$data['merk']} {$data['tipe']}\n\n";
        
        if (!empty($data['reject_reason'])) {
            $message .= "📝 Alasan Penolakan:\n";
            $message .= $data['reject_reason'] . "\n\n";
        }
        
        $message .= "Anda dapat mengajukan kembali setelah memenuhi persyaratan.\n\n";
        $message .= "Hubungi kami untuk informasi lebih lanjut:\n";
        $message .= "📞 WA: 0858-2309-1908";
        
        return $this->sendMessage($data['no_hp'], $message);
    }

    /**
     * Template pesan untuk perpanjangan gadai (ke User)
     */
    public function notifyUserExtension($data, $new_due_date) {
        $message = "🔁 *PERPANJANGAN GADAI BERHASIL*\n\n";
        $message .= "Halo {$data['nama_nasabah']},\n\n";
        $message .= "Permintaan perpanjangan gadai Anda telah kami terima.\n\n";
        $message .= "📋 No. Registrasi: #" . str_pad($data['id'], 6, '0', STR_PAD_LEFT) . "\n";
        $message .= "📱 Barang: {$data['jenis_barang']} {$data['merk']} {$data['tipe']}\n";
        $message .= "📅 Jatuh Tempo Baru: " . date('d F Y', strtotime($new_due_date)) . "\n\n";
        $message .= "Kami akan menghubungi Anda bila ada informasi tambahan.\n\n";
        $message .= "📞 WA: 0858-2309-1908";

        return $this->sendMessage($data['no_hp'], $message);
    }

    /**
     * Template pesan untuk perpanjangan gadai (ke Admin)
     */
    public function notifyAdminExtension($data, $new_due_date) {
        $message = "🔁 *PERPANJANGAN GADAI*\n\n";
        $message .= "📋 No. Registrasi: #" . str_pad($data['id'], 6, '0', STR_PAD_LEFT) . "\n";
        $message .= "👤 Nama: {$data['nama_nasabah']}\n";
        $message .= "📱 Barang: {$data['jenis_barang']} {$data['merk']} {$data['tipe']}\n";
        $message .= "📅 Jatuh Tempo Baru: " . date('d F Y', strtotime($new_due_date)) . "\n\n";
        $message .= "Buka detail di:\n";
        $message .= $this->getBaseUrl() . "/GadaiHp/admin_verifikasi.php";

        return $this->sendMessage($this->sender_number, $message);
    }

    /**
     * Template pesan untuk pelunasan gadai (ke User)
     */
    public function notifyUserPelunasan($data) {
        $message = "💰 *PELUNASAN GADAI*\n\n";
        $message .= "Halo {$data['nama_nasabah']},\n\n";
        $message .= "Permintaan pelunasan gadai Anda telah kami terima.\n\n";
        $message .= "📋 No. Registrasi: #" . str_pad($data['id'], 6, '0', STR_PAD_LEFT) . "\n";
        $message .= "📱 Barang: {$data['jenis_barang']} {$data['merk']} {$data['tipe']}\n\n";
        $message .= "Tim kami akan menghubungi Anda untuk proses selanjutnya.\n\n";
        $message .= "📞 WA: 0858-2309-1908";

        return $this->sendMessage($data['no_hp'], $message);
    }

    /**
     * Template pesan untuk pelunasan gadai (ke Admin)
     */
    public function notifyAdminPelunasan($data) {
        $message = "💰 *PENGAJUAN PELUNASAN*\n\n";
        $message .= "📋 No. Registrasi: #" . str_pad($data['id'], 6, '0', STR_PAD_LEFT) . "\n";
        $message .= "👤 Nama: {$data['nama_nasabah']}\n";
        $message .= "📱 Barang: {$data['jenis_barang']} {$data['merk']} {$data['tipe']}\n\n";
        $message .= "Buka detail di:\n";
        $message .= $this->getBaseUrl() . "/GadaiHp/admin_verifikasi.php";

        return $this->sendMessage($this->sender_number, $message);
    }

    /**
     * Template pesan reminder 3 hari sebelum jatuh tempo (ke User)
     */
    public function notifyUserDueSoon($data) {
        $message = "⏰ *REMINDER JATUH TEMPO*\n\n";
        $message .= "Halo {$data['nama_nasabah']},\n\n";
        $message .= "Jatuh tempo gadai Anda tinggal *3 hari lagi*.\n\n";
        $message .= "📋 No. Registrasi: #" . str_pad($data['id'], 6, '0', STR_PAD_LEFT) . "\n";
        $message .= "📱 Barang: {$data['jenis_barang']} {$data['merk']} {$data['tipe']}\n";
        $message .= "📅 Jatuh Tempo: " . date('d F Y', strtotime($data['tanggal_jatuh_tempo'])) . "\n\n";
        $message .= "Silakan siapkan pelunasan atau hubungi kami untuk perpanjangan.\n\n";
        $message .= "Cek status di:\n";
        $message .= $this->getBaseUrl() . "/GadaiHp/cek_status.php?no_registrasi=" . $data['id'] . "\n\n";
        $message .= "📞 WA: 0858-2309-1908";

        return $this->sendMessage($data['no_hp'], $message);
    }

    /**
     * Template pesan reminder terlambat (H+1 sampai H+7)
     */
    public function notifyUserOverdue($data, $days_overdue) {
        $pokok = !empty($data['jumlah_disetujui']) ? (float)$data['jumlah_disetujui'] : (float)$data['jumlah_pinjaman'];
        $bunga = (float)$data['bunga'];
        $lama = (int)$data['lama_gadai'];
        $bunga_total = $pokok * ($bunga / 100) * $lama;
        $denda_harian = 30000;
        $denda_total = $denda_harian * $days_overdue;
        $total_tebus = $pokok + $bunga_total + $denda_total;

        $message = "⚠️ *REMINDER TERLAMBAT*\n\n";
        $message .= "Halo {$data['nama_nasabah']},\n\n";
        $message .= "Gadai Anda sudah lewat jatuh tempo *{$days_overdue} hari*.\n\n";
        $message .= "📋 No. Registrasi: #" . str_pad($data['id'], 6, '0', STR_PAD_LEFT) . "\n";
        $message .= "📱 Barang: {$data['jenis_barang']} {$data['merk']} {$data['tipe']}\n";
        $message .= "📅 Jatuh Tempo: " . date('d F Y', strtotime($data['tanggal_jatuh_tempo'])) . "\n\n";
        $message .= "Rincian Tebus:\n";
        $message .= "- Pokok: Rp " . number_format($pokok, 0, ',', '.') . "\n";
        $message .= "- Bunga: Rp " . number_format($bunga_total, 0, ',', '.') . " ({$bunga}% x {$lama} bln)\n";
        $message .= "- Denda Harian: Rp " . number_format($denda_harian, 0, ',', '.') . " x {$days_overdue} hari = Rp " . number_format($denda_total, 0, ',', '.') . "\n";
        $message .= "*Total Tebus: Rp " . number_format($total_tebus, 0, ',', '.') . "*\n\n";
        $message .= "Segera lakukan pelunasan atau ajukan perpanjangan.\n\n";
        $message .= "Cek status di:\n";
        $message .= $this->getBaseUrl() . "/GadaiHp/cek_status.php?no_registrasi=" . $data['id'] . "\n\n";
        $message .= "📞 WA: 0858-2309-1908";

        return $this->sendMessage($data['no_hp'], $message);
    }
}

// Inisialisasi helper
$whatsapp = new WhatsAppHelper();
