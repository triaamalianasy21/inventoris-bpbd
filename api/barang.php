<?php
require_once __DIR__ . '/_common.php';
require_once __DIR__ . '/../app/models/Koneksi.php';

$conn = Koneksi::connect();
$input = api_input();
$action = $_GET['action'] ?? $_POST['action'] ?? ($input['action'] ?? null);
if (!$action) api_response(false, null, 'Parameter action tidak ditemukan.');

function api_base_url()
{
    if (defined('BASE_URL')) return BASE_URL;
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = str_replace('\\', '/', ($_SERVER['SCRIPT_NAME'] ?? ''));
    $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');
    $basePath = preg_replace('~/api$~', '', $dir);
    if ($basePath === '') $basePath = '/';
    $suffix = substr($basePath, -1) === '/' ? '' : '/';
    return $scheme . '://' . $host . $basePath . $suffix;
}

function barang_columns($conn)
{
    $cols = [];
    $res = $conn->query("SHOW COLUMNS FROM `barang`");
    if ($res) while ($c = $res->fetch_assoc()) $cols[] = $c['Field'];
    return $cols;
}

function pick_col($cols, $candidates)
{
    foreach ($candidates as $c) {
        if (in_array($c, $cols, true)) return $c;
    }
    foreach ($cols as $c) {
        if (preg_match('/foto|gambar|image|photo/i', $c)) return $c;
    }
    return null;
}

function detect_stok_col($cols)
{
    $candidates = ['stok','stok_barang','stock','jumlah','qty','jumlah_barang'];
    foreach ($candidates as $c) {
        if (in_array($c, $cols, true)) return $c;
    }
    return null;
}

function save_base64_photo($data, $prefix)
{
    if (!is_string($data) || $data === '') return [false, 'Data foto tidak valid.'];
    if (!preg_match('/^data:image\/(png|jpe?g);base64,/', $data, $m)) {
        return [false, 'Format foto tidak valid.'];
    }
    $ext = strtolower($m[1] === 'jpeg' ? 'jpg' : $m[1]);
    $bin = base64_decode(substr($data, strpos($data, ',') + 1), true);
    if ($bin === false) return [false, 'Gagal decode foto.'];

    $folder = dirname(__DIR__) . '/public/uploads/barang/';
    if (!is_dir($folder)) mkdir($folder, 0777, true);
    $fileName = $prefix . '_' . time() . '.' . $ext;
    $path = $folder . $fileName;
    if (file_put_contents($path, $bin) === false) return [false, 'Gagal menyimpan foto.'];
    return [true, $fileName];
}

function input_value_for_column($col, $input, $aliases = [])
{
    if (array_key_exists($col, $input)) return $input[$col];
    if (isset($aliases[$col])) {
        foreach ($aliases[$col] as $alias) {
            if (array_key_exists($alias, $input)) return $input[$alias];
        }
    }
    return null;
}

function uploaded_file_for_column($col, $aliases = [])
{
    if (!empty($_FILES[$col]['name'])) return $col;
    if (isset($aliases[$col])) {
        foreach ($aliases[$col] as $alias) {
            if (!empty($_FILES[$alias]['name'])) return $alias;
        }
    }
    return null;
}

function barang_pk($conn)
{
    $res = $conn->query("SHOW COLUMNS FROM `barang`");
    if (!$res) return 'id_barang';
    $cols = [];
    while ($col = $res->fetch_assoc()) {
        $cols[] = $col['Field'];
        if (!empty($col['Key']) && strtoupper($col['Key']) === 'PRI') return $col['Field'];
    }
    return $cols[0] ?? 'id_barang';
}

