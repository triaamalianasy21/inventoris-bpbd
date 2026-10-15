<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$username = $_SESSION['username'] ?? 'Pengurus';

$title = $title ?? "Dashboard Pengurus Barang - PRANATA";
$waiting = $data['waiting'] ?? 0;
$approved = $data['approved'] ?? 0;
$rejected = $data['rejected'] ?? 0;

ob_start();
?>

<div class="space-y-4">
  <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm">
    <h2 class="text-2xl font-extrabold text-[#4A2A12]">Dashboard</h2>
    <p class="mt-1 text-sm text-[#6B4B35]">Selamat datang, <?= htmlspecialchars($username) ?>!</p>
  </div>

  <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <div class="rounded-2xl border border-black/10 bg-white p-5 shadow-sm">
      <div class="text-xs font-semibold uppercase text-[#B45309]">Menunggu Verifikasi</div>
      <div class="mt-2 text-3xl font-extrabold text-[#4A2A12]"><?= (int)$waiting ?></div>
    </div>
    <div class="rounded-2xl border border-black/10 bg-white p-5 shadow-sm">
      <div class="text-xs font-semibold uppercase text-[#B45309]">Disetujui</div>
      <div class="mt-2 text-3xl font-extrabold text-[#4A2A12]"><?= (int)$approved ?></div>
    </div>
    <div class="rounded-2xl border border-black/10 bg-white p-5 shadow-sm">
      <div class="text-xs font-semibold uppercase text-[#B45309]">Ditolak</div>
      <div class="mt-2 text-3xl font-extrabold text-[#4A2A12]"><?= (int)$rejected ?></div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>
