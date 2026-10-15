<?php
// =======================================================
// TAMBAH ADMIN
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

$title = "Tambah Admin - Android Web App";
ob_start();
?>

<div class="space-y-4">
    <div class="max-w-3xl mx-auto bg-white shadow-sm rounded-2xl p-6 sm:p-8 border border-black/10">
        <h1 class="text-2xl font-bold text-[#4A2A12] mb-6 flex items-center">
            <i class="fa-solid fa-user-plus mr-3 text-green-500"></i>
            Tambah Admin Baru
        </h1>

        <form action="index.php?page=admin_tambah" method="POST" class="space-y-4">
            <!-- Nama Lengkap -->
            <div>
                <label class="block text-gray-700 font-semibold mb-1">Nama Lengkap</label>
                <input type="text" name="nama" required
                    placeholder="Masukkan nama lengkap"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring focus:ring-blue-200 focus:border-blue-500">
            </div>

            <!-- Username -->
            <div>
                <label class="block text-gray-700 font-semibold mb-1">Username</label>
                <input type="text" name="username" required
                    placeholder="Masukkan username"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring focus:ring-blue-200 focus:border-blue-500">
            </div>

            <!-- Email -->
            <div>
                <label class="block text-gray-700 font-semibold mb-1">Email</label>
                <input type="email" name="email" required
                    placeholder="Masukkan email aktif"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring focus:ring-blue-200 focus:border-blue-500">
            </div>

            <!-- Password -->
            <div>
                <label class="block text-gray-700 font-semibold mb-1">Password</label>
                <input type="password" name="password" required
                    placeholder="Masukkan password"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring focus:ring-blue-200 focus:border-blue-500">
            </div>

            <!-- Role -->
            <div>
                <label class="block text-gray-700 font-semibold mb-1">Role</label>
                <select name="role" required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring focus:ring-blue-200 focus:border-blue-500">
                    <option value="">-- Pilih Role --</option>
                    <option value="admin">Admin</option>
                    <option value="user">User</option>
                </select>
            </div>

            <!-- Status Aktif -->
            <div class="flex items-center">
                <input type="checkbox" name="aktif" id="aktif" value="1" checked class="mr-2">
                <label for="aktif" class="text-gray-700">Akun Aktif</label>
            </div>

            <!-- created_at otomatis di controller -->

            <!-- Tombol Aksi -->
            <div class="flex flex-col sm:flex-row justify-end gap-3 mt-6">
                <a href="index.php?page=admin"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-5 py-2 rounded-lg shadow transition text-center w-full sm:w-auto">
                    <i class="fa-solid fa-arrow-left mr-2"></i>Kembali
                </a>
                <button type="submit"
                    class="bg-[#F28B2D] hover:brightness-95 text-white px-5 py-2 rounded-lg shadow transition w-full sm:w-auto">
                    <i class="fa-solid fa-save mr-2"></i>Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>