function detect_lokasi_join($conn)
{
    $tables = [
        ['table' => 'lokasi_gudang', 'id' => 'id_lokasi', 'name' => 'nama_lokasi'],
        ['table' => 'lokasi', 'id' => 'id_lokasi', 'name' => 'nama_lokasi'],
        ['table' => 'lokasi', 'id' => 'id', 'name' => 'nama'],
        ['table' => 'lokasi', 'id' => 'id_lokasi', 'name' => 'lokasi'],
        ['table' => 'gudang', 'id' => 'id_gudang', 'name' => 'nama_gudang'],
    ];
    foreach ($tables as $t) {
        $tbl = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($t['table']) . "'");
        if (!$tbl || !$tbl->num_rows) continue;
        $hasId = $conn->query("SHOW COLUMNS FROM `{$t['table']}` LIKE '" . $conn->real_escape_string($t['id']) . "'");
        $hasName = $conn->query("SHOW COLUMNS FROM `{$t['table']}` LIKE '" . $conn->real_escape_string($t['name']) . "'");
        if ($hasId && $hasId->num_rows && $hasName && $hasName->num_rows) return $t;
    }
    return null;
}

function detect_items_mapping($conn)
{
    $candidates = ['peminjaman_items','peminjaman_item','item_peminjaman','peminjaman_detail','peminjamanitems','detail_peminjaman','detail_pinjaman','detail_peminjam','detail_peminjam'];
    $itemsTable = null;
    foreach ($candidates as $t) {
        $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME = ?");
        $stmt->bind_param('s', $t);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        if (((int)($r['cnt'] ?? 0)) > 0) { $itemsTable = $t; break; }
    }
    if (!$itemsTable) return null;

    $colsRes = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='" . $conn->real_escape_string($itemsTable) . "'");
    $cols = [];
    if ($colsRes) while ($r = $colsRes->fetch_assoc()) $cols[] = $r['COLUMN_NAME'];

    $pCandidates = ['peminjaman_id','peminjamanid','id_peminjaman','id_pinjaman','id_peminjam','id_pinjam','id_pinjam'];
    $bCandidates = ['barang_id','id_barang','id_brg','id_brg'];
    $qCandidates = ['qty','jumlah','jumlah_barang','quantity','jumlah_barang'];
    $pCol = null; $bCol = null; $qCol = null;
    foreach ($pCandidates as $c) if (in_array($c, $cols, true)) { $pCol = $c; break; }
    foreach ($bCandidates as $c) if (in_array($c, $cols, true)) { $bCol = $c; break; }
    foreach ($qCandidates as $c) if (in_array($c, $cols, true)) { $qCol = $c; break; }
    if (!$pCol || !$bCol || !$qCol) return null;
    return ['items_table' => $itemsTable, 'peminjaman_col' => $pCol, 'barang_col' => $bCol, 'qty_col' => $qCol];
}

function status_barang_label($conn, $barangId, $itemsMap)
{
    if (!$itemsMap || empty($itemsMap['items_table']) || empty($itemsMap['peminjaman_col']) || empty($itemsMap['barang_col'])) {
        return null;
    }
    $it = $conn->real_escape_string($itemsMap['items_table']);
    $pCol = $conn->real_escape_string($itemsMap['peminjaman_col']);
    $bCol = $conn->real_escape_string($itemsMap['barang_col']);
    $sql = "SELECT MAX(CASE
                        WHEN p.status IN ('waiting_verification','pending') THEN 3
                        WHEN p.status = 'approved' THEN 2
                        ELSE 0
                      END) AS st
            FROM `{$it}` pi
            JOIN peminjaman p ON p.`{$pCol}` = pi.`{$pCol}`
            WHERE pi.`{$bCol}` = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $barangId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $st = (int)($row['st'] ?? 0);
    if ($st === 3) return 'Menunggu Verifikasi';
    if ($st === 2) return 'Menunggu Pengembalian';
    return 'Tersedia';
}

