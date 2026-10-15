<?php
// app/views/barang/ubah.php
// Controller provides: $cols, $pk, $data

if (session_status() === PHP_SESSION_NONE) session_start();
$role = $_SESSION['role'] ?? '';
$canVerify = ($role === 'kalaksa');

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/Config.php';
}

$title = "Ubah Barang - PRANATA";
ob_start();

$idVal = htmlspecialchars($data[$pk] ?? '');
?>

<div class="space-y-4">
    <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm">
        <h2 class="text-2xl font-extrabold text-[#4A2A12]">Ubah Barang</h2>
        <p class="mt-1 text-sm text-[#6B4B35]">Perbarui data barang yang sudah tersimpan.</p>
    </div>

    <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm">
        <form action="index.php?page=barang_edit&amp;id=<?= $idVal ?>" method="POST" enctype="multipart/form-data" class="space-y-4">
            <?php foreach ($cols as $col):
                $name = $col['Field'];
                if ($name === ($pk ?? '') || strpos(strtolower($col['Extra'] ?? ''), 'auto_increment') !== false) continue;

                $label = ucwords(str_replace('_', ' ', $name));
                $type = 'text';
                if (preg_match('/int|tinyint|smallint|mediumint/i', $col['Type'])) $type = 'number';
                if (preg_match('/date|timestamp|datetime/i', $col['Type'])) $type = 'date';
                if (preg_match('/text/i', $col['Type'])) $type = 'textarea';
                if (preg_match('/enum\((.*?)\)/i', $col['Type'], $m)) {
                    $enumOptions = array_map(function($v){ return trim($v, " '\""); }, explode(',', $m[1]));
                } else $enumOptions = null;

                $isFile = preg_match('/foto|image|gambar/i', $name);
                $value = $data[$name] ?? '';
            ?>

            <div>
                <label class="block text-sm font-semibold mb-1"><?= ($name === 'id_lokasi') ? 'Nama Lokasi' : $label ?></label>

                <?php if ($name === 'id_lokasi'): ?>
                    <?php if (!empty($lokasiOptions)): ?>
                        <select name="<?= $name ?>" class="w-full rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-[#F7B267]">
                            <option value="">-- Pilih Nama Lokasi --</option>
                            <?php foreach ($lokasiOptions as $opt): ?>
                                <option value="<?= htmlspecialchars($opt['id']) ?>" <?= ((string)$value === (string)$opt['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($opt['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input type="number" name="<?= $name ?>" value="<?= htmlspecialchars($value) ?>" class="w-full rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-[#F7B267]" />
                    <?php endif; ?>
                <?php elseif ($name === 'status_brg' && !$canVerify): ?>
                    <?php
                      $raw = strtolower((string)$value);
                      if (in_array($raw, ['aktif','tersedia','verified','diverifikasi','sudah diverifikasi','terverifikasi'], true)) {
                        $statusLabel = 'Terverifikasi';
                      } elseif (in_array($raw, ['pending','menunggu','waiting_verification','menunggu_verifikasi'], true)) {
                        $statusLabel = 'Menunggu Verifikasi';
                      } elseif ($raw === 'ditolak') {
                        $statusLabel = 'Ditolak';
                      } elseif ($raw !== '') {
                        $statusLabel = ucwords(str_replace('_', ' ', $raw));
                      } else {
                        $statusLabel = '-';
                      }
                    ?>
                    <input type="text" value="<?= htmlspecialchars($statusLabel) ?>" class="w-full rounded-xl border border-black/10 bg-[#F6F2EC] px-3 py-2 text-sm text-[#6B4B35]" readonly />
                <?php elseif ($name === 'status_brg' && $canVerify): ?>
                    <select name="<?= $name ?>" class="w-full rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-[#F7B267]">
                        <?php
                          $cur = strtolower((string)$value);
                          $opts = [
                            'menunggu_verifikasi' => 'Menunggu Verifikasi',
                            'terverifikasi' => 'Terverifikasi',
                            'ditolak' => 'Ditolak'
                          ];
                          if ($cur !== '' && !isset($opts[$cur])) {
                            echo '<option value="' . htmlspecialchars($cur) . '" selected>' . htmlspecialchars($cur) . '</option>';
                          }
                        ?>
                        <?php foreach ($opts as $val => $labelOpt): ?>
                            <option value="<?= htmlspecialchars($val) ?>" <?= ($cur === $val) ? 'selected' : '' ?>><?= htmlspecialchars($labelOpt) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif ($isFile): ?>
                    <?php if (!empty($value)): ?>
                        <div class="mb-2">
                            <img src="<?= BASE_URL ?>public/uploads/barang/<?= htmlspecialchars($value) ?>" alt="<?= htmlspecialchars($label) ?>" class="h-24 rounded-lg border border-black/10 object-cover">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="<?= $name ?>" class="w-full rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-[#F7B267]" />
                <?php elseif ($enumOptions): ?>
                    <select name="<?= $name ?>" class="w-full rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-[#F7B267]">
                        <option value="">-- Pilih <?= $label ?> --</option>
                        <?php foreach ($enumOptions as $opt): ?>
                            <option value="<?= htmlspecialchars($opt) ?>" <?= $opt == $value ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif ($type === 'textarea'): ?>
                    <textarea name="<?= $name ?>" rows="4" class="w-full rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-[#F7B267]"><?= htmlspecialchars($value) ?></textarea>
                <?php else: ?>
                    <input type="<?= $type ?>" name="<?= $name ?>" value="<?= htmlspecialchars($value) ?>" class="w-full rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-[#F7B267]" />
                <?php endif; ?>
            </div>

            <?php endforeach; ?>

            <div>
                <label class="block text-sm font-semibold mb-1">Status Barang (Otomatis)</label>
                <input type="text" value="<?= htmlspecialchars($statusBarangLabel ?? '-') ?>" class="w-full rounded-xl border border-black/10 bg-[#F6F2EC] px-3 py-2 text-sm text-[#6B4B35]" readonly />
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <a href="index.php?page=admin" class="rounded-xl border border-black/10 px-4 py-2 text-sm">Kembali</a>
                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#F28B2D] px-5 py-2 text-sm font-bold text-white shadow-sm hover:brightness-95">Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout/main.php';
?>
