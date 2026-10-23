<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$title = $title ?? "Pengembalian - PRANATA";
$q = trim($_GET['q'] ?? '');
$err = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']);
$ok = $_SESSION['flash_ok'] ?? null; unset($_SESSION['flash_ok']);
ob_start();

function renderPaginationPengembalian($current, $pages, $q)
{
  if ($pages <= 1) return;
  $base = $_GET;
  $base['page'] = 'pengurus_pengembalian';
  if ($q !== '') {
    $base['q'] = $q;
  } else {
    unset($base['q']);
  }
  echo '<div class="mt-4 flex flex-wrap items-center gap-2">';
  for ($i = 1; $i <= $pages; $i++) {
    $base['page_pengembalian'] = $i;
    $url = 'index.php?' . http_build_query($base);
    $cls = $i === $current
      ? 'bg-[#F28B2D] text-white'
      : 'bg-white text-[#4A2A12] border border-black/10 hover:bg-black/5';
    echo '<a href="' . htmlspecialchars($url) . '" class="px-3 py-1 rounded-lg text-sm ' . $cls . '">' . $i . '</a>';
  }
  echo '</div>';
}
?>

<div class="space-y-4">
  <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
      <div>
        <h2 class="text-2xl font-extrabold text-[#4A2A12]">Pengembalian</h2>
        <p class="mt-1 text-sm text-[#6B4B35]">Daftar pengembalian yang diajukan untuk diverifikasi.</p>
      </div>
      <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
        <form method="GET" action="index.php" class="relative w-full sm:w-auto">
          <input type="hidden" name="page" value="pengurus_pengembalian">
          <input
            type="text"
            name="q"
            value="<?= htmlspecialchars($q) ?>"
            placeholder="cari pengembalian"
            class="w-full sm:w-[220px] rounded-full border border-black/10 bg-white px-4 py-2 pr-10 text-sm outline-none focus:ring-2 focus:ring-[#E9A15D]"
          />
          <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 opacity-60 hover:opacity-90">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
              <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.7"/>
              <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="1.7"/>
            </svg>
          </button>
        </form>
        <a href="index.php?page=pengurus_pengembalian_tambah"
           class="inline-flex w-full items-center justify-center rounded-xl bg-[#F28B2D] px-5 py-3 text-sm font-bold text-white shadow-sm hover:brightness-95 sm:w-auto">
          + Input Pengembalian
        </a>
      </div>
    </div>
  </div>

  <?php if ($err): ?>
    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?php echo htmlspecialchars($err); ?></div>
  <?php endif; ?>
  <?php if ($ok): ?>
    <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"><?php echo htmlspecialchars($ok); ?></div>
  <?php endif; ?>

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
            <th class="px-3 py-3 text-left">Status</th>
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
              <tr class="border-t border-black/5">
                <td class="px-3 py-3"><?php echo htmlspecialchars($row['nama_pihak2'] ?? '-'); ?></td>
                <td class="px-3 py-3"><?php echo htmlspecialchars($row['tujuan'] ?? '-'); ?></td>
                <td class="px-3 py-3"><?php echo htmlspecialchars($row['tgl_pinjam'] ?? '-'); ?></td>
                <td class="px-3 py-3"><?php echo htmlspecialchars($row['tgl_kembali'] ?? '-'); ?></td>
                <td class="px-3 py-3"><?php echo htmlspecialchars($row['kondisi_kembali'] ?? '-'); ?></td>
                <?php
                  $fotoList = [];
                  if (!empty($row['foto_list']) && is_array($row['foto_list'])) {
                    $fotoList = $row['foto_list'];
                  } elseif (!empty($row['foto'])) {
                    $fotoList = [$row['foto']];
                  }
                  $normalized = [];
                  foreach ($fotoList as $fotoItem) {
                    $fotoItem = (string)$fotoItem;
                    if ($fotoItem === '') continue;
                    
                    // Hilangkan awalan public/ jika ada dari database
                    $cleanPath = preg_replace('#^/?public/#', '', $fotoItem);
                    
                    // Pastikan diawali dengan /uploads/
                    if (!str_starts_with($cleanPath, 'uploads/')) {
                      $cleanPath = 'uploads/' . ltrim($cleanPath, '/');
                    }
                    
                    $normalized[] = '/' . $cleanPath;
                  }
                ?>
                <td class="px-3 py-3">
                  <?php if (!empty($normalized)): ?>
                    <div class="flex max-w-[220px] items-center gap-2 overflow-x-auto pr-2">
                      <?php foreach ($normalized as $fotoUrl): ?>
                        <button type="button"
                                class="group inline-flex items-center"
                                data-photo="<?php echo htmlspecialchars($fotoUrl); ?>">
                          <img src="<?php echo htmlspecialchars($fotoUrl); ?>"
                               alt="Foto barang"
                               class="h-12 w-16 flex-none rounded-md border border-black/10 object-cover shadow-sm transition group-hover:scale-[1.02]">
                        </button>
                      <?php endforeach; ?>
                    </div>
                  <?php else: ?>
                    -
                  <?php endif; ?>
                </td>
                <?php
                  $statusRaw = strtolower((string)($row['status'] ?? ''));
                  if ($statusRaw === 'pending' || $statusRaw === '') {
                    $statusLabel = 'Menunggu Verifikasi';
                  } elseif ($statusRaw === 'completed') {
                    $statusLabel = 'Disetujui';
                  } elseif ($statusRaw === 'processing') {
                    $statusLabel = 'Dikembalikan';
                  } elseif ($statusRaw === 'rejected' || $statusRaw === 'ditolak') {
                    $statusLabel = 'Ditolak';
                  } else {
                    $statusLabel = ucwords(str_replace('_', ' ', $statusRaw));
                  }
                ?>
                <td class="px-3 py-3"><?php echo htmlspecialchars($statusLabel); ?></td>
                <td class="px-3 py-3">
                  <div class="flex items-center gap-2">
                    <?php
                      $showDelete = in_array($statusRaw, ['pending','menunggu_verifikasi','waiting_verification','ditolak','rejected',''], true);
                    ?>
                    <a href="index.php?page=pengurus_pengembalian_edit&id=<?= (int)($row['id_kembali'] ?? 0) ?>"
                       class="w-8 h-8 inline-flex items-center justify-center rounded bg-[#7D4CFF] text-white hover:brightness-95"
                       title="Edit">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M3 17.25V21h3.75L17.8 9.94l-3.75-3.75L3 17.25z" stroke="white" stroke-width="1.6"/>
                        <path d="M14.06 4.94l3.75 3.75" stroke="white" stroke-width="1.6"/>
                      </svg>
                    </a>
                    <?php if ($showDelete): ?>
                      <a href="index.php?page=pengurus_pengembalian_delete&id=<?= (int)($row['id_kembali'] ?? 0) ?>"
                         class="deleteRequestBtn w-8 h-8 inline-flex items-center justify-center rounded bg-[#E53935] text-white hover:brightness-95"
                         title="Hapus"
                         data-href="index.php?page=pengurus_pengembalian_delete&id=<?= (int)($row['id_kembali'] ?? 0) ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                          <path d="M4 7h16" stroke="white" stroke-width="1.6"/>
                          <path d="M7 7l1 12h8l1-12" stroke="white" stroke-width="1.6"/>
                          <path d="M9 7V5h6v2" stroke="white" stroke-width="1.6"/>
                        </svg>
                      </a>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <div class="px-6 pb-5">
      <?php renderPaginationPengembalian((int)($page ?? 1), (int)($pages ?? 1), $q); ?>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>

