<?php
// daftar peminjaman
if (session_status() === PHP_SESSION_NONE) session_start();
$role = $_SESSION['role'] ?? '';
$user_id = $_SESSION['user_id'] ?? null;
require_once __DIR__ . '/../../models/Koneksi.php';
$conn = Koneksi::connect();

// cek status pakta integritas untuk pemohon
  $paktaStatus = null;
  $paktaId = null;
  $hasActive = false;
  $hasPending = false;
  if ($role === 'pemohon' && $user_id) {
    $tbl = $conn->query("SHOW TABLES LIKE 'pakta_integritas'");
    if ($tbl && $tbl->num_rows) {
        $colsRes = $conn->query("SHOW COLUMNS FROM `pakta_integritas`");
        $cols = [];
        if ($colsRes) while ($c = $colsRes->fetch_assoc()) $cols[] = $c['Field'];
        $userCol = in_array('user_id', $cols) ? 'user_id' : (in_array('id_user', $cols) ? 'id_user' : null);
        $pkCol = null;
        if ($colsRes) {
            $colsRes->data_seek(0);
            while ($c = $colsRes->fetch_assoc()) {
                if (!empty($c['Key']) && strtoupper($c['Key']) === 'PRI') { $pkCol = $c['Field']; break; }
    }

    // cek status peminjaman untuk blokir pengajuan baru
    foreach ($data as $row) {
      $raw = strtolower((string)($row['status'] ?? ''));
      if ($raw === 'approved') $hasActive = true;
      if (in_array($raw, ['waiting_verification','pending','processing'], true)) $hasPending = true;
    }
  }
        if (!$pkCol) $pkCol = in_array('id', $cols) ? 'id' : null;
        if ($userCol && in_array('status', $cols) && $pkCol) {
            $stmt = $conn->prepare("SELECT `$pkCol` AS id, status FROM pakta_integritas WHERE `$userCol` = ? ORDER BY `$pkCol` DESC LIMIT 1");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $r = $stmt->get_result()->fetch_assoc();
            if ($r) { $paktaStatus = $r['status'] ?? null; $paktaId = $r['id'] ?? null; }
        }
    }
}

// flash
$err = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']);
$ok = $_SESSION['flash_ok'] ?? null; unset($_SESSION['flash_ok']);

$title = "Daftar Peminjaman - PRANATA";
ob_start();
?>

