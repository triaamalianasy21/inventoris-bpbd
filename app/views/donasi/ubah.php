<?php
$title = "Ubah Donasi - Android Web App";
ob_start();
?>

<div class="ml-64 p-8 bg-gray-100 min-h-screen">
    <div class="max-w-4xl mx-auto bg-white shadow-lg rounded-xl p-8 border border-gray-200">
        <h1 class="text-2xl font-bold text-blue-700 mb-6 flex items-center">
                <i class="fa-solid fa-hand-holding-heart mr-3 text-yellow-600"></i>
                Ubah Donasi

        <form action="index.php?page=donasi_ubah&id=<?= urlencode($data['id']) ?>"
            method="POST"
            enctype="multipart/form-data"
            class="grid grid-cols-2 gap-4">

            <!-- Kategori -->
            <div class="flex flex-col">
                <label for="kategori" class="mb-1 font-medium text-gray-700">Kategori</label>
                <select id="kategori" name="kategori" class="border rounded px-4 py-2" required>
                    <option value="">Pilih</option>
                    <option value="Umum" <?= (isset($data['kategori']) && $data['kategori']=='Umum')? 'selected' : '' ?>>Umum</option>
                    <option value="Bantuan" <?= (isset($data['kategori']) && $data['kategori']=='Bantuan')? 'selected' : '' ?>>Bantuan</option>
                    <option value="Zakat" <?= (isset($data['kategori']) && $data['kategori']=='Zakat')? 'selected' : '' ?>>Zakat</option>
                </select>
            </div>

            <!-- Nominal -->
            <div class="flex flex-col">
                <label for="nominal" class="mb-1 font-medium text-gray-700">Nominal</label>
                <input type="number" id="nominal" name="nominal" value="<?= htmlspecialchars($data['nominal'] ?? '') ?>" class="border rounded px-4 py-2" required>
            </div>

            <!-- Metode -->
            <div class="flex flex-col">
                <label for="metode" class="mb-1 font-medium text-gray-700">Metode</label>
                <select id="metode" name="metode" class="border rounded px-4 py-2">
                    <option value="Transfer" <?= (isset($data['metode']) && $data['metode']=='Transfer')? 'selected' : '' ?>>Transfer</option>
                    <option value="QR" <?= (isset($data['metode']) && $data['metode']=='QR')? 'selected' : '' ?>>QR</option>
                    <option value="Cash" <?= (isset($data['metode']) && $data['metode']=='Cash')? 'selected' : '' ?>>Cash</option>
                </select>
            </div>

            <!-- Nama -->
            <div class="flex flex-col">
                <label for="nama" class="mb-1 font-medium text-gray-700">Nama</label>
                <input type="text" id="nama" name="nama" value="<?= htmlspecialchars($data['nama'] ?? '') ?>" class="border rounded px-4 py-2" required>
            </div>

            <!-- Telepon -->
            <div class="flex flex-col">
                <label for="telepon" class="mb-1 font-medium text-gray-700">Telepon</label>
                <input type="text" id="telepon" name="telepon" value="<?= htmlspecialchars($data['telepon'] ?? '') ?>" class="border rounded px-4 py-2">
            </div>

            <!-- Email -->
            <div class="flex flex-col">
                <label for="email" class="mb-1 font-medium text-gray-700">Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($data['email'] ?? '') ?>" class="border rounded px-4 py-2">
            </div>

            <!-- Pesan -->
            <div class="flex flex-col col-span-2">
                <label for="pesan" class="mb-1 font-medium text-gray-700">Pesan</label>
                <textarea id="pesan" name="pesan" class="border rounded px-4 py-2"><?= htmlspecialchars($data['pesan'] ?? '') ?></textarea>
            </div>

            <!-- Bukti -->
            <div class="col-span-2 flex items-center gap-4 mt-2">
                <?php
                $uploadDir = 'public/uploads/donasi/';
                $buktiLama = $data['bukti'] ?? '';
                $buktiPath = $uploadDir . $buktiLama;

                $buktiTampil = (!empty($buktiLama) && file_exists(__DIR__ . '/../../../' . $buktiPath))
                    ? $buktiPath
                    : null;
                ?>
                <?php if ($buktiTampil): ?>
                    <?php $ext = strtolower(pathinfo($buktiTampil, PATHINFO_EXTENSION)); ?>
                    <?php if (in_array($ext,['jpg','jpeg','png','gif'])): ?>
                        <img src="<?= htmlspecialchars($buktiTampil) ?>" alt="Bukti" class="w-24 h-24 rounded-lg object-cover border border-gray-300 shadow">
                    <?php else: ?>
                        <a href="<?= htmlspecialchars($buktiTampil) ?>" target="_blank" class="text-blue-600 underline">Lihat Bukti</a>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="flex flex-col">
                    <label class="block text-gray-600 mb-1 text-sm">Ganti Bukti:</label>
                    <input type="file" name="bukti" accept="image/*,application/pdf" class="border rounded px-3 py-1 text-sm">
                    <input type="hidden" name="bukti_lama" value="<?= htmlspecialchars($buktiLama) ?>">
                </div>
            </div>

            <!-- Tombol -->
            <div class="col-span-2 flex justify-end space-x-3 mt-4">
                <a href="index.php?page=donasi"
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