function barang_reserved_map($conn, $itemsMap, $ids)
{
    if (!$itemsMap || empty($ids)) return [];
    $itemsTable = $itemsMap['items_table'] ?? null;
    $pCol = $itemsMap['peminjaman_col'] ?? null;
    $bCol = $itemsMap['barang_col'] ?? null;
    $qCol = $itemsMap['qty_col'] ?? null;
    if (!$itemsTable || !$pCol || !$bCol || !$qCol) return [];

    $it = $conn->real_escape_string($itemsTable);
    $pColEsc = $conn->real_escape_string($pCol);
    $bColEsc = $conn->real_escape_string($bCol);
    $qColEsc = $conn->real_escape_string($qCol);
    $idList = implode(',', array_map('intval', $ids));

    $sql = "SELECT pi.`{$bColEsc}` AS id_barang,
                   SUM(CASE WHEN p.`{$pColEsc}` IS NOT NULL THEN pi.`{$qColEsc}` ELSE 0 END) AS reserved
            FROM `{$it}` pi
            LEFT JOIN peminjaman p ON p.`{$pColEsc}` = pi.`{$pColEsc}` AND p.status IN ('waiting_verification','pending','approved')
            WHERE pi.`{$bColEsc}` IN ({$idList})
            GROUP BY pi.`{$bColEsc}`";
    $res = $conn->query($sql);
    $map = [];
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $map[(int)$r['id_barang']] = (int)($r['reserved'] ?? 0);
        }
    }
    return $map;
}

function table_exists($conn, $table)
{
    $t = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '{$t}'");
    return $res && $res->num_rows > 0;
}

