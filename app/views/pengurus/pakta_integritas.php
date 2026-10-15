<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$err = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']);
$ok = $_SESSION['flash_ok'] ?? null; unset($_SESSION['flash_ok']);

$title = $title ?? "Pakta Integritas - PRANATA";
$q = trim($_GET['q'] ?? '');
$pkCol = $pkCol ?? null;
$pick = function($row, $candidates, $fallback = '-') {
    foreach ($candidates as $c) {
        if (!empty($row[$c])) return $row[$c];
    }
    return $fallback;
};
ob_start();
?>

<div class="space-y-4">
  <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
      <div>
        <h2 class="text-2xl font-extrabold text-[#4A2A12]">Kelola Pakta Integritas</h2>
        <p class="mt-1 text-sm text-[#6B4B35]">Daftar pakta integritas yang sudah diisi pemohon.</p>
      </div>
      <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
        <form method="GET" action="index.php" class="relative w-full sm:w-auto">
          <input type="hidden" name="page" value="pengurus_pakta">
          <input
            type="text"
            name="q"
            value="<?= htmlspecialchars($q) ?>"
            placeholder="cari pakta"
            class="w-full sm:w-[220px] rounded-full border border-black/10 bg-white px-4 py-2 pr-10 text-sm outline-none focus:ring-2 focus:ring-[#E9A15D]"
          />
          <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 opacity-60 hover:opacity-90">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
              <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.7"/>
              <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="1.7"/>
            </svg>
          </button>
        </form>
        <a href="index.php?page=pengurus_pakta_tambah"
           class="inline-flex w-full items-center justify-center rounded-xl bg-[#F28B2D] px-5 py-3 text-sm font-bold text-white shadow-sm hover:brightness-95 sm:w-auto">
          + Tambah Pakta
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
            <th class="px-3 py-3 text-left">Nama</th>
            <th class="px-3 py-3 text-left">NIP/NRP/NIK</th>
            <th class="px-3 py-3 text-left">Jabatan</th>
            <th class="px-3 py-3 text-left">Barang Digunakan</th>
            <th class="px-3 py-3 text-left">Status</th>
            <th class="px-3 py-3 text-left">Tanggal</th>
            <th class="px-3 py-3 text-left">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($data)): ?>
            <tr>
              <td colspan="7" class="px-4 py-6 text-center text-gray-600">Belum ada data pakta integritas.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($data as $row): ?>
              <?php
                $nama = $pick($row, ['nama','nama_lengkap','nama_peminjam','user_nama'], '-');
                $nip = $pick($row, ['nip_nik','nip','nik','nrp','nip_nrp_nik'], '-');
                $jabatan = $pick($row, ['jabatan','jabatan_peminjam'], '-');
                $barang = $pick($row, ['barang_digunakan','barang_milik_daerah','barang','nama_barang'], '-');
                $status = $row['status'] ?? '-';
                $tanggal = $row['tgl_dibuat'] ?? ($row['created_at'] ?? '-');
                $id = $pkCol && isset($row[$pkCol]) ? $row[$pkCol] : ($row['id'] ?? null);
              ?>
              <tr class="border-t border-black/5">
                <td class="px-3 py-3"><?= htmlspecialchars($nama) ?></td>
                <td class="px-3 py-3"><?= htmlspecialchars($nip) ?></td>
                <td class="px-3 py-3"><?= htmlspecialchars($jabatan) ?></td>
                <td class="px-3 py-3"><?= htmlspecialchars($barang) ?></td>
                <td class="px-3 py-3"><?= htmlspecialchars($status) ?></td>
                <td class="px-3 py-3"><?= htmlspecialchars($tanggal) ?></td>
                <td class="px-3 py-3">
                  <?php if ($id): ?>
                    <div class="flex items-center gap-2">
                      <a href="index.php?page=pengurus_pakta_edit&id=<?= (int)$id ?>"
                         class="w-8 h-8 inline-flex items-center justify-center rounded bg-[#7D4CFF] text-white hover:brightness-95"
                         title="Edit">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                          <path d="M3 17.25V21h3.75L17.8 9.94l-3.75-3.75L3 17.25z" stroke="white" stroke-width="1.6"/>
                          <path d="M14.06 4.94l3.75 3.75" stroke="white" stroke-width="1.6"/>
                        </svg>
                      </a>
                      <a href="index.php?page=pengurus_pakta_delete&id=<?= (int)$id ?>"
                         class="w-8 h-8 inline-flex items-center justify-center rounded bg-[#E53935] text-white hover:brightness-95"
                         title="Hapus"
                         onclick="return confirm('Hapus pakta ini?')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                          <path d="M4 7h16" stroke="white" stroke-width="1.6"/>
                          <path d="M7 7l1 12h8l1-12" stroke="white" stroke-width="1.6"/>
                          <path d="M9 7V5h6v2" stroke="white" stroke-width="1.6"/>
                        </svg>
                      </a>
                    </div>
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

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>
