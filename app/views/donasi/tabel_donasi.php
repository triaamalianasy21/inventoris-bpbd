<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = $_SESSION['role'] ?? null;
?>

<!-- Tabel Donasi -->
<table class="w-full border border-gray-300 text-sm">
    <thead class="bg-gradient-to-r from-blue-500 to-purple-600 text-white">
        <tr class="border-b border-gray-200">
            <th class="py-3 px-4 text-left font-semibold border-r border-gray-200 w-12">No</th>
            <th class="py-3 px-4 text-left font-semibold border-r border-gray-200">Kategori</th>
            <th class="py-3 px-4 text-left font-semibold border-r border-gray-200">Nominal</th>
            <th class="py-3 px-4 text-left font-semibold border-r border-gray-200">Metode</th>
            <th class="py-3 px-4 text-left font-semibold border-r border-gray-200">Nama</th>
            <th class="py-3 px-4 text-left font-semibold border-r border-gray-200">Telepon</th>
            <th class="py-3 px-4 text-left font-semibold border-r border-gray-200">Email</th>
            <th class="py-3 px-4 text-left font-semibold border-r border-gray-200">Bukti</th>
            <th class="py-3 px-4 text-left font-semibold border-r border-gray-200">Status</th>
            <th class="py-3 px-4 text-center font-semibold w-56">Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($data)): ?>
            <?php $no = 1; foreach ($data as $donasi): ?>
                <tr class="border-b border-gray-200 hover:bg-blue-50 transition">
                    <td class="py-3 px-4 text-gray-800 font-semibold border-r border-gray-200"><?= $no++ ?></td>
                    <td class="py-3 px-4 text-gray-800 font-medium border-r border-gray-200"><?= htmlspecialchars($donasi['kategori']) ?></td>
                    <td class="py-3 px-4 text-gray-700 border-r border-gray-200">Rp <?= number_format($donasi['nominal'], 0, ',', '.') ?></td>
                    <td class="py-3 px-4 text-gray-700 border-r border-gray-200"><?= htmlspecialchars($donasi['metode']) ?></td>
                    <td class="py-3 px-4 text-gray-700 border-r border-gray-200"><?= htmlspecialchars($donasi['nama']) ?></td>
                    <td class="py-3 px-4 text-gray-700 border-r border-gray-200"><?= htmlspecialchars($donasi['telepon']) ?></td>
                    <td class="py-3 px-4 text-gray-700 border-r border-gray-200"><?= htmlspecialchars($donasi['email']) ?></td>
                    <td class="py-3 px-4 text-gray-700 border-r border-gray-200 text-center">
                        <?php
                        $buktiSrc = !empty($donasi['bukti']) ? "public/uploads/donasi/" . htmlspecialchars($donasi['bukti']) : null;
                        if ($buktiSrc):
                            $ext = strtolower(pathinfo($buktiSrc, PATHINFO_EXTENSION));
                            $isImage = in_array($ext, ['jpg','jpeg','png','gif']);
                        ?>
                            <?php if ($isImage): ?>
                                <img src="<?= $buktiSrc ?>" alt="Bukti <?= htmlspecialchars($donasi['nama']) ?>" class="w-12 h-12 rounded-full mx-auto cursor-pointer border border-gray-300 shadow-sm" onclick="openImageModal('<?= $buktiSrc ?>')">
                            <?php else: ?>
                                <a href="<?= $buktiSrc ?>" target="_blank" class="text-blue-600 underline">Lihat</a>
                            <?php endif; ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td class="py-3 px-4 text-gray-700 border-r border-gray-200"><?= htmlspecialchars($donasi['status']) ?></td>
                    <td class="py-3 px-4 text-center">
                        <div class="flex justify-center space-x-2">
                            <a href="index.php?page=donasi_detail&id=<?= $donasi['id'] ?>" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1.5 rounded-md text-xs font-semibold shadow-md flex items-center space-x-1 transition">
                                <i class="fa-solid fa-eye"></i>
                                <span>Detail</span>
                            </a>

                            <?php if ($role === 'admin'): ?>
                                <a href="index.php?page=donasi_ubah&id=<?= $donasi['id'] ?>" class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1.5 rounded-md text-xs font-semibold shadow-md flex items-center space-x-1 transition">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                    <span>Ubah</span>
                                </a>
                                <button onclick="openDeleteModal(<?= $donasi['id'] ?>, '<?= htmlspecialchars(addslashes($donasi['nama'])) ?>')" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-md text-xs font-semibold shadow-md flex items-center space-x-1 transition">
                                    <i class="fa-solid fa-trash"></i>
                                    <span>Hapus</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="10" class="py-6 text-center text-gray-500">
                    <i class="fa-solid fa-circle-info text-blue-400 mr-2"></i>
                    Tidak ada data Donasi yang tersedia.
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>