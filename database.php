<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!function_exists('gadai_db_config')) {
    function gadai_db_config(): array
    {
        return [
            'host' => getenv('GADAI_DB_HOST') ?: 'localhost',
            'user' => getenv('GADAI_DB_USER') ?: 'root',
            'pass' => getenv('GADAI_DB_PASS') ?: '',
            'name' => getenv('GADAI_DB_NAME') ?: 'GadaiCepat',
            'fallback_names' => ['gadaicepat'],
        ];
    }
}

if (!function_exists('gadai_connect_database')) {
    function gadai_connect_database(): array
    {
        $config = gadai_db_config();
        $mysqli = new mysqli($config['host'], $config['user'], $config['pass']);
        $mysqli->set_charset('utf8mb4');

        $candidateNames = array_values(array_unique(array_filter(array_merge([$config['name']], $config['fallback_names']))));
        $selectedDatabase = null;

        foreach ($candidateNames as $candidate) {
            $safeCandidate = $mysqli->real_escape_string($candidate);
            $result = $mysqli->query("SHOW DATABASES LIKE '{$safeCandidate}'");

            if ($result && $result->num_rows > 0) {
                $selectedDatabase = $candidate;
                break;
            }
        }

        if ($selectedDatabase === null) {
            $selectedDatabase = $config['name'];
            $mysqli->query("CREATE DATABASE IF NOT EXISTS `{$selectedDatabase}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }

        $mysqli->select_db($selectedDatabase);
        $mysqli->set_charset('utf8mb4');

        $db = new PDO(
            "mysql:host={$config['host']};dbname={$selectedDatabase};charset=utf8mb4",
            $config['user'],
            $config['pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        return [$mysqli, $db, $selectedDatabase];
    }
}

try {
    [$conn, $db, $database_name] = gadai_connect_database();
} catch (Throwable $e) {
    die('Gagal koneksi database: ' . $e->getMessage());
}

?>