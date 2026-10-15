<?php
// app/views/barang/detail.php — styled detail view
// Controller provides $data and $pk
if (session_status() === PHP_SESSION_NONE) session_start();

$title = "Detail Barang - PRANATA";
ob_start();
?>

<div class="ml-64 flex-1 bg-gray-100 min-h-screen p-8">
    <div class="max-w-3xl mx-auto bg-white shadow-lg rounded-xl p-8 border border-gray-200">
        <h1 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
            <i class="fa-solid fa-box-open mr-3 text-orange-400"></i>
            Detail Barang
        </h1>

        <table class="w-full text-sm">
            <tbody>
                <?php foreach ($data as $k => $v): ?>
                <tr class="border-b">
                    <td class="py-2 font-semibold w-1/3"><?= ucwords(str_replace('_', ' ', $k)) ?></td>
                    <td class="py-2"><?php
                        if (preg_match('/foto|image|gambar/i', $k) && !empty($v)) {
                            echo "<img src=\"" . (BASE_URL . "public/uploads/barang/" . htmlspecialchars($v)) . "\" style=\"max-width:200px\" />";
                        } else {
                            echo htmlspecialchars($v);
                        }
                    ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="mt-4">
            <a href="index.php?page=admin" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-5 py-2 rounded-lg shadow transition">
                <i class="fa-solid fa-arrow-left mr-2"></i>Kembali
            </a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>