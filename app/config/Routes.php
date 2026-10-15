<?php
// ===================================================
// ROUTER UTAMA PROJECT
// ===================================================

// Load semua controller
require_once __DIR__ . '/../controllers/LoginController.php';
require_once __DIR__ . '/../controllers/DashboardController.php';
require_once __DIR__ . '/../controllers/AdminController.php';
require_once __DIR__ . '/../controllers/MahasiswaController.php';
require_once __DIR__ . '/../controllers/KegiatanController.php';
require_once __DIR__ . '/../controllers/DonasiController.php';
require_once __DIR__ . '/../controllers/BarangController.php';
require_once __DIR__ . '/../controllers/PeminjamanController.php';
require_once __DIR__ . '/../controllers/NotificationsController.php';
require_once __DIR__ . '/../controllers/KalaksaController.php';
require_once __DIR__ . '/../controllers/PengurusBarangController.php';
require_once __DIR__ . '/../controllers/PetugasGudangController.php';
require_once __DIR__ . '/../controllers/PaktaIntegritasController.php';
require_once __DIR__ . '/../controllers/UserController.php';
require_once __DIR__ . '/auth_helper.php';

// Inisialisasi controller
$loginController      = new LoginController();
$dashboardController  = new DashboardController();
$adminController      = new AdminController();
$mahasiswaController  = new MahasiswaController();
$kegiatanController   = new KegiatanController();
$donasiController     = new DonasiController();
$barangController     = new BarangController();
$peminjamanController   = new PeminjamanController();
$notificationsController = new NotificationsController();
$kalaksaController = new KalaksaController();
$pengurusBarangController = new PengurusBarangController();
$petugasGudangController = new PetugasGudangController();
$paktaIntegritasController = new PaktaIntegritasController();
$userController = new UserController();
// Ambil parameter `page`
if (!isset($page)) {
    $page = 'login';
}

