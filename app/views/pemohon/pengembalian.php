<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$title = $title ?? "Pengembalian - PRANATA";
$returnBlock = $returnBlock ?? false;
$returnBlockReason = $returnBlockReason ?? 'Anda tidak bisa mengajukan pengembalian yang sama dua kali. Silakan tunggu proses verifikasi selesai.';
$baseUrl = defined('BASE_URL') ? BASE_URL : '';
ob_start();
?>

<div class="space-y-4">
  <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
      <div>
        <h2 class="text-2xl font-extrabold text-[#4A2A12]">Pengembalian</h2>
        <p class="mt-1 text-sm text-[#6B4B35]">Status pengembalian dari pengajuan peminjaman Anda.</p>
      </div>
      <a href="index.php?page=pemohon_pengembalian_tambah"
         data-restrict="<?php echo ($returnBlock ? '1' : '0'); ?>"
         class="inline-flex w-full items-center justify-center rounded-xl bg-[#F28B2D] px-5 py-3 text-sm font-bold text-white shadow-sm hover:brightness-95 md:w-auto">
        Ajukan Pengembalian
      </a>
    </div>
  </div>

  <div class="rounded-2xl border border-black/10 bg-white shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-max w-full text-sm">
        <thead class="bg-[#FCE4C4] text-[#4A2A12]">
          <tr>
            <th class="px-3 py-3 text-left">Pemohon</th>
            <th class="px-3 py-3 text-left">Tujuan</th>
            <th class="px-3 py-3 text-left">Tgl Pinjam</th>
            <th class="px-3 py-3 text-left">Tgl Kembali</th>
            <th class="px-3 py-3 text-left">Kondisi</th>
            <th class="px-3 py-3 text-left">Foto</th>
            <th class="px-3 py-3 text-left">Status Pengembalian</th>
            <th class="px-3 py-3 text-left">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($data)): ?>
            <tr>
              <td colspan="8" class="px-4 py-6 text-center text-gray-600">Belum ada data pengembalian.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($data as $row): ?>
              <?php
                $statusRaw = strtolower((string)($row['status_pengembalian'] ?? ''));
                $statusLabel = $statusRaw === 'pending' ? 'Menunggu Verifikasi' : ($statusRaw === 'completed' ? 'Disetujui' : ($statusRaw === 'processing' ? 'Perlu Perbaikan' : $statusRaw));
                $fotoPath = !empty($row['foto']) ? ($baseUrl . 'public/' . ltrim((string)$row['foto'], '/')) : '';
              ?>
              <tr class="border-t border-black/5">
                <td class="px-3 py-3"><?= htmlspecialchars($row['pemohon'] ?? '-') ?></td>
                <td class="px-3 py-3"><?= htmlspecialchars($row['tujuan'] ?? '-') ?></td>
                <td class="px-3 py-3"><?= htmlspecialchars($row['tgl_pinjam'] ?? '-') ?></td>
                <td class="px-3 py-3"><?= htmlspecialchars($row['tgl_kembali'] ?? '-') ?></td>
                <td class="px-3 py-3"><?= htmlspecialchars($row['kondisi_kembali'] ?? '-') ?></td>
                <td class="px-3 py-3">
                  <?php if (!empty($fotoPath)): ?>
                    <button type="button"
                            class="photoPreview inline-flex items-center gap-2 text-[#B45309] font-semibold"
                            data-photo="<?= htmlspecialchars($fotoPath) ?>">
                      <img src="<?= htmlspecialchars($fotoPath) ?>" alt="Foto pengembalian" class="h-9 w-9 rounded-lg border border-black/10 object-cover">
                    </button>
                  <?php else: ?>
                    -
                  <?php endif; ?>
                </td>
                <td class="px-3 py-3"><?= htmlspecialchars($statusLabel ?: '-') ?></td>
                <td class="px-3 py-3">
                  <?php if (!empty($row['id_kembali'])): ?>
                    <a class="text-[#B45309] font-semibold" href="index.php?page=pemohon_pengembalian_preview&id=<?= (int)$row['id_kembali'] ?>">Cetak</a>
                  <?php else: ?>
                    -
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div id="photoModal" class="fixed inset-0 z-50 hidden bg-black/70">
  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div class="relative max-h-[90vh] max-w-[90vw] overflow-hidden rounded-2xl bg-white shadow-xl">
      <button id="photoClose" class="absolute right-3 top-3 rounded-full bg-white/90 px-3 py-1 text-sm font-semibold text-[#4A2A12] shadow">Tutup</button>
      <img id="photoModalImg" src="" alt="Foto pengembalian" class="max-h-[90vh] max-w-[90vw] object-contain">
    </div>
  </div>
</div>

<div id="returnBlockModal" class="fixed inset-0 z-50 hidden bg-black/40">
  <div class="fixed left-1/2 top-1/2 w-full max-w-sm -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-5 shadow-xl">
    <div class="text-lg font-extrabold text-[#4A2A12]">Tidak bisa mengajukan</div>
    <p class="mt-2 text-sm text-[#6B4B35]">
      <?= htmlspecialchars($returnBlockReason) ?>
    </p>
    <div class="mt-4 flex justify-end">
      <button id="returnBlockOk" class="rounded-xl bg-[#F28B2D] px-4 py-2 text-sm font-bold text-white">OK</button>
    </div>
  </div>
</div>

<script>
(function(){
  var btn = document.querySelector('a[href="index.php?page=pemohon_pengembalian_tambah"][data-restrict]');
  var modal = document.getElementById('returnBlockModal');
  var ok = document.getElementById('returnBlockOk');
  if (!btn) return;
  btn.addEventListener('click', function(e){
    if (btn.getAttribute('data-restrict') === '1') {
      e.preventDefault();
      if (modal) modal.classList.remove('hidden');
    }
  });
  if (ok) ok.addEventListener('click', function(){
    if (modal) modal.classList.add('hidden');
  });
})();
</script>

<script>
(function(){
  var modal = document.getElementById('photoModal');
  var img = document.getElementById('photoModalImg');
  var closeBtn = document.getElementById('photoClose');
  var buttons = document.querySelectorAll('.photoPreview');

  if (!modal || !img || buttons.length === 0) return;

  buttons.forEach(function(btn){
    btn.addEventListener('click', function(){
      var src = btn.getAttribute('data-photo') || '';
      if (!src) return;
      img.src = src;
      modal.classList.remove('hidden');
    });
  });

  var close = function(){
    modal.classList.add('hidden');
    img.src = '';
  };

  closeBtn && closeBtn.addEventListener('click', close);
  modal.addEventListener('click', function(e){
    if (e.target === modal) close();
  });
})();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>
