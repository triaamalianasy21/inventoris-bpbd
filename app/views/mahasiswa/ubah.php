<?php
$title = "Ubah Mahasiswa - Android Web App";
ob_start();
?>

<div class="ml-64 p-8 bg-gray-100 min-h-screen">
    <div class="max-w-4xl mx-auto bg-white shadow-lg rounded-xl p-8 border border-gray-200">
        <h1 class="text-2xl font-bold text-blue-700 mb-6 flex items-center">
            <i class="fa-solid fa-user-pen mr-3 text-yellow-600"></i>
            Ubah Mahasiswa
        </h1>

        <form action="index.php?page=mahasiswa_ubah&id=<?= urlencode($data['id']) ?>"
            method="POST"
            enctype="multipart/form-data"
            class="grid grid-cols-2 gap-4">

            <!-- Nama -->
            <div class="flex flex-col">
                <label for="nama" class="mb-1 font-medium text-gray-700">Nama Lengkap</label>
                <input type="text" id="nama" name="nama"
                    value="<?= htmlspecialchars($data['nama'] ?? '') ?>"
                    placeholder="Nama Lengkap"
                    class="border rounded px-4 py-2" required>
            </div>

            <!-- NIM -->
            <div class="flex flex-col">
                <label for="nim" class="mb-1 font-medium text-gray-700">NIM</label>
                <input type="text" id="nim" name="nim"
                    value="<?= htmlspecialchars($data['nim'] ?? '') ?>"
                    placeholder="NIM"
                    class="border rounded px-4 py-2" required>
            </div>

            <!-- Jurusan -->
            <div class="flex flex-col">
                <label for="jurusan" class="mb-1 font-medium text-gray-700">Program Studi</label>
                <input type="text" id="jurusan" name="jurusan"
                    value="<?= htmlspecialchars($data['jurusan'] ?? '') ?>"
                    placeholder="Jurusan"
                    class="border rounded px-4 py-2" required>
            </div>

            <!-- Golongan Darah -->
            <div class="flex flex-col">
                <label for="golongan_darah" class="mb-1 font-medium text-gray-700">Golongan Darah</label>
                <input type="text" id="golongan_darah" name="golongan_darah"
                    value="<?= htmlspecialchars($data['golongan_darah'] ?? '') ?>"
                    placeholder="Golongan Darah"
                    class="border rounded px-4 py-2">
            </div>

            <!-- Tempat Lahir -->
            <div class="flex flex-col">
                <label for="tempat_lahir" class="mb-1 font-medium text-gray-700">Tempat Lahir</label>
                <input type="text" id="tempat_lahir" name="tempat_lahir"
                    value="<?= htmlspecialchars($data['tempat_lahir'] ?? '') ?>"
                    placeholder="Tempat Lahir"
                    class="border rounded px-4 py-2">
            </div>

            <!-- Tanggal Lahir -->
            <div class="flex flex-col">
                <label for="tanggal_lahir" class="mb-1 font-medium text-gray-700">Tanggal Lahir</label>
                <input type="date" id="tanggal_lahir" name="tanggal_lahir"
                    value="<?= htmlspecialchars($data['tanggal_lahir'] ?? '') ?>"
                    class="border rounded px-4 py-2">
            </div>

            <!-- No HP -->
            <div class="flex flex-col">
                <label for="no_hp" class="mb-1 font-medium text-gray-700">No HP</label>
                <input type="text" id="no_hp" name="no_hp"
                    value="<?= htmlspecialchars($data['no_hp'] ?? '') ?>"
                    placeholder="No HP"
                    class="border rounded px-4 py-2">
            </div>

            <!-- Foto -->
            <div class="col-span-2 flex items-center gap-4 mt-2">
                <?php
                $uploadDir = 'public/uploads/mahasiswa/';
                $fotoLama = $data['foto'] ?? '';
                $fotoPath = $uploadDir . $fotoLama;

                $fotoTampil = (!empty($fotoLama) && file_exists(__DIR__ . '/../../../' . $fotoPath))
                    ? $fotoPath
                    : 'public/uploads/fotoprofil.png';
                ?>
                <img src="<?= htmlspecialchars($fotoTampil) ?>"
                    alt="Foto Mahasiswa"
                    class="w-24 h-24 rounded-lg object-cover border border-gray-300 shadow">

                <div class="flex flex-col">
                    <label class="block text-gray-600 mb-1 text-sm">Ganti Foto:</label>
                    <input type="file" name="foto" accept="image/*" class="border rounded px-3 py-1 text-sm">
                    <input type="hidden" name="foto_lama" value="<?= htmlspecialchars($fotoLama) ?>">
                </div>
            </div>

            <!-- Alamat -->
            <div class="col-span-2 flex flex-col">
                <label for="alamat" class="mb-1 font-medium text-gray-700">Alamat Lengkap</label>
                <textarea id="alamat" name="alamat"
                    placeholder="Alamat Lengkap"
                    class="border rounded px-4 py-2 col-span-2"
                    required><?= htmlspecialchars($data['alamat'] ?? '') ?></textarea>
            </div>

            <!-- Tombol -->
            <div class="col-span-2 flex justify-end space-x-3 mt-4">
                <a href="index.php?page=mahasiswa"
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