<?php
// ===================================================
// ROUTER HANDLER
// ===================================================
$page = $_GET['page'] ?? null;

if ($page) {
    // Jika ada parameter ?page=..., arahkan ke router utama
    require_once __DIR__ . '/app/config/Routes.php';
    exit;
}

// Jika tidak ada page, tampilkan beranda
require_once __DIR__ . '/app/views/login.php';
