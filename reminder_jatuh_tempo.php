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
            $whatsapp->notifyUserOverdue($row, $days_overdue);

            $update_sql = "UPDATE data_gadai
                           SET reminder_telat_at = NOW(),
                               reminder_telat_due_date = ?,
                               reminder_telat_last_day = ?
                           WHERE id = ?";
            $update_stmt = $db->prepare($update_sql);
            $update_stmt->execute([$row['tanggal_jatuh_tempo'], $days_overdue, $row['id']]);

            $sent_overdue++;
        } catch (Exception $e) {
            error_log("WhatsApp overdue reminder failed: " . $e->getMessage());
        }
    }

    echo "Reminder sent: {$sent_due}, overdue sent: {$sent_overdue}\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