<div id="photoModalPengurus" class="fixed inset-0 z-50 hidden bg-black/50 p-4">
  <div class="fixed left-1/2 top-1/2 w-full max-w-3xl -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-4 shadow-xl">
    <div class="flex items-center justify-between">
      <div class="text-sm font-semibold text-[#4A2A12]">Preview Foto</div>
      <button id="photoClosePengurus" type="button" class="rounded-lg border border-black/10 px-3 py-1 text-xs">Tutup</button>
    </div>
    <div class="mt-3">
      <img id="photoPreviewPengurus" src="" alt="Preview foto" class="w-full rounded-lg border border-black/10 object-contain">
    </div>
  </div>
</div>

<script>
(function(){
  var modal = document.getElementById('photoModalPengurus');
  var preview = document.getElementById('photoPreviewPengurus');
  var closeBtn = document.getElementById('photoClosePengurus');
  if (!modal || !preview) return;
  document.querySelectorAll('[data-photo]').forEach(function(btn){
    btn.addEventListener('click', function(){
      var src = btn.getAttribute('data-photo');
      if (!src) return;
      
      // Bersihkan jika ada awalan /public atau public
      src = src.replace(/^\/?public\//, '/');
      
      if (!src.startsWith('http://') && !src.startsWith('https://') && !src.startsWith('/')) {
        src = '/' + src;
      }
      
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

<div id="deleteRequestModal" class="fixed inset-0 z-50 hidden bg-black/40">
  <div class="fixed left-1/2 top-1/2 w-full max-w-sm -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-5 shadow-xl">
    <div class="text-lg font-extrabold text-[#4A2A12]">Konfirmasi Penghapusan</div>
    <p class="mt-2 text-sm text-[#6B4B35]">Anda yakin ingin menghapus ini?</p>
    <div class="mt-4 flex justify-end gap-2">
      <button id="deleteRequestCancel" class="rounded-xl border border-black/10 px-4 py-2 text-sm">Batal</button>
      <button id="deleteRequestConfirm" class="rounded-xl bg-[#E53935] px-4 py-2 text-sm font-bold text-white">Hapus</button>
    </div>
  </div>
</div>

<script>
(function(){
  var modal = document.getElementById('deleteRequestModal');
  var confirmBtn = document.getElementById('deleteRequestConfirm');
  var cancelBtn = document.getElementById('deleteRequestCancel');
  var targetHref = '';
  var infoModal = document.getElementById('infoRequestModal');
  var infoMsg = document.getElementById('infoRequestMsg');
  var infoOk = document.getElementById('infoRequestOk');

  if (!modal || !confirmBtn) return;

  function showInfo(msg){
    if (!infoModal || !infoMsg || !infoOk) {
      alert(msg);
      return;
    }
    infoMsg.textContent = msg;
    infoModal.classList.remove('hidden');
  }

  if (infoOk) infoOk.addEventListener('click', function(){
    infoModal.classList.add('hidden');
  });

  document.querySelectorAll('.deleteRequestBtn').forEach(function(btn){
    btn.addEventListener('click', function(e){
      e.preventDefault();
      var isDisabled = btn.getAttribute('data-disabled') === '1';
      if (isDisabled) {
        showInfo(btn.getAttribute('data-deny-msg') || 'Tidak bisa dihapus.');
        return;
      }
      targetHref = btn.getAttribute('data-href') || btn.getAttribute('href') || '';
      if (!targetHref) return;
      modal.classList.remove('hidden');
    });
  });

  document.querySelectorAll('.editGuard').forEach(function(btn){
    btn.addEventListener('click', function(e){
      var isDisabled = btn.getAttribute('data-disabled') === '1';
      if (!isDisabled) return;
      e.preventDefault();
      showInfo(btn.getAttribute('data-deny-msg') || 'Tidak bisa diubah.');
    });
  });

  var close = function(){
    modal.classList.add('hidden');
    targetHref = '';
  };

  if (cancelBtn) cancelBtn.addEventListener('click', close);
  modal.addEventListener('click', function(e){
    if (e.target === modal) close();
  });
  confirmBtn.addEventListener('click', function(){
    if (targetHref) window.location.href = targetHref;
  });
})();
</script>

<div id="infoRequestModal" class="fixed inset-0 z-50 hidden bg-black/40">
  <div class="fixed left-1/2 top-1/2 w-full max-w-sm -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-5 shadow-xl">
    <div class="text-lg font-extrabold text-[#4A2A12]">Tidak Bisa Diproses</div>
    <p id="infoRequestMsg" class="mt-2 text-sm text-[#6B4B35]"></p>
    <div class="mt-4 flex justify-end">
      <button id="infoRequestOk" class="rounded-xl bg-[#E53935] px-4 py-2 text-sm font-bold text-white">OK</button>
    </div>
  </div>
</div>
