<?php
// app/views/barang/tambah.php — view styled like the app's Add form
// Controller provides: $cols (array kolom) and $pk

if (session_status() === PHP_SESSION_NONE) session_start();
$role = $_SESSION['role'] ?? '';
$canVerify = ($role === 'kalaksa');

$title = "Tambah Barang Baru - PRANATA";
ob_start();
?>

<div class="ml-64 flex-1 bg-gray-100 min-h-screen p-8">
    <div class="max-w-3xl mx-auto bg-white shadow-lg rounded-xl p-8 border border-gray-200">
        <h1 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
            <i class="fa-solid fa-box-open mr-3 text-orange-400"></i>
            Tambah Barang Baru
        </h1>

        <form action="index.php?page=barang_create" method="POST" enctype="multipart/form-data" class="space-y-4">
            <?php foreach ($cols as $col):
                $name = $col['Field'];
                // skip primary / auto_increment
                if ($name === ($pk ?? '') || strpos(strtolower($col['Extra'] ?? ''), 'auto_increment') !== false) continue;

                if ($name === 'status_brg' && !$canVerify) continue;

                $label = ucwords(str_replace('_', ' ', $name));
                $placeholder = "Masukkan " . strtolower($label);

                $type = 'text';
                if (preg_match('/int|tinyint|smallint|mediumint/i', $col['Type'])) $type = 'number';
                if (preg_match('/date|timestamp|datetime/i', $col['Type'])) $type = 'date';
                if (preg_match('/text/i', $col['Type'])) $type = 'textarea';
                if (preg_match('/enum\((.*?)\)/i', $col['Type'], $m)) {
                    $enumOptions = array_map(function($v){ return trim($v, " '\""); }, explode(',', $m[1]));
                } else $enumOptions = null;

                $isFile = preg_match('/foto|image|gambar/i', $name);
            ?>

            <div>
                <label class="block text-gray-700 font-semibold mb-1"><?= ($name === 'id_lokasi') ? 'Nama Lokasi' : $label ?></label>

                <?php if ($name === 'id_lokasi'): ?>
                    <?php if (!empty($lokasiOptions)): ?>
                        <select name="<?= $name ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            <option value="">-- Pilih Nama Lokasi --</option>
                            <?php foreach ($lokasiOptions as $opt): ?>
                                <option value="<?= htmlspecialchars($opt['id']) ?>"><?= htmlspecialchars($opt['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input type="number" name="<?= $name ?>" placeholder="<?= $placeholder ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
                    <?php endif; ?>
                <?php elseif ($isFile): ?>
                    <input type="file" name="<?= $name ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
                <?php elseif ($name === 'status_brg' && $canVerify): ?>
                    <select name="<?= $name ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="menunggu_verifikasi">Menunggu Verifikasi</option>
                        <option value="terverifikasi">Terverifikasi</option>
                        <option value="ditolak">Ditolak</option>
                    </select>
                <?php elseif ($enumOptions): ?>
                    <select name="<?= $name ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="">-- Pilih <?= $label ?> --</option>
                        <?php foreach ($enumOptions as $opt): ?>
                            <option value="<?= htmlspecialchars($opt) ?>"><?= htmlspecialchars($opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif ($type === 'textarea'): ?>
                    <textarea name="<?= $name ?>" rows="4" placeholder="<?= $placeholder ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
                <?php else: ?>
                    <input type="<?= $type ?>" name="<?= $name ?>" placeholder="<?= $placeholder ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
                <?php endif; ?>
            </div>

            <?php endforeach; ?>

            <div class="flex justify-end space-x-3 mt-6">
                <a href="index.php?page=admin" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-5 py-2 rounded-lg shadow transition">
                    <i class="fa-solid fa-arrow-left mr-2"></i>Kembali
                </a>
                <button type="submit" class="bg-[#F28B2D] hover:bg-orange-500 text-white px-5 py-2 rounded-lg shadow transition">
                    <i class="fa-solid fa-save mr-2"></i>Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>
