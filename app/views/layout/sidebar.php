<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/Config.php';

$role     = $_SESSION['role'] ?? '';
$username = $_SESSION['username'] ?? 'Username';

$currentPage = $_GET['page'] ?? 'dashboard';

  $menuPage = $currentPage;
if ($role === 'pemohon') {
  if ($currentPage === 'dashboard_pemohon') $menuPage = 'dashboard_pemohon';
  if (in_array($currentPage, ['peminjaman','peminjaman_tambah','peminjaman_detail'])) $menuPage = 'peminjaman';
  if (in_array($currentPage, ['pemohon_pengembalian','pemohon_pengembalian_tambah'])) $menuPage = 'pemohon_pengembalian';
  } elseif ($role === 'kalaksa') {
    if ($currentPage === 'kalaksa_dashboard') $menuPage = 'kalaksa_dashboard';
    if (in_array($currentPage, ['kalaksa_verifikasi','kalaksa_setujui','kalaksa_tolak'])) $menuPage = 'kalaksa_verifikasi';
    if (in_array($currentPage, ['kalaksa_pengembalian','kalaksa_pengembalian_setujui','kalaksa_pengembalian_tolak'])) $menuPage = 'kalaksa_pengembalian';
    if (in_array($currentPage, ['kalaksa_barang','kalaksa_barang_setujui','kalaksa_barang_tolak'])) $menuPage = 'kalaksa_barang';
  } elseif ($role === 'pengurus_barang') {
  if ($currentPage === 'pengurus_dashboard') $menuPage = 'pengurus_dashboard';
  if (in_array($currentPage, ['pengurus_peminjaman','pengurus_peminjaman_tambah'])) $menuPage = 'pengurus_peminjaman';
  if (in_array($currentPage, ['pengurus_pengembalian'])) $menuPage = 'pengurus_pengembalian';
  if (in_array($currentPage, ['pengurus_pakta'])) $menuPage = 'pengurus_pakta';
  if (in_array($currentPage, ['pengurus_laporan'])) $menuPage = 'pengurus_laporan';
} elseif ($role === 'petugas_gudang') {
  if ($currentPage === 'petugas_dashboard') $menuPage = 'petugas_dashboard';
  if (in_array($currentPage, ['petugas_notifikasi','petugas_detail'])) $menuPage = 'petugas_notifikasi';
}
 else {
  // Normalize pages for sidebar highlighting. Treat admin/barang CRUD pages as the 'barang' menu
 if (in_array($currentPage, ['admin', 'barang', 'barang_create', 'barang_edit', 'barang_delete'])) {
    $menuPage = 'barang';
  }
  if ($currentPage === 'laporan') $menuPage = 'laporan';
  if (in_array($currentPage, ['users', 'users_tambah', 'users_ubah', 'users_hapus', 'users_validasi'])) $menuPage = 'users';
}

$active   = "flex items-center justify-between px-4 py-3 rounded-xl bg-white/45";
$inactive = "flex items-center justify-between px-4 py-3 rounded-xl bg-white/20 hover:bg-white/35 transition";
?>

