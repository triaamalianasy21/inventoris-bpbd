<?php
// app/views/admin/index.php  --> sekarang jadi halaman BARANG (Data Barang)

include_once __DIR__ . '/../../controllers/AuthController.php';
include_once __DIR__ . '/../../models/Koneksi.php';
include_once __DIR__ . '/../../models/PeminjamanModel.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$role = $_SESSION['role'] ?? '';
$username = $_SESSION['username'] ?? 'Admin';
$err = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']);
$ok = $_SESSION['flash_ok'] ?? null; unset($_SESSION['flash_ok']);

// koneksi
$conn = Koneksi::connect();

// search
$q = trim($_GET['q'] ?? '');

// pagination
$perPage = 10;
$pageNum = max(1, (int)($_GET['page_num'] ?? 1));
$offset = ($pageNum - 1) * $perPage;

// query barang (tabel BPBD)
$sql = "SELECT * FROM barang";

if ($q !== '') {
    $safe = $conn->real_escape_string($q);
    $sql .= " WHERE kode_barang LIKE '%$safe%'
              OR nomor_register LIKE '%$safe%'
              OR nama_barang LIKE '%$safe%'";
}

$sql .= " ORDER BY id_barang DESC LIMIT {$perPage} OFFSET {$offset}";

$result = $conn->query($sql);
if (!$result) {
    die("Query barang gagal: " . $conn->error);
}

// total rows for pagination
$countSql = "SELECT COUNT(*) AS total FROM barang";
if ($q !== '') {
    $safe = $conn->real_escape_string($q);
    $countSql .= " WHERE kode_barang LIKE '%$safe%'
                   OR nomor_register LIKE '%$safe%'
                   OR nama_barang LIKE '%$safe%'";
}
$countRes = $conn->query($countSql);
$totalRows = $countRes ? (int)($countRes->fetch_assoc()['total'] ?? 0) : 0;
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$title = "Barang - PRANATA";
ob_start();
?>

<!-- CONTENT ONLY (layout sudah handle sidebar & header) -->
<div class="flex flex-col gap-3 mb-4 sm:flex-row sm:items-center sm:justify-between">
  <h1 class="text-2xl font-extrabold text-[#4A2A12] sm:text-2xl">Data Barang</h1>

  <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
    <!-- Search -->
    <form method="GET" action="index.php" class="relative w-full sm:w-auto">
      <!-- IMPORTANT: karena file ini diakses dari page=admin, kita pertahankan page=admin -->
      <input type="hidden" name="page" value="admin">
      <input
        type="text"
        name="q"
        value="<?= htmlspecialchars($q) ?>"
        placeholder="cari barang"
        class="w-full min-w-0 rounded-full border border-black/10 bg-white px-4 py-2 pr-10 text-sm outline-none focus:ring-2 focus:ring-[#E9A15D] sm:w-[220px]"
      />
      <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 opacity-60 hover:opacity-90">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
          <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.7"/>
          <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="1.7"/>
        </svg>
      </button>
    </form>
    <script>
      (function(){
        var input = document.querySelector('form[action="index.php"] input[name="q"]');
        if (!input) return;
        var timer = null;
        input.addEventListener('input', function(){
          clearTimeout(timer);
          timer = setTimeout(function(){
            var v = input.value.trim();
            var url = 'index.php?page=admin' + (v ? '&q=' + encodeURIComponent(v) : '');
            window.location.href = url;
          }, 600);
        });
        // Keep Enter key behavior (form submit) intact
      })();
    </script>

    <!-- Tambah -->
    <a href="index.php?page=barang_create"
       class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#F28B2D] px-4 py-2 text-white font-bold shadow-sm hover:brightness-95 sm:w-auto">
      + Tambah Barang
    </a>
  </div>
</div>

