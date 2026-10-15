<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/Config.php';
require_once __DIR__ . '/../../config/auth_helper.php';

$role = $_SESSION['role'] ?? 'user';
$showNotif = ($role === 'petugas_gudang' || $role === 'pengurus_barang');
$notifUrl = ($role === 'petugas_gudang') ? 'petugas_notifikasi' : 'notifications';
$notifUnread = 0;
if ($showNotif) {
  $uid = currentUserId();
  if ($uid) {
    $notifUnread = count(getNotifications($uid, true));
  }
}
?>

<header class="h-[72px] sm:h-[76px] bg-[#F6F7F8] border-b border-black/10 flex items-center justify-between px-4 sm:px-6">
  <button id="sidebarToggle"
          class="lg:hidden inline-flex h-10 w-10 items-center justify-center rounded-full bg-white border border-black/10 hover:bg-black/5"
          aria-label="Buka menu">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
      <path d="M4 7h16M4 12h16M4 17h16" stroke="#4A2A12" stroke-width="1.8" stroke-linecap="round"/>
    </svg>
  </button>
  <?php if ($showNotif): ?>
    <a href="<?= BASE_URL ?>index.php?page=<?= $notifUrl ?>"
       class="relative inline-flex h-9 w-9 items-center justify-center rounded-full bg-white border border-black/10 hover:bg-black/5"
       title="Notifikasi">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M12 3a5 5 0 00-5 5v3l-2 2v1h14v-1l-2-2V8a5 5 0 00-5-5z"
              stroke="#4A2A12" stroke-width="1.6"/>
        <path d="M9 19a3 3 0 006 0" stroke="#4A2A12" stroke-width="1.6"/>
      </svg>
      <?php if ($notifUnread > 0): ?>
        <span class="absolute -right-1 -top-1 inline-flex min-w-[18px] h-[18px] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold leading-none text-white">
          <?= $notifUnread > 99 ? '99+' : $notifUnread ?>
        </span>
      <?php endif; ?>
    </a>
  <?php endif; ?>
</header>
