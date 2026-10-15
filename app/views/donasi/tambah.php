<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$title = "Tambah Donasi - Android Web App";
ob_start();
?>
<!-- Konten -->
<div class="ml-64 flex-1 bg-gray-100 min-h-screen p-8">
    <div class="max-w-3xl mx-auto bg-white shadow-lg rounded-xl p-8 border border-gray-200">
        <h1 class="text-2xl font-bold text-blue-700 mb-6 flex items-center">
            <i class="fa-solid fa-hand-holding-heart mr-3 text-green-500"></i>
            Tambah Donasi
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
        <form action="index.php?page=donasi_tambah"
            method="POST"
            enctype="multipart/form-data">
            <class="space-y-4">

                <!-- Kategori & Nominal -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Kategori</label>
                        <select name="kategori" required class="border border-gray-300 rounded-lg w-full p-2.5">
                            <option value="">Pilih Kategori</option>
                            <option value="Umum">Umum</option>
                            <option value="Bantuan">Bantuan</option>
                            <option value="Zakat">Zakat</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Nominal</label>
                        <input type="number" name="nominal" required min="1"
                            class="border border-gray-300 rounded-lg w-full p-2.5"
                            placeholder="Masukkan nominal">
                    </div>
                </div>

                <!-- Metode & Nama -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Metode</label>
                        <select name="metode" required class="border border-gray-300 rounded-lg w-full p-2.5">
                            <option value="Transfer">Transfer</option>
                            <option value="QR">QR</option>
                            <option value="Cash">Cash</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Nama</label>
                        <input type="text" name="nama" required class="border border-gray-300 rounded-lg w-full p-2.5" placeholder="Nama donatur">
                    </div>
                </div>

                <!-- Kontak & Email -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Telepon</label>
                        <input type="text" name="telepon" class="border border-gray-300 rounded-lg w-full p-2.5" placeholder="Nomor telepon">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" class="border border-gray-300 rounded-lg w-full p-2.5" placeholder="Email">
                    </div>
                </div>

                <!-- Pesan & Bukti -->
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Pesan</label>
                    <textarea name="pesan" rows="3" class="border border-gray-300 rounded-lg w-full p-2.5" placeholder="Pesan (opsional)"></textarea>
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Bukti Pembayaran</label>
                    <input type="file" name="bukti" accept="image/*,application/pdf" class="border border-gray-300 rounded-lg w-full p-2.5">
                    <p class="text-sm text-gray-500 mt-1">Format: JPG, PNG, PDF. Max 5MB</p>
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