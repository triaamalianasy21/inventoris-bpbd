<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$title = $title ?? "Laporan - PRANATA";
ob_start();
?>

<div class="space-y-4">
  <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm">
    <h2 class="text-2xl font-extrabold text-[#4A2A12]">Laporan</h2>
    <p class="mt-1 text-sm text-[#6B4B35]">Ringkasan data peminjaman dan pengembalian.</p>
  </div>

  <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm text-sm text-[#6B4B35]">
    Laporan akan ditambahkan setelah formatnya ditentukan.
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>
