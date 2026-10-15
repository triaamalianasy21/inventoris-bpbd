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

$donasi = $data ?? null;
if (!$donasi) {
    die("<p class='text-center text-red-500 mt-10'>Data donasi tidak ditemukan.</p>");
}

// Path bukti
$buktiServerPath = __DIR__ . '/../../../public/uploads/donasi/' . ($donasi['bukti'] ?? '');
$buktiTampil = null;
if (!empty($donasi['bukti']) && file_exists($buktiServerPath)) {
    $buktiTampil = BASE_URL . 'public/uploads/donasi/' . $donasi['bukti'];
}

// Judul halaman
$title = "Detail Donasi - Android Web App";

ob_start();
?>



<div class="ml-64 flex-1 bg-gray-100 min-h-screen p-8">
    <div class="max-w-3xl mx-auto bg-white shadow-lg rounded-xl p-8 border border-gray-200">
        <h1 class="text-2xl font-bold text-blue-700 mb-6 flex items-center">
            <i class="fa-solid fa-hand-holding-heart mr-3 text-green-500"></i>
            Detail Donasi
        </h1>

        <div class="flex flex-col md:flex-row items-start gap-6">
            <!-- Bukti Donasi -->
            <div>
                <?php if ($buktiTampil):
                    $ext = strtolower(pathinfo($buktiTampil, PATHINFO_EXTENSION));
                    $isImage = in_array($ext, ['jpg','jpeg','png','gif']);
                ?>
                    <?php if ($isImage): ?>
                        <img src="<?= htmlspecialchars($buktiTampil) ?>" alt="Bukti Donasi" class="w-40 h-40 rounded-lg object-cover border border-gray-300 shadow">
                    <?php else: ?>
                        <a href="<?= htmlspecialchars($buktiTampil) ?>" target="_blank" class="text-blue-600 underline">Download Bukti</a>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="w-40 h-40 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400">No Bukti</div>
                <?php endif; ?>
            </div>

            <div class="flex-1">
                <table class="w-full text-sm">
                    <tr>
                        <td class="font-semibold text-gray-700 w-40 py-2">Kategori</td>
                        <td class="text-gray-800"><?= htmlspecialchars($donasi['kategori'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td class="font-semibold text-gray-700 py-2">Nominal</td>
                        <td class="text-gray-800">Rp <?= number_format($donasi['nominal'] ?? 0,0,',','.') ?></td>
                    </tr>
                    <tr>
                        <td class="font-semibold text-gray-700 py-2">Metode</td>
                        <td class="text-gray-800"><?= htmlspecialchars($donasi['metode'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td class="font-semibold text-gray-700 py-2">Nama</td>
                        <td class="text-gray-800"><?= htmlspecialchars($donasi['nama'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td class="font-semibold text-gray-700 py-2">Telepon</td>
                        <td class="text-gray-800"><?= htmlspecialchars($donasi['telepon'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td class="font-semibold text-gray-700 py-2">Email</td>
                        <td class="text-gray-800"><?= htmlspecialchars($donasi['email'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td class="font-semibold text-gray-700 py-2">Pesan</td>
                        <td class="text-gray-800"><?= nl2br(htmlspecialchars($donasi['pesan'] ?? '-')) ?></td>
                    </tr>
                    <tr>
                        <td class="font-semibold text-gray-700 py-2">Status</td>
                        <td class="text-gray-800"><?= htmlspecialchars($donasi['status'] ?? '-') ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="mt-8 flex justify-end space-x-3">
            <a href="index.php?page=donasi"
                class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-5 py-2 rounded-lg shadow transition">
                <i class="fa-solid fa-arrow-left mr-2"></i>Kembali
            </a>
            <a href="index.php?page=donasi_ubah&id=<?= htmlspecialchars($donasi['id']) ?>"
                class="bg-yellow-500 hover:bg-yellow-600 text-white px-5 py-2 rounded-lg shadow transition">
                <i class="fa-solid fa-pen-to-square mr-2"></i>Ubah
            </a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
