<?php
require_once 'database.php';
require_once 'whatsapp_helper.php';

$target_date = date('Y-m-d', strtotime('+3 days'));
$today_date = date('Y-m-d');

try {
    $sql_due = "SELECT * FROM data_gadai
                WHERE status IN ('Disetujui', 'Diperpanjang')
                  AND tanggal_jatuh_tempo = ?
                  AND (aksi_jatuh_tempo IS NULL OR aksi_jatuh_tempo_at < tanggal_jatuh_tempo OR aksi_jatuh_tempo = 'Pelunasan')
                  AND (reminder_3hari_due_date IS NULL OR reminder_3hari_due_date <> tanggal_jatuh_tempo)";
    $stmt_due = $db->prepare($sql_due);
    $stmt_due->execute([$target_date]);
    $rows_due = $stmt_due->fetchAll(PDO::FETCH_ASSOC);

    $sent_due = 0;
    foreach ($rows_due as $row) {
        try {
            $whatsapp->notifyUserDueSoon($row);

            $update_sql = "UPDATE data_gadai
                           SET reminder_3hari_at = NOW(), reminder_3hari_due_date = ?
                           WHERE id = ?";
            $update_stmt = $db->prepare($update_sql);
            $update_stmt->execute([$row['tanggal_jatuh_tempo'], $row['id']]);

            $sent_due++;
        } catch (Exception $e) {
            error_log("WhatsApp reminder failed: " . $e->getMessage());
        }
    }

    $sql_overdue = "SELECT *, DATEDIFF(?, tanggal_jatuh_tempo) AS hari_telat
                    FROM data_gadai
                    WHERE status IN ('Disetujui', 'Diperpanjang')
                      AND tanggal_jatuh_tempo < ?
                      AND (aksi_jatuh_tempo IS NULL OR aksi_jatuh_tempo_at < tanggal_jatuh_tempo OR aksi_jatuh_tempo = 'Pelunasan')
                      AND DATEDIFF(?, tanggal_jatuh_tempo) BETWEEN 1 AND 7
                      AND (
                        reminder_telat_due_date IS NULL
                        OR reminder_telat_due_date <> tanggal_jatuh_tempo
                        OR reminder_telat_last_day IS NULL
                        OR reminder_telat_last_day <> DATEDIFF(?, tanggal_jatuh_tempo)
                      )";
    $stmt_overdue = $db->prepare($sql_overdue);
    $stmt_overdue->execute([$today_date, $today_date, $today_date, $today_date]);
    $rows_overdue = $stmt_overdue->fetchAll(PDO::FETCH_ASSOC);

    $sent_overdue = 0;
    foreach ($rows_overdue as $row) {
        $days_overdue = (int)$row['hari_telat'];
        try {
            // Calculate accumulated penalty (max 7 days)
            $denda_harian = 30000;
            $denda_days = min($days_overdue, 7);
            $denda_total = $denda_harian * $denda_days;

            // Financial recalculation
            $pokok = !empty($row['jumlah_disetujui']) ? (float)$row['jumlah_disetujui'] : (float)$row['jumlah_pinjaman'];
            $bunga_pct = isset($row['bunga']) ? (float)$row['bunga'] : 0.0;
            $lama = isset($row['lama_gadai']) ? (int)$row['lama_gadai'] : 0;
            $bunga_total = $pokok * ($bunga_pct / 100) * $lama;
            $admin_fee = round($pokok * 0.01);
            $total_tebus = $pokok + $bunga_total + $admin_fee + $denda_total;

            // Update DB: denda_terakumulasi and total_tebus, and reminder metadata
            $update_sql = "UPDATE data_gadai
                           SET denda_terakumulasi = ?,
                               total_tebus = ?,
                               reminder_telat_at = NOW(),
                               reminder_telat_due_date = ?,
                               reminder_telat_last_day = ?
                           WHERE id = ?";
            $update_stmt = $db->prepare($update_sql);
            $update_stmt->execute([$denda_total, $total_tebus, $row['tanggal_jatuh_tempo'], $days_overdue, $row['id']]);

            // Refresh row data for WhatsApp message
            $stmt_ref = $db->prepare("SELECT * FROM data_gadai WHERE id = ?");
            $stmt_ref->execute([$row['id']]);
            $fresh = $stmt_ref->fetch(PDO::FETCH_ASSOC);

            // Send overdue reminder with updated totals
            $whatsapp->notifyUserOverdue($fresh, $days_overdue);

            $sent_overdue++;
        } catch (Exception $e) {
            error_log("WhatsApp overdue reminder failed: " . $e->getMessage());
        }
    }

    // Handle items that have reached day 8 or more -> mark as Gagal Tebus and notify
    $sql_failed = "SELECT *, DATEDIFF(?, tanggal_jatuh_tempo) AS hari_telat
                   FROM data_gadai
                   WHERE status IN ('Disetujui', 'Diperpanjang')
                     AND tanggal_jatuh_tempo < ?
                     AND DATEDIFF(?, tanggal_jatuh_tempo) >= 8
                     AND (gagal_tebus_at IS NULL)";
    $stmt_failed = $db->prepare($sql_failed);
    $stmt_failed->execute([$today_date, $today_date, $today_date]);
    $rows_failed = $stmt_failed->fetchAll(PDO::FETCH_ASSOC);

    $sent_failed = 0;
    foreach ($rows_failed as $row) {
        try {
            // Ensure denda capped to 7 days
            $denda_harian = 30000;
            $denda_total = $denda_harian * 7;

            // Financial recalculation
            $pokok = !empty($row['jumlah_disetujui']) ? (float)$row['jumlah_disetujui'] : (float)$row['jumlah_pinjaman'];
            $bunga_pct = isset($row['bunga']) ? (float)$row['bunga'] : 0.0;
            $lama = isset($row['lama_gadai']) ? (int)$row['lama_gadai'] : 0;
            $bunga_total = $pokok * ($bunga_pct / 100) * $lama;
            $admin_fee = round($pokok * 0.01);
            $total_tebus = $pokok + $bunga_total + $admin_fee + $denda_total;

            // Update status to Gagal Tebus and persist penalty/total
            $update_sql = "UPDATE data_gadai
                           SET status = 'Gagal Tebus',
                               gagal_tebus_at = NOW(),
                               denda_terakumulasi = ?,
                               total_tebus = ?
                           WHERE id = ?";
            $update_stmt = $db->prepare($update_sql);
            $update_stmt->execute([$denda_total, $total_tebus, $row['id']]);

            // Refresh row
            $stmt_ref = $db->prepare("SELECT * FROM data_gadai WHERE id = ?");
            $stmt_ref->execute([$row['id']]);
            $fresh = $stmt_ref->fetch(PDO::FETCH_ASSOC);

            // Notify user and admin
            $whatsapp->notifyUserGagalTebus($fresh);
            $whatsapp->notifyAdminGagalTebus($fresh);

            $sent_failed++;
        } catch (Exception $e) {
            error_log("Failed to mark Gagal Tebus: " . $e->getMessage());
        }
    }

    echo "Reminder sent: {$sent_due}, overdue sent: {$sent_overdue}\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
