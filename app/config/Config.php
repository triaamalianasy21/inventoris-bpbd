<?php
// Aktifkan error reporting saat pengembangan
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set default timezone
date_default_timezone_set('Asia/Jakarta');

// Base URL (ubah sesuai hosting kamu)
define('BASE_URL', 'http://localhost/webmobile/');


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
