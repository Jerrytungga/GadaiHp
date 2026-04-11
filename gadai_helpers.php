<?php

if (!function_exists('gadai_get_pokok')) {
    function gadai_get_pokok(array $row): float {
        return !empty($row['jumlah_disetujui'])
            ? (float)$row['jumlah_disetujui']
            : (float)($row['jumlah_pinjaman'] ?? 0);
    }
}

if (!function_exists('gadai_get_active_statuses')) {
    function gadai_get_active_statuses(): array {
        return ['Disetujui', 'Diperpanjang'];
    }
}

if (!function_exists('gadai_active_status_sql_list')) {
    function gadai_active_status_sql_list(): string {
        return "'" . implode("','", gadai_get_active_statuses()) . "'";
    }
}

if (!function_exists('gadai_is_active_status')) {
    function gadai_is_active_status($status): bool {
        return in_array((string)$status, gadai_get_active_statuses(), true);
    }
}

if (!function_exists('gadai_can_transition')) {
    function gadai_can_transition($currentStatus, string $targetStatus): bool {
        $currentStatus = (string)$currentStatus;

        switch ($targetStatus) {
            case 'Disetujui':
            case 'Ditolak':
                return $currentStatus === 'Pending';
            case 'Diperpanjang':
            case 'Lunas':
            case 'Gagal Tebus':
                return gadai_is_active_status($currentStatus);
            default:
                return false;
        }
    }
}

if (!function_exists('gadai_calculate_days_late')) {
    function gadai_calculate_days_late($tanggal_jatuh_tempo): int {
        if (empty($tanggal_jatuh_tempo)) {
            return 0;
        }

        $dueTs = strtotime((string)$tanggal_jatuh_tempo);
        if ($dueTs === false) {
            return 0;
        }

        return max(0, (int)floor((time() - $dueTs) / 86400));
    }
}

if (!function_exists('gadai_calculate_denda')) {
    function gadai_calculate_denda($tanggal_jatuh_tempo, $persistedDenda = 0.0, int $dailyRate = 30000, int $maxDays = 7): array {
        $daysLate = gadai_calculate_days_late($tanggal_jatuh_tempo);
        $calculatedDenda = min($daysLate, $maxDays) * $dailyRate;
        $finalDenda = max((float)$persistedDenda, (float)$calculatedDenda);

        return [
            'days_late' => $daysLate,
            'daily_rate' => $dailyRate,
            'max_days' => $maxDays,
            'denda' => $finalDenda,
            'denda_calculated' => (float)$calculatedDenda,
        ];
    }
}

if (!function_exists('gadai_calculate_breakdown')) {
    function gadai_calculate_breakdown(array $row, ?float $overrideDenda = null): array {
        $pokok = gadai_get_pokok($row);
        $bungaPct = isset($row['bunga']) ? (float)$row['bunga'] : 0.0;
        $lama = isset($row['lama_gadai']) ? (int)$row['lama_gadai'] : 0;
        $bungaTotal = $pokok * ($bungaPct / 100) * $lama;
        $adminFee = round($pokok * 0.01);
        $biayaAsuransi = 10000;
        $denda = $overrideDenda !== null
            ? (float)$overrideDenda
            : (!empty($row['denda_terakumulasi']) ? (float)$row['denda_terakumulasi'] : 0.0);
        $totalTebus = $pokok + $bungaTotal + $adminFee + $biayaAsuransi + $denda;

        return [
            'pokok' => $pokok,
            'bunga_pct' => $bungaPct,
            'lama' => $lama,
            'bunga_total' => $bungaTotal,
            'admin_fee' => $adminFee,
            'biaya_asuransi' => $biayaAsuransi,
            'denda' => $denda,
            'biaya_perpanjangan' => round($bungaTotal + $adminFee + $biayaAsuransi + $denda),
            'total_tebus' => $totalTebus,
        ];
    }
}
