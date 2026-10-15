<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$title = $title ?? "Laporan - PRANATA";
$jenis = $jenis ?? 'barang';
$startDate = $startDate ?? date('Y-m-01');
$endDate = $endDate ?? date('Y-m-d');
$startMonth = $startMonth ?? substr($startDate, 0, 7);
$filterYear = $filterYear ?? (int)substr($startMonth, 0, 4);
$filterMonth = $filterMonth ?? (int)substr($startMonth, 5, 2);
$rows = $rows ?? [];
$columns = $columns ?? [];
$reportTitle = $reportTitle ?? 'Laporan';
$generatedAt = $generatedAt ?? date('d/m/Y H:i');
$isPrint = $isPrint ?? false;
$roleView = $roleView ?? 'admin';
$pageName = ($roleView === 'admin') ? 'laporan' : 'pengurus_laporan';
$qPrint = http_build_query([
  'page' => $pageName,
  'jenis' => $jenis,
  'month' => $startMonth,
  'year' => $filterYear,
  'month_num' => $filterMonth,
  'export' => 1
]);
ob_start();
?>

<style>
  @media print {
    aside, header, footer, .no-print { display: none !important; }
    body { background: #fff !important; }
    main { padding: 0 !important; }
    .print-container { box-shadow: none !important; border: 0 !important; }
    .print-table th, .print-table td { font-size: 11px; }
  }
</style>

<div class="space-y-4">
  <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm print-container">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
      <div>
        <h2 class="text-2xl font-extrabold text-[#4A2A12]"><?= htmlspecialchars($reportTitle) ?></h2>
    <p class="mt-1 text-sm text-[#6B4B35]">
      Periode: <?= htmlspecialchars($startMonth) ?> · Dibuat <?= htmlspecialchars($generatedAt) ?>
    </p>
      </div>
      <div class="flex flex-col gap-2 sm:flex-row sm:items-center no-print">
        <form method="GET" action="index.php" class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
          <input type="hidden" name="page" value="<?= htmlspecialchars($pageName) ?>">
          <select name="jenis" class="w-full rounded-xl border border-black/10 bg-white px-3 py-2 text-sm sm:w-auto">
            <option value="barang" <?= $jenis === 'barang' ? 'selected' : '' ?>>Laporan Barang</option>
            <option value="peminjaman" <?= $jenis === 'peminjaman' ? 'selected' : '' ?>>Laporan Peminjaman</option>
            <option value="pengembalian" <?= $jenis === 'pengembalian' ? 'selected' : '' ?>>Laporan Pengembalian</option>
          </select>
          <select name="month_num" class="w-full rounded-xl border border-black/10 bg-white px-3 py-2 text-sm sm:w-auto">
            <?php
              $monthLabels = [
                1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
                9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
              ];
              foreach ($monthLabels as $num => $label):
            ?>
              <option value="<?= $num ?>" <?= ((int)$filterMonth === $num) ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
          <select name="year" class="w-full rounded-xl border border-black/10 bg-white px-3 py-2 text-sm sm:w-auto">
            <?php
              $currentYear = (int)date('Y');
              $startYear = max(2000, $currentYear - 30);
              $endYear = $currentYear + 1;
              for ($y = $endYear; $y >= $startYear; $y--):
            ?>
              <option value="<?= $y ?>" <?= ((int)$filterYear === $y) ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
          </select>
          <button type="submit"
                  class="w-full rounded-xl bg-[#F28B2D] px-4 py-2 text-sm font-bold text-white hover:brightness-95 sm:w-auto">
            Tampilkan
          </button>
        </form>
        <a href="index.php?<?= $qPrint ?>"
           class="w-full rounded-xl bg-[#F28B2D] px-4 py-2 text-center text-sm font-semibold text-white shadow-sm hover:brightness-95 sm:w-auto">
          Unduh Excel
        </a>
      </div>
    </div>
  </div>

  <div class="rounded-2xl border border-black/10 bg-white shadow-sm overflow-hidden print-container">
    <div class="overflow-x-auto">
      <table class="min-w-max w-full text-sm print-table">
        <thead class="bg-[#FCE4C4] text-[#4A2A12]">
          <tr>
            <?php foreach ($columns as $col): ?>
              <th class="px-3 py-3 text-left"><?= htmlspecialchars($col['label']) ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr>
              <td colspan="<?= count($columns) ?>" class="px-4 py-6 text-center text-gray-600">
                Tidak ada data untuk periode ini.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($rows as $row): ?>
              <tr class="border-t border-black/5">
                <?php foreach ($columns as $col): ?>
                  <?php $val = $row[$col['key']] ?? '-'; ?>
                  <td class="px-3 py-3"><?= htmlspecialchars((string)$val) ?></td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>