// ===================================================
// ROUTING LOGIKA
// ===================================================
switch ($page) {

    // ===========================
    // LOGIN & AUTH
    // ===========================
    case 'login':
        $loginController->loginPage();
        break;

    case 'login_process':
        $loginController->loginProcess();
        break;

    case 'register':
        $loginController->registerPage();
        break;

    case 'register_process':
        $loginController->registerProcess();
        break;

    case 'logout':
        $loginController->logout();
        break;

    // ===========================
    // DASHBOARD
    // ===========================
    case 'dashboard':
        $dashboardController->index();
        break;

    case 'dashboard_pemohon':
        // hanya pemohon yang lihat halaman khusus ini
        requireRole('pemohon');
        include __DIR__ . '/../views/pemohon/dashboard_pemohon.php';
        break;

    // ===========================
    // ADMIN MANAGEMENT
    // ===========================
    case 'admin':
        $adminController->index();
        break;

    case 'admin_tambah':
        // hanya user dengan role 'admin' yang bisa menambah admin baru
        requireRole('admin');
        $adminController->tambah();
        break;

    case 'admin_ubah':
        requireRole('admin');
        $adminController->ubah();
        break;

    case 'admin_hapus':
        requireRole('admin');
        $adminController->hapus();
        break;

    case 'admin_detail':
        requireRole('admin');
        $adminController->detail();
        break;
    case 'laporan':
        requireRole('admin');
        $adminController->laporan();
        break;

    // ===========================
    // USER MANAGEMENT
    // ===========================
    case 'users':
        requireRole('admin');
        $userController->index();
        break;
    case 'users_tambah':
        requireRole('admin');
        $userController->tambah();
        break;
    case 'users_ubah':
        requireRole('admin');
        $userController->ubah();
        break;
    case 'users_hapus':
        requireRole('admin');
        $userController->hapus();
        break;
    case 'users_validasi':
        requireRole('admin');
        $userController->setStatus();
        break;

    // ===========================
    // MAHASISWA MANAGEMENT
    // ===========================
    case 'mahasiswa':
        $mahasiswaController->index();
        break;

    case 'mahasiswa_tambah':
        $mahasiswaController->tambah();
        break;

    case 'mahasiswa_ubah':
        $mahasiswaController->ubah();
        break;

    case 'mahasiswa_hapus':
        $mahasiswaController->hapus();
        break;

    case 'mahasiswa_detail':
        $mahasiswaController->detail();
        break;

    // ===========================
    // BARANG (CRUD dinamis)
    // ===========================
    case 'barang_create':
        // izinkan admin atau pengurus barang
        requireRole(['admin','pengurus']);
        $barangController = new BarangController();
        $barangController->tambah();
        break;

    case 'barang_edit':
        requireRole(['admin','pengurus']);
        $barangController = new BarangController();
        $barangController->ubah();
        break;

    case 'barang_delete':
        requireRole(['admin','pengurus']);
        $barangController = new BarangController();
        $barangController->hapus();
        break;

    case 'barang_detail':
        $barangController = new BarangController();
        $barangController->detail();
        break;
    // ===========================
    // NOTIFICATIONS
    // ===========================
    case 'notifications':
        $notificationsController->index();
        break;

    case 'notification_mark_read':
        $notificationsController->markRead();
        break;

    // ===========================
    // PEMINJAMAN / PEMOHON
    // ===========================
    case 'peminjaman':
        $peminjamanController->index();
        break;

    case 'peminjaman_tambah':
        $peminjamanController->tambah();
        break;

    case 'peminjaman_detail':
        $peminjamanController->detail();
        break;

    case 'peminjaman_print':
        $peminjamanController->print();
        break;
    case 'peminjaman_preview':
        $peminjamanController->preview();
        break;
    case 'peminjaman_download':
        $peminjamanController->download();
        break;
    case 'pemohon_pengembalian':
        $peminjamanController->pengembalianPemohon();
        break;
    case 'pemohon_pengembalian_tambah':
        $peminjamanController->pengembalianPemohonTambah();
        break;
    case 'pemohon_pengembalian_download':
        $peminjamanController->pengembalianPemohonDownload();
        break;
    case 'pemohon_pengembalian_print':
        $peminjamanController->pengembalianPemohonPrint();
        break;
    case 'pemohon_pengembalian_preview':
        $peminjamanController->pengembalianPemohonPreview();
        break;
    case 'pemohon_pengembalian_download':
        $peminjamanController->pengembalianPemohonDownload();
        break;
    case 'pemohon_pengembalian_tambah':
        $peminjamanController->pengembalianPemohonTambah();
        break;
    case 'pemohon_pengembalian':
        $peminjamanController->pengembalianPemohon();
        break;
    case 'peminjaman_download':
        $peminjamanController->download();
        break;

    case 'peminjaman_approve':
        requireRole(['admin','pengurus']);
        $peminjamanController->approve();
        break;

    case 'peminjaman_reject':
        requireRole(['admin','pengurus']);
        $peminjamanController->reject();
        break;

    // ===========================
    // KEGIATAN MANAGEMENT
    // ===========================
    case 'kegiatan':
        $kegiatanController->index();
        break;

    case 'kegiatan_tambah':
        $kegiatanController->tambah();
        break;

    case 'kegiatan_ubah':
        $kegiatanController->ubah();
        break;

    case 'kegiatan_hapus':
        $kegiatanController->hapus();
        break;

    case 'kegiatan_detail':
        $kegiatanController->detail();
        break;
    // ===========================
    // DONASI MANAGEMENT
    // ===========================
    case 'donasi':
        $donasiController->index();
        break;

    case 'donasi_tambah':
        $donasiController->tambah();
        break;

    case 'donasi_ubah':
        $donasiController->ubah();
        break;

    case 'donasi_hapus':
        $donasiController->hapus();
        break;

    case 'donasi_detail':
        $donasiController->detail();
        break;

    // ===========================
    // KALAKSA
    // ===========================
    case 'kalaksa_dashboard':
        $kalaksaController->dashboard();
        break;

    case 'kalaksa_verifikasi':
        $kalaksaController->verifikasi();
        break;
    case 'kalaksa_pengembalian':
        $kalaksaController->pengembalianVerifikasi();
        break;
    case 'kalaksa_pengembalian_detail':
        $kalaksaController->pengembalianDetail();
        break;
    case 'kalaksa_pengembalian_setujui':
        $kalaksaController->pengembalianSetujui();
        break;
    case 'kalaksa_pengembalian_tolak':
        $kalaksaController->pengembalianTolak();
        break;
    case 'kalaksa_pengembalian':
        $kalaksaController->pengembalianVerifikasi();
        break;
    case 'kalaksa_pengembalian_detail':
        $kalaksaController->pengembalianDetail();
        break;
    case 'kalaksa_pengembalian_setujui':
        $kalaksaController->pengembalianSetujui();
        break;
    case 'kalaksa_pengembalian_tolak':
        $kalaksaController->pengembalianTolak();
        break;

    case 'kalaksa_setujui':
        $kalaksaController->setujui();
        break;

    case 'kalaksa_tolak':
        $kalaksaController->tolak();
        break;
    case 'kalaksa_delete_approve':
        $kalaksaController->deleteRequestApprove();
        break;
    case 'kalaksa_delete_reject':
        $kalaksaController->deleteRequestReject();
        break;


    case 'kalaksa_barang':
        $kalaksaController->barangVerifikasi();
        break;

    case 'kalaksa_barang_setujui':
        $kalaksaController->barangSetujui();
        break;

    case 'kalaksa_barang_tolak':
        $kalaksaController->barangTolak();
        break;

    case 'kalaksa_barang_detail':
        $kalaksaController->barangDetail();
        break;


    // ===========================
    // PENGURUS BARANG
    // ===========================
    case 'pengurus_dashboard':
        $pengurusBarangController->dashboard();
        break;

    case 'pengurus_peminjaman':
        $pengurusBarangController->peminjaman();
        break;
    case 'pengurus_peminjaman_edit':
        $pengurusBarangController->peminjamanEdit();
        break;
    case 'pengurus_peminjaman_delete':
        $pengurusBarangController->peminjamanDelete();
        break;
    case 'pengurus_peminjaman_edit':
        $pengurusBarangController->peminjamanEdit();
        break;
    case 'pengurus_peminjaman_delete':
        $pengurusBarangController->peminjamanDelete();
        break;

    case 'pengurus_peminjaman_tambah':
        $pengurusBarangController->tambah();
        break;

    case 'pengurus_pengembalian':
        $pengurusBarangController->pengembalian();
        break;
    case 'pengurus_pengembalian_tambah':
        $pengurusBarangController->pengembalianTambah();
        break;
    case 'pengurus_pengembalian_edit':
        $pengurusBarangController->pengembalianEdit();
        break;
    case 'pengurus_pengembalian_delete':
        $pengurusBarangController->pengembalianDelete();
        break;
    case 'pengurus_pengembalian_download':
        $pengurusBarangController->pengembalianDownload();
        break;
    case 'pengurus_pengembalian_download':
        $pengurusBarangController->pengembalianDownload();
        break;
    case 'pengurus_pengembalian_tambah':
        $pengurusBarangController->pengembalianTambah();
        break;


    case 'pengurus_pakta':
        $pengurusBarangController->paktaIntegritas();
        break;
    case 'pengurus_pakta_tambah':
        $pengurusBarangController->paktaTambah();
        break;
    case 'pengurus_pakta_edit':
        $pengurusBarangController->paktaEdit();
        break;
    case 'pengurus_pakta_delete':
        $pengurusBarangController->paktaDelete();
        break;

    case 'pengurus_laporan':
        $pengurusBarangController->laporan();
        break;

    // ===========================
    // PAKTA INTEGRITAS (PEMOHON)
    // ===========================
    case 'pakta_integritas':
        $paktaIntegritasController->index();
        break;

    case 'pakta_integritas_print':
        $paktaIntegritasController->print();
        break;
    case 'pakta_integritas_preview':
        $paktaIntegritasController->preview();
        break;
    case 'pakta_integritas_download':
        $paktaIntegritasController->download();
        break;


    // ===========================
    // PETUGAS GUDANG
    // ===========================
    case 'petugas_dashboard':
        $petugasGudangController->dashboard();
        break;

    case 'petugas_notifikasi':
        $petugasGudangController->notifikasi();
        break;

    case 'petugas_detail':
        $petugasGudangController->detail();
        break;

    // ===========================
    // DEFAULT / ERROR PAGE
    // ===========================
    default:
        echo "<h2 style='font-family: sans-serif; text-align: center; margin-top: 100px; color: red;'>⚠️ Halaman tidak ditemukan.</h2>";
        break;
}