function create_delete_request($conn, $module, $targetId)
{
    $userId = currentUserId();
    if (!$userId) return [false, 'Sesi tidak valid.'];
    if (!table_exists($conn, 'delete_requests')) {
        return [false, 'Tabel delete_requests belum tersedia.'];
    }
    $stmt = $conn->prepare("SELECT id_request FROM delete_requests WHERE module = ? AND target_id = ? AND status = 'pending' LIMIT 1");
    $stmt->bind_param('si', $module, $targetId);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();
    if ($exists) return [false, 'Permintaan penghapusan sudah diajukan dan menunggu verifikasi.'];
    $stmt = $conn->prepare("INSERT INTO delete_requests (module, target_id, requested_by, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
    $stmt->bind_param('sii', $module, $targetId, $userId);
    if (!$stmt->execute()) return [false, 'Gagal mengajukan penghapusan.'];
    return [true, 'Permintaan penghapusan dikirim. Menunggu verifikasi Kalaksa.'];
}

function status_brg_allows_change($row, $cols)
{
    if (!in_array('status_brg', $cols, true)) return true;
    $status = strtolower((string)($row['status_brg'] ?? ''));
    $allowed = ['menunggu_verifikasi','pending','menunggu',''];
    return in_array($status, $allowed, true);
}

function barang_permissions($row, $cols, $statusBarang)
{
    $statusBrg = strtolower((string)($row['status_brg'] ?? ''));
    $statusBarang = $statusBarang ?? '';
    $statusBarangOk = ($statusBarang === '' || $statusBarang === 'Tersedia');
    $canEdit = $statusBarangOk;
    $canDelete = in_array($statusBrg, ['menunggu_verifikasi','pending','menunggu','ditolak','rejected',''], true) && $statusBarangOk;
    return [$canEdit, $canDelete];
}

if ($action === 'list') {
    require_login_api();
    $q = trim($_GET['q'] ?? ($input['q'] ?? ''));
    $availableOnly = (int)($_GET['available'] ?? ($input['available'] ?? 0)) === 1;
    $cols = barang_columns($conn);
    $pk = barang_pk($conn);
    $lokasiJoin = null;
    if (in_array('id_lokasi', $cols, true)) $lokasiJoin = detect_lokasi_join($conn);
    $baseSql = "FROM barang b";
    $select = "SELECT b.*";
    if ($lokasiJoin) {
        $t = $lokasiJoin['table'];
        $id = $lokasiJoin['id'];
        $name = $lokasiJoin['name'];
        $select .= ", l.`{$name}` AS lokasi";
        $baseSql .= " LEFT JOIN `{$t}` l ON l.`{$id}` = b.`id_lokasi`";
    }
    $sql = $select . " " . $baseSql;
    if ($q !== '') {
        $sql .= " WHERE b.nama_barang LIKE ? OR b.kode_barang LIKE ? OR b.nomor_register LIKE ?";
        $stmt = $conn->prepare($sql . " ORDER BY b.`{$pk}` DESC");
        $like = '%' . $q . '%';
        $stmt->bind_param('sss', $like, $like, $like);
        $stmt->execute();
        $res = $stmt->get_result();
    } else {
        $res = $conn->query($sql . " ORDER BY b.`{$pk}` DESC");
    }
    $rows = [];
    $stokCol = detect_stok_col($cols);
    $fotoCol = pick_col($cols, ['foto','foto_barang','gambar','image','photo']);
    $itemsMap = detect_items_mapping($conn);
    if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;

    $reservedMap = [];
    if ($availableOnly && $stokCol && !empty($rows)) {
        $ids = [];
        foreach ($rows as $r) {
            $ids[] = (int)($r[$pk] ?? 0);
        }
        $reservedMap = barang_reserved_map($conn, $itemsMap, $ids);
    }

    $filtered = [];
    foreach ($rows as $r) {
        $statusVer = in_array('status_brg', $cols, true) ? ($r['status_brg'] ?? null) : null;
        $r['status_verifikasi'] = $statusVer;
        $idVal = $r[$pk] ?? null;
        $r['status_barang'] = $idVal ? status_barang_label($conn, (int)$idVal, $itemsMap) : null;
        [$canEdit, $canDelete] = barang_permissions($r, $cols, $r['status_barang']);
        $r['can_edit'] = $canEdit;
        $r['can_delete'] = $canDelete;
        if ($stokCol) {
            $stokVal = (int)($r[$stokCol] ?? 0);
            if ($availableOnly) {
                $reserved = $reservedMap[(int)$idVal] ?? 0;
                $available = max(0, $stokVal - $reserved);
                $r[$stokCol] = $available;
                $r['stok_tersisa'] = $available;
                if ($available <= 0) {
                    continue;
                }
            }
        }
        $fotoVal = $fotoCol ? ($r[$fotoCol] ?? '') : '';
        if ($fotoVal && !preg_match('~^https?://~i', $fotoVal)) {
            $r['foto_url'] = api_base_url() . 'public/uploads/barang/' . $fotoVal;
        } else {
            $r['foto_url'] = $fotoVal ?: null;
        }
        if (!array_key_exists('lokasi', $r)) $r['lokasi'] = null;
        $filtered[] = $r;
    }
    api_response(true, $filtered, null);
}

if ($action === 'detail') {
    require_login_api();
    $id = (int)($_GET['id'] ?? ($input['id'] ?? 0));
    if ($id <= 0) api_response(false, null, 'ID tidak ditemukan.');
    $pk = barang_pk($conn);
    $cols = barang_columns($conn);
    $lokasiJoin = null;
    if (in_array('id_lokasi', $cols, true)) $lokasiJoin = detect_lokasi_join($conn);
    $select = "SELECT b.*";
    $from = " FROM barang b";
    if ($lokasiJoin) {
        $t = $lokasiJoin['table'];
        $idCol = $lokasiJoin['id'];
        $nameCol = $lokasiJoin['name'];
        $select .= ", l.`{$nameCol}` AS lokasi";
        $from .= " LEFT JOIN `{$t}` l ON l.`{$idCol}` = b.`id_lokasi`";
    }
    $stmt = $conn->prepare($select . $from . " WHERE b.`{$pk}` = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) api_response(false, null, 'Data tidak ditemukan.');
    $fotoCol = pick_col($cols, ['foto','foto_barang','gambar','image','photo']);
    $statusVer = in_array('status_brg', $cols, true) ? ($row['status_brg'] ?? null) : null;
    $row['status_verifikasi'] = $statusVer;
    $itemsMap = detect_items_mapping($conn);
    $row['status_barang'] = status_barang_label($conn, (int)$id, $itemsMap);
    [$canEdit, $canDelete] = barang_permissions($row, $cols, $row['status_barang']);
    $row['can_edit'] = $canEdit;
    $row['can_delete'] = $canDelete;
    $fotoVal = $fotoCol ? ($row[$fotoCol] ?? '') : '';
    if ($fotoVal && !preg_match('~^https?://~i', $fotoVal)) {
        $row['foto_url'] = api_base_url() . 'public/uploads/barang/' . $fotoVal;
    } else {
        $row['foto_url'] = $fotoVal ?: null;
    }
    if (!array_key_exists('lokasi', $row)) $row['lokasi'] = null;
    api_response(true, $row, null);
}

if ($action === 'create') {
    require_login_api();
    require_role_api(['admin']);

    $cols = barang_columns($conn);
    $pk = barang_pk($conn);
    $hasStatusBrg = in_array('status_brg', $cols, true);
    $aliases = [
        'id_lokasi' => ['lokasi_id'],
        'stok_barang' => ['stok','stock','jumlah','qty','jumlah_barang'],
        'status_brg' => ['status_verifikasi','status'],
    ];
    $fields = [];
    $values = [];
    $types = '';

    foreach ($cols as $c) {
        if ($c === $pk) continue;
        $isPhoto = preg_match('/foto|gambar|image|photo/i', $c);
        if ($isPhoto) {
            $fileKey = uploaded_file_for_column($c, ['foto' => ['foto','foto_barang','gambar','image','photo'], $c => ['foto','foto_barang','gambar','image','photo']]);
            if ($fileKey) {
                $folder = dirname(__DIR__) . '/public/uploads/barang/';
                if (!is_dir($folder)) mkdir($folder, 0777, true);
                $ext = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION));
                $fileName = 'barang_' . $c . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $folder . $fileName)) {
                    $fields[] = $c;
                    $values[] = $fileName;
                    $types .= 's';
                }
                continue;
            }

            $fotoVal = input_value_for_column($c, $input, ['foto' => ['foto','foto_barang','gambar','image','photo'], $c => ['foto','foto_barang','gambar','image','photo']]);
            if (is_string($fotoVal) && strpos($fotoVal, 'data:image/') === 0) {
                [$okUpload, $pathOrErr] = save_base64_photo($fotoVal, 'barang_' . $c);
                if ($okUpload) {
                    $fields[] = $c;
                    $values[] = $pathOrErr;
                    $types .= 's';
                }
                continue;
            }
        }

        $val = input_value_for_column($c, $input, $aliases);
        if ($val === null) continue;
        $fields[] = $c;
        $values[] = $val;
        $types .= 's';
    }

    if ($hasStatusBrg && !hasRole('kalaksa')) {
        $idx = array_search('status_brg', $fields, true);
        if ($idx === false) {
            $fields[] = 'status_brg';
            $values[] = 'menunggu_verifikasi';
            $types .= 's';
        } else {
            $values[$idx] = 'menunggu_verifikasi';
        }
    }

    if (empty($fields)) api_response(false, null, 'Field barang belum lengkap.');
    $sql = "INSERT INTO barang (`" . implode('`,`', $fields) . "`) VALUES (" . rtrim(str_repeat('?,', count($fields)), ',') . ")";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);
    if (!$stmt->execute()) api_response(false, null, 'Gagal menyimpan barang.');
    api_response(true, ['id' => (int)$conn->insert_id], null);
}

