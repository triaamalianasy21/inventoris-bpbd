<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$title = $title ?? "Notifikasi Barang - PRANATA";
$keluar = $data['keluar'] ?? [];
$masuk = $data['masuk'] ?? [];

ob_start();
?>

<div class="space-y-4">
  <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm">
    <h2 class="text-2xl font-extrabold text-[#4A2A12]">Notifikasi Barang</h2>
    <p class="mt-1 text-sm text-[#6B4B35]">Barang keluar (disetujui) dan barang masuk (selesai).</p>
  </div>

  <div class="grid gap-4 md:grid-cols-2">
    <div class="rounded-2xl border border-black/10 bg-white shadow-sm overflow-hidden">
      <div class="px-4 py-3 bg-[#FCE4C4] text-[#4A2A12] font-semibold">Barang Keluar</div>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="text-left">
              <th class="px-3 py-2">ID</th>
              <th class="px-3 py-2">Nama Pihak 2</th>
              <th class="px-3 py-2">Tgl Pinjam</th>
              <th class="px-3 py-2">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($keluar)): ?>
              <tr><td colspan="4" class="px-3 py-3 text-center text-gray-600">Tidak ada data.</td></tr>
            <?php else: ?>
              <?php foreach ($keluar as $row): ?>
                <tr class="border-t border-black/5">
                  <td class="px-3 py-2"><?= htmlspecialchars($row['id']) ?></td>
                  <td class="px-3 py-2"><?= htmlspecialchars($row['nama_pihak2'] ?? '-') ?></td>
                  <td class="px-3 py-2"><?= htmlspecialchars($row['tgl_pinjam'] ?? ($row['created_at'] ?? '-')) ?></td>
                  <td class="px-3 py-2"><a class="text-[#B45309] font-semibold" href="index.php?page=petugas_detail&id=<?= (int)$row['id'] ?>">Detail</a></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="rounded-2xl border border-black/10 bg-white shadow-sm overflow-hidden">
      <div class="px-4 py-3 bg-[#FCE4C4] text-[#4A2A12] font-semibold">Barang Masuk</div>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="text-left">
              <th class="px-3 py-2">ID</th>
              <th class="px-3 py-2">Nama Pihak 2</th>
              <th class="px-3 py-2">Tgl Pinjam</th>
              <th class="px-3 py-2">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($masuk)): ?>
              <tr><td colspan="4" class="px-3 py-3 text-center text-gray-600">Tidak ada data.</td></tr>
            <?php else: ?>
              <?php foreach ($masuk as $row): ?>
                <tr class="border-t border-black/5">
                  <td class="px-3 py-2"><?= htmlspecialchars($row['id']) ?></td>
                  <td class="px-3 py-2"><?= htmlspecialchars($row['nama_pihak2'] ?? '-') ?></td>
                  <td class="px-3 py-2"><?= htmlspecialchars($row['tgl_pinjam'] ?? ($row['created_at'] ?? '-')) ?></td>
                  <td class="px-3 py-2"><a class="text-[#B45309] font-semibold" href="index.php?page=petugas_detail&id=<?= (int)$row['id'] ?>">Detail</a></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>
