<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!function_exists('gadai_redirect_with_message')) {
    function gadai_redirect_with_message(string $location): void {
        header('Location: ' . $location);
        exit;
    }
}

if (!function_exists('gadai_require_admin')) {
    function gadai_require_admin(): void {
        $isAdmin = !empty($_SESSION['admin_logged_in']) && !empty($_SESSION['admin_id']);
        if (!$isAdmin) {
            gadai_redirect_with_message('login.php?role=admin');
        }
    }
}

if (!function_exists('gadai_require_customer')) {
    function gadai_require_customer(): void {
        $isCustomer = !empty($_SESSION['customer_logged_in']) && !empty($_SESSION['customer_id']);
        if (!$isCustomer) {
            gadai_redirect_with_message('login.php?role=customer');
        }
    }
}
