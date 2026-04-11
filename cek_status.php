<?php
require_once 'database.php';
require_once 'whatsapp_helper.php';
require_once 'gadai_helpers.php';

$result = null;
$error = null;
$message = null;
$message_type = 'info';
$daily_denda = 30000;
$denda_max_days = 7;
$max_denda_total = $daily_denda * $denda_max_days;
$show_payment = false;
$payment_amount = null;
$last_action_type = null;
$briva_number = '305101007702502';
$briva_name = 'Jerri Christian Gedeon Tungga';
$allowed_active_status = gadai_get_active_statuses();

if (isset($_GET['upload']) && $_GET['upload'] === 'success') {
    $message = 'Bukti pembayaran berhasil diunggah. Menunggu konfirmasi admin.';
    $message_type = 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['byrcicilan'])) {
    $upload_errors = [];
    $no_registrasi = $_POST['no_registrasi'] ?? '';
    $pelanggan = mysqli_real_escape_string($conn, trim($_POST['ktp'] ?? ''));
    $payment_input = trim($_POST['amount'] ?? '');
    $action_for = isset($_POST['action_for']) ? trim($_POST['action_for']) : 'cicilan';
    $metode = mysqli_real_escape_string($conn, $_POST['method'] ?? '');
    $bukti = $_FILES['receipt'] ?? null;

    if ($no_registrasi === '' || $pelanggan === '' || $payment_input === '' || $metode === '') {
        $upload_errors[] = 'Semua field harus diisi.';
    }

    $payment = preg_replace('/[^0-9]/', '', $payment_input);
    if ($payment === '' || (int)$payment <= 0) {
        $upload_errors[] = 'Jumlah pembayaran tidak valid.';
    }

    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $max_file_size = 5 * 1024 * 1024;
    if (!$bukti || $bukti['error'] !== UPLOAD_ERR_OK) {
        $upload_errors[] = 'Terjadi kesalahan saat mengupload file.';
    } elseif ($bukti['size'] > $max_file_size) {
        $upload_errors[] = 'Ukuran file terlalu besar. Maksimal 5MB.';
    } elseif (!in_array($bukti['type'], $allowed_types, true)) {
        $upload_errors[] = 'Format file tidak didukung. Gunakan JPG, PNG, atau GIF.';
    }

    if (empty($upload_errors)) {
    $checkGadai = mysqli_query($conn, "SELECT nik, imei_serial, jenis_barang, merk_barang, spesifikasi_barang FROM data_gadai WHERE id = '" . mysqli_real_escape_string($conn, $no_registrasi) . "' LIMIT 1");
        if (!$checkGadai) {
            $upload_errors[] = 'Gagal memeriksa data gadai.';
        } elseif (mysqli_num_rows($checkGadai) === 0) {
            $upload_errors[] = 'ID gadai tidak ditemukan.';
        } else {
            $rowGadai = mysqli_fetch_assoc($checkGadai);
            if ($rowGadai['nik'] !== $pelanggan) {
                $upload_errors[] = 'NIK tidak sesuai dengan data gadai.';
            }
        }
    }

    if (empty($upload_errors)) {
        // Batasi 1x upload untuk transaksi yang masih pending pada jenis yang sama (cicilan/pelunasan/perpanjangan)
        $safe_reg = mysqli_real_escape_string($conn, (string)$no_registrasi);
        $checkAttempts = mysqli_query($conn, "SELECT COUNT(*) AS total FROM transaksi WHERE pelanggan_nik = '$pelanggan' AND barang_id = '$safe_reg' AND keterangan = '" . mysqli_real_escape_string($conn, $action_for) . "'");
        if (!$checkAttempts) {
            $upload_errors[] = 'Gagal memeriksa data transaksi.';
        } else {
            $attempts = (int)mysqli_fetch_assoc($checkAttempts)['total'];
            if ($attempts >= 1) {
                $upload_errors[] = 'Anda hanya dapat mengirim bukti pembayaran 1 kali. Jika perlu bantuan, hubungi admin.';
            }
        }
    }

    if (empty($upload_errors)) {
        $base_dir = 'payment/';
        $target_dir = $base_dir . $pelanggan . '/';
        if (!is_dir($target_dir)) {
            if (!mkdir($target_dir, 0755, true)) {
                $upload_errors[] = 'Gagal membuat direktori untuk menyimpan file.';
            }
        }
    }

    if (empty($upload_errors)) {
        $file_extension = strtolower(pathinfo($bukti['name'], PATHINFO_EXTENSION));
        $timestamp = date('YmdHis');
        $new_file_name = $payment . '_' . $timestamp . '.' . $file_extension;
        $target_file = $target_dir . $new_file_name;

        mysqli_begin_transaction($conn);
            try {
            // determine keterangan based on action_for
            $allowed_ket = ['cicilan', 'pelunasan', 'perpanjangan'];
            $keterangan_val = in_array($action_for, $allowed_ket, true) ? $action_for : 'cicilan';
            $kesc = mysqli_real_escape_string($conn, $keterangan_val);
            // include item identity (imei, jenis, merk, tipe) if available for easier admin lookup
            $imei_val = isset($rowGadai['imei_serial']) ? mysqli_real_escape_string($conn, $rowGadai['imei_serial']) : null;
            // serial_number will mirror imei_serial when available
            $serial_val = $imei_val;
            $jenis_val = isset($rowGadai['jenis_barang']) ? mysqli_real_escape_string($conn, $rowGadai['jenis_barang']) : null;
            $merk_val = isset($rowGadai['merk_barang']) ? mysqli_real_escape_string($conn, $rowGadai['merk_barang']) : null;
            $tipe_val = isset($rowGadai['spesifikasi_barang']) ? mysqli_real_escape_string($conn, $rowGadai['spesifikasi_barang']) : null;

            $cols = [];
            $vals = [];
            if ($imei_val !== null) { $cols[] = "`imei`"; $vals[] = "'{$imei_val}'"; }
            if ($serial_val !== null) { $cols[] = "`serial_number`"; $vals[] = "'{$serial_val}'"; }
            if ($jenis_val !== null) { $cols[] = "`jenis_barang`"; $vals[] = "'{$jenis_val}'"; }
            if ($merk_val !== null) { $cols[] = "`merk`"; $vals[] = "'{$merk_val}'"; }
            if ($tipe_val !== null) { $cols[] = "`tipe`"; $vals[] = "'{$tipe_val}'"; }

            $cols[] = "`pelanggan_nik`";
            $cols[] = "`barang_id`";
            $cols[] = "`jumlah_bayar`";
            $cols[] = "`keterangan`";
            $cols[] = "`metode_pembayaran`";
            $cols[] = "`bukti`";

            $vals[] = "'$pelanggan'";
            $vals[] = "'$no_registrasi'";
            $vals[] = "'$payment'";
            $vals[] = "'$kesc'";
            $vals[] = "'$metode'";
            $vals[] = "'$new_file_name'";

            $insert_sql = "INSERT INTO `transaksi`(" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ")";
            $query = mysqli_query($conn, $insert_sql);

            if (!$query) {
                throw new Exception('Gagal menyimpan data pembayaran.');
            }

            if (!move_uploaded_file($bukti['tmp_name'], $target_file)) {
                throw new Exception('Gagal menyimpan file bukti pembayaran.');
            }

            mysqli_commit($conn);
            // If this upload is intended for perpanjangan, the transaksi record will mark it for admin review
            if ($action_for === 'perpanjangan') {
                $safe_reg = mysqli_real_escape_string($conn, $no_registrasi);
                // No need to update data_gadai; transaksi record is sufficient for admin to see pending perpanjangan
                $message = 'Bukti pembayaran perpanjangan berhasil diunggah. Menunggu konfirmasi admin.';

                // notify admin via WhatsApp (best-effort)
                try {
                    if (isset($whatsapp)) {
                        // fetch fresh data via PDO for richer message
                        $stmt = $db->prepare("SELECT * FROM data_gadai WHERE id = ?");
                        $stmt->execute([$no_registrasi]);
                        $fresh = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($fresh) {
                            // pass the bukti filename so admin can quickly view the uploaded image
                            $whatsapp->notifyAdminPerpanjanganUpload($fresh, (float)$payment, $new_file_name);
                            // notify user that upload was received and is pending admin ACC
                            try {
                                if (method_exists($whatsapp, 'notifyUserPerpanjanganUpload')) {
                                    $whatsapp->notifyUserPerpanjanganUpload($fresh, (float)$payment, $new_file_name);
                                }
                            } catch (Exception $e) {
                                error_log('WA notify user perpanjangan failed: ' . $e->getMessage());
                            }
                        }
                    }
                } catch (Exception $e) {
                    error_log('WA notify admin perpanjangan failed: ' . $e->getMessage());
                }
            } else {
                $message = 'Bukti pembayaran berhasil diunggah. Menunggu konfirmasi admin.';
            }
            $last_action_type = $action_for;
            $message_type = 'success';
        } catch (Exception $e) {
            mysqli_rollback($conn);
            if (isset($target_file) && file_exists($target_file)) {
                unlink($target_file);
            }
            $upload_errors[] = $e->getMessage();
        }
    }

    if (!empty($upload_errors)) {
        $message = implode(' ', $upload_errors);
        $message_type = 'danger';
    }
}

