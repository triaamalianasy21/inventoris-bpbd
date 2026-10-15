<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$username = $_SESSION['username'] ?? 'Petugas Gudang';

$title = $title ?? "Dashboard Petugas Gudang - PRANATA";
$peminjamanToday = $data['peminjamanToday'] ?? 0;
$barangMasukToday = $data['barangMasukToday'] ?? 0;

ob_start();
?>

<div class="space-y-4">
  <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm">
    <h2 class="text-2xl font-extrabold text-[#4A2A12]">Dashboard</h2>
    <p class="mt-1 text-sm text-[#6B4B35]">Selamat datang Petugas Gudang!</p>
  </div>

  <div class="grid gap-4 md:grid-cols-2">
    <div class="rounded-2xl border border-black/10 bg-white p-5 shadow-sm">
      <div class="flex items-center justify-between">
        <div class="text-xs font-semibold uppercase text-[#6B4B35]">Peminjaman Hari Ini</div>
        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[#FCE4C4] text-[#B45309]">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
            <path d="M12 3l8 4.5v9L12 21 4 16.5v-9L12 3z" stroke="currentColor" stroke-width="1.6"/>
            <path d="M4 7.5l8 4 8-4" stroke="currentColor" stroke-width="1.2"/>
          </svg>
        </span>
      </div>
      <div class="mt-3 text-3xl font-extrabold text-[#4A2A12]"><?= (int)$peminjamanToday ?></div>
    </div>
    <div class="rounded-2xl border border-black/10 bg-white p-5 shadow-sm">
      <div class="flex items-center justify-between">
        <div class="text-xs font-semibold uppercase text-[#6B4B35]">Barang Masuk Hari Ini</div>
        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[#DCFCE7] text-[#16A34A]">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
            <path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </span>
      </div>
      <div class="mt-3 text-3xl font-extrabold text-[#4A2A12]"><?= (int)$barangMasukToday ?></div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>
