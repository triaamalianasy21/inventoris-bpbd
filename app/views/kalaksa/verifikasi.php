<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$role = $_SESSION['role'] ?? '';

$err = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']);
$ok = $_SESSION['flash_ok'] ?? null; unset($_SESSION['flash_ok']);

$title = $title ?? "Verifikasi Peminjaman - PRANATA";
ob_start();
?>

<div class="space-y-4">
  <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
      <div>
        <h2 class="text-2xl font-extrabold text-[#4A2A12]">Menu Verifikasi</h2>
        <p class="mt-1 text-sm text-[#6B4B35]">Daftar peminjaman yang menunggu persetujuan Kalaksa.</p>
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
    <div class="px-4 py-3 bg-[#FCE4C4] text-[#4A2A12] font-semibold">Menunggu Persetujuan</div>
    <div class="overflow-x-auto">
      <table class="min-w-max w-full text-sm">
        <thead class="bg-[#FCE4C4] text-[#4A2A12]">
          <tr>
            <th class="px-3 py-3 text-left">Nama Pihak 2</th>
            <th class="px-3 py-3 text-left">Tujuan</th>
            <th class="px-3 py-3 text-left">Tgl Pinjam</th>
            <th class="px-3 py-3 text-left">Status</th>
            <th class="px-3 py-3 text-left">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($data)): ?>
            <tr>
              <td colspan="5" class="px-4 py-6 text-center text-gray-600">Belum ada data menunggu persetujuan.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($data as $row): ?>
              <?php $isDelete = !empty($row['delete_request_id']); ?>
              <tr class="border-t border-black/5">
                <td class="px-3 py-3"><?php echo htmlspecialchars($row['nama_pihak2'] ?? ($row['nama'] ?? '-')); ?></td>
                <td class="px-3 py-3"><?php echo htmlspecialchars($row['tujuan'] ?? '-'); ?></td>
                <td class="px-3 py-3"><?php echo htmlspecialchars($row['tgl_pinjam'] ?? ($row['created_at'] ?? '-')); ?></td>
                <td class="px-3 py-3">
                  <?php
                    if ($isDelete) {
                      echo 'Menunggu Verifikasi Penghapusan';
                    } else {
                      echo htmlspecialchars(($row['status'] ?? '') === 'approved' ? 'Disetujui' : 'Menunggu Verifikasi');
                    }
                  ?>
                </td>
                <td class="px-3 py-3">
                  <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
                    <a class="text-[#B45309] font-semibold" href="index.php?page=peminjaman_detail&id=<?php echo $row['id']; ?>">Detail</a>
                    <?php if ($isDelete): ?>
                      <a class="text-green-700 font-semibold" href="index.php?page=kalaksa_delete_approve&id=<?php echo (int)$row['delete_request_id']; ?>">Setujui Hapus</a>
                      <a class="text-red-700 font-semibold" href="index.php?page=kalaksa_delete_reject&id=<?php echo (int)$row['delete_request_id']; ?>">Tolak</a>
                    <?php else: ?>
                      <a class="text-green-700 font-semibold" href="index.php?page=kalaksa_setujui&id=<?php echo $row['id']; ?>">Setujui</a>
                      <a class="text-red-700 font-semibold" href="index.php?page=kalaksa_tolak&id=<?php echo $row['id']; ?>">Tolak</a>
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
