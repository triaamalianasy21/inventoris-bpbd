<?php
// Reusable peminjaman form partial
// Expects $barangList (array), $action (string) optional, $isModal (bool) optional, $old (array) optional
$action = $action ?? '';
$isModal = $isModal ?? false;
$old = $old ?? [];
$itemsMap = $itemsMap ?? [];

if (session_status() === PHP_SESSION_NONE) session_start();
$defaultNama = '';
$defaultNamaPihak2 = $prefillNamaPihak1 ?? '';
$hasOldItems = is_array($old) && isset($old['items']) && is_array($old['items']);
if (empty($itemsMap) && $hasOldItems) {
    foreach ($old['items'] as $bid => $it) {
        if (!is_array($it)) continue;
        $selected = isset($it['selected']) ? (string)$it['selected'] === '1' : false;
        $qty = max(1, (int)($it['qty'] ?? 1));
        if ($selected) {
            $itemsMap[(int)$bid] = $qty;
        }
    }
}
$val = function($key, $default = '') use ($old) {
    return htmlspecialchars($old[$key] ?? $default);
};
?>
<form id="peminjamanForm" method="POST" action="<?= $action ?: '' ?>" class="space-y-6">
    <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-extrabold text-[#4A2A12]">Data Pemohon</h3>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-semibold mb-1">Nama Pihak 1</label>
                <input type="text" name="nama_pihak1" value="<?= $val('nama_pihak1', $defaultNama) ?>" class="w-full rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-[#F7B267]">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">NIP Pihak 1</label>
                <input type="text" name="nip" value="<?= $val('nip') ?>" class="w-full rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-[#F7B267]">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Jabatan Pihak 1</label>
                <input type="text" name="jabatan" value="<?= $val('jabatan') ?>" class="w-full rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-[#F7B267]">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Nama Pemohon</label>
                <div class="text-xs text-[#6B4B35] mb-1">Nama pemohon (otomatis dari pemohon, bisa diedit).</div>
                <input type="text" name="nama_pihak2" value="<?= $val('nama_pihak2', $defaultNamaPihak2) ?>" required class="w-full rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-[#F7B267]">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">NIP Pihak 2</label>
                <input type="text" name="nip1" value="<?= $val('nip1') ?>" class="w-full rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-[#F7B267]">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Jabatan Pihak 2</label>
                <input type="text" name="jabatan1" value="<?= $val('jabatan1') ?>" required class="w-full rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-[#F7B267]">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold mb-1">Alamat</label>
                <textarea name="alamat" rows="2" class="w-full rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-[#F7B267]"><?= $val('alamat') ?></textarea>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">No HP</label>
                <input type="text" name="no_hp" value="<?= $val('no_hp') ?>" class="w-full rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-[#F7B267]">
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-extrabold text-[#4A2A12]">Detail Peminjaman</h3>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-semibold mb-1">Tujuan</label>
                <input type="text" name="tujuan" value="<?= $val('tujuan') ?>" class="w-full rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-[#F7B267]">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Tanggal Pinjam</label>
                <input type="date" name="tgl_pinjam" value="<?= $val('tgl_pinjam') ?>" class="w-full rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-[#F7B267]">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Tanggal Jatuh Tempo</label>
                <input type="date" name="tgl_jatuh_tempo" value="<?= $val('tgl_jatuh_tempo') ?>" class="w-full rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-[#F7B267]">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold mb-1">Spesifikasi</label>
                <textarea name="spesifikasi" rows="2" class="w-full rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-[#F7B267]"><?= $val('spesifikasi') ?></textarea>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold mb-1">Keterangan</label>
                <textarea name="keterangan" rows="3" class="w-full rounded-xl border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-[#F7B267]"><?= $val('keterangan') ?></textarea>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-black/10 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-extrabold text-[#4A2A12]">Pilih Barang</h3>
            <span class="text-xs font-semibold text-[#B45309]">Centang lalu isi jumlah</span>
        </div>
        <div class="mt-4 overflow-x-auto rounded-xl border border-black/10">
            <table class="min-w-full text-sm">
                <thead class="bg-[#FCE4C4] text-[#4A2A12]">
                    <tr>
                        <th class="p-2 text-left">Pilih</th>
                        <th class="p-2 text-left">Nama</th>
                        <th class="p-2 text-left">Stok</th>
                        <th class="p-2 text-left">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($barangList)): ?>
                        <tr><td colspan="4" class="p-3 text-center text-gray-600">Belum ada barang.</td></tr>
                    <?php else: ?>
                        <?php foreach ($barangList as $b): ?>
                            <?php
                              $bid = (int)$b['id_barang'];
                              $checked = isset($itemsMap[$bid]);
                              $qtyVal = $checked ? (int)$itemsMap[$bid] : 1;
                            ?>
                            <tr class="border-t border-black/5">
                                <td class="p-2">
                                  <input type="checkbox" name="items[<?= $bid ?>][selected]" value="1" data-id="<?= $bid ?>" <?= $checked ? 'checked' : '' ?>>
                                </td>
                                <td class="p-2"><?= htmlspecialchars($b['nama_barang']) ?></td>
                                <?php $stokVal = isset($b['stok']) ? (int)$b['stok'] : 0; ?>
                                <td class="p-2"><?= htmlspecialchars($b['stok'] ?? '-') ?></td>
                                <td class="p-2">
                                  <input type="number" min="1" name="items[<?= $bid ?>][qty]" value="<?= $qtyVal ?>" data-id="<?= $bid ?>" data-stok="<?= $stokVal ?>" class="w-24 rounded-lg border border-black/10 px-2 py-1 text-sm">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
        <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-[#F28B2D] px-5 py-3 text-sm font-bold text-white shadow-sm hover:brightness-95 sm:w-auto">Kirim Permintaan</button>
        <?php if ($isModal): ?>
            <button type="button" class="inline-flex w-full items-center justify-center rounded-xl border border-black/10 px-4 py-2 text-sm sm:w-auto" id="peminjamanCancel">Batal</button>
        <?php else: ?>
            <a href="index.php?page=peminjaman" class="inline-flex w-full items-center justify-center rounded-xl border border-black/10 px-4 py-2 text-sm sm:w-auto">Batal</a>
        <?php endif; ?>
    </div>
</form>

<div id="peminjamanPopup" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        <div class="text-lg font-extrabold text-[#4A2A12]">Peringatan</div>
        <div id="peminjamanPopupMsg" class="mt-2 text-sm text-[#5D4037]"></div>
        <div class="mt-5 flex justify-end">
            <button type="button" id="peminjamanPopupOk" class="rounded-xl bg-[#F28B2D] px-4 py-2 text-sm font-bold text-white hover:brightness-95">OK</button>
        </div>
    </div>
</div>

<script>
// Minimal client-side hook added only when this partial is embedded
(function(){
    var form = document.getElementById('peminjamanForm');
    if (!form) return;

    var popup = document.getElementById('peminjamanPopup');
    var popupMsg = document.getElementById('peminjamanPopupMsg');
    var popupOk = document.getElementById('peminjamanPopupOk');

    var showPopup = function(msg){
        if (!popup || !popupMsg || !popupOk) {
            alert(msg);
            return;
        }
        popupMsg.textContent = msg;
        popup.classList.remove('hidden');
        popup.classList.add('flex');
    };
    if (popupOk) {
        popupOk.addEventListener('click', function(){
            popup.classList.add('hidden');
            popup.classList.remove('flex');
        });
    }

    // If page URL has json=1 or the form is inside a modal, submit via AJAX
    var isJson = (location.search.indexOf('json=1') !== -1) || (typeof window.isPeminjamanModal !== 'undefined' && window.isPeminjamanModal === true);

    var buildItems = function(){
        var items = [];
        var stokOver = null;
        var checkboxes = form.querySelectorAll('input[type="checkbox"][name^="items["]');
        checkboxes.forEach(function(chk){
            if (!chk.checked) return;
            var id = chk.getAttribute('data-id') || '';
            if (!id) {
                var name = chk.getAttribute('name') || '';
                var idMatch = name.match(/items\\[(\\d+)\\]/);
                if (!idMatch) return;
                id = idMatch[1];
            }
            var qtyInput = form.querySelector('input[name="items[' + id + '][qty]"]');
            var qty = qtyInput ? parseInt(qtyInput.value || '1', 10) : 1;
            if (isNaN(qty) || qty < 1) qty = 1;
            var stokAttr = qtyInput ? qtyInput.getAttribute('data-stok') : null;
            var stok = stokAttr === null ? NaN : parseInt(stokAttr || '0', 10);
            if (!isNaN(stok) && qty > stok) {
                stokOver = { id: id, qty: qty, stok: stok };
            }
            items.push({barang_id: parseInt(id,10), qty: qty});
        });
        return { items: items, stokOver: stokOver };
    };

    form.addEventListener('submit', function(e){
        try {
            sessionStorage.setItem('peminjamanFormScroll', String(window.scrollY || 0));
        } catch (err) {}
        var result = buildItems();
        if (result.stokOver) {
            e.preventDefault();
            showPopup('Pengajuan melebihi stok. Stok tersisa: ' + result.stokOver.stok + ', jumlah diminta: ' + result.stokOver.qty + '.');
            return;
        }
        if (result.items.length === 0) {
            e.preventDefault();
            showPopup('Pilih setidaknya satu barang.');
            return;
        }
        if (!isJson) return;

        e.preventDefault();
        var getVal = function(name){
            var el = form.querySelector('[name=\"' + name + '\"]');
            return el ? el.value : '';
        };

        var payload = {
            nama_pihak1: getVal('nama_pihak1'),
            nip: getVal('nip'),
            jabatan: getVal('jabatan'),
            nama_pihak2: getVal('nama_pihak2'),
            nip1: getVal('nip1'),
            jabatan1: getVal('jabatan1'),
            alamat: getVal('alamat'),
            no_hp: getVal('no_hp'),
            tujuan: getVal('tujuan'),
            tgl_pinjam: getVal('tgl_pinjam'),
            tgl_jatuh_tempo: getVal('tgl_jatuh_tempo'),
            spesifikasi: getVal('spesifikasi'),
            keterangan: getVal('keterangan'),
            items: result.items
        };

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'index.php?page=peminjaman_tambah&json=1', true);
        xhr.setRequestHeader('Content-Type','application/json');
        xhr.onreadystatechange = function(){
            if (xhr.readyState === 4) {
                if (xhr.status >= 200 && xhr.status < 300) {
                    var res = JSON.parse(xhr.responseText);
                    if (res.ok) {
                        alert('Permintaan berhasil dibuat.');
                        location.href = 'index.php?page=peminjaman';
                    } else {
                        alert('Error: ' + (res.error || 'Gagal membuat peminjaman'));
                    }
                } else {
                    alert('Terjadi error jaringan / server. Silakan coba lagi.');
                }
            }
        };
        xhr.send(JSON.stringify(payload));
    });

    // cancel button for modal
    var btnCancel = document.getElementById('peminjamanCancel');
    if (btnCancel) btnCancel.addEventListener('click', function(){
        var evt = new CustomEvent('closePeminjamanModal');
        window.dispatchEvent(evt);
    });

    try {
        var saved = sessionStorage.getItem('peminjamanFormScroll');
        if (saved !== null) {
            window.scrollTo(0, parseInt(saved, 10) || 0);
            sessionStorage.removeItem('peminjamanFormScroll');
        }
    } catch (err) {}
})();
</script>