if ($action === 'update') {
    require_login_api();
    require_role_api(['admin']);

    $id = (int)($input['id'] ?? ($_GET['id'] ?? 0));
    if ($id <= 0) api_response(false, null, 'ID tidak ditemukan.');
    $cols = barang_columns($conn);
    $pk = barang_pk($conn);
    $stmt = $conn->prepare("SELECT * FROM barang WHERE `{$pk}` = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) api_response(false, null, 'Data tidak ditemukan.');
    $itemsMap = detect_items_mapping($conn);
    $statusBarang = status_barang_label($conn, (int)$id, $itemsMap);
    if ($statusBarang && $statusBarang !== 'Tersedia') {
        api_response(false, null, 'Barang sedang dipinjam atau menunggu pengembalian, tidak bisa diubah.');
    }
    $hasStatusBrg = in_array('status_brg', $cols, true);
    $aliases = [
        'id_lokasi' => ['lokasi_id'],
        'stok_barang' => ['stok','stock','jumlah','qty','jumlah_barang'],
        'status_brg' => ['status_verifikasi','status'],
    ];
    $sets = [];
    $values = [];
    $types = '';

    foreach ($cols as $c) {
        if ($c === $pk) continue;
        $isPhoto = preg_match('/foto|gambar|image|photo/i', $c);
        if ($isPhoto) {
            $fileKey = uploaded_file_for_column($c, ['foto' => ['foto','foto_barang','gambar','image','photo'], $c => ['foto','foto_barang','gambar','image','photo']]);
            if ($fileKey) {
                $folder = dirname(__DIR__) . '/public/uploads/barang/';
                if (!is_dir($folder)) mkdir($folder, 0777, true);
                $ext = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION));
                $fileName = 'barang_' . $c . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $folder . $fileName)) {
                    $sets[] = "`{$c}` = ?";
                    $values[] = $fileName;
                    $types .= 's';
                }
                continue;
            }

            $fotoVal = input_value_for_column($c, $input, ['foto' => ['foto','foto_barang','gambar','image','photo'], $c => ['foto','foto_barang','gambar','image','photo']]);
            if (is_string($fotoVal) && strpos($fotoVal, 'data:image/') === 0) {
                [$okUpload, $pathOrErr] = save_base64_photo($fotoVal, 'barang_' . $c);
                if ($okUpload) {
                    $sets[] = "`{$c}` = ?";
                    $values[] = $pathOrErr;
                    $types .= 's';
                }
                continue;
            }
        }

        $val = input_value_for_column($c, $input, $aliases);
        if ($val === null) continue;
        $sets[] = "`{$c}` = ?";
        $values[] = $val;
        $types .= 's';
    }

    if ($hasStatusBrg && !hasRole('kalaksa') && !empty($sets)) {
        $filteredSets = [];
        $filteredValues = [];
        $filteredTypes = '';
        foreach ($sets as $i => $set) {
            if (stripos($set, '`status_brg`') !== false) continue;
            $filteredSets[] = $set;
            $filteredValues[] = $values[$i];
            $filteredTypes .= $types[$i] ?? 's';
        }
        $sets = $filteredSets;
        $values = $filteredValues;
        $types = $filteredTypes;

        $sets[] = "`status_brg` = ?";
        $values[] = 'menunggu_verifikasi';
        $types .= 's';
    }
    if (empty($sets)) api_response(false, null, 'Tidak ada data yang diubah.');
    $values[] = $id;
    $types .= 'i';

    $sql = "UPDATE barang SET " . implode(',', $sets) . " WHERE `{$pk}` = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);
    if (!$stmt->execute()) api_response(false, null, 'Gagal memperbarui barang.');
    api_response(true, ['id' => $id], null);
}

