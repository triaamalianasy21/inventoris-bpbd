<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$err = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']);
$ok = $_SESSION['flash_ok'] ?? null; unset($_SESSION['flash_ok']);

$q = trim($_GET['q'] ?? '');

ob_start();

function renderPaginationUsers($current, $pages, $param, $q)
{
  if ($pages <= 1) return;
  $base = $_GET;
  $base['page'] = 'users';
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

<div class="flex flex-col gap-3 mb-6 sm:flex-row sm:items-center sm:justify-between">
  <div>
    <h1 class="text-2xl font-extrabold text-[#4A2A12]">Kelola User</h1>
    <p class="text-sm text-[#6B4B35]">Kelola data user, cari, dan validasi akun.</p>
  </div>
  <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
    <form method="GET" action="index.php" class="relative w-full sm:w-auto">
      <input type="hidden" name="page" value="users">
      <input
        type="text"
        name="q"
        value="<?= htmlspecialchars($q) ?>"
        placeholder="cari user"
        class="w-full min-w-0 rounded-full border border-black/10 bg-white px-4 py-2 pr-10 text-sm outline-none focus:ring-2 focus:ring-[#E9A15D] sm:w-[220px]"
      />
      <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 opacity-60 hover:opacity-90">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
          <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.7"/>
          <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="1.7"/>
        </svg>
      </button>
    </form>
    <a href="index.php?page=users_tambah"
       class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#F28B2D] px-4 py-2 text-white font-bold shadow-sm hover:brightness-95 sm:w-auto">
      + Tambah User
    </a>
  </div>
</div>

<?php if ($err): ?>
  <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?php echo htmlspecialchars($err); ?></div>
<?php endif; ?>
<?php if ($ok): ?>
  <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"><?php echo htmlspecialchars($ok); ?></div>
<?php endif; ?>

<!-- Pending Users -->
<div class="mb-8">
  <div class="flex items-center justify-between mb-3">
    <h2 class="text-lg font-bold text-[#4A2A12]">User Pending</h2>
    <span class="text-xs rounded-full bg-[#F7E2B3] px-3 py-1 text-[#4A2A12] font-semibold">
      <?= (int)($pendingData['total'] ?? count($pendingUsers)) ?> user
    </span>
  </div>
  <div class="bg-white rounded-xl shadow-sm border border-black/10 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-max w-full text-sm">
        <thead class="bg-[#F7E2B3] text-[#4A2A12]">
          <tr>
            <th class="px-4 py-3 text-left w-[60px]">No</th>
            <th class="px-4 py-3 text-left">Nama</th>
            <th class="px-4 py-3 text-left">Username</th>
            <th class="px-4 py-3 text-left">Role</th>
            <th class="px-4 py-3 text-left">No Kontak</th>
            <th class="px-4 py-3 text-center w-[180px]">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($pendingUsers) === 0): ?>
            <tr>
              <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                Tidak ada user pending.
              </td>
            </tr>
          <?php else: $no = (($pendingData['page'] ?? 1) - 1) * 10 + 1; foreach ($pendingUsers as $row): ?>
            <tr class="border-t border-black/5 hover:bg-black/3">
              <td class="px-4 py-3"><?= $no++ ?></td>
              <td class="px-4 py-3 font-semibold text-[#4A2A12]"><?= htmlspecialchars($row['nama'] ?? '-') ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($row['username'] ?? '-') ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($row['role'] ?? '-') ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($row['no_kontak'] ?? '-') ?></td>
              <td class="px-4 py-3">
                <div class="flex items-center justify-center gap-2">
                  <a href="index.php?page=users_validasi&id=<?= (int)$row['id_user'] ?>&status=active"
                     class="inline-flex items-center rounded bg-[#2E7D32] px-3 py-1 text-xs font-semibold text-white hover:brightness-95">
                    Setujui
                  </a>
                  <a href="index.php?page=users_validasi&id=<?= (int)$row['id_user'] ?>&status=rejected"
                     class="inline-flex items-center rounded bg-[#E53935] px-3 py-1 text-xs font-semibold text-white hover:brightness-95">
                    Tolak
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php renderPaginationUsers((int)($pendingData['page'] ?? 1), (int)($pendingData['pages'] ?? 1), 'page_pending', $q); ?>
</div>