function calculateTotalTebus($row, $denda_total) {
    $calc = gadai_calculate_breakdown((array)$row, (float)$denda_total);
    return [$calc['pokok'], $calc['bunga_total'], $calc['total_tebus']];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'], $_POST['no_registrasi']) && !isset($_POST['byrcicilan'])) {
    $no_registrasi = $_POST['no_registrasi'];
    $action_type = $_POST['action_type'];
    $last_action_type = $action_type;

    try {
        $data_sql = "SELECT * FROM data_gadai WHERE id = ?";
        $data_stmt = $db->prepare($data_sql);
        $data_stmt->execute([$no_registrasi]);
        $data = $data_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            $error = "Nomor registrasi tidak ditemukan!";
        } else {
            if (!gadai_is_active_status($data['status'] ?? '')) {
                $error = "Tindakan tidak dapat diproses untuk status saat ini.";
            } else {
                // Check if pelunasan pending by looking at transaksi records
                try {
                    $check_pelunasan = $db->prepare("SELECT COUNT(*) FROM transaksi WHERE barang_id = ? AND pelanggan_nik = ? AND keterangan = 'pelunasan'");
                    $check_pelunasan->execute([$no_registrasi, $data['nik']]);
                    $pelunasan_pending = (int)$check_pelunasan->fetchColumn() > 0;
                } catch (Exception $e) {
                    $pelunasan_pending = false;
                }

                if ($pelunasan_pending) {
                    $error = "Pelunasan sedang menunggu pembayaran dan ACC admin. Gadai tetap berjalan.";
                } else {
                $today = new DateTime(date('Y-m-d'));
                $due_date = new DateTime($data['tanggal_jatuh_tempo']);
                
                if ($today < $due_date) {
                    $error = "Tindakan hanya bisa dilakukan saat jatuh tempo.";
                } else {
                    $days_overdue = 0;
                    if ($today > $due_date) {
                        $days_overdue = (int)$due_date->diff($today)->format('%a');
                    }

                    $days_overdue_capped = min($days_overdue, $denda_max_days);
                    $denda_total = $days_overdue_capped > 0 ? $daily_denda * $days_overdue_capped : 0;

                    if ($days_overdue > $denda_max_days) {
                        list($pokok_calc, $bunga_total_calc, $total_tebus_calc) = calculateTotalTebus($data, $max_denda_total);
                        $update_sql = "UPDATE data_gadai SET status = 'Gagal Tebus', gagal_tebus_at = NOW(), denda_terakumulasi = ?, total_tebus = ? WHERE id = ?";
                        $update_stmt = $db->prepare($update_sql);
                        $update_stmt->execute([$max_denda_total, $total_tebus_calc, $no_registrasi]);
                        $error = "Masa respon sudah lewat. Status berubah menjadi Gagal Tebus.";
                    } else {
                        if ($denda_total > 0) {
                            list($pokok_calc, $bunga_total_calc, $total_tebus_calc) = calculateTotalTebus($data, $denda_total);
                            $update_denda_sql = "UPDATE data_gadai SET denda_terakumulasi = ? WHERE id = ?";
                            $update_denda_stmt = $db->prepare($update_denda_sql);
                            $update_denda_stmt->execute([$denda_total, $no_registrasi]);

                            $update_total_sql = "UPDATE data_gadai SET total_tebus = ? WHERE id = ?";
                            $update_total_stmt = $db->prepare($update_total_sql);
                            $update_total_stmt->execute([$total_tebus_calc, $no_registrasi]);
                        }
            if ($action_type === 'perpanjangan') {
                // Before allowing extension, require that bunga dan denda berjalan sudah dilunasi.
                $denda_for_check = isset($denda_total) ? $denda_total : 0;
                $denda_stored_check = !empty($data['denda_terakumulasi']) ? (float)$data['denda_terakumulasi'] : 0;
                $denda_display_check = max($denda_for_check, $denda_stored_check);

                $calc_extension = gadai_calculate_breakdown($data, $denda_display_check);
                $required_for_extension = (float)$calc_extension['biaya_perpanjangan'];

                // Sum payments already made for this barang/pelanggan
                try {
                    // Hitung pembayaran sejak perpanjangan terakhir (agar histori lama tidak membuat perpanjangan berikutnya gratis)
                    $since = !empty($data['perpanjangan_terakhir_at']) ? $data['perpanjangan_terakhir_at'] : (!empty($data['created_at']) ? $data['created_at'] : null);
                    if ($since) {
                        $paid_stmt = $db->prepare("SELECT COALESCE(SUM(jumlah_bayar),0) FROM transaksi WHERE barang_id = ? AND pelanggan_nik = ? AND created_at >= ?");
                        $paid_stmt->execute([$no_registrasi, $data['nik'], $since]);
                    } else {
                        $paid_stmt = $db->prepare("SELECT COALESCE(SUM(jumlah_bayar),0) FROM transaksi WHERE barang_id = ? AND pelanggan_nik = ?");
                        $paid_stmt->execute([$no_registrasi, $data['nik']]);
                    }
                    $total_paid = (float)$paid_stmt->fetchColumn();
                } catch (Exception $e) {
                    // If transaksi table missing or query fails, assume no payments recorded
                    $total_paid = 0.0;
                }

                if ($total_paid < $required_for_extension) {
                    $short = $required_for_extension - $total_paid;
                    $error = "Sebelum perpanjangan, harap lunasi semua tagihan (Bunga + Denda + Admin 1% + Asuransi Rp 10.000) sebesar Rp " . number_format($required_for_extension, 0, ',', '.') . ". Kekurangan saat ini: Rp " . number_format($short, 0, ',', '.') . ". Silakan pilih Pelunasan dan unggah bukti pembayaran.";
                    // do not process extension
                } else {
                    $current_due = $data['tanggal_jatuh_tempo'];
                    $new_due = date('Y-m-d', strtotime($current_due . ' +30 days'));

                    $update_sql = "UPDATE data_gadai SET 
                        status = 'Diperpanjang',
                        tanggal_jatuh_tempo = ?,
                        perpanjangan_ke = perpanjangan_ke + 1,
                        perpanjangan_terakhir_at = NOW(),
                        updated_at = NOW()
                    WHERE id = ?";
                    $update_stmt = $db->prepare($update_sql);
                    $update_stmt->execute([$new_due, $no_registrasi]);

                    try {
                        $whatsapp->notifyUserExtension($data, $new_due);
                        $whatsapp->notifyAdminExtension($data, $new_due);
                    } catch(Exception $e) {
                        error_log("WhatsApp notification failed: " . $e->getMessage());
                    }

                    $message = "Perpanjangan berhasil diproses. Jatuh tempo baru: " . date('d F Y', strtotime($new_due)) . ".";
                    $message_type = 'success';
                }
            } elseif ($action_type === 'pelunasan') {
                // No need to update data_gadai for pelunasan pending
                // User will upload bukti and admin will ACC it

                $denda_existing = !empty($data['denda_terakumulasi']) ? (float)$data['denda_terakumulasi'] : 0;
                $denda_for_payment = $denda_total > 0 ? max($denda_total, $denda_existing) : $denda_existing;
                list($_pokok, $_bunga_total, $payment_amount_calc) = calculateTotalTebus($data, $denda_for_payment);
                $payment_amount = !empty($data['total_tebus']) ? (float)$data['total_tebus'] : $payment_amount_calc;
                $show_payment = true;

                try {
                    $whatsapp->notifyUserPelunasan($data, $payment_amount, $briva_number, $briva_name);
                    $whatsapp->notifyAdminPelunasan($data);
                } catch(Exception $e) {
                    error_log("WhatsApp notification failed: " . $e->getMessage());
                }

                $message = "Permintaan pelunasan telah kami terima. Silakan lakukan pembayaran via BRIVA BRI.";
                $message_type = 'success';
            }
                        } // end else (days > max_denda check)
                    } // end else (today < due_date check)
                } // end else (pelunasan_pending check)
            } // end else (allowed status check)
        } // end else (!$data check)
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

if (isset($_GET['no_registrasi']) || isset($_POST['no_registrasi'])) {
    $no_registrasi = $_GET['no_registrasi'] ?? $_POST['no_registrasi'];
    
    try {
        $sql = "SELECT * FROM data_gadai WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$no_registrasi]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            $error = "Nomor registrasi tidak ditemukan!";
        } else {
            if (gadai_is_active_status($result['status'] ?? '') && !empty($result['tanggal_jatuh_tempo'])) {
                $today = new DateTime(date('Y-m-d'));
                $due_date = new DateTime($result['tanggal_jatuh_tempo']);

                if ($today > $due_date) {
                    $days_overdue = (int)$due_date->diff($today)->format('%a');
                    $days_overdue_capped = min($days_overdue, $denda_max_days);
                    $denda_total = $days_overdue_capped > 0 ? $daily_denda * $days_overdue_capped : 0;
                    if ($days_overdue > $denda_max_days) {
                        list($pokok_calc, $bunga_total_calc, $total_tebus_calc) = calculateTotalTebus($result, $max_denda_total);
                        $update_sql = "UPDATE data_gadai SET status = 'Gagal Tebus', gagal_tebus_at = NOW(), denda_terakumulasi = ?, total_tebus = ? WHERE id = ?";
                        $update_stmt = $db->prepare($update_sql);
                        $update_stmt->execute([$max_denda_total, $total_tebus_calc, $no_registrasi]);

                        $sql = "SELECT * FROM data_gadai WHERE id = ?";
                        $stmt = $db->prepare($sql);
                        $stmt->execute([$no_registrasi]);
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    } elseif ($denda_total > 0) {
                        list($pokok_calc, $bunga_total_calc, $total_tebus_calc) = calculateTotalTebus($result, $denda_total);
                        $update_denda_sql = "UPDATE data_gadai SET denda_terakumulasi = ?, total_tebus = ? WHERE id = ?";
                        $update_denda_stmt = $db->prepare($update_denda_sql);
                        $update_denda_stmt->execute([$denda_total, $total_tebus_calc, $no_registrasi]);
                    }
                }

                if ((float)$result['total_tebus'] <= 0) {
                    $denda_existing = !empty($result['denda_terakumulasi']) ? (float)$result['denda_terakumulasi'] : 0;
                    list($pokok_calc, $bunga_total_calc, $total_tebus_calc) = calculateTotalTebus($result, $denda_existing);
                    $update_total_sql = "UPDATE data_gadai SET total_tebus = ? WHERE id = ?";
                    $update_total_stmt = $db->prepare($update_total_sql);
                    $update_total_stmt->execute([$total_tebus_calc, $no_registrasi]);
                    $result['total_tebus'] = $total_tebus_calc;
                }
            }
        }
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Status Pengajuan - Gadai Cepat Timika</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@700;800;900&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 30px 0;
        }
        
        .container-box {
            background: #ffffff;
            border-radius: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 50px;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .page-title {
            font-family: 'Raleway', sans-serif;
            font-weight: 800;
            font-size: 2.5rem;
            background: linear-gradient(135deg, #0056b3, #007bff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-align: center;
            margin-bottom: 10px;
        }
        
        .page-subtitle {
            text-align: center;
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 40px;
        }
        
        .search-box {
            background: linear-gradient(135deg, #e3f2fd, #f0f8ff);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .form-control {
            border: 2px solid #e3f2fd;
            border-radius: 15px;
            padding: 12px 20px;
            font-size: 1.1rem;
        }
        
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15);
        }
        
        .btn-search {
            background: linear-gradient(135deg, #0056b3, #007bff);
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px 40px;
            border-radius: 50px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }
        
        .btn-search:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 86, 179, 0.4);
            color: white;
        }
        
        .btn-back {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            border: none;
            color: white;
            font-weight: 600;
            padding: 10px 30px;
            border-radius: 50px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-back:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.4);
        }
        
        .status-card {
            border-radius: 20px;
            padding: 30px;
            margin-top: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .status-pending {
            background: linear-gradient(135deg, #fff8e1, #fffbf0);
            border: 3px solid #ffc107;
        }
        
        .status-approved {
            background: linear-gradient(135deg, #e8f5e9, #f1f8f4);
            border: 3px solid #28a745;
        }
        
        .status-rejected {
            background: linear-gradient(135deg, #ffebee, #fef5f6);
            border: 3px solid #dc3545;
        }
        
        .status-badge {
            display: inline-block;
            padding: 15px 30px;
            border-radius: 50px;
            font-weight: 800;
            font-size: 1.3rem;
            margin-bottom: 20px;
        }
        
        .badge-pending {
            background: linear-gradient(135deg, #ffc107, #ff9800);
            color: white;
        }
        
        .badge-approved {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .badge-rejected {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }
        
        .info-section {
            margin-top: 25px;
        }
        
        .info-section h5 {
            font-family: 'Raleway', sans-serif;
            font-weight: 700;
            color: #0056b3;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e3f2fd;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .info-label {
            font-weight: 600;
            color: #555;
        }
        
        .info-value {
            color: #333;
            font-weight: 500;
        }
        
        .alert-box {
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
        }
        
        @media (max-width: 768px) {
            .container-box {
                padding: 30px 20px;
            }
            
            .page-title {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="container-box">
            <h1 class="page-title">🔍 CEK STATUS PENGAJUAN</h1>
            <p class="page-subtitle">Masukkan nomor registrasi untuk melihat status pengajuan gadai Anda</p>
            
            <a href="index.php" class="btn-back">← Kembali ke Beranda</a>
            
            <div class="search-box">
                <form method="POST">
                    <div class="input-group mb-3">
                        <span class="input-group-text" style="background: linear-gradient(135deg, #0056b3, #007bff); color: white; border: none; border-radius: 15px 0 0 15px; font-weight: 600;">#</span>
                        <input type="text" class="form-control" name="no_registrasi" placeholder="Contoh: 000001" required pattern="[0-9]+" title="Masukkan nomor registrasi (angka saja)">
                    </div>
                    <button type="submit" class="btn btn-search w-100">🔎 Cek Status</button>
                </form>
                
                <div class="alert alert-info mt-3 mb-0" style="border-radius: 15px;">
                    <strong>💡 Tip:</strong> Nomor registrasi dikirim via SMS/WhatsApp setelah Anda mengisi form pengajuan.
                </div>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-box">
                    <strong>❌ Error!</strong> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-box">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($result): ?>
                <?php
                $status_class = '';
                $badge_class = '';
                $status_icon = '';
                $status_text = '';
                $status_message = '';
                
                switch($result['status']) {
                    case 'Pending':
                        $status_class = 'status-pending';
                        $badge_class = 'badge-pending';
                        $status_icon = '⏳';
                        $status_text = 'MENUNGGU VERIFIKASI';
                        $status_message = 'Pengajuan Anda sedang dalam proses verifikasi oleh admin. Mohon tunggu notifikasi selanjutnya via WhatsApp.';
                        break;
                    case 'Disetujui':
                        $status_class = 'status-approved';
                        $badge_class = 'badge-approved';
                        $status_icon = '✅';
                        $status_text = 'DISETUJUI';
                        $status_message = 'Selamat! Pengajuan Anda telah disetujui. Silakan datang ke kantor kami untuk melanjutkan proses pencairan dana.';
                        break;
                    case 'Diperpanjang':
                        $status_class = 'status-approved';
                        $badge_class = 'badge-approved';
                        $status_icon = '🔁';
                        $status_text = 'DIPERPANJANG';
                        $status_message = 'Perpanjangan gadai Anda aktif. Mohon perhatikan tanggal jatuh tempo terbaru.';
                        break;
                    case 'Ditolak':
                        $status_class = 'status-rejected';
                        $badge_class = 'badge-rejected';
                        $status_icon = '❌';
                        $status_text = 'DITOLAK';
                        $status_message = 'Maaf, pengajuan Anda ditolak. Anda dapat mengajukan kembali setelah memenuhi persyaratan.';
                        break;
                    case 'Ditebus':
                        $status_class = 'status-approved';
                        $badge_class = 'badge-approved';
                        $status_icon = '💰';
                        $status_text = 'DITEBUS';
                        $status_message = 'Pelunasan telah diproses. Terima kasih.';
                        break;
                    case 'Siap Dijual':
                        $status_class = 'status-rejected';
                        $badge_class = 'badge-rejected';
                        $status_icon = '🏷️';
                        $status_text = 'SIAP DIJUAL';
                        $status_message = 'Status gadai telah masuk penanganan internal lanjutan. Untuk informasi lebih detail silakan hubungi admin.';
                        break;
                    case 'Terjual':
                    case 'Barang Dijual':
                    case 'Dijual':
                        $status_class = 'status-rejected';
                        $badge_class = 'badge-rejected';
                        $status_icon = '📦';
                        $status_text = 'TERJUAL';
                        $status_message = 'Barang telah ditangani sesuai proses internal. Jika membutuhkan informasi lebih lanjut, silakan hubungi admin.';
                        break;
                    case 'Gagal Tebus':
                        $status_class = 'status-rejected';
                        $badge_class = 'badge-rejected';
                        $status_icon = '⚠️';
                        $status_text = 'GAGAL TEBUS';
                        $status_message = 'Tidak ada respon setelah masa denda berakhir. Proses gagal tebus dijalankan.';
                        break;
                    default:
                        $status_class = 'status-pending';
                        $badge_class = 'badge-pending';
                        $status_icon = 'ℹ️';
                        $status_text = 'STATUS TIDAK DIKENAL';
                        $status_message = 'Status pengajuan belum tersedia.';
                        break;
                }

                $today = new DateTime(date('Y-m-d'));
                $days_overdue = 0;
                $denda_total = 0;

                if (!empty($result['tanggal_jatuh_tempo']) && gadai_is_active_status($result['status'] ?? '')) {
                    $due_date = new DateTime($result['tanggal_jatuh_tempo']);

                    if ($today > $due_date) {
                        $days_overdue = (int)$due_date->diff($today)->format('%a');
                        $days_overdue_capped = min($days_overdue, $denda_max_days);
                        if ($days_overdue_capped > 0) {
                            $denda_total = $daily_denda * $days_overdue_capped;
                        }
                    }
                }

                $denda_stored = !empty($result['denda_terakumulasi']) ? (float)$result['denda_terakumulasi'] : 0;
                $denda_display = $denda_total > 0 ? max($denda_total, $denda_stored) : $denda_stored;
                list($pokok_display, $bunga_total_display, $total_tebus_calc_display) = calculateTotalTebus($result, $denda_display);
                $total_tebus_display = !empty($result['total_tebus']) ? (float)$result['total_tebus'] : $total_tebus_calc_display;
                $payment_amount_display = $payment_amount !== null ? $payment_amount : $total_tebus_display;
                
                // Check pelunasan pending from transaksi table
                try {
                    $check_pelunasan_display = $db->prepare("SELECT COUNT(*) FROM transaksi WHERE barang_id = ? AND pelanggan_nik = ? AND keterangan = 'pelunasan'");
                    $check_pelunasan_display->execute([$result['id'], $result['nik']]);
                    $pelunasan_pending = (int)$check_pelunasan_display->fetchColumn() > 0 && $result['status'] !== 'Lunas';
                } catch (Exception $e) {
                    $pelunasan_pending = false;
                }
                
                // Check perpanjangan pending from transaksi table
                try {
                    $check_perpanjangan_display = $db->prepare("SELECT COUNT(*) FROM transaksi WHERE barang_id = ? AND pelanggan_nik = ? AND keterangan = 'perpanjangan'");
                    $check_perpanjangan_display->execute([$result['id'], $result['nik']]);
                    $perpanjangan_pending = (int)$check_perpanjangan_display->fetchColumn() > 0;
                } catch (Exception $e) {
                    $perpanjangan_pending = false;
                }
                ?>
                
                <div class="status-card <?php echo $status_class; ?>">
                    <div class="text-center">
                        <div class="status-badge <?php echo $badge_class; ?>">
                            <?php echo $status_icon . ' ' . $status_text; ?>
                        </div>
                        <h4>Nomor Registrasi: #<?php echo str_pad($result['id'], 6, '0', STR_PAD_LEFT); ?></h4>
                        <p class="text-muted">Diajukan pada: <?php echo date('d F Y, H:i', strtotime($result['created_at'])); ?></p>
                    </div>
                    
                    <div class="alert-box" style="background: rgba(255, 255, 255, 0.7);">
                        <p class="mb-0"><strong>Status:</strong> <?php echo $status_message; ?></p>
                    </div>
                    
                    <?php $reject_reason_display = $result['reject_reason'] ?? ($result['alasan_penolakan'] ?? ''); ?>
                    <?php if ($result['status'] == 'Ditolak' && $reject_reason_display): ?>
                        <div class="alert alert-danger alert-box">
                            <strong>📝 Alasan Penolakan:</strong><br>
                            <?php echo htmlspecialchars($reject_reason_display); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="info-section">
                        <h5>📱 Informasi Barang</h5>
                        <div class="info-row">
                            <span class="info-label">Jenis:</span>
                            <span class="info-value"><?php echo $result['jenis_barang']; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Merk & Spesifikasi:</span>
                            <span class="info-value"><?php echo ($result['merk_barang'] ?? '-') . ' ' . ($result['spesifikasi_barang'] ?? ''); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Kondisi:</span>
                            <span class="info-value"><?php echo $result['kondisi_barang'] ?? '-'; ?></span>
                        </div>
                    </div>
                    
                    <div class="info-section">
                        <h5>💰 Informasi Pinjaman</h5>
                        <div class="info-row">
                            <span class="info-label">Pengajuan Anda:</span>
                            <span class="info-value">Rp <?php echo number_format($result['jumlah_pinjaman'], 0, ',', '.'); ?></span>
                        </div>
                        
                        <?php if ($result['jumlah_disetujui'] && $result['status'] == 'Disetujui'): ?>
                            <div class="info-row" style="background: linear-gradient(135deg, #e8f5e9, #f1f8f4); padding: 15px; border-radius: 10px; margin: 10px 0;">
                                <span class="info-label" style="font-size: 1.1rem;">✅ Nominal Disetujui:</span>
                                <span class="info-value" style="font-size: 1.3rem; font-weight: 800; color: #28a745;">
                                    Rp <?php echo number_format($result['jumlah_disetujui'], 0, ',', '.'); ?>
                                </span>
                            </div>
                            
                            <?php if ($result['jumlah_disetujui'] != $result['jumlah_pinjaman']): ?>
                                <div class="alert alert-warning" style="padding: 10px; font-size: 0.9rem; margin-top: 10px;">
                                    <strong>ℹ️ Penyesuaian Nominal:</strong> 
                                    <?php 
                                    $selisih = $result['jumlah_disetujui'] - $result['jumlah_pinjaman'];
                                    if ($selisih > 0) {
                                        echo "Ditambah Rp " . number_format(abs($selisih), 0, ',', '.');
                                    } else {
                                        echo "Dikurangi Rp " . number_format(abs($selisih), 0, ',', '.');
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <div class="info-row">
                            <span class="info-label">Bunga:</span>
                            <span class="info-value"><?php echo $result['bunga']; ?>% per bulan</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Durasi:</span>
                            <span class="info-value"><?php echo $result['lama_gadai']; ?> bulan</span>
                        </div>
                        <?php if (gadai_is_active_status($result['status'] ?? '')): ?>
                            <div class="info-row">
                                <span class="info-label">Tanggal Jatuh Tempo:</span>
                                <span class="info-value text-danger"><strong><?php echo date('d F Y', strtotime($result['tanggal_jatuh_tempo'])); ?></strong></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($result['perpanjangan_ke'])): ?>
                            <div class="info-row">
                                <span class="info-label">Jumlah Perpanjangan:</span>
                                <span class="info-value"><?php echo (int)$result['perpanjangan_ke']; ?> kali</span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($result['denda_terakumulasi']) && $result['denda_terakumulasi'] > 0): ?>
                            <div class="info-row">
                                <span class="info-label">Denda Terkini:</span>
                                <span class="info-value">Rp <?php echo number_format($result['denda_terakumulasi'], 0, ',', '.'); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="info-row" style="background: linear-gradient(135deg, #e3f2fd, #f0f8ff); padding: 12px; border-radius: 10px; margin-top: 10px;">
                            <span class="info-label" style="font-weight: 700;">Total Tebus:</span>
                            <span class="info-value" style="font-weight: 800; color: #0056b3;">Rp <?php echo number_format($total_tebus_display, 0, ',', '.'); ?></span>
                        </div>
                    </div>

                    <?php if ($show_payment && $last_action_type === 'pelunasan'): ?>
                        <div class="info-section">
                            <h5>🏦 Pembayaran BRIVA BRI</h5>
                            <div class="alert alert-success alert-box">
                                Instruksi pembayaran ditampilkan di pop up agar Anda tetap di halaman ini.
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal" style="border-radius: 50px; padding: 12px 30px; font-weight: 600;">
                                📌 Lihat Instruksi Pembayaran
                                </button>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadBuktiModal" style="border-radius: 50px; padding: 12px 30px; font-weight: 600;">
                                    📤 Upload Bukti Pembayaran
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($result['keterangan_admin']) && $result['status'] == 'Disetujui'): ?>
                        <div class="alert alert-info" style="border-radius: 15px; padding: 15px; margin-top: 20px;">
                            <strong>📝 Catatan dari Admin:</strong><br>
                            <?php echo nl2br(htmlspecialchars($result['keterangan_admin'])); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($result['verified_at']): ?>
                        <div class="info-section">
                            <h5>ℹ️ Informasi Verifikasi</h5>
                            <div class="info-row">
                                <span class="info-label">Diverifikasi pada:</span>
                                <span class="info-value"><?php echo date('d F Y, H:i', strtotime($result['verified_at'])); ?></span>
                            </div>
                            <?php if ($result['verified_by']): ?>
                                <div class="info-row">
                                    <span class="info-label">Diverifikasi oleh:</span>
                                    <span class="info-value"><?php echo $result['verified_by']; ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($days_overdue > 0 && $days_overdue <= 7 && !$pelunasan_pending && !$perpanjangan_pending): ?>
                        <div class="alert alert-warning alert-box">
                            <strong>⚠️ Denda Harian Aktif</strong><br>
                            Terlambat <?php echo $days_overdue; ?> hari. Denda berjalan: Rp 30.000 x <?php echo $days_overdue; ?> = <strong>Rp <?php echo number_format($denda_total, 0, ',', '.'); ?></strong>
                        </div>
                    <?php endif; ?>

                    <?php // Aksi jatuh tempo info removed - no longer using aksi_jatuh_tempo column ?>

                    <?php if ($pelunasan_pending): ?>
                        <div class="alert alert-warning alert-box">
                            <strong>⏳ Pelunasan Menunggu</strong><br>
                            Pembayaran belum masuk atau belum di-ACC admin. Gadai tetap berjalan hingga pembayaran dikonfirmasi.
                        </div>
                        <div class="info-section">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadBuktiModal" style="border-radius: 50px; padding: 12px 30px; font-weight: 600;">
                                📤 Upload Bukti Pembayaran
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($result['tanggal_jatuh_tempo']) && gadai_is_active_status($result['status'] ?? '') && !$pelunasan_pending && !$perpanjangan_pending): ?>
                        <?php if ($today >= new DateTime($result['tanggal_jatuh_tempo'])): ?>
                            <?php
                                $calc_extension_display = gadai_calculate_breakdown($result, $denda_display);
                                $required_for_extension = (float)$calc_extension_display['biaya_perpanjangan'];
                            ?>
                            <div class="info-section">
                                <h5>🧾 Pilih Tindakan Jatuh Tempo</h5>
                                <div class="d-grid gap-2">
                                    <!-- Perpanjangan: open a modal to instruct payment of bunga+denda+admin+asuransi and upload proof -->
                                    <button type="button" id="btnPerpanjangan" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#perpanjanganModal" style="border-radius: 50px; padding: 12px 30px; font-weight: 600;">
                                        🔁 Perpanjangan Gadai
                                    </button>

                                    <!-- Pelunasan still uses server form submit -->
                                    <form method="POST">
                                        <input type="hidden" name="no_registrasi" value="<?php echo $result['id']; ?>">
                                        <button type="submit" name="action_type" value="pelunasan" class="btn btn-success" style="border-radius: 50px; padding: 12px 30px; font-weight: 600;">
                                            💰 Pelunasan Gadai
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <div class="text-center mt-4">
                    <a href="https://wa.me/6285823091908" target="_blank" class="btn btn-success" style="border-radius: 50px; padding: 12px 30px; font-weight: 600;">
                        💬 Hubungi Kami via WhatsApp
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <?php if ($result && ($show_payment || $pelunasan_pending)): ?>
        <div class="modal fade" id="uploadBuktiModal" tabindex="-1" aria-labelledby="uploadBuktiModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius: 20px;">
                    <div class="modal-header" style="border-bottom: 1px solid #e3f2fd;">
                        <h5 class="modal-title" id="uploadBuktiModalLabel">📤 Upload Bukti Pembayaran</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="cek_status.php" enctype="multipart/form-data">
                        <div class="modal-body">
                            <input type="hidden" name="no_registrasi" value="<?php echo $result['id']; ?>">
                            <div class="mb-3">
                                <label class="form-label">NIK/KTP</label>
                                <input type="text" class="form-control" name="ktp" value="<?php echo htmlspecialchars($result['nik'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nominal Pembayaran</label>
                                <input type="text" class="form-control" id="uploadAmount" name="amount" value="<?php echo number_format($payment_amount_display, 0, ',', '.'); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Metode Pembayaran</label>
                                <select class="form-select" name="method" required>
                                    <option value="" disabled selected>Pilih metode</option>
                                    <option value="BRIVA">BRIVA</option>
                                    <option value="Transfer">Transfer</option>
                                    <option value="ATM">ATM</option>
                                    <option value="Teller">Teller</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Bukti Pembayaran</label>
                                <input type="file" class="form-control" id="uploadReceipt" name="receipt" accept="image/jpeg,image/jpg,image/png,image/gif" required>
                                <div class="mt-2" id="uploadPreview"></div>
                            </div>
                            <div class="form-text">Pastikan foto bukti pembayaran jelas dan dapat dibaca.</div>
                        </div>
                        <div class="modal-footer" style="border-top: 1px solid #e3f2fd;">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 50px;">Batal</button>
                            <button type="submit" class="btn btn-primary" name="byrcicilan" style="border-radius: 50px;">Kirim Bukti</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <?php if ($result): ?>
        <!-- Perpanjangan Modal: show required bunga + denda + admin + asuransi and allow upload proof for perpanjangan -->
        <div class="modal fade" id="perpanjanganModal" tabindex="-1" aria-labelledby="perpanjanganModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius: 20px;">
                    <div class="modal-header" style="border-bottom: 1px solid #e3f2fd;">
                        <h5 class="modal-title" id="perpanjanganModalLabel">🔁 Perpanjangan - Bunga + Denda + Admin + Asuransi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="cek_status.php" enctype="multipart/form-data">
                        <div class="modal-body">
                            <input type="hidden" name="no_registrasi" value="<?php echo $result['id']; ?>">
                            <input type="hidden" name="action_for" value="perpanjangan">
                            <div class="mb-3">
                                <label class="form-label">NIK/KTP</label>
                                <input type="text" class="form-control" name="ktp" value="<?php echo htmlspecialchars($result['nik'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Total yang harus dilunasi (Bunga + Denda + Admin 1% + Asuransi Rp 10.000)</label>
                                <input type="text" class="form-control" id="perpanjanganAmount" name="amount" value="<?php echo number_format($required_for_extension ?? 0, 0, ',', '.'); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Metode Pembayaran</label>
                                <select class="form-select" name="method" required>
                                    <option value="" disabled selected>Pilih metode</option>
                                    <option value="BRIVA">BRIVA</option>
                                    <option value="Transfer">Transfer</option>
                                    <option value="ATM">ATM</option>
                                    <option value="Teller">Teller</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Bukti Pembayaran</label>
                                <input type="file" class="form-control" id="perpanjanganReceipt" name="receipt" accept="image/jpeg,image/jpg,image/png,image/gif" required>
                                <div class="mt-2" id="perpanjanganPreview"></div>
                            </div>
                            <div class="form-text">Unggah bukti pembayaran untuk perpanjangan. Setelah diunggah, status akan dicatat sebagai <strong>Perpanjangan</strong> dan menunggu konfirmasi admin.</div>
                        </div>
                        <div class="modal-footer" style="border-top: 1px solid #e3f2fd;">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 50px;">Batal</button>
                            <button type="submit" class="btn btn-primary" name="byrcicilan" style="border-radius: 50px;">Kirim Bukti & Ajukan Perpanjangan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <?php if ($show_payment && $last_action_type === 'pelunasan'): ?>
        <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius: 20px;">
                    <div class="modal-header" style="border-bottom: 1px solid #e3f2fd;">
                        <h5 class="modal-title" id="paymentModalLabel">🏦 Pembayaran BRIVA BRI</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="info-row">
                            <span class="info-label">Nominal Pembayaran:</span>
                            <span class="info-value"><strong>Rp <?php echo number_format($payment_amount_display, 0, ',', '.'); ?></strong></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">No. BRIVA:</span>
                            <span class="info-value"><strong><?php echo $briva_number; ?></strong></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Atas Nama:</span>
                            <span class="info-value"><strong><?php echo $briva_name; ?></strong></span>
                        </div>
                        <div class="mt-3">
                            <strong>Cara bayar:</strong>
                            <div>1) Buka BRImo/ATM BRI</div>
                            <div>2) Pilih BRIVA</div>
                            <div>3) Masukkan nomor BRIVA di atas</div>
                            <div>4) Pastikan nominal sesuai, lalu konfirmasi</div>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid #e3f2fd;">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 50px;">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const modalEl = document.getElementById('paymentModal');
                if (modalEl) {
                    const paymentModal = new bootstrap.Modal(modalEl, {
                        backdrop: 'static',
                        keyboard: false
                    });
                    paymentModal.show();
                }
            });
        </script>
    <?php endif; ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const amountInput = document.getElementById('uploadAmount');
            if (amountInput) {
                amountInput.addEventListener('input', () => {
                    const raw = amountInput.value.replace(/[^0-9]/g, '');
                    if (raw === '') {
                        amountInput.value = '';
                        return;
                    }
                    const formatted = new Intl.NumberFormat('id-ID').format(parseInt(raw, 10));
                    amountInput.value = formatted;
                });
            }

            const receiptInput = document.getElementById('uploadReceipt');
            const preview = document.getElementById('uploadPreview');
            if (receiptInput && preview) {
                receiptInput.addEventListener('change', () => {
                    const file = receiptInput.files && receiptInput.files[0];
                    if (!file) {
                        preview.innerHTML = '';
                        return;
                    }
                    const reader = new FileReader();
                    reader.onload = (event) => {
                        preview.innerHTML = '<img src="' + event.target.result + '" alt="Preview" style="max-width: 120px; max-height: 120px; border-radius: 8px;">';
                    };
                    reader.readAsDataURL(file);
                });
            }
            // Perpanjangan fields (if present)
            const perpanjanganAmount = document.getElementById('perpanjanganAmount');
            if (perpanjanganAmount) {
                perpanjanganAmount.addEventListener('input', () => {
                    const raw = perpanjanganAmount.value.replace(/[^0-9]/g, '');
                    if (raw === '') {
                        perpanjanganAmount.value = '';
                        return;
                    }
                    const formatted = new Intl.NumberFormat('id-ID').format(parseInt(raw, 10));
                    perpanjanganAmount.value = formatted;
                });
            }

            const perpanjanganReceipt = document.getElementById('perpanjanganReceipt');
            const perpanjanganPreview = document.getElementById('perpanjanganPreview');
            if (perpanjanganReceipt && perpanjanganPreview) {
                perpanjanganReceipt.addEventListener('change', () => {
                    const file = perpanjanganReceipt.files && perpanjanganReceipt.files[0];
                    if (!file) {
                        perpanjanganPreview.innerHTML = '';
                        return;
                    }
                    const reader = new FileReader();
                    reader.onload = (event) => {
                        perpanjanganPreview.innerHTML = '<img src="' + event.target.result + '" alt="Preview" style="max-width: 120px; max-height: 120px; border-radius: 8px;">';
                    };
                    reader.readAsDataURL(file);
                });
            }
        });
    </script>
    <?php if ($result): ?>
    <script>
        // Auto-trigger reminder endpoint for this registration so reminders run while user views the page.
        (function() {
            const reg = <?php echo json_encode($result['id']); ?>;
            if (!reg) return;

            const endpoint = 'trigger_reminder.php?no_registrasi=' + encodeURIComponent(reg);

            async function trigger() {
                try {
                    const resp = await fetch(endpoint, {cache: 'no-store'});
                    // optional: handle response for debugging
                    const data = await resp.json();
                    // console.log('trigger_reminder', data);
                } catch (err) {
                    // ignore network errors; do not disturb the user
                    // console.error('trigger_reminder error', err);
                }
            }

            // call immediately, then every 60 seconds
            trigger();
            setInterval(trigger, 60000);
        })();
    </script>
    <?php endif; ?>
</body>
</html>
