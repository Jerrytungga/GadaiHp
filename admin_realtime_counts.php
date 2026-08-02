<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once 'database.php';
require_once 'gadai_helpers.php';
require_once 'auth_guard.php';

gadai_require_admin();

$activeStatuses = ['Disetujui', 'Diperpanjang', 'Gagal Tebus', 'Siap Dijual'];
$placeholders = implode(',', array_fill(0, count($activeStatuses), '?'));

$total = 0;
$pending = 0;
$approved = 0;
$rejected = 0;
$pinjamanRequestPending = 0;

try {
    $stmtTotal = $db->query("SELECT COUNT(*) FROM data_gadai");
    $total = (int)$stmtTotal->fetchColumn();

    $stmtPending = $db->query("SELECT COUNT(*) FROM data_gadai WHERE status = 'Pending'");
    $pending = (int)$stmtPending->fetchColumn();

    $stmtRejected = $db->query("SELECT COUNT(*) FROM data_gadai WHERE status = 'Ditolak'");
    $rejected = (int)$stmtRejected->fetchColumn();

    $stmtApproved = $db->prepare("SELECT COUNT(*) FROM data_gadai WHERE status IN ($placeholders)");
    $stmtApproved->execute($activeStatuses);
    $approved = (int)$stmtApproved->fetchColumn();

    $pinjamanRows = gadai_get_pending_pinjaman_requests($db);
    $pinjamanRequestPending = is_array($pinjamanRows) ? count($pinjamanRows) : 0;

    echo json_encode([
        'success' => true,
        'counts' => [
            'total' => $total,
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
            'pinjaman_request_pending' => $pinjamanRequestPending,
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal memuat data realtime admin.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
