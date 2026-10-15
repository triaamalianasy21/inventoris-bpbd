<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = $_SESSION['role'] ?? null;
?>

<!-- Tabel Kegiatan -->
<table class="w-full border border-gray-300 text-sm">
    <thead class="bg-gradient-to-r from-blue-500 to-purple-600 text-white">
        <tr class="border-b border-gray-200">
            <th class="py-3 px-4 text-left font-semibold border-r border-gray-200 w-16">No</th>
            <th class="py-3 px-4 text-left font-semibold border-r border-gray-200">Nama</th>
            <th class="py-3 px-4 text-left font-semibold border-r border-gray-200">Tanggal</th>
            <th class="py-3 px-4 text-left font-semibold border-r border-gray-200">Lokasi</th>
            <th class="py-3 px-4 text-left font-semibold border-r border-gray-200">Deskripsi</th>
            <th class="py-3 px-4 text-center font-semibold border-r border-gray-200 w-32">Foto</th>
            <th class="py-3 px-4 text-center font-semibold w-56">Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($data)): ?>
            <?php $no = 1;
            foreach ($data as $kegiatan): ?>
                <tr class="border-b border-gray-200 hover:bg-blue-50 transition">
                    <td class="py-3 px-4 text-gray-800 font-semibold border-r border-gray-200"><?= $no++ ?></td>
                    <td class="py-3 px-4 text-gray-800 font-medium border-r border-gray-200"><?= htmlspecialchars($kegiatan['nama']) ?></td>
                    <td class="py-3 px-4 text-gray-700 border-r border-gray-200"><?= htmlspecialchars($kegiatan['tanggal']) ?></td>
                    <td class="py-3 px-4 text-gray-700 border-r border-gray-200"><?= htmlspecialchars($kegiatan['lokasi']) ?></td>
                    <td class="py-3 px-4 text-gray-700 border-r border-gray-200"><?= htmlspecialchars($kegiatan['deskripsi']) ?></td>
                    <td class="py-3 px-4 text-center border-r border-gray-200">
                        <?php
                        $fotoSrc = !empty($kegiatan['foto'])
                            ? "public/uploads/kegiatan/" . htmlspecialchars($kegiatan['foto'])
                            : "public/uploads/fotoprofil.png";
                        ?>
                        <img src="<?= $fotoSrc ?>" alt="Foto <?= htmlspecialchars($kegiatan['nama']) ?>"
                            class="w-12 h-12 rounded-full mx-auto cursor-pointer border border-gray-300 shadow-sm"
                            onclick="openImageModal('<?= $fotoSrc ?>')">
                    </td>
                    <td class="py-3 px-4 text-center">
                        <div class="flex justify-center space-x-2">
                            <!-- Tombol Detail -->
                            <a href="index.php?page=kegiatan_detail&id=<?= $kegiatan['id'] ?>"
                                class="bg-green-500 hover:bg-green-600 text-white px-3 py-1.5 rounded-md text-xs font-semibold shadow-md flex items-center space-x-1 transition">
                                <i class="fa-solid fa-eye"></i>
                                <span>Detail</span>
                            </a>

                            <?php if ($role === 'admin'): ?>
                                <!-- Tombol Edit -->
                                <a href="index.php?page=kegiatan_ubah&id=<?= $kegiatan['id'] ?>"
                                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1.5 rounded-md text-xs font-semibold shadow-md flex items-center space-x-1 transition">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                    <span>Ubah</span>
                                </a>

                                <!-- Tombol Hapus -->
                                <button onclick="openDeleteModal(<?= $kegiatan['id'] ?>, '<?= htmlspecialchars(addslashes($kegiatan['nama'])) ?>')"
                                    class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-md text-xs font-semibold shadow-md flex items-center space-x-1 transition">
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
                <td colspan="7" class="py-6 text-center text-gray-500">
                    <i class="fa-solid fa-circle-info text-blue-400 mr-2"></i>
                    Tidak ada data Kegiatan yang tersedia.
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>