<?php
class DashboardController
{
    public function index()
    {
        // Pastikan session aktif (tanpa error duplikat)
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Cek apakah sudah login
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }

        // Batasi akses hanya untuk admin
        if ($_SESSION['role'] !== 'admin') {
            echo "<h2 style='color:red; text-align:center; margin-top:40px;'>⚠️ Akses ditolak. Anda bukan admin.</h2>";
            exit;
        }

        // Load tampilan dashboard admin
        // Pastikan file ini memanfaatkan layout main.php (tidak include header/footer lagi)
        require_once __DIR__ . '/../views/admin/dashboard.php';
    }
}
