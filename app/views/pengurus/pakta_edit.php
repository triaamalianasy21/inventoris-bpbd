<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$title = $title ?? "Ubah Pakta Integritas - PRANATA";

$pickKey = function($row, $candidates, $fallback = '') {
    foreach ($candidates as $c) {
        if (array_key_exists($c, $row)) return $c;
    }
    return $fallback;
};

$namaKey = $pickKey($data, ['nama','nama_lengkap','nama_peminjam'], 'nama');
$nipKey = $pickKey($data, ['nip_nik','nip','nik','nrp','nip_nrp_nik'], 'nip_nik');
$jabatanKey = $pickKey($data, ['jabatan','jabatan_peminjam'], 'jabatan');
$barangKey = $pickKey($data, ['barang_digunakan','barang_milik_daerah','barang','nama_barang'], 'barang_digunakan');
$tanggalKey = array_key_exists('tgl_dibuat', $data) ? 'tgl_dibuat' : (array_key_exists('created_at', $data) ? 'created_at' : null);

ob_start();
?>

<div class="space-y-4">
  <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm">
    <h2 class="text-2xl font-extrabold text-[#4A2A12]">Ubah Pakta Integritas</h2>
    <p class="mt-1 text-sm text-[#6B4B35]">Perbarui data pakta integritas.</p>
  </div>

  <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm">
    <form method="POST" action="index.php?page=pengurus_pakta_edit&id=<?= (int)($data['id_pakta'] ?? ($data['pakta_id'] ?? ($data['id'] ?? 0))) ?>" class="space-y-4">
      <div>
        <label class="block text-sm font-semibold mb-1">Nama</label>
        <input type="text" name="<?= htmlspecialchars($namaKey) ?>" value="<?= htmlspecialchars($data[$namaKey] ?? '') ?>" class="w-full rounded-xl border border-black/10 px-3 py-2 text-sm">
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">NIP/NRP/NIK</label>
        <input type="text" name="<?= htmlspecialchars($nipKey) ?>" value="<?= htmlspecialchars($data[$nipKey] ?? '') ?>" class="w-full rounded-xl border border-black/10 px-3 py-2 text-sm">
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Jabatan</label>
        <input type="text" name="<?= htmlspecialchars($jabatanKey) ?>" value="<?= htmlspecialchars($data[$jabatanKey] ?? '') ?>" class="w-full rounded-xl border border-black/10 px-3 py-2 text-sm">
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Barang Digunakan</label>
        <textarea name="<?= htmlspecialchars($barangKey) ?>" rows="2" class="w-full rounded-xl border border-black/10 px-3 py-2 text-sm"><?= htmlspecialchars($data[$barangKey] ?? '') ?></textarea>
      </div>
      <?php if ($tanggalKey): ?>
        <div>
          <label class="block text-sm font-semibold mb-1">Tanggal</label>
          <input type="date" name="<?= htmlspecialchars($tanggalKey) ?>" value="<?= htmlspecialchars(substr((string)($data[$tanggalKey] ?? ''), 0, 10)) ?>" class="w-full rounded-xl border border-black/10 px-3 py-2 text-sm">
        </div>
      <?php endif; ?>

      <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
        <a href="index.php?page=pengurus_pakta" class="inline-flex w-full items-center justify-center rounded-xl border border-black/10 px-4 py-2 text-sm sm:w-auto">Kembali</a>
        <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-[#F28B2D] px-5 py-2 text-sm font-bold text-white sm:w-auto">Simpan</button>
      </div>
    </form>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>
