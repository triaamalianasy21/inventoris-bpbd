<?php
if (!$data) die('Data tidak ditemukan');
if (session_status() === PHP_SESSION_NONE) session_start();
$role = $_SESSION['role'] ?? '';

$pemohon = $data['nama_pihak2'] ?? ($data['pemohon_nama'] ?? ($data['user_id'] ?? '-'));
$statusRaw = strtolower((string)($data['status'] ?? 'pending'));
if (in_array($statusRaw, ['waiting_verification','pending'], true)) {
    $statusLabel = 'Menunggu Verifikasi';
    $badgeClass = 'bg-yellow-100 text-yellow-800';
} elseif ($statusRaw === 'approved') {
    $statusLabel = 'Disetujui';
    $badgeClass = 'bg-green-100 text-green-800';
} elseif ($statusRaw === 'rejected') {
    $statusLabel = 'Ditolak';
    $badgeClass = 'bg-red-100 text-red-800';
} elseif ($statusRaw === 'completed') {
    $statusLabel = 'Selesai';
    $badgeClass = 'bg-blue-100 text-blue-800';
} else {
    $statusLabel = ucwords(str_replace('_', ' ', $statusRaw));
    $badgeClass = 'bg-gray-100 text-gray-700';
}

$title = $title ?? "Detail Peminjaman - PRANATA";
ob_start();
?>

<div class="space-y-4">
  <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
      <div>
        <h2 class="text-2xl font-extrabold text-[#4A2A12]">Detail Peminjaman</h2>
        <p class="mt-1 text-sm text-[#6B4B35]">Informasi lengkap permintaan peminjaman.</p>
      </div>
      <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold <?= $badgeClass ?>">
        <?= htmlspecialchars($statusLabel) ?>
      </span>
    </div>
  </div>

  <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm">
    <div class="grid gap-4 md:grid-cols-2 text-sm">
      <div>
        <div class="text-xs font-semibold text-[#6B4B35] mb-1">Pemohon</div>
        <div class="rounded-lg border border-gray-200 px-3 py-2"><?= htmlspecialchars($pemohon) ?></div>
      </div>
      <div>
        <div class="text-xs font-semibold text-[#6B4B35] mb-1">Tanggal Dibuat</div>
        <div class="rounded-lg border border-gray-200 px-3 py-2"><?= htmlspecialchars($data['created_at'] ?? ($data['tgl_pinjam'] ?? '-')) ?></div>
      </div>
      <div>
        <div class="text-xs font-semibold text-[#6B4B35] mb-1">Keterangan</div>
        <div class="rounded-lg border border-gray-200 px-3 py-2"><?= nl2br(htmlspecialchars($data['keterangan'] ?? '-')) ?></div>
      </div>
      <div>
        <div class="text-xs font-semibold text-[#6B4B35] mb-1">No HP</div>
        <div class="rounded-lg border border-gray-200 px-3 py-2"><?= htmlspecialchars($data['no_hp'] ?? '-') ?></div>
      </div>
      <div>
        <div class="text-xs font-semibold text-[#6B4B35] mb-1">Diverifikasi Oleh</div>
        <div class="rounded-lg border border-gray-200 px-3 py-2"><?= htmlspecialchars($data['verified_by_name'] ?? ($data['verified_by'] ?? '-')) ?></div>
      </div>
    </div>
  </div>

  <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm">
    <div class="text-xs font-semibold text-[#6B4B35] mb-2">Barang yang Diminta</div>
    <?php if (!empty($data['items'])): ?>
      <div class="overflow-x-auto">
        <table class="min-w-max w-full text-sm">
          <thead class="bg-[#FCE4C4] text-[#4A2A12]">
            <tr>
              <th class="px-3 py-3 text-left">Nama Barang</th>
              <th class="px-3 py-3 text-left">Qty</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($data['items'] as $it): ?>
              <tr class="border-t border-black/5">
                <td class="px-3 py-3"><?= htmlspecialchars($it['nama_barang'] ?? $it['barang_id']) ?></td>
                <td class="px-3 py-3"><?= (int)($it['qty'] ?? ($it['jumlah'] ?? 0)) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="text-sm text-gray-600">Tidak ada data barang.</div>
    <?php endif; ?>
  </div>

  <div class="flex justify-end">
    <?php
      $backUrl = 'index.php?page=peminjaman';
      if ($role === 'kalaksa') $backUrl = 'index.php?page=kalaksa_verifikasi';
      if ($role === 'pengurus_barang') $backUrl = 'index.php?page=pengurus_peminjaman';
    ?>
    <a href="<?= htmlspecialchars($backUrl) ?>" class="rounded-xl border border-black/10 px-4 py-2 text-sm">Kembali</a>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>
