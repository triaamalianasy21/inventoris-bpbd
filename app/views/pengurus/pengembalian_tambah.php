<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$title = $title ?? "Input Pengembalian - PRANATA";
$selectedPinjam = $selectedPinjam ?? (int)($_GET['id_pinjam'] ?? 0);
ob_start();
?>

<div class="space-y-4">
  <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm">
    <h2 class="text-2xl font-extrabold text-[#4A2A12]">Input Pengembalian</h2>
    <p class="mt-1 text-sm text-[#6B4B35]">Pilih peminjaman yang akan dikembalikan, isi kondisi dan tanggal kembali.</p>
  </div>

  <?php if (!empty($err)): ?>
    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?php echo htmlspecialchars($err); ?></div>
  <?php endif; ?>

  <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm">
    <form method="POST" action="index.php?page=pengurus_pengembalian_tambah" class="space-y-4" enctype="multipart/form-data">
      <div>
        <label class="block text-sm font-semibold mb-1">Pilih Peminjaman</label>
        <select name="id_pinjam" class="w-full rounded-xl border border-black/10 px-3 py-2 text-sm" onchange="if(this.value){window.location='index.php?page=pengurus_pengembalian_tambah&id_pinjam='+this.value}">
          <option value="">-- Pilih Peminjaman --</option>
          <?php foreach ($pinjamanList as $p): ?>
            <?php $pid = (int)($p['id'] ?? $p['id_pinjam'] ?? 0); ?>
            <option value="<?= $pid ?>" <?= $selectedPinjam === $pid ? 'selected' : '' ?>>
              <?= htmlspecialchars($p['nama_pihak2'] ?? '-') ?> (<?= htmlspecialchars($p['tujuan'] ?? '-') ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="block text-sm font-semibold mb-1">Kondisi Kembali</label>
        <textarea name="kondisi_kembali" rows="2" class="w-full rounded-xl border border-black/10 px-3 py-2 text-sm" required><?= htmlspecialchars($_POST['kondisi_kembali'] ?? '') ?></textarea>
      </div>

      <div>
        <label class="block text-sm font-semibold mb-1">Tanggal Kembali</label>
        <input type="date" name="tgl_kembali" value="<?= htmlspecialchars($_POST['tgl_kembali'] ?? date('Y-m-d')) ?>" class="w-full rounded-xl border border-black/10 px-3 py-2 text-sm" required>
      </div>

      <div class="rounded-xl border border-black/10 p-4">
        <div class="font-semibold text-[#4A2A12] mb-2">Barang yang Dikembalikan</div>
        <?php if (empty($items)): ?>
          <div class="text-sm text-gray-600">Pilih peminjaman untuk melihat daftar barang.</div>
        <?php else: ?>
          <div class="space-y-3 text-sm">
            <?php foreach ($items as $it): ?>
              <?php $bid = (int)($it['id_barang'] ?? ($it['barang_id'] ?? 0)); ?>
              <div class="rounded-lg border border-black/10 p-3">
                <div class="font-semibold text-[#4A2A12]"><?= htmlspecialchars($it['nama_barang'] ?? ($it['barang_id'] ?? '-')) ?></div>
                <div class="mt-2">
                  <label class="block text-xs font-semibold mb-1">Foto Barang (JPG/PNG, maks 10MB)</label>
                  <input type="file" name="foto[<?= $bid ?>]" accept=".jpg,.jpeg,.png" class="w-full text-sm" required>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
        <a href="index.php?page=pengurus_pengembalian" class="inline-flex w-full items-center justify-center rounded-xl border border-black/10 px-4 py-2 text-sm sm:w-auto">Kembali</a>
        <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-[#F28B2D] px-5 py-2 text-sm font-bold text-white sm:w-auto">Simpan</button>
      </div>
    </form>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>


