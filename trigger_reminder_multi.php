<?php
// trigger_reminder_multi.php
// Optional helper that calls trigger_reminder.php concurrently using curl_multi
// Useful when you want to push many trigger_reminder calls at once (parallel HTTP requests).

// Make sure to set $baseUrl to your local webserver path where trigger_reminder.php is reachable
$baseUrl = 'http://localhost/GadaiHp/trigger_reminder.php';
$logFile = __DIR__ . '/trigger_reminder_multi.log';
function logLine($msg) {
    global $logFile;
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $msg . PHP_EOL, FILE_APPEND | LOCK_EX);
}

require_once __DIR__ . '/database.php';

try {
    $today = date('Y-m-d');
    // Example: find registrations that have due date = tomorrow (change criteria as needed)
    $target = date('Y-m-d', strtotime('+1 day'));

    $sql = "SELECT id FROM data_gadai WHERE status IN ('Disetujui','Diperpanjangan') AND tanggal_jatuh_tempo = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$target]);
    $ids = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id');

    if (empty($ids)) {
        echo json_encode(['ok' => true, 'count' => 0, 'ids' => []]);
        exit;
    }

    logLine('Found ' . count($ids) . ' ids to trigger: ' . implode(',', $ids));

    $mh = curl_multi_init();
    $handles = [];

    foreach ($ids as $id) {
        $url = $baseUrl . '?no_registrasi=' . urlencode($id);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_multi_add_handle($mh, $ch);
        $handles[$id] = $ch;
    }

    // execute
    $running = null;
    do {
        curl_multi_exec($mh, $running);
        curl_multi_select($mh, 1.0);
    } while ($running > 0);

    $results = [];
    foreach ($handles as $id => $ch) {
        $resp = curl_multi_getcontent($ch);
        $err = curl_error($ch);
        if ($err) {
            logLine("id={$id} curl error: {$err}");
            $results[$id] = ['ok' => false, 'error' => $err];
        } else {
            $results[$id] = json_decode($resp, true);
            logLine("id={$id} response: " . substr($resp, 0, 300));
        }
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }

    curl_multi_close($mh);

    echo json_encode(['ok' => true, 'count' => count($ids), 'results' => $results]);

} catch (Exception $e) {
    logLine('Error: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}

?>