<!-- Table -->
<div class="bg-white rounded-xl shadow-sm border border-black/10 overflow-hidden">
  <div class="overflow-x-auto">
    <table class="min-w-max w-full text-sm">
      <thead class="bg-[#F7E2B3] text-[#4A2A12]">
        <tr>
          <th class="px-4 py-3 text-left w-[60px]">No</th>
          <?php
            // Dapatkan metadata kolom dari result
            $fields = $result->fetch_fields();
            $columns = array_map(function($f){ return $f->name; }, $fields);

            // Hilangkan kolom internal yang tidak perlu ditampilkan
            $columns = array_values(array_filter($columns, function($c){ return $c !== 'id_barang'; }));

            // Jika ada id_lokasi, kita tampilkan sebagai 'Lokasi' dan lakukan lookup nama lokasi
            $hasIdLokasi = in_array('id_lokasi', $columns);

            // Ambil semua baris ke array agar kita bisa prefetch lokasi
            $rows = $result->fetch_all(MYSQLI_ASSOC);

            // Prefetch lokasi names jika perlu (lebih aman)
            $lokasiCache = [];
            if ($hasIdLokasi) {
              $ids = array_unique(array_filter(array_map(function($r){ return $r['id_lokasi'] ?? null; }, $rows)));
              if (!empty($ids)) {
                // Pastikan hanya integer dan buat list yang aman
                $ids_int = array_map('intval', $ids);
                $id_list = implode(',', $ids_int);

                // Daftar kandidat tabel/kolom (urutkan yang paling mungkin)
                $candidates = [
                  ['table' => 'lokasi_gudang', 'name' => 'nama_lokasi'],
                  ['table' => 'lokasi', 'name' => 'nama'],
                  ['table' => 'lokasi', 'name' => 'lokasi'],
                  ['table' => 'gudang', 'name' => 'nama_gudang'],
                ];

                $found = null;
                foreach ($candidates as $c) {
                  $t = $conn->real_escape_string($c['table']);
                  $n = $conn->real_escape_string($c['name']);
                  $tblExists = $conn->query("SHOW TABLES LIKE '{$t}'");
                  if ($tblExists && $tblExists->num_rows) {
                    $colExists = $conn->query("SHOW COLUMNS FROM `{$t}` LIKE '{$n}'");
                    if ($colExists && $colExists->num_rows) {
                      $found = $c;
                      break;
                    }
                  }
                }

                if ($found) {
                  $t = $found['table']; $n = $found['name'];

                  // Cari nama kolom PK yang sesuai di tabel $t (jangan anggap selalu 'id')
                  $pk = null;
                  $colsRes = $conn->query("SHOW COLUMNS FROM `{$t}`");
                  if ($colsRes) {
                    // Prefer column literally named 'id'
                    while ($col = $colsRes->fetch_assoc()) {
                      if (strtolower($col['Field']) === 'id') { $pk = $col['Field']; break; }
                    }
                    // Jika tidak ada 'id', cari pola umum seperti 'id' di awal/akhir
                    if (!$pk) {
                      $colsRes->data_seek(0);
                      while ($col = $colsRes->fetch_assoc()) {
                        if (preg_match('/(^|_)id$|^id(_|$)/i', $col['Field'])) { $pk = $col['Field']; break; }
                      }
                    }
                    // Fallback ke kolom pertama bila tidak ketemu
                    if (!$pk) { $colsRes->data_seek(0); $first = $colsRes->fetch_assoc(); $pk = $first['Field']; }
                  } else {
                    $pk = 'id';
                  }

                  $pkEsc = $conn->real_escape_string($pk);
                  $res = $conn->query("SELECT `{$pkEsc}` AS id, `{$n}` AS nama FROM `{$t}` WHERE `{$pkEsc}` IN ($id_list)");
                  if ($res) {
                    while ($r = $res->fetch_assoc()) $lokasiCache[$r['id']] = $r['nama'];
                  }
                }
              }
            }

            // Map status barang berdasarkan peminjaman aktif (priority: waiting_verification > approved)
            $loanStatusMap = [];
            $pm = new PeminjamanModel();
            $itemsMap = $pm->getItemsMapping();
            if (!empty($itemsMap['items_table']) && !empty($itemsMap['peminjaman_col']) && !empty($itemsMap['barang_col'])) {
              $it = $conn->real_escape_string($itemsMap['items_table']);
              $pCol = $conn->real_escape_string($itemsMap['peminjaman_col']);
              $bCol = $conn->real_escape_string($itemsMap['barang_col']);
              $sqlStatus = "SELECT pi.`{$bCol}` AS barang_id,
                                MAX(CASE
                                      WHEN p.status IN ('waiting_verification','pending') THEN 3
                                      WHEN p.status = 'approved' THEN 2
                                      ELSE 0
                                    END) AS st
                            FROM `{$it}` pi
                            JOIN peminjaman p ON p.`{$pCol}` = pi.`{$pCol}`
                            GROUP BY pi.`{$bCol}`";
              $rs = $conn->query($sqlStatus);
              if ($rs) {
                while ($r = $rs->fetch_assoc()) {
                  $loanStatusMap[(string)$r['barang_id']] = (int)$r['st'];
                }
              }
            }

            // Tampilkan header (ganti label id_lokasi jadi 'Lokasi')
            foreach ($columns as $col):
              if ($col === 'status_brg') {
                $label = 'Status Verifikasi';
              } elseif ($col === 'foto') {
                $label = 'Foto';
              } else {
                $label = ucwords(str_replace('_', ' ', $col));
                if ($col === 'id_lokasi') $label = 'Lokasi';
              }
          ?>
            <th class="px-4 py-3 text-left"><?= $label ?></th>
            <?php if ($col === 'status_brg'): ?>
              <th class="px-4 py-3 text-left">Status Barang</th>
            <?php endif; ?>
          <?php endforeach; ?>
          <th class="px-4 py-3 text-center w-[120px]">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($rows) === 0): ?>
          <?php $extraCols = in_array('status_brg', $columns) ? 1 : 0; ?>
          <?php $colspan = count($columns) + 1 + $extraCols; ?>
          <tr>
            <td colspan="<?= $colspan ?>" class="px-4 py-6 text-center text-gray-500">
              Data barang belum ada.
            </td>
          </tr>
        <?php else:
          $no = $offset + 1;
          // Tampilkan setiap baris dari array rows
          foreach ($rows as $row):
        ?>
          <tr class="border-t border-black/5 hover:bg-black/3">
            <td class="px-4 py-3"><?= $no++ ?></td>
            <?php foreach ($columns as $col): ?>
              <?php if ($col === 'id_lokasi'): ?>
                <?php $lid = $row['id_lokasi'] ?? null; ?>
                <td class="px-4 py-3"><?= htmlspecialchars($lokasiCache[$lid] ?? ($lid !== null ? $lid : '-')) ?></td>
              <?php elseif ($col === 'status_brg'): ?>
                <?php
                  $bid = (string)($row['id_barang'] ?? '');
                  $st = $loanStatusMap[$bid] ?? 0;

                  $raw = strtolower((string)($row['status_brg'] ?? ''));
                  if (in_array($raw, ['aktif','tersedia','verified','diverifikasi','sudah diverifikasi','terverifikasi'], true)) {
                    $verifyLabel = 'Terverifikasi';
                  } elseif (in_array($raw, ['pending','menunggu','waiting_verification'], true)) {
                    $verifyLabel = 'Menunggu Verifikasi';
                  } elseif ($raw !== '') {
                    $verifyLabel = ucwords(str_replace('_', ' ', $raw));
                  } else {
                    $verifyLabel = '-';
                  }

                  if ($st === 3) {
                    $loanLabel = 'Menunggu Verifikasi';
                  } elseif ($st === 2) {
                    $loanLabel = 'Menunggu Pengembalian';
                  } else {
                    $loanLabel = 'Tersedia';
                  }
                ?>
                <td class="px-4 py-3"><?= htmlspecialchars($verifyLabel) ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($loanLabel) ?></td>
              <?php elseif ($col === 'foto'): ?>
                <?php
                  $foto = $row['foto'] ?? '';
                  if ($foto && !preg_match('/^https?:\\/\\//', $foto) && strpos($foto, '/') === false) {
                    $foto = 'public/uploads/barang/' . $foto;
                  }
                  $fotoUrl = $foto ?: '';
                ?>
                <td class="px-4 py-3">
                  <?php if (!empty($fotoUrl)): ?>
                    <button type="button"
                            class="group inline-flex items-center"
                            data-photo="<?php echo htmlspecialchars($fotoUrl); ?>">
                      <img src="<?php echo htmlspecialchars($fotoUrl); ?>"
                           alt="Foto barang"
                           class="h-12 w-16 rounded-md border border-black/10 object-cover shadow-sm transition group-hover:scale-[1.02]">
                    </button>
                  <?php else: ?>
                    -
                  <?php endif; ?>
                </td>
              <?php else: ?>
                <td class="px-4 py-3<?= $col === 'nama_barang' ? ' font-semibold text-[#4A2A12]' : '' ?>"><?= htmlspecialchars($row[$col] ?? '-') ?></td>
              <?php endif; ?>
            <?php endforeach; ?>
            <td class="px-4 py-3">
              <div class="flex items-center justify-center gap-2">
                <?php
                  $bidGuard = (string)($row['id_barang'] ?? '');
                  $stGuard = $loanStatusMap[$bidGuard] ?? 0;
                  $statusVerifRaw = strtolower(trim((string)($row['status_brg'] ?? '')));
                  if ($stGuard === 3) {
                    $loanLabelGuard = 'menunggu verifikasi';
                  } elseif ($stGuard === 2) {
                    $loanLabelGuard = 'menunggu pengembalian';
                  } else {
                    $loanLabelGuard = 'tersedia';
                  }
                  $showDelete = in_array($statusVerifRaw, ['pending','menunggu','menunggu verifikasi','menunggu_verifikasi','waiting_verification','ditolak','rejected'], true)
                    && $loanLabelGuard === 'tersedia';
                ?>
                <a href="index.php?page=barang_edit&id=<?= (int)$row['id_barang'] ?>"
                   class="w-8 h-8 inline-flex items-center justify-center rounded bg-[#7D4CFF] text-white hover:brightness-95"
                   title="Edit"
                   aria-label="Edit">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M3 17.25V21h3.75L17.8 9.94l-3.75-3.75L3 17.25z" stroke="white" stroke-width="1.6"/>
                    <path d="M14.06 4.94l3.75 3.75" stroke="white" stroke-width="1.6"/>
                  </svg>
                </a>

                <?php if ($showDelete): ?>
                  <a href="index.php?page=barang_delete&id=<?= (int)$row['id_barang'] ?>"
                     class="deleteRequestBtn w-8 h-8 inline-flex items-center justify-center rounded bg-[#E53935] text-white hover:brightness-95"
                     title="Hapus"
                     aria-label="Hapus"
                     data-href="index.php?page=barang_delete&id=<?= (int)$row['id_barang'] ?>"
                     data-status-verifikasi="<?= htmlspecialchars($statusVerifRaw) ?>"
                     data-status-barang="<?= htmlspecialchars($loanLabelGuard) ?>">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($totalPages > 1): ?>
  <div class="mt-4 flex flex-wrap items-center gap-2">
    <?php
      $base = $_GET;
      $base['page'] = 'admin';
      if ($q !== '') {
        $base['q'] = $q;
      } else {
        unset($base['q']);
      }
      for ($i = 1; $i <= $totalPages; $i++):
        $base['page_num'] = $i;
        $url = 'index.php?' . http_build_query($base);
        $cls = $i === $pageNum
          ? 'bg-[#F28B2D] text-white'
          : 'bg-white text-[#4A2A12] border border-black/10 hover:bg-black/5';
    ?>
      <a href="<?= htmlspecialchars($url) ?>" class="px-3 py-1 rounded-lg text-sm <?= $cls ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';

