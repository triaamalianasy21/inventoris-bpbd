<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$title = $title ?? "Detail Barang - PRANATA";
$lokasiMap = $lokasiMap ?? [];
$lid = (string)($data['id_lokasi'] ?? '');
$lokasiLabel = $lokasiMap[$lid] ?? ($lid !== '' ? $lid : '-');

// helper to render label
$labelize = function($name) {
    return ucwords(str_replace('_', ' ', $name));
};

ob_start();
?>

<div class="space-y-4">
  <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm">
    <h2 class="text-2xl font-extrabold text-[#4A2A12]">Detail Barang</h2>
    <p class="mt-1 text-sm text-[#6B4B35]">Informasi lengkap barang untuk verifikasi.</p>
  </div>

  <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm">
    <?php
      $foto = $data['foto'] ?? '';
      if ($foto && !preg_match('/^https?:\\/\\//', $foto) && strpos($foto, '/') === false) {
        $foto = 'public/uploads/barang/' . $foto;
      }
      $fotoUrl = $foto ?: '';
    ?>
    <?php if (!empty($fotoUrl)): ?>
      <div class="mb-4">
        <div class="text-xs font-semibold text-[#6B4B35] mb-2">Foto Barang</div>
        <button type="button" class="group inline-flex items-center" data-photo="<?= htmlspecialchars($fotoUrl) ?>">
          <img src="<?= htmlspecialchars($fotoUrl) ?>"
               alt="Foto barang"
               class="h-28 w-40 rounded-lg border border-black/10 object-cover shadow-sm transition group-hover:scale-[1.02]">
        </button>
      </div>
    <?php endif; ?>
    <div class="grid gap-4 md:grid-cols-2 text-sm">
      <div>
        <div class="text-xs font-semibold text-[#6B4B35] mb-1">ID Barang</div>
        <div class="rounded-lg border border-gray-200 px-3 py-2"><?= htmlspecialchars($data['id_barang'] ?? '-') ?></div>
      </div>
      <div>
        <div class="text-xs font-semibold text-[#6B4B35] mb-1">Nama Barang</div>
        <div class="rounded-lg border border-gray-200 px-3 py-2"><?= htmlspecialchars($data['nama_barang'] ?? '-') ?></div>
      </div>
      <div>
        <div class="text-xs font-semibold text-[#6B4B35] mb-1">Lokasi</div>
        <div class="rounded-lg border border-gray-200 px-3 py-2"><?= htmlspecialchars($lokasiLabel) ?></div>
      </div>
      <div>
        <div class="text-xs font-semibold text-[#6B4B35] mb-1">Status Verifikasi</div>
        <div class="rounded-lg border border-gray-200 px-3 py-2">
          <?= htmlspecialchars($data['status_brg'] ?? '-') ?>
        </div>
      </div>
    </div>
  </div>

  <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm">
    <div class="grid gap-4 md:grid-cols-2 text-sm">
<?php foreach ($data as $key => $val): ?>
  <?php if (in_array($key, ['id_barang','nama_barang','id_lokasi','status_brg','foto'], true)) continue; ?>
  <div>
    <div class="text-xs font-semibold text-[#6B4B35] mb-1"><?= htmlspecialchars($labelize($key)) ?></div>
    <div class="rounded-lg border border-gray-200 px-3 py-2"><?= htmlspecialchars((string)$val) ?></div>
  </div>
<?php endforeach; ?>
    </div>
  </div>

  <div class="flex justify-end">
    <a href="index.php?page=kalaksa_barang" class="inline-flex w-full items-center justify-center rounded-xl border border-black/10 px-4 py-2 text-sm sm:w-auto">Kembali</a>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>

<div id="photoModalBarangDetail" class="fixed inset-0 z-50 hidden bg-black/50 p-4">
  <div class="fixed left-1/2 top-1/2 w-full max-w-3xl -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-4 shadow-xl">
    <div class="flex items-center justify-between">
      <div class="text-sm font-semibold text-[#4A2A12]">Preview Foto</div>
      <button id="photoCloseBarangDetail" type="button" class="rounded-lg border border-black/10 px-3 py-1 text-xs">Tutup</button>
    </div>
    <div class="mt-3">
      <img id="photoPreviewBarangDetail" src="" alt="Preview foto" class="w-full rounded-lg border border-black/10 object-contain">
    </div>
  </div>
</div>

<script>
(function(){
  var modal = document.getElementById('photoModalBarangDetail');
  var preview = document.getElementById('photoPreviewBarangDetail');
  var closeBtn = document.getElementById('photoCloseBarangDetail');
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
