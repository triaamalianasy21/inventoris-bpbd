<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$user_id = $_SESSION['user_id'] ?? null;

$title = "Ajukan Peminjaman - PRANATA";
$role = $_SESSION['role'] ?? '';
ob_start();
?>

<div class="space-y-4">
    <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm">
        <h2 class="text-2xl font-extrabold text-[#4A2A12]">Ajukan Peminjaman</h2>
        <p class="mt-1 text-sm text-[#6B4B35]">Lengkapi data peminjaman dan pilih barang yang ingin dipinjam.</p>
    </div>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <?= htmlspecialchars($_SESSION['flash_error']) ?>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <?php if (!$user_id): ?>
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            Sesi login tidak ditemukan. Silakan login ulang.
        </div>
    <?php else: ?>
        <?php
        $action = "index.php?page=peminjaman_tambah";
        if ($role === 'pengurus_barang') {
            $action .= "&from=pengurus";
        }
        $isModal = false;
        $old = $_SESSION['old_peminjaman'] ?? ($_POST ?? []);
        unset($_SESSION['old_peminjaman']);
        include __DIR__ . '/_form.php';
        ?>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>
