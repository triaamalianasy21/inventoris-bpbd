<?php
require_once __DIR__ . '/../../../app/config/Config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=login");
    exit;
}

$kegiatan = $data ?? null;
if (!$kegiatan) {
    die("<p class='text-center text-red-500 mt-10'>Data kegiatan tidak ditemukan.</p>");
}

// Path sebenarnya di server
$fotoServerPath = __DIR__ . '/../../../public/uploads/kegiatan/' . ($kegiatan['foto'] ?? '');

// URL untuk ditampilkan di browser
$fotoTampil = BASE_URL . 'public/uploads/fotoprofil.png';

if (!empty($kegiatan['foto']) && file_exists($fotoServerPath)) {
    $fotoTampil = BASE_URL . 'public/uploads/kegiatan/' . $kegiatan['foto'];
}

// Judul halaman
$title = "Detail Kegiatan - Android Web App";

ob_start();
?>



<div class="ml-64 flex-1 bg-gray-100 min-h-screen p-8">
    <div class="max-w-3xl mx-auto bg-white shadow-lg rounded-xl p-8 border border-gray-200">
        <h1 class="text-2xl font-bold text-blue-700 mb-6 flex items-center">
            <i class="fa-solid fa-calendar-days mr-3 text-green-500"></i>
            Detail Kegiatan
        </h1>

        <div class="flex flex-col md:flex-row items-start gap-6">
            <!-- Foto kegiatan -->
            <img src="<?= htmlspecialchars($fotoTampil) ?>" alt="Foto Kegiatan"
                class="w-40 h-40 rounded-lg object-cover border border-gray-300 shadow">

            <div class="flex-1">
                <table class="w-full text-sm">
                    <tr>
                        <td class="font-semibold text-gray-700 w-40 py-2">Nama Kegiatan</td>
                        <td class="text-gray-800"><?= htmlspecialchars($kegiatan['nama'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td class="font-semibold text-gray-700 py-2">Tanggal</td>
                        <td class="text-gray-800"><?= htmlspecialchars($kegiatan['tanggal'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td class="font-semibold text-gray-700 py-2">Lokasi</td>
                        <td class="text-gray-800"><?= htmlspecialchars($kegiatan['lokasi'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td class="font-semibold text-gray-700 py-2">Deskripsi</td>
                        <td class="text-gray-800"><?= htmlspecialchars($kegiatan['deskripsi'] ?? '-') ?></td>
                    </tr>
                    
                </table>
            </div>
        </div>

        <div class="mt-8 flex justify-end space-x-3">
            <a href="index.php?page=kegiatan"
                class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-5 py-2 rounded-lg shadow transition">
                <i class="fa-solid fa-arrow-left mr-2"></i>Kembali
            </a>
            <a href="index.php?page=kegiatan_ubah&id=<?= htmlspecialchars($kegiatan['id']) ?>"
                class="bg-yellow-500 hover:bg-yellow-600 text-white px-5 py-2 rounded-lg shadow transition">
                <i class="fa-solid fa-pen-to-square mr-2"></i>Ubah
            </a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