if ($action === 'delete') {
    require_login_api();
    require_role_api(['admin']);

    $id = (int)($input['id'] ?? ($_GET['id'] ?? 0));
    if ($id <= 0) api_response(false, null, 'ID tidak ditemukan.');
    $pk = barang_pk($conn);
    $stmt = $conn->prepare("SELECT * FROM barang WHERE `{$pk}` = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) api_response(false, null, 'Data tidak ditemukan.');

    $itemsMap = detect_items_mapping($conn);
    $statusBarang = status_barang_label($conn, (int)$id, $itemsMap);
    if ($statusBarang && $statusBarang !== 'Tersedia') {
        api_response(false, null, 'Barang sedang dipinjam atau menunggu pengembalian, tidak bisa dihapus.');
    }

    $statusBrg = strtolower((string)($row['status_brg'] ?? ''));
    $directDeleteStatuses = ['menunggu_verifikasi','pending','menunggu','ditolak','rejected',''];
    if (in_array($statusBrg, $directDeleteStatuses, true)) {
        $stmt = $conn->prepare("DELETE FROM barang WHERE `{$pk}` = ?");
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) api_response(false, null, 'Gagal menghapus barang.');
        api_response(true, ['id' => $id], null);
    }

    api_response(false, null, 'Barang sudah disetujui, tidak bisa dihapus.');
}

api_response(false, null, 'Action tidak valid.');
