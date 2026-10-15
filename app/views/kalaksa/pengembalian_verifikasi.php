<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$title = $title ?? "Verifikasi Pengembalian - PRANATA";
$err = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']);
$ok = $_SESSION['flash_ok'] ?? null; unset($_SESSION['flash_ok']);
ob_start();
?>

<div class="space-y-4">
  <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm">
    <h2 class="text-2xl font-extrabold text-[#4A2A12]">Verifikasi Pengembalian</h2>
    <p class="mt-1 text-sm text-[#6B4B35]">Daftar pengembalian yang menunggu persetujuan Kalaksa.</p>
  </div>

  <?php if ($ok): ?>
    <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"><?php echo htmlspecialchars($ok); ?></div>
  <?php endif; ?>
  <?php if ($err): ?>
    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?php echo htmlspecialchars($err); ?></div>
  <?php endif; ?>

  <div class="rounded-2xl border border-black/10 bg-white shadow-sm overflow-hidden">
    <div class="bg-[#FCE4C4] px-4 py-3 font-semibold text-[#4A2A12]">Menunggu Verifikasi</div>
    <div class="overflow-x-auto">
      <table class="min-w-max w-full text-sm">
        <thead class="bg-[#FCE4C4] text-[#4A2A12]">
          <tr>
            <th class="px-3 py-3 text-left">Pemohon</th>
            <th class="px-3 py-3 text-left">Nama Pihak 1</th>
            <th class="px-3 py-3 text-left">Tujuan</th>
            <th class="px-3 py-3 text-left">Tgl Pinjam</th>
            <th class="px-3 py-3 text-left">Kondisi</th>
            <th class="px-3 py-3 text-left">Status</th>
            <th class="px-3 py-3 text-left">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($pending)): ?>
            <tr>
              <td colspan="7" class="px-4 py-6 text-center text-gray-600">Belum ada pengembalian menunggu verifikasi.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($pending as $row): ?>
              <?php $isDelete = !empty($row['delete_request_id']); ?>
              <tr class="border-t border-black/5">
                <td class="px-3 py-3"><?= htmlspecialchars($row['nama_pihak2'] ?? '-') ?></td>
                <td class="px-3 py-3"><?= htmlspecialchars($row['nama_pihak1'] ?? '-') ?></td>
                <td class="px-3 py-3"><?= htmlspecialchars($row['tujuan'] ?? '-') ?></td>
                <td class="px-3 py-3"><?= htmlspecialchars($row['tgl_pinjam'] ?? '-') ?></td>
                <td class="px-3 py-3"><?= htmlspecialchars($row['kondisi_kembali'] ?? '-') ?></td>
                <?php
                  if ($isDelete) {
                    $statusLabel = 'Menunggu Verifikasi Penghapusan';
                  } else {
                    $statusRaw = strtolower((string)($row['status'] ?? 'pending'));
                    if ($statusRaw === 'pending') {
                      $statusLabel = 'Menunggu Verifikasi';
                    } elseif ($statusRaw === 'completed') {
                      $statusLabel = 'Disetujui';
                    } elseif ($statusRaw === 'processing') {
                      $statusLabel = 'Dikembalikan';
                    } else {
                      $statusLabel = $statusRaw ? ucwords(str_replace('_', ' ', $statusRaw)) : '-';
                    }
                  }
                ?>
                <td class="px-3 py-3"><?= htmlspecialchars($statusLabel) ?></td>
                <td class="px-3 py-3">
                  <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
                    <a class="text-[#B45309] font-semibold" href="index.php?page=kalaksa_pengembalian_detail&id=<?= (int)($row['id_kembali'] ?? 0) ?>">Detail</a>
                    <?php if ($isDelete): ?>
                      <a class="text-green-700 font-semibold" href="index.php?page=kalaksa_delete_approve&id=<?= (int)$row['delete_request_id'] ?>">Setujui Hapus</a>
                      <a class="text-red-700 font-semibold" href="index.php?page=kalaksa_delete_reject&id=<?= (int)$row['delete_request_id'] ?>">Tolak</a>
                    <?php else: ?>
                      <a class="text-green-700 font-semibold" href="index.php?page=kalaksa_pengembalian_setujui&id=<?= (int)($row['id_kembali'] ?? 0) ?>">Setujui</a>
                      <a class="text-red-700 font-semibold" href="index.php?page=kalaksa_pengembalian_tolak&id=<?= (int)($row['id_kembali'] ?? 0) ?>">Tolak</a>
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
