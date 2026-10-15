<?php
$title = "Ubah Kegiatan - Android Web App";
ob_start();
?>

<div class="ml-64 p-8 bg-gray-100 min-h-screen">
    <div class="max-w-4xl mx-auto bg-white shadow-lg rounded-xl p-8 border border-gray-200">
        <h1 class="text-2xl font-bold text-blue-700 mb-6 flex items-center">
            <i class="fa-solid fa-calendar-day mr-3 text-yellow-600"></i>
            Ubah Kegiatan
        </h1>

        <form action="index.php?page=kegiatan_ubah&id=<?= urlencode($data['id']) ?>"
            method="POST"
            enctype="multipart/form-data"
            class="grid grid-cols-2 gap-4">

            <!-- Nama -->
            <div class="flex flex-col">
                <label for="nama" class="mb-1 font-medium text-gray-700">Nama Kegiatan</label>
                <input type="text" id="nama" name="nama"
                    value="<?= htmlspecialchars($data['nama'] ?? '') ?>"
                    placeholder="Nama Kegiatan"
                    class="border rounded px-4 py-2" required>
            </div>

            <!-- NIM -->
            <div class="flex flex-col">
                <label for="tanggal" class="mb-1 font-medium text-gray-700">Tanggal</label>
                <input type="date" id="tanggal" name="tanggal"
                    value="<?= htmlspecialchars($data['tanggal'] ?? '') ?>"
                    class="border rounded px-4 py-2" required> 
            </div>

            <!-- Lokasi -->
            <div class="flex flex-col">
                <label for="lokasi" class="mb-1 font-medium text-gray-700">Lokasi</label>
                <input type="text" id="lokasi" name="lokasi"
                    value="<?= htmlspecialchars($data['lokasi'] ?? '') ?>"
                    placeholder="Lokasi"
                    class="border rounded px-4 py-2" required> 
            </div>

            <!-- Golongan Darah -->
            <div class="flex flex-col">
                <label for="deskripsi" class="mb-1 font-medium text-gray-700">Deskripsi</label>
                <textarea id="deskripsi" name="deskripsi" class="border rounded px-4 py-2"><?= htmlspecialchars($data['deskripsi'] ?? '') ?></textarea>
            </div>

            <!-- Foto -->
            <div class="col-span-2 flex items-center gap-4 mt-2">
                <?php
                $uploadDir = 'public/uploads/kegiatan/';
                $fotoLama = $data['foto'] ?? '';
                $fotoPath = $uploadDir . $fotoLama;

                $fotoTampil = (!empty($fotoLama) && file_exists(__DIR__ . '/../../../' . $fotoPath))
                    ? $fotoPath
                    : 'public/uploads/fotoprofil.png';
                ?>
                <img src="<?= htmlspecialchars($fotoTampil) ?>"
                    alt="Foto Kegiatan"
                    class="w-24 h-24 rounded-lg object-cover border border-gray-300 shadow">

                <div class="flex flex-col">
                    <label class="block text-gray-600 mb-1 text-sm">Ganti Foto:</label>
                    <input type="file" name="foto" accept="image/*" class="border rounded px-3 py-1 text-sm">
                    <input type="hidden" name="foto_lama" value="<?= htmlspecialchars($fotoLama) ?>">
                </div>
            </div>

            <!-- Tombol -->
            <div class="col-span-2 flex justify-end space-x-3 mt-4">
                <a href="index.php?page=kegiatan"
                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-5 py-2 rounded-lg transition">
                    <i class="fa-solid fa-arrow-left mr-2"></i>Kembali
                </a>
                <button type="submit" name="submit" value="1"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-5 py-2 rounded-lg transition">
                    <i class="fa-solid fa-save mr-2"></i>Perbarui
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>