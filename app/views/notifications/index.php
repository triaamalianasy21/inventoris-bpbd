<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$role = $_SESSION['role'] ?? '';
$title = $title ?? "Notifikasi - PRANATA";
$backUrl = $backUrl ?? (($role === 'petugas_gudang') ? 'petugas_dashboard' : (($role === 'pengurus_barang') ? 'pengurus_dashboard' : 'dashboard'));
$showAll = isset($_GET['all']) && $_GET['all'] === '1';
$notifTotal = is_array($notifs) ? count($notifs) : 0;
$displayNotifs = $showAll ? $notifs : array_slice($notifs ?? [], 0, 6);
ob_start();
?>

<div class="space-y-4">
  <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm">
    <h2 class="text-2xl font-extrabold text-[#4A2A12]">Notifikasi</h2>
    <p class="mt-1 text-sm text-[#6B4B35]">Daftar notifikasi terbaru.</p>
  </div>

    <?php if (empty($displayNotifs)): ?>
      <div class="rounded-2xl border border-black/10 bg-white p-6 text-sm text-gray-600">Belum ada notifikasi.</div>
    <?php else: ?>
      <div class="rounded-2xl border border-black/10 bg-white p-4 sm:p-6 shadow-sm">
        <ul class="space-y-3">
        <?php foreach ($displayNotifs as $n): ?>
          <?php
            $payload = $n['payload'] ?? '';
            $decoded = json_decode($payload, true);
            $msg = '';
            $detailLink = null;
            $idValue = null;
            if (is_array($decoded)) {
              $msg = $decoded['message'] ?? json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
              $idValue = $decoded['id'] ?? ($decoded['pinjam_id'] ?? null);
              $detailLink = $decoded['link'] ?? null;
            } elseif (is_string($decoded)) {
              $msg = $decoded;
            } else {
              $msg = $payload;
            }

            if (!$detailLink && $idValue) {
              if ($role === 'petugas_gudang') {
                $type = (string)($n['type'] ?? '');
                if (str_starts_with($type, 'pengembalian:')) {
                  $detailLink = "index.php?page=petugas_detail&kind=pengembalian&id=" . (int)$idValue;
                } elseif (str_starts_with($type, 'barang:')) {
                  $detailLink = "index.php?page=petugas_detail&kind=barang&id=" . (int)$idValue;
                } elseif (str_starts_with($type, 'peminjaman:')) {
                  $detailLink = "index.php?page=peminjaman_detail&id=" . (int)$idValue;
                } else {
                  $detailLink = "index.php?page=petugas_detail&kind=barang&id=" . (int)$idValue;
                }
              } else {
                $detailLink = "index.php?page=peminjaman_detail&id=" . (int)$idValue;
              }
            }
          ?>
          <li class="rounded-xl border border-black/10 p-4 <?= $n['is_read'] ? 'bg-white' : 'bg-[#FFF6E7]' ?>">
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
              <div>
                <div class="text-xs text-[#6B4B35]"><?= htmlspecialchars($n['created_at']) ?></div>
                <div class="mt-1 text-sm text-[#4A2A12]"><?= nl2br(htmlspecialchars($msg)) ?></div>
              </div>
              <div class="flex w-full flex-col items-start gap-2 sm:w-auto sm:flex-row sm:items-center sm:justify-end">
                <?php if ($detailLink && $role !== 'pengurus_barang'): ?>
                  <a href="<?= htmlspecialchars($detailLink) ?>" class="text-sm font-semibold text-[#B45309]">Detail</a>
                <?php endif; ?>
                <a href="index.php?page=notification_mark_read&id=<?= (int)$n['id'] ?>" class="text-sm text-blue-600">Tandai dibaca</a>
              </div>
            </div>
          </li>
        <?php endforeach; ?>
        </ul>
        <?php if (!$showAll && $notifTotal > 6): ?>
          <div class="mt-4 flex justify-center">
            <a href="index.php?page=<?= htmlspecialchars($_GET['page'] ?? (($role === 'petugas_gudang') ? 'petugas_notifikasi' : 'notifications')) ?>&all=1"
               class="inline-flex items-center gap-2 text-sm font-semibold text-[#B45309]">
              Lihat semua notifikasi
              <span class="text-base">▼</span>
            </a>
          </div>
        <?php endif; ?>
        <?php if ($showAll && $notifTotal > 6): ?>
          <div class="mt-4 flex justify-center">
            <a href="index.php?page=<?= htmlspecialchars($_GET['page'] ?? (($role === 'petugas_gudang') ? 'petugas_notifikasi' : 'notifications')) ?>"
               class="inline-flex items-center gap-2 text-sm font-semibold text-[#B45309]">
              Sembunyikan
              <span class="text-base">▲</span>
            </a>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

  <div class="flex justify-end">
    <a href="index.php?page=<?= htmlspecialchars($backUrl) ?>" class="inline-flex w-full items-center justify-center rounded-xl border border-black/10 px-4 py-2 text-sm sm:w-auto">Kembali</a>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>
