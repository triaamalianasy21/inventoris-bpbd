<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$err = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']);
$ok = $_SESSION['flash_ok'] ?? null; unset($_SESSION['flash_ok']);

$title = $title ?? "Data Peminjaman - PRANATA";
$q = trim($_GET['q'] ?? '');
ob_start();

function renderPagination($current, $pages, $param, $q)
{
  if ($pages <= 1) return;
  $base = $_GET;
  $base['page'] = 'pengurus_peminjaman';
  if ($q !== '') {
    $base['q'] = $q;
  } else {
    unset($base['q']);
  }
  echo '<div class="mt-4 flex flex-wrap items-center gap-2">';
  for ($i = 1; $i <= $pages; $i++) {
    $base[$param] = $i;
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
        <h2 class="text-2xl font-extrabold text-[#4A2A12]">Data Peminjaman</h2>
        <p class="mt-1 text-sm text-[#6B4B35]">Semua pengajuan dari pemohon maupun input langsung.</p>
      </div>
      <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
        <form method="GET" action="index.php" class="relative w-full sm:w-auto">
          <input type="hidden" name="page" value="pengurus_peminjaman">
          <input
            type="text"
            name="q"
            value="<?= htmlspecialchars($q) ?>"
            placeholder="cari peminjaman"
            class="w-full sm:w-[220px] rounded-full border border-black/10 bg-white px-4 py-2 pr-10 text-sm outline-none focus:ring-2 focus:ring-[#E9A15D]"
          />
          <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 opacity-60 hover:opacity-90">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
              <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.7"/>
              <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="1.7"/>
            </svg>
          </button>
        </form>
        <a href="index.php?page=pengurus_peminjaman_tambah"
           class="inline-flex w-full items-center justify-center rounded-xl bg-[#F28B2D] px-5 py-3 text-sm font-bold text-white shadow-sm hover:brightness-95 sm:w-auto">
          + Input Peminjaman
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

  <?php
    $activeRows = $activeData['rows'] ?? [];
    $activePage = (int)($activeData['page'] ?? 1);
    $activePages = (int)($activeData['pages'] ?? 1);
    $doneRows = $doneData['rows'] ?? [];
    $donePage = (int)($doneData['page'] ?? 1);
    $donePages = (int)($doneData['pages'] ?? 1);
  ?>

  <div class="rounded-2xl border border-black/10 bg-white shadow-sm overflow-hidden">
    <div class="px-6 pt-5">
      <h3 class="text-lg font-bold text-[#4A2A12]">Peminjaman Berjalan</h3>
      <p class="text-sm text-[#6B4B35]">Menunggu verifikasi, disetujui, atau ditolak.</p>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-max w-full text-sm">
        <thead class="bg-[#FCE4C4] text-[#4A2A12]">
          <tr>
            <th class="px-3 py-3 text-left">Nama Pihak 1</th>
            <th class="px-3 py-3 text-left">NIP</th>
            <th class="px-3 py-3 text-left">Jabatan</th>
            <th class="px-3 py-3 text-left">Nama Pihak 2</th>
            <th class="px-3 py-3 text-left">NIP 2</th>
            <th class="px-3 py-3 text-left">Jabatan 2</th>
            <th class="px-3 py-3 text-left">Alamat</th>
            <th class="px-3 py-3 text-left">No HP</th>
            <th class="px-3 py-3 text-left">Tujuan</th>
            <th class="px-3 py-3 text-left">Tgl Pinjam</th>
            <th class="px-3 py-3 text-left">Tgl Jatuh Tempo</th>
            <th class="px-3 py-3 text-left">Spesifikasi</th>
            <th class="px-3 py-3 text-left">Keterangan</th>
            <th class="px-3 py-3 text-left">Status</th>
            <th class="px-3 py-3 text-left">Barang</th>
            <th class="px-3 py-3 text-left">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($activeRows)): ?>
            <tr>
              <td colspan="16" class="px-4 py-6 text-center text-gray-600">Belum ada data peminjaman.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($activeRows as $row): ?>
              <tr class="border-t border-black/5">
                <td class="px-3 py-3"><?php echo htmlspecialchars($row['nama_pihak1'] ?? ($row['nama'] ?? '-')); ?></td>
                <td class="px-3 py-3"><?php echo htmlspecialchars($row['nip'] ?? '-'); ?></td>
                <td class="px-3 py-3"><?php echo htmlspecialchars($row['jabatan'] ?? '-'); ?></td>
                <td class="px-3 py-3"><?php echo htmlspecialchars($row['nama_pihak2'] ?? '-'); ?></td>
                <td class="px-3 py-3"><?php echo htmlspecialchars($row['nip1'] ?? '-'); ?></td>
                <td class="px-3 py-3"><?php echo htmlspecialchars($row['jabatan1'] ?? '-'); ?></td>
                <td class="px-3 py-3"><?php echo htmlspecialchars($row['alamat'] ?? '-'); ?></td>
                <td class="px-3 py-3"><?php echo htmlspecialchars($row['no_hp'] ?? '-'); ?></td>
                <td class="px-3 py-3"><?php echo htmlspecialchars($row['tujuan'] ?? '-'); ?></td>
                <td class="px-3 py-3"><?php echo htmlspecialchars($row['tgl_pinjam'] ?? ($row['created_at'] ?? '-')); ?></td>
                <td class="px-3 py-3"><?php echo htmlspecialchars($row['tgl_jatuh_tempo'] ?? '-'); ?></td>
                <td class="px-3 py-3"><?php echo htmlspecialchars($row['spesifikasi'] ?? '-'); ?></td>
                <td class="px-3 py-3"><?php echo htmlspecialchars($row['keterangan'] ?? '-'); ?></td>
                <?php
                  $statusRaw = strtolower((string)($row['status'] ?? ''));
                  if (in_array($statusRaw, ['waiting_verification','pending'], true)) {
                    $statusLabel = 'Menunggu Verifikasi';
                  } elseif ($statusRaw === 'approved') {
                    $statusLabel = 'Disetujui';
                  } elseif ($statusRaw === 'rejected') {
                    $statusLabel = 'Ditolak';
                  } elseif ($statusRaw === 'completed') {
                    $statusLabel = 'Selesai';
                  } else {
                    $statusLabel = $statusRaw ? ucwords(str_replace('_', ' ', $statusRaw)) : '-';
                  }
                ?>
                <td class="px-3 py-3"><?php echo htmlspecialchars($statusLabel); ?></td>
                <td class="px-3 py-3"><?php echo htmlspecialchars($row['items_summary'] ?? ''); ?></td>
                <td class="px-3 py-3">
                  <div class="flex items-center gap-2">
                    <?php
                      $showDelete = in_array($statusRaw, ['waiting_verification','pending','menunggu_verifikasi','menunggu','ditolak','rejected',''], true);
                    ?>
                    <a href="index.php?page=pengurus_peminjaman_edit&id=<?= (int)$row['id'] ?>"
                       class="w-8 h-8 inline-flex items-center justify-center rounded bg-[#7D4CFF] text-white hover:brightness-95"
                       title="Edit">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M3 17.25V21h3.75L17.8 9.94l-3.75-3.75L3 17.25z" stroke="white" stroke-width="1.6"/>
                        <path d="M14.06 4.94l3.75 3.75" stroke="white" stroke-width="1.6"/>
                      </svg>
                    </a>
                    <?php if ($showDelete): ?>
                      <a href="index.php?page=pengurus_peminjaman_delete&id=<?= (int)$row['id'] ?>"
                         class="deleteRequestBtn w-8 h-8 inline-flex items-center justify-center rounded bg-[#E53935] text-white hover:brightness-95"
                         title="Hapus"
                         data-href="index.php?page=pengurus_peminjaman_delete&id=<?= (int)$row['id'] ?>">
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
      <?php renderPagination($activePage, $activePages, 'page_active', $q); ?>
    </div>
  </div>

  <div class="rounded-2xl border border-black/10 bg-white shadow-sm overflow-hidden">
    <div class="px-6 pt-5">
      <h3 class="text-lg font-bold text-[#4A2A12]">Peminjaman Selesai</h3>
      <p class="text-sm text-[#6B4B35]">Data selesai (tanpa aksi).</p>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-max w-full text-sm">
        <thead class="bg-[#FCE4C4] text-[#4A2A12]">
          <tr>
            <th class="px-3 py-3 text-left">Nama Pihak 1</th>
            <th class="px-3 py-3 text-left">NIP</th>
            <th class="px-3 py-3 text-left">Jabatan</th>
            <th class="px-3 py-3 text-left">Nama Pihak 2</th>
            <th class="px-3 py-3 text-left">NIP 2</th>
            <th class="px-3 py-3 text-left">Jabatan 2</th>
            <th class="px-3 py-3 text-left">Alamat</th>
            <th class="px-3 py-3 text-left">No HP</th>
            <th class="px-3 py-3 text-left">Tujuan</th>
            <th class="px-3 py-3 text-left">Tgl Pinjam</th>
            <th class="px-3 py-3 text-left">Tgl Jatuh Tempo</th>
            <th class="px-3 py-3 text-left">Spesifikasi</th>
            <th class="px-3 py-3 text-left">Keterangan</th>
            <th class="px-3 py-3 text-left">Status</th>
            <th class="px-3 py-3 text-left">Barang</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($doneRows)): ?>
            <tr>
              <td colspan="15" class="px-4 py-6 text-center text-gray-600">Belum ada data peminjaman selesai.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($doneRows as $row): ?>
              <tr class="border-t border-black/5">
                <td class="px-3 py-3"><?php echo htmlspecialchars($row['nama_pihak1'] ?? ($row['nama'] ?? '-')); ?></td>
                <td class="px-3 py-3"><?php echo htmlspecialchars($row['nip'] ?? '-'); ?></td>
                <td class="px-3 py-3"><?php echo htmlspecialchars($row['jabatan'] ?? '-'); ?></td>
                <td class="px-3 py-3"><?php echo htmlspecialchars($row['nama_pihak2'] ?? '-'); ?></td>
                <td class="px-3 py-3"><?php echo htmlspecialchars($row['nip1'] ?? '-'); ?></td>
                <td class="px-3 py-3"><?php echo htmlspecialchars($row['jabatan1'] ?? '-'); ?></td>
                <td class="px-3 py-3"><?php echo htmlspecialchars($row['alamat'] ?? '-'); ?></td>
                <td class="px-3 py-3"><?php echo htmlspecialchars($row['no_hp'] ?? '-'); ?></td>
                <td class="px-3 py-3"><?php echo htmlspecialchars($row['tujuan'] ?? '-'); ?></td>
                <td class="px-3 py-3"><?php echo htmlspecialchars($row['tgl_pinjam'] ?? ($row['created_at'] ?? '-')); ?></td>
                <td class="px-3 py-3"><?php echo htmlspecialchars($row['tgl_jatuh_tempo'] ?? '-'); ?></td>
                <td class="px-3 py-3"><?php echo htmlspecialchars($row['spesifikasi'] ?? '-'); ?></td>
                <td class="px-3 py-3"><?php echo htmlspecialchars($row['keterangan'] ?? '-'); ?></td>
                <?php
                  $statusRaw = strtolower((string)($row['status'] ?? ''));
                  if (in_array($statusRaw, ['waiting_verification','pending'], true)) {
                    $statusLabel = 'Menunggu Verifikasi';
                  } elseif ($statusRaw === 'approved') {
                    $statusLabel = 'Disetujui';
                  } elseif ($statusRaw === 'rejected') {
                    $statusLabel = 'Ditolak';
                  } elseif ($statusRaw === 'completed') {
                    $statusLabel = 'Selesai';
                  } else {
                    $statusLabel = $statusRaw ? ucwords(str_replace('_', ' ', $statusRaw)) : '-';
                  }
                ?>
                <td class="px-3 py-3"><?php echo htmlspecialchars($statusLabel); ?></td>
                <td class="px-3 py-3"><?php echo htmlspecialchars($row['items_summary'] ?? ''); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <div class="px-6 pb-5">
      <?php renderPagination($donePage, $donePages, 'page_done', $q); ?>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>

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
