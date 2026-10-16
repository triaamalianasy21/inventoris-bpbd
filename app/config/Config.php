<?php
// Aktifkan error reporting saat pengembangan
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set default timezone
date_default_timezone_set('Asia/Jakarta');

// Base URL otomatis & dinamis mengikuti domain tempat web dijalankan
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'inventoris-bpbd-production.up.railway.app';
define('BASE_URL', $protocol . $host . '/');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fungsi redirect singkat
function redirect($path)
{
    header("Location: " . BASE_URL . $path);
    exit;
}