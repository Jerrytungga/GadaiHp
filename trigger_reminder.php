<?php
header('Content-Type: application/json');
require_once 'database.php';
require_once 'whatsapp_helper.php';

$response = ['ok' => false, 'actions' => []];

try {
    $no = isset($_GET['no_registrasi']) ? trim($_GET['no_registrasi']) : '';
    if ($no === '' || !ctype_digit((string)$no)) {
        throw new Exception('no_registrasi required and must be numeric');
    }

    $today_date = date('Y-m-d');

    $sql = "SELECT * FROM data_gadai WHERE id = ? LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([$no]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        throw new Exception('Record not found');
    }

    // 1) 3-day reminder
    $target_date = date('Y-m-d', strtotime('+3 days'));
    if (in_array($row['status'], ['Disetujui', 'Diperpanjang'], true) && $row['tanggal_jatuh_tempo'] === $target_date) {
        // ensure not already reminded
        if (empty($row['reminder_3hari_due_date']) || $row['reminder_3hari_due_date'] !== $row['tanggal_jatuh_tempo']) {
            try {
                $whatsapp->notifyUserDueSoon($row);
                $u = $db->prepare("UPDATE data_gadai SET reminder_3hari_at = NOW(), reminder_3hari_due_date = ? WHERE id = ?");
                $u->execute([$row['tanggal_jatuh_tempo'], $row['id']]);
                $response['actions'][] = 'notify_due_3day';
            } catch (Exception $e) {
                // ignore send failure but report
                $response['actions'][] = 'notify_due_3day_failed';
            }
        }
    }

    // 2) Overdue 1..7
    if (in_array($row['status'], ['Disetujui', 'Diperpanjangan'], true) && $row['tanggal_jatuh_tempo'] < $today_date) {
        $days_overdue = (int)date_diff(new DateTime($row['tanggal_jatuh_tempo']), new DateTime($today_date))->format('%a');
        if ($days_overdue >= 1 && $days_overdue <= 7) {
            $denda_harian = 30000;
            $denda_days = min($days_overdue, 7);
            $denda_total = $denda_harian * $denda_days;

            $pokok = !empty($row['jumlah_disetujui']) ? (float)$row['jumlah_disetujui'] : (float)$row['jumlah_pinjaman'];
            $bunga_pct = isset($row['bunga']) ? (float)$row['bunga'] : 0.0;
            $lama = isset($row['lama_gadai']) ? (int)$row['lama_gadai'] : 0;
            $bunga_total = $pokok * ($bunga_pct / 100) * $lama;
            $admin_fee = round($pokok * 0.01);
            $total_tebus = $pokok + $bunga_total + $admin_fee + $denda_total;

            // Send reminder at most once per day. Check existing reminder_telat_at date.
            $last_sent_date = !empty($row['reminder_telat_at']) ? date('Y-m-d', strtotime($row['reminder_telat_at'])) : null;
            $should_send = ($last_sent_date !== $today_date);

            if ($should_send) {
                $u = $db->prepare("UPDATE data_gadai SET denda_terakumulasi = ?, total_tebus = ?, reminder_telat_at = NOW(), reminder_telat_due_date = ?, reminder_telat_last_day = ? WHERE id = ?");
                $u->execute([$denda_total, $total_tebus, $row['tanggal_jatuh_tempo'], $days_overdue, $row['id']]);
            } else {
                // update financials but do not update reminder timestamp
                $u = $db->prepare("UPDATE data_gadai SET denda_terakumulasi = ?, total_tebus = ? WHERE id = ?");
                $u->execute([$denda_total, $total_tebus, $row['id']]);
            }

            // refetch
            $stmt2 = $db->prepare("SELECT * FROM data_gadai WHERE id = ?");
            $stmt2->execute([$row['id']]);
            $fresh = $stmt2->fetch(PDO::FETCH_ASSOC);

            if ($should_send) {
                try {
                    $whatsapp->notifyUserOverdue($fresh, $days_overdue);
                    $response['actions'][] = 'notify_overdue';
                } catch (Exception $e) {
                    $response['actions'][] = 'notify_overdue_failed';
                }
            } else {
                $response['actions'][] = 'skipped_notify_overdue_already_sent_today';
            }
        }
    }

    // 3) Day 8+ -> Gagal Tebus
    if (in_array($row['status'], ['Disetujui', 'Diperpanjang'], true) && $row['tanggal_jatuh_tempo'] < $today_date) {
        $days_overdue = (int)date_diff(new DateTime($row['tanggal_jatuh_tempo']), new DateTime($today_date))->format('%a');
        if ($days_overdue >= 8 && empty($row['gagal_tebus_at'])) {
            $denda_harian = 30000;
            $denda_total = $denda_harian * 7;

            $pokok = !empty($row['jumlah_disetujui']) ? (float)$row['jumlah_disetujui'] : (float)$row['jumlah_pinjaman'];
            $bunga_pct = isset($row['bunga']) ? (float)$row['bunga'] : 0.0;
            $lama = isset($row['lama_gadai']) ? (int)$row['lama_gadai'] : 0;
            $bunga_total = $pokok * ($bunga_pct / 100) * $lama;
            $admin_fee = round($pokok * 0.01);
            $total_tebus = $pokok + $bunga_total + $admin_fee + $denda_total;

            $u = $db->prepare("UPDATE data_gadai SET status = 'Gagal Tebus', gagal_tebus_at = NOW(), denda_terakumulasi = ?, total_tebus = ? WHERE id = ?");
            $u->execute([$denda_total, $total_tebus, $row['id']]);

            // refetch
            $stmt2 = $db->prepare("SELECT * FROM data_gadai WHERE id = ?");
            $stmt2->execute([$row['id']]);
            $fresh = $stmt2->fetch(PDO::FETCH_ASSOC);

            try {
                $whatsapp->notifyUserGagalTebus($fresh);
                $whatsapp->notifyAdminGagalTebus($fresh);
                $response['actions'][] = 'mark_gagal_tebus';
            } catch (Exception $e) {
                $response['actions'][] = 'mark_gagal_tebus_failed';
            }
        }
    }

    $response['ok'] = true;
    $response['record'] = $no;
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);

?>