?>

<div id="deleteRequestModal" class="fixed inset-0 z-50 hidden bg-black/40">
  <div class="fixed left-1/2 top-1/2 w-full max-w-sm -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-5 shadow-xl">
    <div id="deleteRequestTitle" class="text-lg font-extrabold text-[#4A2A12]">Konfirmasi Penghapusan</div>
    <p id="deleteRequestMessage" class="mt-2 text-sm text-[#6B4B35]">Anda yakin ingin menghapus data ini?</p>
    <div class="mt-4 flex justify-end gap-2">
      <button id="deleteRequestCancel" class="rounded-xl border border-black/10 px-4 py-2 text-sm">Batal</button>
      <button id="deleteRequestConfirm" class="rounded-xl bg-[#E53935] px-4 py-2 text-sm font-bold text-white">Hapus</button>
    </div>
  </div>
</div>

<div id="photoModalBarang" class="fixed inset-0 z-50 hidden bg-black/50 p-4">
  <div class="fixed left-1/2 top-1/2 w-full max-w-3xl -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-4 shadow-xl">
    <div class="flex items-center justify-between">
      <div class="text-sm font-semibold text-[#4A2A12]">Preview Foto</div>
      <button id="photoCloseBarang" type="button" class="rounded-lg border border-black/10 px-3 py-1 text-xs">Tutup</button>
    </div>
    <div class="mt-3">
      <img id="photoPreviewBarang" src="" alt="Preview foto" class="w-full rounded-lg border border-black/10 object-contain">
    </div>
  </div>
