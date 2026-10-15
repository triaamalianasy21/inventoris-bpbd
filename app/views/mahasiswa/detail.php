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

$mahasiswa = $data ?? null;
if (!$mahasiswa) {
    die("<p class='text-center text-red-500 mt-10'>Data mahasiswa tidak ditemukan.</p>");
}

// Path sebenarnya di server
$fotoServerPath = __DIR__ . '/../../../public/uploads/mahasiswa/' . ($mahasiswa['foto'] ?? '');

// URL untuk ditampilkan di browser
$fotoTampil = BASE_URL . 'public/uploads/fotoprofil.png';

if (!empty($mahasiswa['foto']) && file_exists($fotoServerPath)) {
    $fotoTampil = BASE_URL . 'public/uploads/mahasiswa/' . $mahasiswa['foto'];
}

// Judul halaman
$title = "Detail Mahasiswa - Android Web App";

ob_start();
?>



<div class="ml-64 flex-1 bg-gray-100 min-h-screen p-8">
    <div class="max-w-3xl mx-auto bg-white shadow-lg rounded-xl p-8 border border-gray-200">
        <h1 class="text-2xl font-bold text-blue-700 mb-6 flex items-center">
            <i class="fa-solid fa-user-graduate mr-3 text-green-500"></i>
            Detail Mahasiswa
        </h1>

        <div class="flex flex-col md:flex-row items-start gap-6">
            <!-- Foto mahasiswa -->
            <img src="<?= htmlspecialchars($fotoTampil) ?>" alt="Foto Mahasiswa"
                class="w-40 h-40 rounded-lg object-cover border border-gray-300 shadow">

            <div class="flex-1">
                <table class="w-full text-sm">
                    <tr>
                        <td class="font-semibold text-gray-700 w-40 py-2">Nama Lengkap</td>
                        <td class="text-gray-800"><?= htmlspecialchars($mahasiswa['nama'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td class="font-semibold text-gray-700 py-2">NIM</td>
                        <td class="text-gray-800"><?= htmlspecialchars($mahasiswa['nim'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td class="font-semibold text-gray-700 py-2">Jurusan</td>
                        <td class="text-gray-800"><?= htmlspecialchars($mahasiswa['jurusan'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td class="font-semibold text-gray-700 py-2">Alamat</td>
                        <td class="text-gray-800"><?= htmlspecialchars($mahasiswa['alamat'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td class="font-semibold text-gray-700 py-2">Golongan Darah</td>
                        <td class="text-gray-800"><?= htmlspecialchars($mahasiswa['golongan_darah'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td class="font-semibold text-gray-700 py-2">Tempat, Tanggal Lahir</td>
                        <td class="text-gray-800">
                            <?= htmlspecialchars($mahasiswa['tempat_lahir'] ?? '-') ?>,
                            <?= htmlspecialchars($mahasiswa['tanggal_lahir'] ?? '-') ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="font-semibold text-gray-700 py-2">No HP</td>
                        <td class="text-gray-800"><?= htmlspecialchars($mahasiswa['no_hp'] ?? '-') ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="mt-8 flex justify-end space-x-3">
            <a href="index.php?page=mahasiswa"
                class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-5 py-2 rounded-lg shadow transition">
                <i class="fa-solid fa-arrow-left mr-2"></i>Kembali
            </a>
            <a href="index.php?page=mahasiswa_ubah&id=<?= htmlspecialchars($mahasiswa['id']) ?>"
                class="bg-yellow-500 hover:bg-yellow-600 text-white px-5 py-2 rounded-lg shadow transition">
                <i class="fa-solid fa-pen-to-square mr-2"></i>Ubah
            </a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
