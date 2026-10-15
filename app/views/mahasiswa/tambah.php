<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$title = "Data Mahasiswa - Android Web App";
ob_start();
?>
<!-- Konten -->
<div class="ml-64 flex-1 bg-gray-100 min-h-screen p-8">
    <div class="max-w-3xl mx-auto bg-white shadow-lg rounded-xl p-8 border border-gray-200">
        <h1 class="text-2xl font-bold text-blue-700 mb-6 flex items-center">
            <i class="fa-solid fa-user-plus mr-3 text-green-500"></i>
            Tambah Mahasiswa
        </h1>

        <!-- Pesan sukses / error -->
        <?php if (isset($_GET['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($_GET['error']) ?>
            </div>
        <?php elseif (isset($_GET['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($_GET['success']) ?>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form action="index.php?page=mahasiswa_tambah"
            method="POST"
            enctype="multipart/form-data">
            <class="space-y-4">

                <!-- Nama & NIM -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Nama Lengkap</label>
                        <input type="text" name="nama" required
                            class="border border-gray-300 rounded-lg w-full p-2.5 focus:ring-2 focus:ring-blue-400 focus:outline-none"
                            placeholder="Masukkan nama lengkap">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">NIM</label>
                        <input type="text" name="nim" required
                            class="border border-gray-300 rounded-lg w-full p-2.5 focus:ring-2 focus:ring-blue-400 focus:outline-none"
                            placeholder="Masukkan NIM">
                    </div>
                </div>

                <!-- Jurusan & Golongan Darah -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Jurusan</label>
                        <input type="text" name="jurusan"
                            class="border border-gray-300 rounded-lg w-full p-2.5 focus:ring-2 focus:ring-blue-400 focus:outline-none"
                            placeholder="Masukkan Jurusan">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Golongan Darah</label>
                        <select name="golongan_darah"
                            class="border border-gray-300 rounded-lg w-full p-2.5 focus:ring-2 focus:ring-blue-400 focus:outline-none">
                            <option value="">Pilih</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="AB">AB</option>
                            <option value="O">O</option>
                        </select>
                    </div>
                </div>

                <!-- Tempat & Tanggal Lahir -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Tempat Lahir</label>
                        <input type="text" name="tempat_lahir"
                            class="border border-gray-300 rounded-lg w-full p-2.5 focus:ring-2 focus:ring-blue-400 focus:outline-none"
                            placeholder="Masukkan tempat lahir">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir"
                            class="border border-gray-300 rounded-lg w-full p-2.5 focus:ring-2 focus:ring-blue-400 focus:outline-none">
                    </div>
                </div>

                <!-- No HP -->
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">No HP</label>
                    <input type="text" name="no_hp"
                        class="border border-gray-300 rounded-lg w-full p-2.5 focus:ring-2 focus:ring-blue-400 focus:outline-none"
                        placeholder="Masukkan nomor HP">
                </div>

                <!-- Alamat -->
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Alamat</label>
                    <textarea name="alamat" rows="3"
                        class="border border-gray-300 rounded-lg w-full p-2.5 focus:ring-2 focus:ring-blue-400 focus:outline-none"
                        placeholder="Masukkan alamat lengkap"></textarea>
                </div>

                <!-- Upload Foto -->
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Foto Mahasiswa</label>

                    <!-- Preview foto -->
                    <div class="mb-2">
                        <img id="fotoPreview" src="#" alt="Preview Foto"
                            class="w-32 h-32 object-cover rounded border border-gray-300 hidden">
                    </div>

                    <input type="file" name="foto" accept="image/*"
                        class="border border-gray-300 rounded-lg w-full p-2.5 bg-gray-50 focus:ring-2 focus:ring-blue-400 focus:outline-none"
                        onchange="previewFoto(this)">
                    <p class="text-sm text-gray-500 mt-1">Format: JPG, PNG, max 2MB</p>
                </div>

                <!-- Tombol Aksi -->
                <div class="flex justify-end space-x-3 pt-4">
                    <a href="index.php"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-5 py-2 rounded-lg shadow transition">
                        <i class="fa-solid fa-arrow-left mr-2"></i>Kembali
                    </a>
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg shadow transition">
                        <i class="fa-solid fa-save mr-2"></i>Simpan
                    </button>
                </div>
        </form>

        <script>
            function previewFoto(input) {
                const preview = document.getElementById('fotoPreview');
                if (input.files && input.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        preview.classList.remove('hidden');
                    }
                    reader.readAsDataURL(input.files[0]);
                } else {
                    preview.src = '#';
                    preview.classList.add('hidden');
                }
            }
        </script>

        <?php
        $content = ob_get_clean();
        include __DIR__ . '/../layout/main.php';
        ?>