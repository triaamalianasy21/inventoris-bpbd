<?php
// =======================================================
// DETAIL ADMIN
// =======================================================

// Pastikan session aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=login");
    exit;
}

// Data admin dikirim dari controller dalam variabel $data
$admin = $data ?? null;
if (!$admin) {
    die("<p class='text-center text-red-500 mt-10'>Data admin tidak ditemukan.</p>");
}

// Path foto admin (cek apakah ada foto tersimpan)
$fotoPath = __DIR__ . '/../../../public/uploads/' . ($admin['foto'] ?? '');
$fotoTampil = (file_exists($fotoPath) && !empty($admin['foto']))
    ? '../../../public/uploads/' . $admin['foto']
    : '../../../public/uploads/fotoprofil.png';

// Judul halaman
$title = "Detail Admin - Android Web App";

ob_start();
?>

<div class="space-y-4">
    <div class="max-w-3xl mx-auto bg-white shadow-sm rounded-2xl p-6 sm:p-8 border border-black/10">
        <h1 class="text-2xl font-bold text-[#4A2A12] mb-6 flex items-center">
            <i class="fa-solid fa-user-shield mr-3 text-yellow-500"></i>
            Detail Admin
        </h1>

        <div class="flex flex-col md:flex-row items-start gap-6">
            <!-- Foto admin -->
            <img src="<?= htmlspecialchars($fotoTampil) ?>" alt="Foto Admin"
                class="w-28 h-28 sm:w-40 sm:h-40 rounded-lg object-cover border border-gray-300 shadow">

            <div class="flex-1 overflow-x-auto">
                <table class="w-full min-w-[320px] text-sm">
                    <tr>
                        <td class="font-semibold text-gray-700 w-40 py-2">Nama Lengkap</td>
                        <td class="text-gray-800"><?= htmlspecialchars($admin['nama'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td class="font-semibold text-gray-700 py-2">Role</td>
                        <td class="text-gray-800 capitalize"><?= htmlspecialchars($admin['role'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td class="font-semibold text-gray-700 py-2">Email</td>
                        <td class="text-gray-800"><?= htmlspecialchars($admin['email'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td class="font-semibold text-gray-700 py-2">Username</td>
                        <td class="text-gray-800"><?= htmlspecialchars($admin['username'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td class="font-semibold text-gray-700 py-2">Tanggal Dibuat</td>
                        <td class="text-gray-800"><?= htmlspecialchars($admin['created_at'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td class="font-semibold text-gray-700 py-2">Terakhir Login</td>
                        <td class="text-gray-800"><?= htmlspecialchars($admin['last_login'] ?? '-') ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="mt-8 flex flex-col sm:flex-row justify-end gap-3">
            <a href="index.php?page=admin"
                class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-5 py-2 rounded-lg shadow transition text-center w-full sm:w-auto">
                <i class="fa-solid fa-arrow-left mr-2"></i>Kembali
            </a>
            <a href="index.php?page=admin_ubah&id=<?= htmlspecialchars($admin['id']) ?>"
                class="bg-[#F28B2D] hover:brightness-95 text-white px-5 py-2 rounded-lg shadow transition text-center w-full sm:w-auto">
                <i class="fa-solid fa-pen-to-square mr-2"></i>Ubah
            </a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
