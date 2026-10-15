<?php
if (!$data) die('Data tidak ditemukan');
if (session_status() === PHP_SESSION_NONE) session_start();

$pemohon = $data['nama_pihak2'] ?? ($data['pemohon_nama'] ?? ($data['user_id'] ?? '-'));
$pihak1 = $data['nama_pihak1'] ?? '-';
$statusRaw = strtolower((string)($data['status'] ?? 'pending'));
if ($statusRaw === 'pending') {
    $statusLabel = 'Menunggu Verifikasi';
    $badgeClass = 'bg-yellow-100 text-yellow-800';
} elseif ($statusRaw === 'completed') {
    $statusLabel = 'Disetujui';
    $badgeClass = 'bg-green-100 text-green-800';
} elseif ($statusRaw === 'processing') {
    $statusLabel = 'Perlu Perbaikan';
    $badgeClass = 'bg-red-100 text-red-800';
} else {
    $statusLabel = ucwords(str_replace('_', ' ', $statusRaw));
    $badgeClass = 'bg-gray-100 text-gray-700';
}

$title = $title ?? "Detail Pengembalian - PRANATA";
ob_start();
?>

<div class="space-y-4">
  <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
      <div>
        <h2 class="text-2xl font-extrabold text-[#4A2A12]">Detail Pengembalian</h2>
        <p class="mt-1 text-sm text-[#6B4B35]">Informasi lengkap pengembalian.</p>
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
        <div class="text-xs font-semibold text-[#6B4B35] mb-1">Nama Pihak 1</div>
        <div class="rounded-lg border border-gray-200 px-3 py-2"><?= htmlspecialchars($pihak1) ?></div>
      </div>
      <div>
        <div class="text-xs font-semibold text-[#6B4B35] mb-1">Tujuan</div>
        <div class="rounded-lg border border-gray-200 px-3 py-2"><?= htmlspecialchars($data['tujuan'] ?? '-') ?></div>
      </div>
      <div>
        <div class="text-xs font-semibold text-[#6B4B35] mb-1">Tgl Pinjam</div>
        <div class="rounded-lg border border-gray-200 px-3 py-2"><?= htmlspecialchars($data['tgl_pinjam'] ?? '-') ?></div>
      </div>
      <div>
        <div class="text-xs font-semibold text-[#6B4B35] mb-1">Tgl Kembali</div>
        <div class="rounded-lg border border-gray-200 px-3 py-2"><?= htmlspecialchars($data['tgl_kembali'] ?? '-') ?></div>
      </div>
      <div>
        <div class="text-xs font-semibold text-[#6B4B35] mb-1">Kondisi</div>
        <div class="rounded-lg border border-gray-200 px-3 py-2"><?= htmlspecialchars($data['kondisi_kembali'] ?? '-') ?></div>
      </div>
    </div>
  </div>

  <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm">
    <div class="text-xs font-semibold text-[#6B4B35] mb-2">Barang yang Dikembalikan</div>
    <?php if (!empty($data['items'])): ?>
      <div class="overflow-x-auto">
        <table class="min-w-max w-full text-sm">
          <thead class="bg-[#FCE4C4] text-[#4A2A12]">
            <tr>
              <th class="px-3 py-3 text-left">Nama Barang</th>
              <th class="px-3 py-3 text-left">Qty</th>
              <th class="px-3 py-3 text-left">Foto</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($data['items'] as $it): ?>
              <?php
                $bid = (int)($it['id_barang'] ?? ($it['barang_id'] ?? 0));
                $foto = $data['foto_map'][$bid] ?? '';
                $isAbsolute = $foto && (preg_match('/^https?:\\/\\//', $foto) || str_starts_with($foto, '/'));
                if ($foto && !$isAbsolute && !str_starts_with($foto, 'public/')) {
                    $foto = 'public/' . ltrim($foto, '/');
                }
                $fotoUrl = $foto ? $foto : '';
              ?>
              <tr class="border-t border-black/5">
                <td class="px-3 py-3"><?= htmlspecialchars($it['nama_barang'] ?? $it['barang_id']) ?></td>
                <td class="px-3 py-3"><?= (int)($it['qty'] ?? ($it['jumlah'] ?? 0)) ?></td>
                <td class="px-3 py-3">
                  <?php if (!empty($fotoUrl)): ?>
                    <button type="button"
                            class="group inline-flex items-center"
                            data-photo="<?= htmlspecialchars($fotoUrl) ?>">
                      <img src="<?= htmlspecialchars($fotoUrl) ?>"
                           alt="Foto barang"
                           class="h-12 w-16 rounded-md border border-black/10 object-cover shadow-sm transition group-hover:scale-[1.02]">
                    </button>
                  <?php else: ?>
                    -
                  <?php endif; ?>
                </td>
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
    <a href="index.php?page=<?= htmlspecialchars($backUrl ?? 'kalaksa_pengembalian') ?>" class="rounded-xl border border-black/10 px-4 py-2 text-sm">Kembali</a>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>

<div id="photoModal" class="fixed inset-0 z-50 hidden bg-black/50 p-4">
  <div class="fixed left-1/2 top-1/2 w-full max-w-3xl -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-4 shadow-xl">
    <div class="flex items-center justify-between">
      <div class="text-sm font-semibold text-[#4A2A12]">Preview Foto</div>
      <button id="photoClose" type="button" class="rounded-lg border border-black/10 px-3 py-1 text-xs">Tutup</button>
    </div>
    <div class="mt-3">
      <img id="photoPreview" src="" alt="Preview foto" class="w-full rounded-lg border border-black/10 object-contain">
    </div>
  </div>
</div>

<script>
(function(){
  var modal = document.getElementById('photoModal');
  var preview = document.getElementById('photoPreview');
  var closeBtn = document.getElementById('photoClose');
  if (!modal || !preview) return;
  document.querySelectorAll('[data-photo]').forEach(function(btn){
    btn.addEventListener('click', function(){
      var src = btn.getAttribute('data-photo');
      if (!src) return;
      preview.src = src;
      modal.classList.remove('hidden');
    });
  });
  function closeModal(){
    modal.classList.add('hidden');
    preview.src = '';
  }
  if (closeBtn) closeBtn.addEventListener('click', closeModal);
  modal.addEventListener('click', function(e){
    if (e.target === modal) closeModal();
  });
})();
</script>
