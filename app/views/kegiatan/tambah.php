<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$title = "Data Kegiatan - Android Web App";
ob_start();
?>
<!-- Konten -->
<div class="ml-64 flex-1 bg-gray-100 min-h-screen p-8">
    <div class="max-w-3xl mx-auto bg-white shadow-lg rounded-xl p-8 border border-gray-200">
        <h1 class="text-2xl font-bold text-blue-700 mb-6 flex items-center">
            <i class="fa-solid fa-calendar-plus mr-3 text-green-500"></i>
            Tambah Kegiatan
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
        <form action="index.php?page=kegiatan_tambah"
            method="POST"
            enctype="multipart/form-data">
            <class="space-y-4">

                <!-- Nama & Tanggal -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Nama Kegiatan</label>
                        <input type="text" name="nama" required
                            class="border border-gray-300 rounded-lg w-full p-2.5 focus:ring-2 focus:ring-blue-400 focus:outline-none"
                            placeholder="Masukkan nama kegiatan">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Tanggal</label>
                        <input type="date" name="tanggal" required
                            class="border border-gray-300 rounded-lg w-full p-2.5 focus:ring-2 focus:ring-blue-400 focus:outline-none"
                            placeholder="Masukkan tanggal">
                    </div>
                </div>

                <!-- Lokasi & Deskripsi -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Lokasi</label>
                        <input type="text" name="lokasi"
                            class="border border-gray-300 rounded-lg w-full p-2.5 focus:ring-2 focus:ring-blue-400 focus:outline-none"
                            placeholder="Masukkan Lokasi">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Deskripsi</label>
                        <input type="text" name="deskripsi"
                            class="border border-gray-300 rounded-lg w-full p-2.5 focus:ring-2 focus:ring-blue-400 focus:outline-none"
                            placeholder="Masukkan Deskripsi">
                    </div>
                </div>

                <!-- Upload Foto -->
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Foto Kegiatan</label>

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