</div>

<?php if ($err): ?>
  <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?php echo htmlspecialchars($err); ?></div>
<?php endif; ?>
<?php if ($ok): ?>
  <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"><?php echo htmlspecialchars($ok); ?></div>
<?php endif; ?>

<script>
(function(){
  var modal = document.getElementById('photoModalBarang');
  var preview = document.getElementById('photoPreviewBarang');
  var closeBtn = document.getElementById('photoCloseBarang');
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

<script>
(function(){
  var modal = document.getElementById('deleteRequestModal');
  var confirmBtn = document.getElementById('deleteRequestConfirm');
  var cancelBtn = document.getElementById('deleteRequestCancel');
  var titleEl = document.getElementById('deleteRequestTitle');
  var msgEl = document.getElementById('deleteRequestMessage');
  var targetHref = '';

  if (!modal || !confirmBtn) return;

  document.querySelectorAll('.deleteRequestBtn').forEach(function(btn){
    btn.addEventListener('click', function(e){
      e.preventDefault();
      targetHref = btn.getAttribute('data-href') || btn.getAttribute('href') || '';
      if (!targetHref) return;
      var isDisabled = btn.getAttribute('data-disabled') === '1';
      if (isDisabled) {
        if (titleEl) titleEl.textContent = 'Tidak Bisa Hapus';
        if (msgEl) msgEl.textContent = btn.getAttribute('data-deny-msg') || 'Barang tidak bisa dihapus.';
        confirmBtn.classList.add('hidden');
        modal.classList.remove('hidden');
        return;
      }
      var verif = (btn.getAttribute('data-status-verifikasi') || '').toLowerCase();
      var statusBarang = (btn.getAttribute('data-status-barang') || '').toLowerCase();
      var boleh = (verif === '' || verif === 'menunggu_verifikasi' || verif === 'pending' || verif === 'menunggu')
        && statusBarang === 'tersedia';
      if (boleh) {
        if (titleEl) titleEl.textContent = 'Konfirmasi Penghapusan';
        if (msgEl) msgEl.textContent = 'Anda yakin ingin menghapus data ini?';
        confirmBtn.classList.remove('hidden');
        confirmBtn.textContent = 'Hapus';
      } else {
        if (titleEl) titleEl.textContent = 'Tidak Bisa Hapus';
        if (msgEl) msgEl.textContent = 'Barang sudah disetujui atau sedang dipinjam/menunggu pengembalian.';
        confirmBtn.classList.add('hidden');
      }
      modal.classList.remove('hidden');
    });
  });

  document.querySelectorAll('.editGuard').forEach(function(btn){
    btn.addEventListener('click', function(e){
      var isDisabled = btn.getAttribute('data-disabled') === '1';
      if (!isDisabled) return;
      e.preventDefault();
      if (titleEl) titleEl.textContent = 'Tidak Bisa Edit';
      if (msgEl) msgEl.textContent = btn.getAttribute('data-deny-msg') || 'Barang tidak bisa diubah.';
      confirmBtn.classList.add('hidden');
      modal.classList.remove('hidden');
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

