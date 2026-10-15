<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$err = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']);

$title = "Tambah User - PRANATA";
ob_start();
?>

<div class="max-w-3xl">
  <h1 class="text-2xl font-extrabold text-[#4A2A12] mb-2">Tambah User</h1>
  <p class="text-sm text-[#6B4B35] mb-6">Isi data user baru di bawah ini.</p>

  <?php if ($err): ?>
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?php echo htmlspecialchars($err); ?></div>
  <?php endif; ?>

  <form method="POST" action="index.php?page=users_tambah" class="space-y-4 bg-white rounded-xl border border-black/10 p-6">
    <div>
      <label class="block text-sm font-semibold text-[#4A2A12] mb-1">Nama</label>
      <input type="text" name="nama" required
             class="w-full rounded-lg border border-black/10 px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-[#E9A15D]">
    </div>

    <div>
      <label class="block text-sm font-semibold text-[#4A2A12] mb-1">Username</label>
      <input type="text" name="username" required
             class="w-full rounded-lg border border-black/10 px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-[#E9A15D]">
    </div>

    <div>
      <label class="block text-sm font-semibold text-[#4A2A12] mb-1">Password</label>
      <input type="password" name="password" required minlength="6"
             class="w-full rounded-lg border border-black/10 px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-[#E9A15D]">
    </div>

    <div>
      <label class="block text-sm font-semibold text-[#4A2A12] mb-1">Role</label>
      <select name="role" required
              class="w-full rounded-lg border border-black/10 px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-[#E9A15D]">
        <option value="pemohon">Pemohon</option>
        <option value="admin">Admin</option>
        <option value="kalaksa">Kalaksa</option>
        <option value="pengurus_barang">Pengurus Barang</option>
        <option value="petugas_gudang">Petugas Gudang</option>
      </select>
    </div>

    <div>
      <label class="block text-sm font-semibold text-[#4A2A12] mb-1">No Kontak</label>
      <input type="text" name="no_kontak"
             class="w-full rounded-lg border border-black/10 px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-[#E9A15D]">
    </div>

    <div>
      <label class="block text-sm font-semibold text-[#4A2A12] mb-1">Status</label>
      <select name="status"
              class="w-full rounded-lg border border-black/10 px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-[#E9A15D]">
        <option value="active">Aktif</option>
        <option value="pending">Pending</option>
        <option value="rejected">Ditolak</option>
      </select>
    </div>

    <div class="flex items-center gap-3">
      <a href="index.php?page=users"
         class="rounded-lg border border-black/10 px-4 py-2 text-sm font-semibold text-[#4A2A12] hover:bg-black/5">
        Kembali
      </a>
      <button type="submit"
              class="rounded-lg bg-[#F28B2D] px-5 py-2 text-sm font-bold text-white hover:brightness-95">
        Simpan
      </button>
    </div>
  </form>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>
