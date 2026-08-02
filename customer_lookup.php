<?php

declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/gadai_helpers.php';
require_once __DIR__ . '/auth_guard.php';

gadai_require_admin();

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

try {
    $nik = trim((string)($_GET['nik'] ?? ''));
    $nik = preg_replace('/\s+/', '', $nik) ?? '';

    if ($nik === '' || strlen($nik) < 8) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'found' => false,
            'message' => 'NIK minimal 8 digit.',
        ]);
        exit;
    }

    $customer = gadai_get_customer_by_nik($db, $nik);
    if (!$customer) {
        echo json_encode([
            'success' => true,
            'found' => false,
            'message' => 'Customer tidak ditemukan.',
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'found' => true,
        'customer' => [
            'id' => (int)($customer['id'] ?? 0),
            'nik' => (string)($customer['nik'] ?? ''),
            'nama' => (string)($customer['nama'] ?? ''),
            'no_wa' => (string)($customer['no_wa'] ?? ''),
            'alamat' => (string)($customer['alamat'] ?? ''),
        ],
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'found' => false,
        'message' => 'Terjadi kesalahan saat mencari customer.',
    ]);
}