<aside id="sidebar"
       class="w-[260px] min-h-screen bg-[#E9A15D] text-[#4A2A12] fixed top-0 left-0 z-50 flex flex-col justify-between overflow-y-auto
              -translate-x-full transition-transform duration-200 ease-out lg:translate-x-0"
       style="padding-bottom: calc(env(safe-area-inset-bottom) + 28px);">
  <div>
    <!-- Brand -->
    <div class="px-6 py-5 flex items-center gap-3 border-b border-black/10">
      <div class="w-9 h-9 rounded-xl bg-[#D97A2B] flex items-center justify-center shadow-sm">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M12 2l8 4.5v11L12 22 4 17.5v-11L12 2z" stroke="white" stroke-width="1.8" />
          <path d="M12 2v20" stroke="white" stroke-width="1.2" opacity="0.7" />
          <path d="M4 6.5l8 4 8-4" stroke="white" stroke-width="1.2" opacity="0.7" />
        </svg>
      </div>
      <div class="font-extrabold tracking-wide text-lg">PRANATA</div>
    </div>

    <!-- Menu -->
    <nav class="px-4 py-5 space-y-2">
      <?php if ($role === 'pengurus_barang'): ?>
        <a href="<?= BASE_URL ?>index.php?page=pengurus_dashboard" class="<?= $menuPage === 'pengurus_dashboard' ? $active : $inactive ?>">
          <span class="flex items-center gap-3">
            <span class="w-5 h-5 inline-flex items-center justify-center">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                <path d="M4 13h7V4H4v9zm9 7h7V11h-7v9zM4 20h7v-5H4v5zm9-9h7V4h-7v7z"
                      stroke="currentColor" stroke-width="1.6"/>
              </svg>
            </span>
            <span class="font-semibold">Dashboard</span>
          </span>
        </a>

        <a href="<?= BASE_URL ?>index.php?page=pengurus_peminjaman" class="<?= $menuPage === 'pengurus_peminjaman' ? $active : $inactive ?>">
          <span class="flex items-center gap-3">
            <span class="w-5 h-5 inline-flex items-center justify-center">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                <path d="M6 7h12M6 12h12M6 17h8" stroke="currentColor" stroke-width="1.6"/>
              </svg>
            </span>
            <span class="font-semibold">Kelola Peminjaman</span>
          </span>
        </a>

        <a href="<?= BASE_URL ?>index.php?page=pengurus_pakta" class="<?= $menuPage === 'pengurus_pakta' ? $active : $inactive ?>">
          <span class="flex items-center gap-3">
            <span class="w-5 h-5 inline-flex items-center justify-center">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                <path d="M6 2h9l3 3v17H6V2z" stroke="currentColor" stroke-width="1.6"/>
                <path d="M9 11h6M9 15h6M9 7h3" stroke="currentColor" stroke-width="1.6"/>
              </svg>
            </span>
            <span class="font-semibold">Kelola Pakta Integritas</span>
          </span>
        </a>

        <a href="<?= BASE_URL ?>index.php?page=pengurus_pengembalian" class="<?= $menuPage === 'pengurus_pengembalian' ? $active : $inactive ?>">
          <span class="flex items-center gap-3">
            <span class="w-5 h-5 inline-flex items-center justify-center">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.6"/>
              </svg>
            </span>
            <span class="font-semibold">Kelola Pengembalian</span>
          </span>
        </a>

        <a href="<?= BASE_URL ?>index.php?page=pengurus_laporan" class="<?= $menuPage === 'pengurus_laporan' ? $active : $inactive ?>">
          <span class="flex items-center gap-3">
            <span class="w-5 h-5 inline-flex items-center justify-center">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                <path d="M6 2h9l3 3v17H6V2z" stroke="currentColor" stroke-width="1.6"/>
                <path d="M9 11h6M9 15h6M9 7h3" stroke="currentColor" stroke-width="1.6"/>
              </svg>
            </span>
            <span class="font-semibold">Laporan</span>
          </span>
        </a>
      <?php elseif ($role === 'kalaksa'): ?>
        <a href="<?= BASE_URL ?>index.php?page=kalaksa_dashboard" class="<?= $menuPage === 'kalaksa_dashboard' ? $active : $inactive ?>">
          <span class="flex items-center gap-3">
            <span class="w-5 h-5 inline-flex items-center justify-center">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                <path d="M4 13h7V4H4v9zm9 7h7V11h-7v9zM4 20h7v-5H4v5zm9-9h7V4h-7v7z"
                      stroke="currentColor" stroke-width="1.6"/>
              </svg>
            </span>
            <span class="font-semibold">Dashboard</span>
          </span>
        </a>

      <a href="<?= BASE_URL ?>index.php?page=kalaksa_verifikasi" class="<?= $menuPage === 'kalaksa_verifikasi' ? $active : $inactive ?>">
        <span class="flex items-center gap-3">
          <span class="w-5 h-5 inline-flex items-center justify-center">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                <path d="M6 7h12M6 12h12M6 17h8" stroke="currentColor" stroke-width="1.6"/>
              </svg>
            </span>
            <span class="font-semibold">Verifikasi Peminjaman</span>
        </span>
      </a>

      <a href="<?= BASE_URL ?>index.php?page=kalaksa_pengembalian" class="<?= $menuPage === 'kalaksa_pengembalian' ? $active : $inactive ?>">
        <span class="flex items-center gap-3">
          <span class="w-5 h-5 inline-flex items-center justify-center">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
              <path d="M7 7h10M7 12h6M7 17h4" stroke="currentColor" stroke-width="1.6"/>
              <path d="M17 9l3 3-3 3" stroke="currentColor" stroke-width="1.6"/>
            </svg>
          </span>
          <span class="font-semibold">Verifikasi Pengembalian</span>
        </span>
      </a>

      <a href="<?= BASE_URL ?>index.php?page=kalaksa_barang" class="<?= $menuPage === 'kalaksa_barang' ? $active : $inactive ?>">
        <span class="flex items-center gap-3">
          <span class="w-5 h-5 inline-flex items-center justify-center">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                <path d="M21 8V7l-9-4-9 4v1m18 0-9 4-9-4m18 0v9l-9 4-9-4V8" stroke="currentColor" stroke-width="1.6"/>
              </svg>
            </span>
            <span class="font-semibold">Verifikasi Barang Masuk</span>
          </span>
        </a>

      <?php elseif ($role === 'petugas_gudang'): ?>
        <a href="<?= BASE_URL ?>index.php?page=petugas_dashboard" class="<?= $menuPage === 'petugas_dashboard' ? $active : $inactive ?>">
          <span class="flex items-center gap-3">
            <span class="w-5 h-5 inline-flex items-center justify-center">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                <path d="M4 13h7V4H4v9zm9 7h7V11h-7v9zM4 20h7v-5H4v5zm9-9h7V4h-7v7z"
                      stroke="currentColor" stroke-width="1.6"/>
              </svg>
            </span>
            <span class="font-semibold">Dashboard</span>
          </span>
        </a>
      <?php elseif ($role === 'pemohon'): ?>
        <a href="<?= BASE_URL ?>index.php?page=dashboard_pemohon" class="<?= $menuPage === 'dashboard_pemohon' ? $active : $inactive ?>">
          <span class="flex items-center gap-3">
            <span class="w-5 h-5 inline-flex items-center justify-center">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                <path d="M4 13h7V4H4v9zm9 7h7V11h-7v9zM4 20h7v-5H4v5zm9-9h7V4h-7v7z"
                      stroke="currentColor" stroke-width="1.6"/>
              </svg>
            </span>
            <span class="font-semibold">Dashboard</span>
          </span>
        </a>

          <a href="<?= BASE_URL ?>index.php?page=peminjaman" class="<?= $menuPage === 'peminjaman' ? $active : $inactive ?>">
            <span class="flex items-center gap-3">
              <span class="w-5 h-5 inline-flex items-center justify-center">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                  <path d="M6 7h12M6 12h12M6 17h8" stroke="currentColor" stroke-width="1.6"/>
                </svg>
              </span>
              <span class="font-semibold">Menu Peminjaman</span>
            </span>
          </a>

          <a href="<?= BASE_URL ?>index.php?page=pemohon_pengembalian" class="<?= $menuPage === 'pemohon_pengembalian' ? $active : $inactive ?>">
            <span class="flex items-center gap-3">
              <span class="w-5 h-5 inline-flex items-center justify-center">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                  <path d="M7 7h10M7 12h6M7 17h4" stroke="currentColor" stroke-width="1.6"/>
                  <path d="M17 9l3 3-3 3" stroke="currentColor" stroke-width="1.6" />
                </svg>
              </span>
              <span class="font-semibold">Menu Pengembalian</span>
            </span>
          </a>
      <?php else: ?>
        <a href="<?= BASE_URL ?>index.php?page=dashboard" class="<?= $menuPage === 'dashboard' ? $active : $inactive ?>">
          <span class="flex items-center gap-3">
            <span class="w-5 h-5 inline-flex items-center justify-center">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                <path d="M4 13h7V4H4v9zm9 7h7V11h-7v9zM4 20h7v-5H4v5zm9-9h7V4h-7v7z"
                      stroke="currentColor" stroke-width="1.6"/>
              </svg>
            </span>
            <span class="font-semibold">Dashboard</span>
          </span>
        </a>

        <a href="<?= BASE_URL ?>index.php?page=admin" class="<?= $menuPage === 'barang' ? $active : $inactive ?>">
          <span class="flex items-center gap-3">
            <span class="w-5 h-5 inline-flex items-center justify-center">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                <path d="M21 8V7l-9-4-9 4v1m18 0-9 4-9-4m18 0v9l-9 4-9-4V8"
                      stroke="currentColor" stroke-width="1.6"/>
              </svg>
            </span>
            <span class="font-semibold">Barang</span>
          </span>
        </a>

        <a href="<?= BASE_URL ?>index.php?page=users" class="<?= $menuPage === 'users' ? $active : $inactive ?>">
          <span class="flex items-center gap-3">
            <span class="w-5 h-5 inline-flex items-center justify-center">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                <path d="M16 11a4 4 0 10-8 0 4 4 0 008 0z" stroke="currentColor" stroke-width="1.6"/>
                <path d="M4 20a6 6 0 0116 0" stroke="currentColor" stroke-width="1.6"/>
              </svg>
            </span>
            <span class="font-semibold">Kelola User</span>
          </span>
        </a>

        <a href="<?= BASE_URL ?>index.php?page=laporan" class="<?= $menuPage === 'laporan' ? $active : $inactive ?>">
          <span class="flex items-center gap-3">
            <span class="w-5 h-5 inline-flex items-center justify-center">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                <path d="M6 2h9l3 3v17H6V2z" stroke="currentColor" stroke-width="1.6"/>
                <path d="M9 11h6M9 15h6M9 7h3" stroke="currentColor" stroke-width="1.6"/>
              </svg>
            </span>
            <span class="font-semibold">Laporan</span>
          </span>
        </a>
      <?php endif; ?>
    </nav>
  </div>

  <!-- Footer User -->
  <div class="px-6 py-5 border-t border-black/10" style="padding-bottom: calc(env(safe-area-inset-bottom) + 28px);">
    <div class="flex items-center gap-3 mb-3">
      <div class="w-9 h-9 rounded-full bg-white/60 flex items-center justify-center">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
          <path d="M20 21a8 8 0 10-16 0" stroke="currentColor" stroke-width="1.6"/>
          <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.6"/>
        </svg>
      </div>
      <div>
        <div class="font-bold leading-tight"><?= htmlspecialchars($username) ?></div>
        <div class="text-xs opacity-80"><?= htmlspecialchars($role ?: 'Admin') ?></div>
      </div>
    </div>

    <a id="logoutSidebar"
       href="<?= BASE_URL ?>index.php?page=logout"
       class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white/40 hover:bg-white/55 transition font-semibold">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
        <path d="M10 17l5-5-5-5" stroke="currentColor" stroke-width="1.8"/>
        <path d="M15 12H3" stroke="currentColor" stroke-width="1.8"/>
        <path d="M21 3v18" stroke="currentColor" stroke-width="1.8"/>
      </svg>
      Keluar
    </a>
  </div>
</aside>
