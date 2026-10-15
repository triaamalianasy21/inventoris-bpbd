<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$title = $title ?? "Pakta Integritas - PRANATA";

$action = "index.php?page=pakta_integritas";
if (!empty($_GET['id'])) $action .= "&id=" . (int)$_GET['id'];

// map expected fields to actual column names
$findCol = function($candidates) use ($cols) {
    foreach ($candidates as $c) {
        foreach ($cols as $col) {
            if ($col['Field'] === $c) return $c;
        }
    }
    return null;
};

$colNama = $findCol(['nama','nama_lengkap','nama_peminjam']);
$colNip = $findCol(['nip','nip_nik','nik','nrp','nip_nrp_nik']);
$colJabatan = $findCol(['jabatan','jabatan_peminjam']);
$colBarang = $findCol(['barang','barang_milik_daerah','barang_digunakan','nama_barang']);
$colTanggal = $findCol(['tgl_dibuat','tanggal','tanggal_dibuat','tgl']);

ob_start();
?>

<div class="space-y-4">
  <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm">
    <h2 class="text-2xl font-extrabold text-[#4A2A12]">Pakta Integritas</h2>
    <p class="mt-1 text-sm text-[#6B4B35]">Lengkapi data pakta integritas setelah peminjaman disetujui.</p>
  </div>

  <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm">
    <form method="POST" action="<?= $action ?>" class="space-y-4">
      <?php if (!$colNama && !$colNip && !$colJabatan && !$colBarang && !$colTanggal): ?>
        <div class="text-sm text-gray-600">Tidak ada field yang bisa diisi.</div>
      <?php else: ?>
        <div class="grid gap-4 md:grid-cols-2">
          <?php if ($colTanggal): ?>
            <div>
              <label class="block text-sm font-semibold mb-1">Tanggal</label>
              <input type="date" name="<?= $colTanggal ?>" value="<?= htmlspecialchars($data[$colTanggal] ?? date('Y-m-d')) ?>" class="w-full rounded-xl border border-black/10 px-3 py-2 text-sm" />
            </div>
          <?php endif; ?>
          <?php if ($colNama): ?>
            <div>
              <label class="block text-sm font-semibold mb-1">Nama</label>
              <input type="text" name="<?= $colNama ?>" value="<?= htmlspecialchars($data[$colNama] ?? '') ?>" class="w-full rounded-xl border border-black/10 px-3 py-2 text-sm bg-gray-100" readonly />
            </div>
          <?php endif; ?>

          <?php if ($colNip): ?>
            <div>
              <label class="block text-sm font-semibold mb-1">NIP/NRP/NIK</label>
              <input type="text" name="<?= $colNip ?>" value="<?= htmlspecialchars($data[$colNip] ?? '') ?>" class="w-full rounded-xl border border-black/10 px-3 py-2 text-sm bg-gray-100" readonly />
            </div>
          <?php endif; ?>

          <?php if ($colJabatan): ?>
            <div>
              <label class="block text-sm font-semibold mb-1">Jabatan</label>
              <input type="text" name="<?= $colJabatan ?>" value="<?= htmlspecialchars($data[$colJabatan] ?? '') ?>" class="w-full rounded-xl border border-black/10 px-3 py-2 text-sm bg-gray-100" readonly />
            </div>
          <?php endif; ?>

          <?php if ($colBarang): ?>
            <div class="md:col-span-2">
              <label class="block text-sm font-semibold mb-1">Barang Milik Daerah yang digunakan</label>
              <textarea name="<?= $colBarang ?>" rows="3" class="w-full rounded-xl border border-black/10 px-3 py-2 text-sm bg-gray-100" readonly><?= htmlspecialchars($data[$colBarang] ?? '') ?></textarea>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
        <a href="index.php?page=peminjaman" class="inline-flex w-full items-center justify-center rounded-xl border border-black/10 px-4 py-2 text-sm sm:w-auto">Kembali</a>
        <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-[#F28B2D] px-5 py-2 text-sm font-bold text-white sm:w-auto">Simpan & Cetak</button>
      </div>
    </form>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>
