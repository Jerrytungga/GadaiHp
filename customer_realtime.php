<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once 'database.php';
require_once 'auth_guard.php';

gadai_require_customer();

$customerId = (int)($_SESSION['customer_id'] ?? 0);
$customerNik = trim((string)($_SESSION['customer_nik'] ?? ''));

if ($customerId <= 0 && $customerNik === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Sesi customer tidak valid.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$stmt = $db->prepare("SELECT dg.id, dg.status, dg.jumlah_pinjaman, dg.jumlah_disetujui, dg.total_tebus, dg.updated_at
    FROM data_gadai dg
    WHERE dg.customer_id = ? OR dg.nik = ?
    ORDER BY dg.created_at DESC");
$stmt->execute([$customerId, $customerNik]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$snapshot = [];
$rowPayload = [];
$activeCount = 0;

foreach ($rows as $row) {
    $id = (int)($row['id'] ?? 0);
    $status = (string)($row['status'] ?? '');
    $disetujui = !empty($row['jumlah_disetujui']) ? (float)$row['jumlah_disetujui'] : null;
    $totalTebus = !empty($row['total_tebus']) ? (float)$row['total_tebus'] : null;

    $snapshot[(string)$id] = [
        'id' => $id,
        'status' => $status,
        'updated_at' => (string)($row['updated_at'] ?? ''),
        'disetujui' => $disetujui,
        'total_tebus' => $totalTebus,
    ];

    $badgeClass = 'badge-secondary';
    if ($status === 'Pending') {
        $badgeClass = 'status-Pending';
    } elseif (in_array($status, ['Disetujui', 'Diperpanjang'], true)) {
        $badgeClass = 'status-Disetujui';
    } elseif ($status === 'Ditolak') {
        $badgeClass = 'status-Ditolak';
    } elseif ($status === 'Lunas') {
        $badgeClass = 'status-Lunas';
    } elseif (in_array($status, ['Gagal Tebus', 'Siap Dijual'], true)) {
        $badgeClass = 'status-Gagal';
    }

    $rowPayload[(string)$id] = [
        'status_label' => $status !== '' ? $status : '-',
        'badge_class' => $badgeClass,
        'disetujui_display' => $disetujui !== null ? number_format($disetujui, 0, ',', '.') : '-',
        'updated_at_display' => !empty($row['updated_at']) ? date('d M Y H:i', strtotime((string)$row['updated_at'])) : '-',
    ];

    if (in_array($status, ['Disetujui', 'Diperpanjang', 'Gagal Tebus', 'Siap Dijual'], true)) {
        $activeCount++;
    }
}

echo json_encode([
    'success' => true,
    'snapshot' => $snapshot,
    'rows' => $rowPayload,
    'total_count' => count($rows),
    'active_count' => $activeCount,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
