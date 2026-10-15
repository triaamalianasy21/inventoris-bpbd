<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../models/PeminjamanModel.php';
require_once __DIR__ . '/../../models/Koneksi.php';

$user = $_SESSION['username'] ?? 'Pemohon';
$userId = $_SESSION['user_id'] ?? null;

$pm = new PeminjamanModel();
$userLoans = $userId ? $pm->getByUser($userId) : [];

$pendingLoanCount = count(array_filter($userLoans, function($l){
    return ($l['status'] ?? '') === 'waiting_verification' || ($l['status'] ?? '') === 'pending';
}));
$activeCount = count(array_filter($userLoans, function($l){
    return ($l['status'] ?? '') === 'approved';
}));

$pendingReturnCount = 0;
$completedReturnCount = 0;
if ($userId) {
    $conn = Koneksi::connect();
    $res = $conn->query("SHOW TABLES LIKE 'pengembalian'");
    if ($res && $res->num_rows > 0) {
        $stmt = $conn->prepare("SELECT status FROM pengembalian WHERE id_user = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $r = $stmt->get_result();
        while ($row = $r->fetch_assoc()) {
            $st = strtolower((string)($row['status'] ?? ''));
            if ($st === 'pending' || $st === 'processing') $pendingReturnCount++;
            if ($st === 'completed') $completedReturnCount++;
        }
    }
}

$pendingCount = $pendingLoanCount + $pendingReturnCount;
$pendingLink = $pendingReturnCount > 0 ? 'pemohon_pengembalian' : 'peminjaman';
$activeLink = 'peminjaman';
$doneLink = 'pemohon_pengembalian';

$title = "Dashboard Pemohon - PRANATA";
ob_start();
?>

<div class="space-y-6">
    <div class="relative overflow-hidden rounded-2xl border border-black/10 bg-gradient-to-br from-[#FFD6A5] via-[#F7B267] to-[#F28B2D] p-6 text-[#4A2A12] shadow-sm">
        <div class="absolute -right-12 -top-12 h-40 w-40 rounded-full bg-white/30 blur-2xl"></div>
        <div class="absolute -left-10 -bottom-10 h-32 w-32 rounded-full bg-[#F9C784]/70 blur-2xl"></div>
        <div class="relative">
            <h2 class="text-2xl font-extrabold">Halo, <?php echo htmlspecialchars($user); ?></h2>
            <p class="mt-2 text-sm text-[#5A3A22]">Selamat datang di sistem peminjaman. Kelola permintaanmu dengan cepat dan mudah.</p>

            <div class="mt-4 flex flex-wrap items-center gap-3">
                <a href="index.php?page=peminjaman"
                   class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2 text-sm font-bold text-[#8A3A12] shadow-sm hover:brightness-95">
                    Menu Peminjaman
                </a>
                <div class="text-xs font-semibold uppercase tracking-wide text-[#6B3E1F]">
                    Status Aktif: <?php echo (int)$activeCount; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($userLoans)): ?>
        <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h3 class="text-xl font-extrabold text-[#4A2A12]">Anda belum memiliki peminjaman, ajukan sekarang!</h3>
                    <p class="mt-1 text-sm text-[#6B4B35]">Klik tombol di samping untuk menuju menu peminjaman di sidebar.</p>
                </div>
                <a href="index.php?page=peminjaman_tambah"
                   class="inline-flex items-center justify-center rounded-xl bg-[#F28B2D] px-5 py-3 text-sm font-bold text-white shadow-sm hover:brightness-95">
                    Ajukan Peminjaman
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="grid gap-4 md:grid-cols-3">
            <a href="index.php?page=<?= $activeLink ?>" class="rounded-2xl border border-black/10 bg-white p-5 shadow-sm hover:shadow-md transition">
                <div class="text-xs font-semibold uppercase text-[#B45309]">Peminjaman Aktif</div>
                <div class="mt-2 text-3xl font-extrabold text-[#4A2A12]"><?php echo (int)$activeCount; ?></div>
                <p class="mt-1 text-sm text-[#6B4B35]">Peminjaman yang sudah disetujui.</p>
            </a>
            <a href="index.php?page=<?= $pendingLink ?>" class="rounded-2xl border border-black/10 bg-white p-5 shadow-sm hover:shadow-md transition">
                <div class="text-xs font-semibold uppercase text-[#B45309]">Menunggu Persetujuan</div>
                <div class="mt-2 text-3xl font-extrabold text-[#4A2A12]"><?php echo (int)$pendingCount; ?></div>
                <p class="mt-1 text-sm text-[#6B4B35]">Menunggu verifikasi peminjaman/pengembalian.</p>
            </a>
            <a href="index.php?page=<?= $doneLink ?>" class="rounded-2xl border border-black/10 bg-white p-5 shadow-sm hover:shadow-md transition">
                <div class="text-xs font-semibold uppercase text-[#B45309]">Selesai</div>
                <div class="mt-2 text-3xl font-extrabold text-[#4A2A12]"><?php echo (int)$completedReturnCount; ?></div>
                <p class="mt-1 text-sm text-[#6B4B35]">Riwayat peminjaman yang sudah dikembalikan.</p>
            </a>
        </div>

        <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h4 class="text-lg font-bold text-[#4A2A12]">Butuh peminjaman baru?</h4>
                    <p class="text-sm text-[#6B4B35]">Masuk ke menu peminjaman untuk membuat permintaan baru.</p>
                </div>
                <a href="index.php?page=peminjaman_tambah"
                   data-restrict="<?php echo ($activeCount > 0 || $pendingCount > 0) ? '1' : '0'; ?>"
                   class="inline-flex items-center justify-center rounded-xl bg-[#F28B2D] px-5 py-3 text-sm font-bold text-white shadow-sm hover:brightness-95">
                    Ajukan Peminjaman
                </a>
            </div>
        </div>
    <?php endif; ?>
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
