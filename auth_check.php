<?php
/**
 * Admin Authentication Check
 * Include file ini di awal admin_verifikasi.php untuk proteksi login
 */

// Start session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug: Log session status
error_log("Auth check - Session ID: " . session_id());
error_log("Auth check - admin_logged_in: " . (isset($_SESSION['admin_logged_in']) ? 'true' : 'false'));

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Not logged in, redirect to login page
    error_log("Auth check FAILED - Redirecting to login.php");
    header("Location: login.php");
    exit;
}

error_log("Auth check PASSED - User: " . ($_SESSION['admin_user'] ?? 'unknown'));

// Optional: Check session timeout (30 minutes)
$timeout_duration = 1800; // 30 minutes
if (isset($_SESSION['login_time'])) {
    $elapsed_time = time() - $_SESSION['login_time'];
    if ($elapsed_time > $timeout_duration) {
        // Session expired
        session_destroy();
        header("Location: login.php?timeout=1");
        exit;
    }
}

// Update last activity time
$_SESSION['login_time'] = time();
?>