<!-- Active Users -->
<div class="mb-8">
  <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between mb-3">
    <div>
      <h2 class="text-lg font-bold text-[#4A2A12]">User Aktif</h2>
      <div class="text-xs text-[#6B4B35]">Catatan: user yang bisa dihapus hanya user yang tidak memiliki transaksi.</div>
    </div>
    <span class="text-xs rounded-full bg-[#F7E2B3] px-3 py-1 text-[#4A2A12] font-semibold">
      <?= (int)($activeData['total'] ?? count($activeUsers)) ?> user
    </span>
  </div>
  <div class="bg-white rounded-xl shadow-sm border border-black/10 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-max w-full text-sm">
        <thead class="bg-[#F7E2B3] text-[#4A2A12]">
          <tr>
            <th class="px-4 py-3 text-left w-[60px]">No</th>
            <th class="px-4 py-3 text-left">Nama</th>
            <th class="px-4 py-3 text-left">Username</th>
            <th class="px-4 py-3 text-left">Role</th>
            <th class="px-4 py-3 text-left">No Kontak</th>
            <th class="px-4 py-3 text-center w-[140px]">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($activeUsers) === 0): ?>
            <tr>
              <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                Tidak ada user aktif.
              </td>
            </tr>
          <?php else: $no = (($activeData['page'] ?? 1) - 1) * 10 + 1; foreach ($activeUsers as $row): ?>
            <tr class="border-t border-black/5 hover:bg-black/3">
              <td class="px-4 py-3"><?= $no++ ?></td>
              <td class="px-4 py-3 font-semibold text-[#4A2A12]"><?= htmlspecialchars($row['nama'] ?? '-') ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($row['username'] ?? '-') ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($row['role'] ?? '-') ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($row['no_kontak'] ?? '-') ?></td>
              <td class="px-4 py-3">
                <div class="flex items-center justify-center gap-2">
                  <a href="index.php?page=users_ubah&id=<?= (int)$row['id_user'] ?>"
                     class="inline-flex items-center rounded bg-[#7D4CFF] px-3 py-1 text-xs font-semibold text-white hover:brightness-95">
                    Edit
                  </a>
                  <?php if (!empty($row['can_delete'])): ?>
                    <a href="index.php?page=users_hapus&id=<?= (int)$row['id_user'] ?>"
                       class="deleteUser inline-flex items-center rounded bg-[#E53935] px-3 py-1 text-xs font-semibold text-white hover:brightness-95">
                      Hapus
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
  <?php renderPaginationUsers((int)($activeData['page'] ?? 1), (int)($activeData['pages'] ?? 1), 'page_active', $q); ?>
</div>

<!-- Rejected Users -->
<div>
  <div class="flex items-center justify-between mb-3">
    <h2 class="text-lg font-bold text-[#4A2A12]">User Ditolak</h2>
    <span class="text-xs rounded-full bg-[#F7E2B3] px-3 py-1 text-[#4A2A12] font-semibold">
      <?= count($rejectedUsers) ?> user
    </span>
  </div>
  <div class="bg-white rounded-xl shadow-sm border border-black/10 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-max w-full text-sm">
        <thead class="bg-[#F7E2B3] text-[#4A2A12]">
          <tr>
            <th class="px-4 py-3 text-left w-[60px]">No</th>
            <th class="px-4 py-3 text-left">Nama</th>
            <th class="px-4 py-3 text-left">Username</th>
            <th class="px-4 py-3 text-left">Role</th>
            <th class="px-4 py-3 text-left">No Kontak</th>
            <th class="px-4 py-3 text-center w-[140px]">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($rejectedUsers) === 0): ?>
            <tr>
              <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                Tidak ada user ditolak.
              </td>
            </tr>
          <?php else: $no = 1; foreach ($rejectedUsers as $row): ?>
            <tr class="border-t border-black/5 hover:bg-black/3">
              <td class="px-4 py-3"><?= $no++ ?></td>
              <td class="px-4 py-3 font-semibold text-[#4A2A12]"><?= htmlspecialchars($row['nama'] ?? '-') ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($row['username'] ?? '-') ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($row['role'] ?? '-') ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($row['no_kontak'] ?? '-') ?></td>
              <td class="px-4 py-3">
                <div class="flex items-center justify-center gap-2">
                  <a href="index.php?page=users_validasi&id=<?= (int)$row['id_user'] ?>&status=active"
                     class="inline-flex items-center rounded bg-[#2E7D32] px-3 py-1 text-xs font-semibold text-white hover:brightness-95">
                    Aktifkan
                  </a>
                  <?php if (!empty($row['can_delete'])): ?>
                    <a href="index.php?page=users_hapus&id=<?= (int)$row['id_user'] ?>"
                       class="deleteUser inline-flex items-center rounded bg-[#E53935] px-3 py-1 text-xs font-semibold text-white hover:brightness-95">
                      Hapus
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
</div>

<script>
(function(){
  document.querySelectorAll('.deleteUser').forEach(function(btn){
    btn.addEventListener('click', function(e){
      if (!confirm('Hapus user ini?')) {
        e.preventDefault();
      }
    });
  });
})();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>
