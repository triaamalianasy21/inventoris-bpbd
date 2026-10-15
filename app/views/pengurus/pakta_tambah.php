<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$title = $title ?? "Tambah Pakta Integritas - PRANATA";

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
    <h2 class="text-2xl font-extrabold text-[#4A2A12]">Tambah Pakta Integritas</h2>
    <p class="mt-1 text-sm text-[#6B4B35]">Isi data pakta integritas secara manual.</p>
  </div>

  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
  <?php endif; ?>

  <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm">
    <form method="POST" action="index.php?page=pengurus_pakta_tambah" class="space-y-4">
      <div>
        <label class="block text-sm font-semibold mb-1">Pilih Peminjaman</label>
        <select name="id_pinjam" id="paktaPinjamSelect" class="w-full rounded-xl border border-black/10 bg-white px-3 py-2 text-sm">
          <option value="">-- Pilih Peminjaman --</option>
          <?php foreach (($pinjamanList ?? []) as $p): ?>
            <?php
              $pid = (int)($p['id_pinjam'] ?? ($p['id'] ?? 0));
              $labelNama = trim((string)($p['nama_pihak2'] ?? '-'));
              $labelTujuan = trim((string)($p['tujuan'] ?? '-'));
              $labelTgl = trim((string)($p['tgl_pinjam'] ?? '-'));
              $label = $labelNama . ' | ' . $labelTujuan . ' | ' . $labelTgl;
            ?>
            <option value="<?= $pid ?>"
              data-nama="<?= htmlspecialchars($p['nama_pihak2'] ?? '') ?>"
              data-nip="<?= htmlspecialchars($p['nip1'] ?? '') ?>"
              data-jabatan="<?= htmlspecialchars($p['jabatan1'] ?? '') ?>"
              data-barang="<?= htmlspecialchars($p['items_summary'] ?? '') ?>"
              <?= !empty($selectedPinjamId) && (int)$selectedPinjamId === $pid ? 'selected' : '' ?>>
              <?= htmlspecialchars($label) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Nama</label>
        <input type="text" id="paktaNama" name="<?= htmlspecialchars($namaKey) ?>" value="<?= htmlspecialchars($data[$namaKey] ?? '') ?>" class="w-full rounded-xl border border-black/10 px-3 py-2 text-sm" readonly>
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">NIP/NRP/NIK</label>
        <input type="text" id="paktaNip" name="<?= htmlspecialchars($nipKey) ?>" value="<?= htmlspecialchars($data[$nipKey] ?? '') ?>" class="w-full rounded-xl border border-black/10 px-3 py-2 text-sm" readonly>
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Jabatan</label>
        <input type="text" id="paktaJabatan" name="<?= htmlspecialchars($jabatanKey) ?>" value="<?= htmlspecialchars($data[$jabatanKey] ?? '') ?>" class="w-full rounded-xl border border-black/10 px-3 py-2 text-sm" readonly>
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1">Barang Digunakan</label>
        <textarea id="paktaBarang" name="<?= htmlspecialchars($barangKey) ?>" rows="2" class="w-full rounded-xl border border-black/10 px-3 py-2 text-sm" readonly><?= htmlspecialchars($data[$barangKey] ?? '') ?></textarea>
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

<script>
(function(){
  var select = document.getElementById('paktaPinjamSelect');
  var nama = document.getElementById('paktaNama');
  var nip = document.getElementById('paktaNip');
  var jabatan = document.getElementById('paktaJabatan');
  var barang = document.getElementById('paktaBarang');
  if (!select) return;

  var applyFromOption = function(opt){
    if (!opt) return;
    if (nama) nama.value = opt.getAttribute('data-nama') || '';
    if (nip) nip.value = opt.getAttribute('data-nip') || '';
    if (jabatan) jabatan.value = opt.getAttribute('data-jabatan') || '';
    if (barang) barang.value = opt.getAttribute('data-barang') || '';
  };

  applyFromOption(select.options[select.selectedIndex]);
  select.addEventListener('change', function(){
    applyFromOption(select.options[select.selectedIndex]);
  });
})();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>
