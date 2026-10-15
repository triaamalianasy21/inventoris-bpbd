<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$err = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']);
$ok = $_SESSION['flash_ok'] ?? null; unset($_SESSION['flash_ok']);

$title = $title ?? "Ubah Peminjaman - PRANATA";
ob_start();
?>

<div class="space-y-4">
  <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm">
    <h2 class="text-2xl font-extrabold text-[#4A2A12]">Ubah Peminjaman</h2>
    <p class="mt-1 text-sm text-[#6B4B35]">Perbarui data pengajuan peminjaman.</p>
  </div>

  <?php if ($err): ?>
    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?php echo htmlspecialchars($err); ?></div>
  <?php endif; ?>
  <?php if ($ok): ?>
    <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"><?php echo htmlspecialchars($ok); ?></div>
  <?php endif; ?>

  <?php
    $action = "index.php?page=pengurus_peminjaman_edit&id=" . (int)($old['id'] ?? 0);
    $isModal = false;
    include __DIR__ . '/../peminjaman/_form.php';
  ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>
