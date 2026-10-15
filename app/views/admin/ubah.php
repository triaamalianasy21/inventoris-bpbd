<?php
// =======================================================
// UBAH ADMIN
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

$admin = $data ?? null;
if (!$admin) {
    die("<p class='text-center text-red-500 mt-10'>Data admin tidak ditemukan.</p>");
}

$title = "Ubah Admin - Android Web App";
ob_start();
?>

<div class="space-y-4">
    <div class="max-w-3xl mx-auto bg-white shadow-sm rounded-2xl p-6 sm:p-8 border border-black/10">
        <h1 class="text-2xl font-bold text-[#4A2A12] mb-6 flex items-center">
            <i class="fa-solid fa-pen-to-square mr-3 text-yellow-500"></i>
            Ubah Data Admin
        </h1>

        <form action="index.php?page=admin_ubah&id=<?= $admin['id'] ?>" method="POST" class="space-y-4">
            <div>
                <label class="block text-gray-700 font-semibold mb-1">Nama Lengkap</label>
                <input type="text" name="nama" value="<?= htmlspecialchars($admin['nama']) ?>" required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring focus:ring-yellow-200 focus:border-yellow-500">
            </div>
            <div>
                <label class="block text-gray-700 font-semibold mb-1">Username</label>
                <input type="text" name="username" value="<?= htmlspecialchars($admin['username']) ?>" required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring focus:ring-yellow-200 focus:border-yellow-500">
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-1">Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring focus:ring-yellow-200 focus:border-yellow-500">
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-1">Password (kosongkan jika tidak ingin diubah)</label>
                <input type="password" name="password"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring focus:ring-yellow-200 focus:border-yellow-500">
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-1">Role</label>
                <select name="role"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring focus:ring-yellow-200 focus:border-yellow-500">
                    <option value="admin" <?= $admin['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="user" <?= $admin['role'] === 'user' ? 'selected' : '' ?>>User</option>
                </select>
            </div>

            <div class="flex items-center">
                <input type="checkbox" name="aktif" id="aktif" <?= $admin['aktif'] ? 'checked' : '' ?> class="mr-2">
                <label for="aktif" class="text-gray-700">Akun Aktif</label>
            </div>

            <div class="flex flex-col sm:flex-row justify-end gap-3 mt-6">
                <a href="index.php?page=admin"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-5 py-2 rounded-lg shadow transition text-center w-full sm:w-auto">
                    <i class="fa-solid fa-arrow-left mr-2"></i>Kembali
                </a>
                <button type="submit"
                    class="bg-[#F28B2D] hover:brightness-95 text-white px-5 py-2 rounded-lg shadow transition w-full sm:w-auto">
                    <i class="fa-solid fa-save mr-2"></i>Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>
