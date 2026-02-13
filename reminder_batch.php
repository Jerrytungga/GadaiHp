<?php
// reminder_batch.php
// Run this PHP script from CLI or via web to process reminders in batch.
// It will:
//  - send "due soon" reminders (3 days before)
//  - send overdue reminders (days 1..7) once per day per record
//  - mark Gagal Tebus on day 8+

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/whatsapp_helper.php';

$logFile = __DIR__ . '/reminder_batch.log';
function logLine($msg) {
    global $logFile;
    $line = date('[Y-m-d H:i:s] ') . $msg . PHP_EOL;
    file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

try {
    $today = date('Y-m-d');
    $target_due_in_3 = date('Y-m-d', strtotime('+3 days'));

    logLine("Starting batch reminder run. Today={$today}, target_due_in_3={$target_due_in_3}");

    // 1) Due-in-3-days reminders
    $sql3 = "SELECT * FROM data_gadai WHERE status IN ('Disetujui','Diperpanjangan') AND tanggal_jatuh_tempo = ?";
    $stmt3 = $db->prepare($sql3);
    $stmt3->execute([$target_due_in_3]);
    $rows3 = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    logLine("Found " . count($rows3) . " records with due in 3 days");

    foreach ($rows3 as $row) {
        try {
            // ensure not already reminded for this due date
            if (empty($row['reminder_3hari_due_date']) || $row['reminder_3hari_due_date'] !== $row['tanggal_jatuh_tempo']) {
                $whatsapp->notifyUserDueSoon($row);
                $u = $db->prepare("UPDATE data_gadai SET reminder_3hari_at = NOW(), reminder_3hari_due_date = ? WHERE id = ?");
                $u->execute([$row['tanggal_jatuh_tempo'], $row['id']]);
                logLine("notify_due_3day sent for id={$row['id']}");
            } else {
                logLine("skip notify_due_3day already sent today for id={$row['id']}");
            }
        } catch (Exception $e) {
            logLine("notify_due_3day failed for id={$row['id']}: " . $e->getMessage());
        }
    }

    // 2) Overdue reminders (1..7 days) - update financials and send at most once per day
    $sqlOver = "SELECT * FROM data_gadai WHERE status IN ('Disetujui','Diperpanjangan') AND tanggal_jatuh_tempo < ?";
    $stmtOver = $db->prepare($sqlOver);
    $stmtOver->execute([$today]);
    $rowsOver = $stmtOver->fetchAll(PDO::FETCH_ASSOC);
    logLine("Found " . count($rowsOver) . " overdue records (tanggal_jatuh_tempo < today)");

    foreach ($rowsOver as $row) {
        try {
            $days_overdue = (int)date_diff(new DateTime($row['tanggal_jatuh_tempo']), new DateTime($today))->format('%a');
            if ($days_overdue < 1) continue;

            // handle day 1..7
            if ($days_overdue >=1 && $days_overdue <= 7) {
                $denda_harian = 30000;
                $denda_days = min($days_overdue, 7);
                $denda_total = $denda_harian * $denda_days;

                $pokok = !empty($row['jumlah_disetujui']) ? (float)$row['jumlah_disetujui'] : (float)$row['jumlah_pinjaman'];
                $bunga_pct = isset($row['bunga']) ? (float)$row['bunga'] : 0.0;
                $lama = isset($row['lama_gadai']) ? (int)$row['lama_gadai'] : 0;
                $bunga_total = $pokok * ($bunga_pct / 100) * $lama;
                $admin_fee = round($pokok * 0.01);
                $total_tebus = $pokok + $bunga_total + $admin_fee + $denda_total;

                $last_sent_date = !empty($row['reminder_telat_at']) ? date('Y-m-d', strtotime($row['reminder_telat_at'])) : null;
                $should_send = ($last_sent_date !== $today);

                if ($should_send) {
                    $u = $db->prepare("UPDATE data_gadai SET denda_terakumulasi = ?, total_tebus = ?, reminder_telat_at = NOW(), reminder_telat_due_date = ?, reminder_telat_last_day = ? WHERE id = ?");
                    $u->execute([$denda_total, $total_tebus, $row['tanggal_jatuh_tempo'], $days_overdue, $row['id']]);
                } else {
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
                        logLine("notify_overdue sent for id={$row['id']} days={$days_overdue}");
                    } catch (Exception $e) {
                        logLine("notify_overdue failed for id={$row['id']}: " . $e->getMessage());
                    }
                } else {
                    logLine("skipped notify_overdue already_sent_today for id={$row['id']}");
                }
            }

            // day 8+ -> Gagal Tebus
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
                    logLine("marked gagal_tebus for id={$row['id']}");
                } catch (Exception $e) {
                    logLine("notify gagal_tebus failed for id={$row['id']}: " . $e->getMessage());
                }
            }

        } catch (Exception $e) {
            logLine("processing failed for id={$row['id']}: " . $e->getMessage());
        }
    }

    logLine("Batch reminder run finished.");
    echo json_encode(['ok' => true, 'message' => 'Batch run completed']);
} catch (Exception $e) {
    logLine('Batch run error: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}

?>