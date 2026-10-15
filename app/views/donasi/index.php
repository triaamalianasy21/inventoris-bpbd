<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$title = "Data Donasi - Android Web App";
ob_start();
?>

<div class="ml-64 flex-1 bg-gray-100 min-h-screen p-8">
    <div class="bg-white shadow-lg rounded-xl p-8 border border-gray-200">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-3">
            <h1 class="text-2xl font-bold text-blue-700 flex items-center">
                <i class="fa-solid fa-hand-holding-heart mr-3 text-blue-500"></i>
                Data Donasi
            </h1>

            <div class="flex items-center gap-3 w-full sm:w-auto">
                <!-- Kolom Search -->
                <div class="flex items-center">
                    <input type="text" id="searchInput"
                        placeholder="Cari Donasi..."
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 w-48 sm:w-60">
                    <button id="searchBtn"
                        class="ml-2 bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm shadow transition">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </div>

                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="index.php?page=donasi_tambah"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow transition flex items-center">
                        <i class="fa-solid fa-plus mr-2"></i>Tambah Donasi
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tabel Donasi -->
        <div class="overflow-x-auto" id="tableContainer">
            <?php include __DIR__ . '/tabel_donasi.php'; ?>
        </div>
    </div>
</div>

<!-- Modal Preview Foto -->
<div id="imageModal" class="fixed inset-0 bg-black bg-opacity-70 hidden items-center justify-center z-50">
    <div class="relative bg-transparent">
        <img id="modalImage" src="" alt="Preview Foto"
            class="max-w-xs md:max-w-md lg:max-w-lg rounded-lg shadow-lg border-4 border-white">
        <button onclick="closeImageModal()"
            class="absolute top-0 right-0 mt-2 mr-2 text-white bg-red-600 hover:bg-red-700 rounded-full px-3 py-1 font-bold shadow-md">
            ✕
        </button>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-sm p-6 text-center">
        <i class="fa-solid fa-triangle-exclamation text-red-500 text-4xl mb-3"></i>
        <h2 class="text-xl font-semibold text-gray-800 mb-2">Konfirmasi Hapus</h2>
        <p class="text-gray-600 mb-4">
            Apakah Anda yakin ingin menghapus <span id="namaDonasi" class="font-semibold text-gray-900"></span>?
        </p>
        <div class="flex justify-center space-x-4">
            <button onclick="closeDeleteModal()"
                class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg transition">
                Batal
            </button>
            <a id="btnHapus" href="#"
                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                Hapus
            </a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>

<script>
    //Preview Foto Modal
    function openImageModal(src) {
        const modal = document.getElementById('imageModal');
        document.getElementById('modalImage').src = src;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeImageModal() {
        const modal = document.getElementById('imageModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // 🗑️ Hapus Modal
    function openDeleteModal(id, nama) {
        document.getElementById('namaDonasi').textContent = nama;
        document.getElementById('btnHapus').href = 'index.php?page=donasi_hapus&id=' + id;
        const modal = document.getElementById('deleteModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeDeleteModal() {
        const modal = document.getElementById('deleteModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    //AJAX Live Search (optional)
    const searchInput = document.getElementById('searchInput');
    const tableContainer = document.getElementById('tableContainer');

    searchInput.addEventListener('keyup', async () => {
        const keyword = searchInput.value.trim();
        // you can implement app/ajax/search_donasi.php to support this
        const response = await fetch(`app/ajax/search_donasi.php?search=${encodeURIComponent(keyword)}`);
        const html = await response.text();
        tableContainer.innerHTML = html;
    });
</script>