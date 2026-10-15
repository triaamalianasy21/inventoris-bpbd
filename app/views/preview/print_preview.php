<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$pageTitle = $pageTitle ?? 'Preview Dokumen';
$iframeSrc = $iframeSrc ?? '';
$downloadUrl = $downloadUrl ?? '';
$backUrl = $backUrl ?? 'peminjaman';
ob_start();
?>

<div class="space-y-4">
  <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
      <div>
        <h2 class="text-2xl font-extrabold text-[#4A2A12]"><?= htmlspecialchars($pageTitle) ?></h2>
        <p class="mt-1 text-sm text-[#6B4B35]">Preview dokumen sebelum diunduh.</p>
      </div>
      <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
        <?php if (!empty($downloadUrl)): ?>
          <a href="<?= htmlspecialchars($downloadUrl) ?>"
             class="inline-flex w-full items-center justify-center rounded-xl bg-[#F28B2D] px-5 py-3 text-sm font-bold text-white shadow-sm hover:brightness-95 sm:w-auto">
            Unduh PDF
          </a>
        <?php endif; ?>
        <a href="index.php?page=<?= htmlspecialchars($backUrl) ?>"
           class="inline-flex w-full items-center justify-center rounded-xl border border-black/10 px-4 py-2 text-sm sm:w-auto">
          Kembali
        </a>
      </div>
    </div>
  </div>

  <div class="rounded-2xl border border-black/10 bg-white p-2 shadow-sm">
    <?php if (!empty($iframeSrc)): ?>
      <iframe src="<?= htmlspecialchars($iframeSrc) ?>" class="h-[75vh] w-full rounded-xl border border-black/10 bg-white"></iframe>
    <?php else: ?>
      <div class="p-6 text-sm text-gray-600">Preview tidak tersedia.</div>
    <?php endif; ?>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>