<div class="space-y-4">
    <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-2xl font-extrabold text-[#4A2A12]">Daftar Peminjaman</h2>
                <p class="mt-1 text-sm text-[#6B4B35]">Pantau status peminjaman dan kelola permintaan.</p>
            </div>
            <?php if ($user_id): ?>
                <a href="index.php?page=peminjaman_tambah"
                   data-restrict="<?php echo ($role === 'pemohon' && ($hasActive || $hasPending)) ? '1' : '0'; ?>"
                   class="inline-flex w-full items-center justify-center rounded-xl bg-[#F28B2D] px-5 py-3 text-sm font-bold text-white shadow-sm hover:brightness-95 md:w-auto">
                    Ajukan Peminjaman
                </a>
            <?php endif; ?>
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
                        <th class="px-3 py-3 text-left">Jumlah Barang</th>
                        <th class="px-3 py-3 text-left">Barang</th>
                        <th class="px-3 py-3 text-left">Keterangan</th>
                        <th class="px-3 py-3 text-left">Status</th>
                        <th class="px-3 py-3 text-left">Dibuat</th>
                        <th class="px-3 py-3 text-left">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data)): ?>
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-gray-600">Belum ada data peminjaman.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($data as $row): ?>
                            <tr class="border-t border-black/5">
                                <td class="px-3 py-3"><?php echo htmlspecialchars($row['nama_pihak2'] ?? ($row['pemohon_nama'] ?? ($row['user_id'] ?? '-'))); ?></td>
                                <td class="px-3 py-3"><?= (int)($row['items_count'] ?? 0) ?></td>
                                <td class="px-3 py-3"><?= htmlspecialchars($row['items_summary'] ?? '') ?></td>
                                <td class="px-3 py-3"><?php echo nl2br(htmlspecialchars($row['keterangan'] ?? '')); ?></td>
                                <?php
                                  $raw = strtolower((string)($row['status'] ?? 'pending'));
                                  if (in_array($raw, ['waiting_verification','pending'], true)) {
                                    $label = 'Menunggu Verifikasi';
                                    $cls = 'bg-yellow-100 text-yellow-800';
                                  } elseif ($raw === 'approved') {
                                    $label = 'Disetujui';
                                    $cls = 'bg-green-100 text-green-700';
                                  } elseif ($raw === 'rejected') {
                                    $label = 'Ditolak';
                                    $cls = 'bg-red-100 text-red-700';
                                  } elseif ($raw === 'completed') {
                                    $label = 'Selesai';
                                    $cls = 'bg-blue-100 text-blue-700';
                                  } else {
                                    $label = ucwords(str_replace('_', ' ', $raw));
                                    $cls = 'bg-gray-100 text-gray-700';
                                  }
                                ?>
                                <td class="px-3 py-3">
                                  <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold <?= $cls ?>">
                                    <?= htmlspecialchars($label) ?>
                                  </span>
                                </td>
                                <td class="px-3 py-3"><?php echo htmlspecialchars($row['created_at'] ?? ($row['tgl_pinjam'] ?? '')); ?></td>
                <td class="px-3 py-3">
                    <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
                      <?php if ($role !== 'pemohon'): ?>
                          <a class="text-[#B45309] font-semibold" href="index.php?page=peminjaman_detail&id=<?php echo $row['id']; ?>">Detail</a>
                      <?php endif; ?>
                      <?php if ($role === 'pemohon'): ?>
                          <a class="text-[#B45309] font-semibold" href="index.php?page=peminjaman_preview&id=<?php echo $row['id']; ?>">Cetak</a>
                          <?php if (($row['status'] ?? '') === 'approved'): ?>
                              <?php if ($paktaStatus === 'filled' && $paktaId): ?>
                                  <a class="text-green-700 font-semibold" href="index.php?page=pakta_integritas_preview&id=<?php echo (int)$paktaId; ?>">Cetak Pakta</a>
                              <?php else: ?>
                                  <a class="text-green-700 font-semibold" href="index.php?page=pakta_integritas&id=<?php echo $row['id']; ?>">Cetak Pakta</a>
                              <?php endif; ?>
                          <?php endif; ?>
                      <?php endif; ?>
                      <?php if ($role === 'admin' || $role === 'pengurus'): ?>
                          <a class="text-green-700 font-semibold" href="index.php?page=peminjaman_approve&id=<?php echo $row['id']; ?>">Setujui</a>
                          <a class="text-red-700 font-semibold" href="index.php?page=peminjaman_reject&id=<?php echo $row['id']; ?>">Tolak</a>
                      <?php endif; ?>
                    </div>
                </td>
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

<div id="loanBlockModal" class="fixed inset-0 z-50 hidden bg-black/40">
  <div class="fixed left-1/2 top-1/2 w-full max-w-sm -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-5 shadow-xl">
    <div class="text-lg font-extrabold text-[#4A2A12]">Tidak bisa mengajukan</div>
    <p class="mt-2 text-sm text-[#6B4B35]">
      Anda tidak bisa mengajukan peminjaman baru karena masih memiliki peminjaman aktif atau pengajuan yang menunggu verifikasi.
    </p>
    <div class="mt-4 flex justify-end">
      <button id="loanBlockOk" class="rounded-xl bg-[#F28B2D] px-4 py-2 text-sm font-bold text-white">OK</button>
    </div>
  </div>
</div>

<script>
(function(){
  var btn = document.querySelector('a[href="index.php?page=peminjaman_tambah"][data-restrict]');
  var modal = document.getElementById('loanBlockModal');
  var ok = document.getElementById('loanBlockOk');
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
