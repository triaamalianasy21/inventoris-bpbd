<?php
// app/views/admin/dashboard.php

require_once __DIR__ . '/../../models/Koneksi.php';
require_once __DIR__ . '/../../models/PeminjamanModel.php';

$conn = Koneksi::connect();

if (session_status() === PHP_SESSION_NONE) session_start();

$username = $_SESSION['username'] ?? 'Admin';

// total barang
$totalBarang = 0;
$res = $conn->query("SELECT COUNT(*) AS cnt FROM barang");
if ($res && ($row = $res->fetch_assoc())) $totalBarang = (int)($row['cnt'] ?? 0);

// peralatan rusak (cari kolom kondisi yang ada)
$rusakCount = 0;
$kondisiCol = null;
$candidates = ['kondisi','kondisi_barang','kondisi_brg','status_kondisi'];
$colsRes = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='barang'");
$cols = [];
if ($colsRes) while ($c = $colsRes->fetch_assoc()) $cols[] = $c['COLUMN_NAME'];
foreach ($candidates as $c) {
    if (in_array($c, $cols, true)) { $kondisiCol = $c; break; }
}
if ($kondisiCol) {
    $colEsc = $conn->real_escape_string($kondisiCol);
    $r = $conn->query("SELECT COUNT(*) AS cnt FROM barang WHERE LOWER(`{$colEsc}`) LIKE '%rusak%'");
    if ($r && ($row = $r->fetch_assoc())) $rusakCount = (int)($row['cnt'] ?? 0);
}

// peralatan dipinjam (distinct barang dengan status approved)
$dipinjamCount = 0;
$pm = new PeminjamanModel();
$map = $pm->getItemsMapping();
if (!empty($map['items_table']) && !empty($map['peminjaman_col']) && !empty($map['barang_col'])) {
    $it = $conn->real_escape_string($map['items_table']);
    $pCol = $conn->real_escape_string($map['peminjaman_col']);
    $bCol = $conn->real_escape_string($map['barang_col']);
    $sql = "SELECT COUNT(DISTINCT pi.`{$bCol}`) AS cnt FROM `{$it}` pi JOIN peminjaman p ON p.`{$pCol}` = pi.`{$pCol}` WHERE p.status = 'approved'";
    $r = $conn->query($sql);
    if ($r && ($row = $r->fetch_assoc())) $dipinjamCount = (int)($row['cnt'] ?? 0);
}

$title = "Dashboard Admin - PRANATA";
ob_start();
?>

<div class="space-y-4">
  <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm">
    <h2 class="text-2xl font-extrabold text-[#4A2A12]">Dashboard</h2>
    <p class="mt-1 text-sm text-[#6B4B35]">Selamat datang, <?= htmlspecialchars($username) ?>!</p>
  </div>

  <div class="grid gap-4 md:grid-cols-3">
    <div class="rounded-2xl border border-black/10 bg-white p-5 shadow-sm">
      <div class="flex items-center justify-between">
        <div class="text-xs font-semibold uppercase text-[#6B4B35]">Jumlah Peralatan</div>
        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[#FCE4C4] text-[#B45309]">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
            <path d="M7 7h10v10H7V7z" stroke="currentColor" stroke-width="1.6"/>
            <path d="M4 7h16" stroke="currentColor" stroke-width="1.6"/>
          </svg>
        </span>
      </div>
      <div class="mt-3 text-3xl font-extrabold text-[#4A2A12]"><?= (int)$totalBarang ?></div>
    </div>

    <div class="rounded-2xl border border-black/10 bg-white p-5 shadow-sm">
      <div class="flex items-center justify-between">
        <div class="text-xs font-semibold uppercase text-[#6B4B35]">Peralatan Yang Rusak</div>
        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[#FEE2E2] text-[#B91C1C]">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
            <path d="M12 4l8 14H4L12 4z" stroke="currentColor" stroke-width="1.6"/>
            <path d="M12 9v4" stroke="currentColor" stroke-width="1.6"/>
            <circle cx="12" cy="16.5" r="1" fill="currentColor"/>
          </svg>
        </span>
      </div>
      <div class="mt-3 text-3xl font-extrabold text-[#4A2A12]"><?= (int)$rusakCount ?></div>
    </div>

    <div class="rounded-2xl border border-black/10 bg-white p-5 shadow-sm">
      <div class="flex items-center justify-between">
        <div class="text-xs font-semibold uppercase text-[#6B4B35]">Peralatan Yang Dipinjam</div>
        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[#FCE4C4] text-[#B45309]">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
            <path d="M12 3l8 4.5v9L12 21 4 16.5v-9L12 3z" stroke="currentColor" stroke-width="1.6"/>
            <path d="M4 7.5l8 4 8-4" stroke="currentColor" stroke-width="1.2"/>
          </svg>
        </span>
      </div>
      <div class="mt-3 text-3xl font-extrabold text-[#4A2A12]"><?= (int)$dipinjamCount ?></div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>
