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
     * Template pesan ke user setelah pengajuan diterima (menunggu verifikasi)
     */
    public function notifyUserSubmissionReceived($data) {
        $message = "🔔 *PENGAJUAN GADAI DITERIMA (MENUNGGU VERIFIKASI)*\n\n";
        $message .= "Halo " . ($data['nama_nasabah'] ?? '-') . ",\n\n";
        $message .= "Terima kasih telah mengajukan gadai. Pengajuan Anda telah kami terima dan akan segera diverifikasi oleh admin.\n\n";
        $message .= "📋 *No. Registrasi:* #" . str_pad($data['id'], 6, '0', STR_PAD_LEFT) . "\n";

        // Barang details
        $item = trim((($data['merk'] ?? '') . ' ' . ($data['tipe'] ?? '')));
        $message .= "📱 *Barang:* " . ($data['jenis_barang'] ?? '-') . (!empty($item) ? " - {$item}" : "") . "\n";
        if (!empty($data['imei_serial'])) {
            $message .= "🔢 IMEI/Serial: " . $data['imei_serial'] . "\n";
        }
        if (!empty($data['kelengkapan_hp'])) {
            $message .= "📦 Kelengkapan: " . $data['kelengkapan_hp'] . "\n";
        }
        if (!empty($data['kondisi'])) {
            $message .= "🛠️ Kondisi: " . $data['kondisi'] . "\n";
        }
        $message .= "\n";

        if (!empty($data['jumlah_pinjaman'])) {
            $message .= "💰 *Jumlah Pengajuan:* Rp " . number_format($data['jumlah_pinjaman'], 0, ',', '.') . "\n";
        }
        if (!empty($data['tanggal_jatuh_tempo'])) {
            $message .= "📅 *Jatuh Tempo (estimasi):* " . date('d F Y', strtotime($data['tanggal_jatuh_tempo'])) . "\n";
        }

        $message .= "\nKami akan menghubungi Anda melalui WhatsApp setelah verifikasi selesai.\n";
        $message .= "Jika ada pertanyaan, hubungi: 0858-2309-1908\n\n";
        $message .= "Hormat kami,\nGadai Cepat Timika Papua";

        return $this->sendMessage($data['no_hp'], $message);
    }
    
    /**
     * Template pesan untuk pengajuan disetujui (ke User)
     */
    public function notifyUserApproved($data) {
        // Calculate financial breakdown
        $pokok = !empty($data['jumlah_disetujui']) ? (float)$data['jumlah_disetujui'] : (float)$data['jumlah_pinjaman'];
        $harga_pasar = !empty($data['harga_pasar']) ? (float)$data['harga_pasar'] : 0.0;
        $bunga_pct = isset($data['bunga']) ? (float)$data['bunga'] : 0.0;
        $lama = isset($data['lama_gadai']) ? (int)$data['lama_gadai'] : 0;
        $bunga_total = $pokok * ($bunga_pct / 100) * $lama;
    $admin_fee = round($pokok * 0.01); // 1% biaya admin, rounded to nearest rupiah
        $denda = !empty($data['denda_terakumulasi']) ? (float)$data['denda_terakumulasi'] : 0.0;
        $total_tebus_calc = $pokok + $bunga_total + $admin_fee + $denda;

        // Build message matching provided template
        $pengajuan_display = isset($data['jumlah_pinjaman']) ? (float)$data['jumlah_pinjaman'] : 0.0;
        $disetujui_display = isset($data['jumlah_disetujui']) ? (float)$data['jumlah_disetujui'] : $pengajuan_display;
      
    $message = "✅ *PENGAJUAN DISETUJUI*\n\n";
    $message .= "Halo {$data['nama_nasabah']},\n\n";
        $message .= "📋 *No. Registrasi:* #" . str_pad($data['id'], 6, '0', STR_PAD_LEFT) . "\n";
        $item = trim((string)($data['merk'] . ' ' . $data['tipe']));
        $message .= "📱 *Barang:* {$data['jenis_barang']}" . (!empty($item) ? " - {$item}" : "") . "\n";
        $message .= "💰 *Pengajuan:* Rp " . number_format($pengajuan_display, 0, ',', '.') . "\n";
        $message .= "✅ *Disetujui:* Rp " . number_format($disetujui_display, 0, ',', '.') . "\n";
        if (!empty($data['tanggal_jatuh_tempo'])) {
            $message .= "📅 *Jatuh Tempo:* " . date('d F Y', strtotime($data['tanggal_jatuh_tempo'])) . "\n\n";
        }
        $message .= "*Kelengkapan:* " . (!empty($data['kelengkapan_hp']) ? $data['kelengkapan_hp'] : '-') . "\n";
        $message .= "*Kondisi:* " . (!empty($data['kondisi']) ? $data['kondisi'] : '-') . "\n";
        $message .= "*IMEI/Serial Number:* " . (!empty($data['imei_serial']) ? $data['imei_serial'] : '-') . "\n\n";

        $message .= "*Rincian Pembiayaan:*\n";
        $message .= "- *Pokok pinjaman:* Rp " . number_format($disetujui_display, 0, ',', '.') . "\n";
        $message .= "- *Bunga:* Rp " . number_format($bunga_total, 0, ',', '.') . " ({$bunga_pct}% x {$lama} bulan)\n";
        $message .= "- *Biaya administrasi (1%):* Rp " . number_format($admin_fee, 0, ',', '.') . "\n\n";

        $message .= "*Perkiraan Total Yang Harus Dibayar:* Rp " . number_format($total_tebus_calc, 0, ',', '.') . "\n\n";
        $message .= "📝 *Catatan Admin:* " . (!empty($data['keterangan_admin']) ? $data['keterangan_admin'] : '-') . "\n\n";

        $message .= "Silakan datang ke kantor kami untuk proses pencairan dana.\n\n";
        $message .= "📍 Gadai Cepat Timika Papua\n";
        $message .= "📞 WA: 0858-2309-1908\n\n";
     

        return $this->sendMessage($data['no_hp'], $message);
    }
    
    /**
     * Template pesan untuk pengajuan ditolak (ke User)
     */
    public function notifyUserRejected($data) {
        $message = "❌ *PENGAJUAN DITOLAK*\n\n";
        $message .= "Halo {$data['nama_nasabah']},\n\n";
        $message .= "Mohon maaf, pengajuan gadai Anda *DITOLAK*.\n\n";
        $message .= "📋 *No. Registrasi:* #" . str_pad($data['id'], 6, '0', STR_PAD_LEFT) . "\n";
        $item = trim((string)($data['merk'] . ' ' . $data['tipe']));
        $message .= "📱 *Barang:* {$data['jenis_barang']}" . (!empty($item) ? " - {$item}" : "") . "\n";
        if (!empty($data['kelengkapan_hp'])) {
            $message .= "- *Kelengkapan:* {$data['kelengkapan_hp']}\n";
        }
        if (!empty($data['kondisi'])) {
            $message .= "- *Kemulusan/Kondisi:* {$data['kondisi']}\n";
        }
        $message .= "\n";

        if (!empty($data['reject_reason'])) {
            $message .= "📝 *Alasan Penolakan:*\n";
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
     * Notify admin when a user uploads proof for perpanjangan (pending review)
     */
    public function notifyAdminPerpanjanganUpload($data, $amount = null, $buktiFile = null) {
        $message = "🔔 *UPLOAD BUKTI PERPANJANGAN*\n\n";
        $message .= "📋 No. Registrasi: #" . str_pad($data['id'], 6, '0', STR_PAD_LEFT) . "\n";
        $message .= "👤 Nama: {$data['nama_nasabah']}\n";
        $message .= "📱 Barang: {$data['jenis_barang']} {$data['merk']} {$data['tipe']}\n";
        if (!empty($amount)) {
            $message .= "💸 Nominal dibayar (bunga+denda): Rp " . number_format($amount, 0, ',', '.') . "\n";
        }
        $message .= "\nStatus: *Menunggu ACC Admin*\n";

        if (!empty($buktiFile) && !empty($data['no_ktp'])) {
            $buktiUrl = rtrim($this->getBaseUrl(), '/') . "/GadaiHp/payment/" . rawurlencode($data['no_ktp']) . "/" . rawurlencode($buktiFile);
            $message .= "Bukti: " . $buktiUrl . "\n";
        }

        $message .= "\nBuka detail di:\n";
        $message .= $this->getBaseUrl() . "/GadaiHp/admin_verifikasi.php";

        return $this->sendMessage($this->sender_number, $message);
    }

    /**
     * Notify user when they upload perpanjangan proof (confirmation)
     */
    public function notifyUserPerpanjanganUpload($data, $amount = null, $buktiFile = null) {
        $nama = !empty($data['nama_nasabah']) ? $data['nama_nasabah'] : '-';
        $message = "🔔 *BUKTI PERPANJANGAN DITERIMA*\n\n";
        $message .= "Halo {$nama},\n\n";
        $message .= "Kami telah menerima bukti pembayaran perpanjangan untuk pengajuan Anda. Tim admin akan memverifikasi bukti tersebut.\n\n";
        $message .= "📋 No. Registrasi: #" . str_pad($data['id'], 6, '0', STR_PAD_LEFT) . "\n";
        if (!empty($amount)) {
            $message .= "💸 Nominal dibayar (bunga+denda): Rp " . number_format($amount, 0, ',', '.') . "\n";
        }
        if (!empty($buktiFile) && !empty($data['no_ktp'])) {
            $buktiUrl = rtrim($this->getBaseUrl(), '/') . "/GadaiHp/payment/" . rawurlencode($data['no_ktp']) . "/" . rawurlencode($buktiFile);
            $message .= "Bukti: " . $buktiUrl . "\n";
        }

        $message .= "\nStatus: *Menunggu ACC Admin*. Kami akan menghubungi Anda setelah bukti diverifikasi.\n\n";
        $message .= "Terima kasih,\nGadai Cepat Timika Papua";

        return $this->sendMessage($data['no_hp'], $message);
    }

    /**
     * Template pesan untuk pelunasan gadai (ke User)
     */
    public function notifyUserPelunasan($data, $amount = null, $briva_number = null, $briva_name = null) {
        $pokok = !empty($data['jumlah_disetujui']) ? (float)$data['jumlah_disetujui'] : (float)$data['jumlah_pinjaman'];
        $bunga_pct = isset($data['bunga']) ? (float)$data['bunga'] : 0.0;
        $lama = isset($data['lama_gadai']) ? (int)$data['lama_gadai'] : 0;
        $bunga_total = $pokok * ($bunga_pct / 100) * $lama;
    $admin_fee = round($pokok * 0.01);

    $message = "💰 *PERMINTAAN PELUNASAN DITERIMA*\n\n";
    $message .= "Yth. Bapak/Ibu {$data['nama_nasabah']},\n\n";
    $message .= "Terima kasih. Permintaan pelunasan gadai Anda telah kami terima dan sedang diproses.\n\n";
    $message .= "📋 *No. Registrasi:* #" . str_pad($data['id'], 6, '0', STR_PAD_LEFT) . "\n";
    $item_desc = trim((string)($data['merk'] . ' ' . $data['tipe']));
    $imei_text = !empty($data['imei_serial']) ? " (IMEI/Serial: {$data['imei_serial']})" : '';
    $message .= "📱 *Barang:* {$data['jenis_barang']}" . (!empty($item_desc) ? " - {$item_desc}" : "") . $imei_text . "\n";
    if (!empty($data['kelengkapan_hp'])) {
        $message .= "- *Kelengkapan:* {$data['kelengkapan_hp']}\n";
    }
    if (!empty($data['kondisi'])) {
        $message .= "- *Kemulusan/Kondisi:* {$data['kondisi']}\n";
    }
    if (!empty($data['tanggal_gadai'])) {
        $message .= "- *Tanggal Gadai:* " . date('d F Y', strtotime($data['tanggal_gadai'])) . "\n";
    }
    if (!empty($data['tanggal_jatuh_tempo'])) {
        $message .= "- *Tanggal Jatuh Tempo:* " . date('d F Y', strtotime($data['tanggal_jatuh_tempo'])) . "\n";
    }
    $message .= "\n";
    if (!empty($amount) && !empty($briva_number) && !empty($briva_name)) {
        $message .= "🏦 *Pembayaran BRIVA BRI*\n";
        $message .= "Nominal: Rp " . number_format($amount, 0, ',', '.') . "\n";
        $message .= "No. BRIVA: {$briva_number}\n";
        $message .= "Atas Nama: {$briva_name}\n\n";
    }
    $message .= "*Rincian estimasi (untuk referensi):*\n";
    $message .= "- *Pokok pinjaman:* Rp " . number_format($pokok, 0, ',', '.') . "\n";
    $message .= "- *Bunga:* Rp " . number_format($bunga_total, 0, ',', '.') . " ({$bunga_pct}% x {$lama} bulan)\n";
    $message .= "- *Biaya administrasi (1%):* Rp " . number_format($admin_fee, 0, ',', '.') . "\n";
    $estimated_total = $pokok + $bunga_total + $admin_fee;
    $message .= "\n*Estimasi Total (tanpa denda): Rp " . number_format($estimated_total, 0, ',', '.') . "*\n\n";
    $message .= "Petugas kami akan menghubungi Anda untuk konfirmasi lebih lanjut dan instruksi pembayaran jika diperlukan.\n\n";
    $message .= "Hormat kami,\nGadai Cepat Timika Papua\n";
    $message .= "📞 0858-2309-1908";

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
     * Template pesan pelunasan terverifikasi (ke User)
     */
    public function notifyUserPelunasanVerified($data) {
        $pokok = !empty($data['jumlah_disetujui']) ? (float)$data['jumlah_disetujui'] : (float)$data['jumlah_pinjaman'];
        $bunga_pct = isset($data['bunga']) ? (float)$data['bunga'] : 0.0;
        $lama = isset($data['lama_gadai']) ? (int)$data['lama_gadai'] : 0;
        $bunga_total = $pokok * ($bunga_pct / 100) * $lama;
    $admin_fee = round($pokok * 0.01);
        $denda = !empty($data['denda_terakumulasi']) ? (float)$data['denda_terakumulasi'] : 0.0;
        $total_tebus_calc = $pokok + $bunga_total + $admin_fee + $denda;

        $message = "✅ *PEMBAYARAN TERVERIFIKASI*\n\n";
        $message .= "Yth. Bapak/Ibu {$data['nama_nasabah']},\n\n";
        $message .= "Pembayaran Anda telah kami terima dan diverifikasi. Status pengajuan: *Ditebus*.\n\n";
        $message .= "📋 *No. Registrasi:* #" . str_pad($data['id'], 6, '0', STR_PAD_LEFT) . "\n";
        $item_desc = trim((string)($data['merk'] . ' ' . $data['tipe']));
        $imei_text = !empty($data['imei_serial']) ? " (IMEI/Serial: {$data['imei_serial']})" : '';
        $message .= "📱 *Barang:* {$data['jenis_barang']}" . (!empty($item_desc) ? " - {$item_desc}" : "") . $imei_text . "\n";
        if (!empty($data['kelengkapan_hp'])) {
            $message .= "- *Kelengkapan:* {$data['kelengkapan_hp']}\n";
        }
        if (!empty($data['kondisi'])) {
            $message .= "- *Kemulusan/Kondisi:* {$data['kondisi']}\n";
        }
        if (!empty($data['tanggal_gadai'])) {
            $message .= "- *Tanggal Gadai:* " . date('d F Y', strtotime($data['tanggal_gadai'])) . "\n";
        }
        if (!empty($data['tanggal_jatuh_tempo'])) {
            $message .= "- *Tanggal Jatuh Tempo:* " . date('d F Y', strtotime($data['tanggal_jatuh_tempo'])) . "\n";
        }
        $message .= "\n*Rincian Pembayaran:*\n";
        $message .= "- *Pokok:* Rp " . number_format($pokok, 0, ',', '.') . "\n";
        $message .= "- *Bunga:* Rp " . number_format($bunga_total, 0, ',', '.') . " ({$bunga_pct}% x {$lama} bulan)\n";
        $message .= "- *Biaya administrasi (1%):* Rp " . number_format($admin_fee, 0, ',', '.') . "\n";
        if ($denda > 0) {
            $message .= "- *Denda:* Rp " . number_format($denda, 0, ',', '.') . "\n";
        }
        $message .= "\n*Total Tebus:* Rp " . number_format($total_tebus_calc, 0, ',', '.') . "\n\n";
        $message .= "Terima kasih atas kepercayaan Anda.\n\n";
        $message .= "Hormat kami,\nGadai Cepat Timika Papua\n";
        $message .= "📞 0858-2309-1908";

        return $this->sendMessage($data['no_hp'], $message);
    }

    /**
     * Template pesan reminder 3 hari sebelum jatuh tempo (ke User)
     */
    public function notifyUserDueSoon($data) {
        $pokok = !empty($data['jumlah_disetujui']) ? (float)$data['jumlah_disetujui'] : (float)$data['jumlah_pinjaman'];
        $bunga_pct = isset($data['bunga']) ? (float)$data['bunga'] : 0.0;
        $lama = isset($data['lama_gadai']) ? (int)$data['lama_gadai'] : 0;
        $bunga_total = $pokok * ($bunga_pct / 100) * $lama;
    $admin_fee = round($pokok * 0.01);
        $total_tebus_est = $pokok + $bunga_total + $admin_fee;

    $message = "⏰ *PENGINGAT JATUH TEMPO (3 HARI)*\n\n";
    $message .= "Yth. Bapak/Ibu {$data['nama_nasabah']},\n\n";
    $message .= "Kami informasikan bahwa jatuh tempo gadai Anda akan tiba dalam *3 hari* pada tanggal *" . date('d F Y', strtotime($data['tanggal_jatuh_tempo'])) . "*.\n\n";
    $message .= "📋 *No. Registrasi:* #" . str_pad($data['id'], 6, '0', STR_PAD_LEFT) . "\n";
    $item_desc = trim((string)($data['merk'] . ' ' . $data['tipe']));
    $imei_text = !empty($data['imei_serial']) ? " (IMEI/Serial: {$data['imei_serial']})" : '';
    $message .= "📱 *Barang:* {$data['jenis_barang']}" . (!empty($item_desc) ? " - {$item_desc}" : "") . $imei_text . "\n";
    if (!empty($data['kelengkapan_hp'])) {
        $message .= "- *Kelengkapan:* {$data['kelengkapan_hp']}\n";
    }
    if (!empty($data['kondisi'])) {
        $message .= "- *Kemulusan/Kondisi:* {$data['kondisi']}\n";
    }
    if (!empty($data['tanggal_gadai'])) {
        $message .= "- *Tanggal Gadai:* " . date('d F Y', strtotime($data['tanggal_gadai'])) . "\n";
    }
    $message .= "\n";
    $message .= "*Rincian estimasi untuk referensi:*\n";
    $message .= "- *Pokok:* Rp " . number_format($pokok, 0, ',', '.') . "\n";
    $message .= "- *Bunga:* Rp " . number_format($bunga_total, 0, ',', '.') . " ({$bunga_pct}% x {$lama} bulan)\n";
    $message .= "- *Biaya administrasi (1%):* Rp " . number_format($admin_fee, 0, ',', '.') . "\n";
    $message .= "\n*Estimasi Total Tebus (tanpa denda):* Rp " . number_format($total_tebus_est, 0, ',', '.') . "\n\n";
    $message .= "Silakan siapkan pelunasan atau hubungi kami untuk opsi perpanjangan.\n\n";
    $message .= "Cek status di: \n";
    $message .= $this->getBaseUrl() . "/GadaiHp/cek_status.php?no_registrasi=" . $data['id'] . "\n\n";
    $message .= "Hormat kami,\nGadai Cepat Timika Papua\n";
    $message .= "📞 0858-2309-1908";

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
    // Denda hanya dihitung sampai maksimal 7 hari; pada hari ke-8 sistem akan menandai sebagai Gagal Tebus
    $denda_days_counted = min($days_overdue, 7);
    $denda_total = $denda_harian * $denda_days_counted;
    $total_tebus = $pokok + $bunga_total + $denda_total;

    $message = "⚠️ *PENGINGAT: JATUH TEMPO TERLEWAT*\n\n";
    $message .= "Yth. Bapak/Ibu {$data['nama_nasabah']},\n\n";
    $message .= "Kami mencatat bahwa gadai Anda telah melewati tanggal jatuh tempo sebesar *{$days_overdue} hari*. Mohon segera mengambil tindakan untuk menghindari konsekuensi lebih lanjut.\n\n";
    $message .= "📋 *No. Registrasi:* #" . str_pad($data['id'], 6, '0', STR_PAD_LEFT) . "\n";
    $item_desc = trim((string)($data['merk'] . ' ' . $data['tipe']));
    $imei_text = !empty($data['imei_serial']) ? " (IMEI/Serial: {$data['imei_serial']})" : '';
    $message .= "📱 *Barang:* {$data['jenis_barang']}" . (!empty($item_desc) ? " - {$item_desc}" : "") . $imei_text . "\n";
    if (!empty($data['kelengkapan_hp'])) {
        $message .= "- *Kelengkapan:* {$data['kelengkapan_hp']}\n";
    }
    if (!empty($data['kondisi'])) {
        $message .= "- *Kemulusan/Kondisi:* {$data['kondisi']}\n";
    }
    if (!empty($data['tanggal_gadai'])) {
        $message .= "- *Tanggal Gadai:* " . date('d F Y', strtotime($data['tanggal_gadai'])) . "\n";
    }
    $message .= "- *Tanggal Jatuh Tempo:* " . date('d F Y', strtotime($data['tanggal_jatuh_tempo'])) . "\n\n";
    $message .= "*Rincian Tebus:*\n";
    $message .= "- *Pokok:* Rp " . number_format($pokok, 0, ',', '.') . "\n";
    $message .= "- *Bunga:* Rp " . number_format($bunga_total, 0, ',', '.') . " ({$bunga}% x {$lama} bulan)\n";
    $message .= "- *Denda Harian:* Rp " . number_format($denda_harian, 0, ',', '.') . " x {$denda_days_counted} hari = Rp " . number_format($denda_total, 0, ',', '.') . "\n";
    $message .= "\n*Total Tebus:* Rp " . number_format($total_tebus, 0, ',', '.') . "\n\n";
    $message .= "Mohon segera melakukan pelunasan atau menghubungi kami untuk mendiskusikan opsi perpanjangan.\n\n";
    if ($days_overdue > 7) {
        $message .= "⚠️ *PENTING:* Karena keterlambatan lebih dari 7 hari, pada hari ke-8 status akan otomatis berubah menjadi *Gagal Tebus*. Barang akan diproses sesuai ketentuan apabila tidak ada pelunasan.\n\n";
    } else {
        $message .= "Catatan: denda dihitung hingga maksimal 7 hari; jika tidak ada pelunasan hingga hari ke-8, pengajuan akan dinyatakan Gagal Tebus.\n\n";
    }
    $message .= "Cek status di:\n";
    $message .= $this->getBaseUrl() . "/GadaiHp/cek_status.php?no_registrasi=" . $data['id'] . "\n\n";
    $message .= "Hormat kami,\nGadai Cepat Timika Papua\n";
    $message .= "📞 0858-2309-1908";

        return $this->sendMessage($data['no_hp'], $message);
    }

    /**
     * Template pesan ketika status menjadi Gagal Tebus (ke User)
     */
    public function notifyUserGagalTebus($data) {
        $message = "❗ *PENGUMUMAN: GAGAL TEBUS*\n\n";
        $message .= "Yth. Bapak/Ibu {$data['nama_nasabah']},\n\n";
        $message .= "Mohon maaf, pengajuan gadai Anda dinyatakan *Gagal Tebus* karena melewati batas tenggang. Barang akan diproses sesuai ketentuan.\n\n";
        $message .= "📋 *No. Registrasi:* #" . str_pad($data['id'], 6, '0', STR_PAD_LEFT) . "\n";
        $item_desc = trim((string)($data['merk'] . ' ' . $data['tipe']));
        $imei_text = !empty($data['imei_serial']) ? " (IMEI/Serial: {$data['imei_serial']})" : '';
        $message .= "📱 *Barang:* {$data['jenis_barang']}" . (!empty($item_desc) ? " - {$item_desc}" : "") . $imei_text . "\n\n";
        if (!empty($data['tanggal_jatuh_tempo'])) {
            $message .= "- *Tanggal Jatuh Tempo:* " . date('d F Y', strtotime($data['tanggal_jatuh_tempo'])) . "\n";
        }
        $denda = !empty($data['denda_terakumulasi']) ? (float)$data['denda_terakumulasi'] : 0.0;
        if ($denda > 0) {
            $message .= "- *Denda Terakumulasi:* Rp " . number_format($denda, 0, ',', '.') . "\n";
        }
        if (!empty($data['total_tebus'])) {
            $message .= "\n*Total Tebus saat ini:* Rp " . number_format($data['total_tebus'], 0, ',', '.') . "\n\n";
        }
        $message .= "Silakan hubungi kami untuk informasi lebih lanjut.\n\n";
        $message .= "Hormat kami,\nGadai Cepat Timika Papua\n";
        $message .= "📞 0858-2309-1908";

        return $this->sendMessage($data['no_hp'], $message);
    }

    /**
     * Template pesan ketika status menjadi Gagal Tebus (ke Admin)
     */
    public function notifyAdminGagalTebus($data) {
        $message = "⚠️ *PENGUMUMAN: GAGAL TEBUS*\n\n";
        $message .= "📋 No. Registrasi: #" . str_pad($data['id'], 6, '0', STR_PAD_LEFT) . "\n";
        $message .= "👤 Nama: {$data['nama_nasabah']}\n";
        $message .= "📱 Barang: {$data['jenis_barang']} {$data['merk']} {$data['tipe']}\n";
        if (!empty($data['tanggal_jatuh_tempo'])) {
            $message .= "📅 Tgl Jatuh Tempo: " . date('d F Y', strtotime($data['tanggal_jatuh_tempo'])) . "\n";
        }
        $denda = !empty($data['denda_terakumulasi']) ? (float)$data['denda_terakumulasi'] : 0.0;
        if ($denda > 0) {
            $message .= "💸 Denda terakumulasi: Rp " . number_format($denda, 0, ',', '.') . "\n";
        }
        if (!empty($data['total_tebus'])) {
            $message .= "💰 Total Tebus: Rp " . number_format($data['total_tebus'], 0, ',', '.') . "\n";
        }
        $message .= "\nBuka detail di:\n";
        $message .= $this->getBaseUrl() . "/GadaiHp/admin_verifikasi.php";

        return $this->sendMessage($this->sender_number, $message);
    }
}

// Inisialisasi helper
$whatsapp = new WhatsAppHelper();
