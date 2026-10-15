<?php
require_once __DIR__ . '/_common.php';
require_once __DIR__ . '/../app/models/Koneksi.php';
require_once __DIR__ . '/../app/models/PeminjamanModel.php';

$conn = Koneksi::connect();
$model = new PeminjamanModel();

$input = api_input();
$action = $_GET['action'] ?? $_POST['action'] ?? ($input['action'] ?? null);

if (!$action) {
    api_response(false, null, 'Parameter action tidak ditemukan.');
}

function normalize_items($items)
{
    $out = [];
    if (!is_array($items)) return $out;

    // If list of items
    $isList = array_keys($items) === range(0, count($items) - 1);
    if ($isList) {
        foreach ($items as $it) {
            if (!is_array($it)) continue;
            $bid = (int)($it['barang_id'] ?? ($it['id_barang'] ?? 0));
            $qty = (int)($it['qty'] ?? ($it['jumlah'] ?? 1));
            if ($bid > 0) $out[] = ['barang_id' => $bid, 'qty' => max(1, $qty)];
        }
        return $out;
    }

    // If associative by barang_id
    foreach ($items as $bid => $vals) {
        if (!is_array($vals)) continue;
        if (($vals['selected'] ?? '') !== '1' && !isset($vals['qty'])) continue;
        $qty = (int)($vals['qty'] ?? 1);
        $id = (int)$bid;
        if ($id > 0) $out[] = ['barang_id' => $id, 'qty' => max(1, $qty)];
    }
    return $out;
}

function detect_barang_stok_col($conn)
{
    $candidates = ['stok','stok_barang','stock','jumlah','qty','jumlah_barang'];
    $placeholders = "'" . implode("','", array_map([$conn, 'real_escape_string'], $candidates)) . "'";
    $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='barang' AND COLUMN_NAME IN ($placeholders) LIMIT 1";
    $res = $conn->query($sql);
    if ($res && ($row = $res->fetch_assoc())) return $row['COLUMN_NAME'];
    return null;
}

function validate_items_stock($conn, $model, $items)
{
    $stokCol = detect_barang_stok_col($conn);
    if (!$stokCol) return [true, null];

    $ids = [];
    foreach ($items as $it) {
        $bid = (int)($it['barang_id'] ?? 0);
        if ($bid > 0) $ids[$bid] = true;
    }
    if (empty($ids)) return [true, null];

    $idList = implode(',', array_keys($ids));
    $sql = "SELECT id_barang, nama_barang, `{$stokCol}` AS stok FROM barang WHERE id_barang IN ($idList)";
    $res = $conn->query($sql);
    $stokMap = [];
    $namaMap = [];
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $stokMap[(int)$r['id_barang']] = (int)($r['stok'] ?? 0);
            $namaMap[(int)$r['id_barang']] = $r['nama_barang'] ?? '';
        }
    }

    $reservedMap = [];
    $map = $model->getItemsMapping();
    $itemsTable = $map['items_table'] ?? null;
    $pCol = $map['peminjaman_col'] ?? null;
    $bCol = $map['barang_col'] ?? null;
    $qCol = $map['qty_col'] ?? null;
    if ($itemsTable && $pCol && $bCol && $qCol) {
        $it = $conn->real_escape_string($itemsTable);
        $pColEsc = $conn->real_escape_string($pCol);
        $bColEsc = $conn->real_escape_string($bCol);
        $qColEsc = $conn->real_escape_string($qCol);
        $sql = "SELECT pi.`{$bColEsc}` AS id_barang,
                       SUM(CASE WHEN p.`{$pColEsc}` IS NOT NULL THEN pi.`{$qColEsc}` ELSE 0 END) AS reserved
                FROM `{$it}` pi
                LEFT JOIN peminjaman p ON p.`{$pColEsc}` = pi.`{$pColEsc}` AND p.status IN ('waiting_verification','pending','approved')
                WHERE pi.`{$bColEsc}` IN ($idList)
                GROUP BY pi.`{$bColEsc}`";
        $res = $conn->query($sql);
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $reservedMap[(int)$r['id_barang']] = (int)($r['reserved'] ?? 0);
            }
        }
    }

    foreach ($items as $it) {
        $bid = (int)($it['barang_id'] ?? 0);
        $qty = (int)($it['qty'] ?? 1);
        if ($bid <= 0) continue;
        $stok = $stokMap[$bid] ?? 0;
        $reserved = $reservedMap[$bid] ?? 0;
        $available = max(0, $stok - $reserved);
        if ($qty > $available) {
            $nama = $namaMap[$bid] ?? '';
            $label = $nama !== '' ? $nama : ('ID: ' . $bid);
            return [false, "Jumlah barang melebihi stok tersedia ({$label})."];
        }
    }
    return [true, null];
}

function peminjaman_status_allows_change($row)
{
    $status = strtolower((string)($row['status'] ?? ''));
    $allowed = ['waiting_verification','pending','menunggu_verifikasi','approved','rejected','ditolak',''];
    return in_array($status, $allowed, true);
}

function peminjaman_permissions($row)
{
    $status = strtolower((string)($row['status'] ?? ''));
    $canEdit = in_array($status, ['waiting_verification','pending','menunggu_verifikasi','approved','rejected','ditolak',''], true);
    $canDelete = in_array($status, ['waiting_verification','pending','menunggu_verifikasi','rejected','ditolak',''], true);
    return [$canEdit, $canDelete];
}

if ($action === 'list') {
    require_login_api();
    $role = currentUserRole();
    $uid = currentUserId();
    $includeItems = (int)($_GET['include_items'] ?? ($input['include_items'] ?? 0)) === 1;
    $q = trim($_GET['q'] ?? ($input['q'] ?? ''));

    if (in_array($role, ['admin', 'pengurus_barang', 'pengurus', 'kalaksa'], true)) {
        $rows = $model->getAll();
    } else {
        $rows = $uid ? $model->getByUser($uid) : [];
    }

    $pk = $model->getPkColumn();
    foreach ($rows as &$row) {
        $pid = $row['id'] ?? ($pk && isset($row[$pk]) ? $row[$pk] : null);
        $itemsList = $pid ? $model->getItems((int)$pid) : [];
        if ($includeItems) {
            $row['items'] = $itemsList;
        } else {
            $row['items_count'] = $pid ? $model->getItemsCount((int)$pid) : 0;
        }
        if (!empty($itemsList)) {
            $names = [];
            foreach ($itemsList as $it) {
                if (!empty($it['nama_barang'])) $names[] = $it['nama_barang'];
            }
            $row['items_summary'] = implode(', ', $names);
        } else {
            $row['items_summary'] = '';
        }
        [$canEdit, $canDelete] = peminjaman_permissions($row);
        $row['can_edit'] = $canEdit;
        $row['can_delete'] = $canDelete;
    }
    unset($row);

    if ($q !== '') {
        $qLower = mb_strtolower($q, 'UTF-8');
        $rows = array_values(array_filter($rows, function($row) use ($qLower) {
            $fields = [
                $row['nama_pihak1'] ?? '',
                $row['pemohon_nama'] ?? '',
            ];
            foreach ($fields as $f) {
                if ($f === '') continue;
                if (mb_stripos((string)$f, $qLower, 0, 'UTF-8') !== false) return true;
            }
            return false;
        }));
    }

    api_response(true, $rows, null);
}

if ($action === 'detail') {
    require_login_api();
    $id = (int)($_GET['id'] ?? ($input['id'] ?? 0));
    if ($id <= 0) api_response(false, null, 'ID tidak ditemukan.');

    $row = $model->find($id);
    if (!$row) api_response(false, null, 'Data tidak ditemukan.');

    if (currentUserRole() === 'pemohon') {
        $col = $model->getUserColumn();
        if ($col && isset($row[$col]) && (int)$row[$col] !== (int)currentUserId()) {
            api_response(false, null, 'Akses ditolak.');
        }
    }

    [$canEdit, $canDelete] = peminjaman_permissions($row);
    $row['can_edit'] = $canEdit;
    $row['can_delete'] = $canDelete;

    api_response(true, $row, null);
}

if ($action === 'has_active') {
    require_login_api();
    $uid = currentUserId();
    if (!$uid) api_response(false, null, 'User tidak ditemukan.');
    $hasActive = $model->userHasActiveLoan($uid);
    api_response(true, ['has_active' => $hasActive], null);
}

if ($action === 'create') {
    require_login_api();
    $role = currentUserRole();
    if (!in_array($role, ['pemohon', 'pengurus_barang', 'pengurus', 'admin'], true)) {
        api_response(false, null, 'Akses ditolak.');
    }

    if ($role === 'pemohon') {
        $uid = currentUserId();
        if ($uid && $model->userHasActiveLoan($uid)) {
            api_response(false, null, 'Anda masih memiliki peminjaman aktif.');
        }
    }

    $items = normalize_items($input['items'] ?? []);
    if (empty($items)) api_response(false, null, 'Items wajib diisi.');
    [$stokOk, $stokErr] = validate_items_stock($conn, $model, $items);
    if (!$stokOk) api_response(false, null, $stokErr);

    $uid = currentUserId();
    $data = [
        'user_id' => $uid,
        'requested_by' => $uid,
        'status' => 'waiting_verification',
        'items' => $items
    ];

    if (in_array($role, ['pengurus_barang', 'admin'], true)) {
        if (!empty($input['user_id'])) {
            $data['user_id'] = (int)$input['user_id'];
            $data['requested_by'] = (int)$input['user_id'];
        }
    }

    $fields = [
        'nama_pihak1','nip','jabatan','nama_pihak2','nip1','jabatan1',
        'alamat','no_hp','tujuan','tgl_pinjam','tgl_jatuh_tempo','spesifikasi','keterangan'
    ];
    foreach ($fields as $f) {
        if (isset($input[$f])) $data[$f] = trim((string)$input[$f]);
    }

    $id = $model->create($data);
    if (!$id) api_response(false, null, 'Gagal membuat peminjaman.');
    api_response(true, ['id' => (int)$id], null);
}

if ($action === 'update') {
    require_login_api();
    require_role_api(['pengurus_barang', 'pengurus', 'admin']);

    $id = (int)($input['id'] ?? ($_GET['id'] ?? 0));
    if ($id <= 0) api_response(false, null, 'ID tidak ditemukan.');

    $row = $model->find($id);
    if (!$row) api_response(false, null, 'Data tidak ditemukan.');
    if (!peminjaman_status_allows_change($row)) {
        api_response(false, null, 'Peminjaman sudah disetujui/menunggu pengembalian/selesai, tidak bisa diubah.');
    }

    $colsRes = $conn->query("SHOW COLUMNS FROM `peminjaman`");
    $cols = [];
    if ($colsRes) while ($c = $colsRes->fetch_assoc()) $cols[] = $c['Field'];

    $fields = [
        'nama_pihak1','nip','jabatan','nama_pihak2','nip1','jabatan1',
        'alamat','no_hp','tujuan','tgl_pinjam','tgl_jatuh_tempo','spesifikasi','keterangan'
    ];
    $sets = [];
    $values = [];
    $types = '';

    if (in_array('status', $cols, true)) $sets[] = "`status` = 'waiting_verification'";
    if (in_array('verified_by', $cols, true)) $sets[] = "`verified_by` = NULL";
    if (in_array('verified_at', $cols, true)) $sets[] = "`verified_at` = NULL";

    foreach ($fields as $f) {
        if (!in_array($f, $cols, true)) continue;
        if (!array_key_exists($f, $input)) continue;
        $sets[] = "`{$f}` = ?";
        $values[] = trim((string)$input[$f]);
        $types .= 's';
    }

    if (isset($input['items'])) {
        $itemsCheck = normalize_items($input['items'] ?? []);
        if (!empty($itemsCheck)) {
            [$stokOk, $stokErr] = validate_items_stock($conn, $model, $itemsCheck);
            if (!$stokOk) api_response(false, null, $stokErr);
        }
    }

    $pk = $model->getPkColumn() ?: 'id';
    $values[] = $id;
    $types .= 'i';

    $conn->begin_transaction();
    try {
        if (!empty($sets)) {
            $sql = "UPDATE peminjaman SET " . implode(',', $sets) . " WHERE `{$pk}` = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) throw new Exception('Gagal memperbarui data.');
            $bind = [];
            $bind[] = $types;
            foreach ($values as $k => $v) $bind[] = &$values[$k];
            call_user_func_array([$stmt, 'bind_param'], $bind);
            if (!$stmt->execute()) throw new Exception('Gagal memperbarui data.');
        }

        if (isset($input['items'])) {
            $items = normalize_items($input['items']);
            if (!empty($items)) {
                $map = $model->getItemsMapping();
                $itemsTable = $map['items_table'] ?? null;
                $pCol = $map['peminjaman_col'] ?? null;
                $bCol = $map['barang_col'] ?? null;
                $qCol = $map['qty_col'] ?? null;
                if ($itemsTable && $pCol && $bCol && $qCol) {
                    $stmt = $conn->prepare("DELETE FROM `{$itemsTable}` WHERE `{$pCol}` = ?");
                    $stmt->bind_param('i', $id);
                    $stmt->execute();

                    $stmt = $conn->prepare("INSERT INTO `{$itemsTable}` (`{$pCol}`, `{$bCol}`, `{$qCol}`) VALUES (?, ?, ?)");
                    foreach ($items as $it) {
                        $bid = (int)$it['barang_id'];
                        $qty = (int)$it['qty'];
                        if ($bid <= 0) continue;
                        $stmt->bind_param('iii', $id, $bid, $qty);
                        if (!$stmt->execute()) throw new Exception('Gagal memperbarui items.');
                    }
                }
            }
        }

        $conn->commit();
        api_response(true, ['id' => $id], null);
    } catch (Exception $e) {
        $conn->rollback();
        api_response(false, null, $e->getMessage());
    }
}

if ($action === 'delete') {
    require_login_api();
    require_role_api(['pengurus_barang', 'pengurus', 'admin']);

    $id = (int)($input['id'] ?? ($_GET['id'] ?? 0));
    if ($id <= 0) api_response(false, null, 'ID tidak ditemukan.');

    $row = $model->find($id);
    if (!$row) api_response(false, null, 'Data tidak ditemukan.');

    $statusNow = strtolower((string)($row['status'] ?? ''));
    $allowedDelete = in_array($statusNow, ['waiting_verification','pending','menunggu_verifikasi','rejected','ditolak',''], true);
    if (!$allowedDelete) {
        api_response(false, null, 'Peminjaman sudah disetujui/menunggu pengembalian/selesai, tidak bisa dihapus.');
    }

    $map = $model->getItemsMapping();
    $itemsTable = $map['items_table'] ?? null;
    $pCol = $map['peminjaman_col'] ?? null;
    $pk = $model->getPkColumn() ?: 'id';
    $items = $model->getItems($id);
    $qtyMap = [];
    foreach ($items as $it) {
        $bid = (int)($it['id_barang'] ?? ($it['barang_id'] ?? 0));
        $qty = (int)($it['qty'] ?? ($it['jumlah'] ?? 1));
        if ($bid <= 0 || $qty <= 0) continue;
        if (!isset($qtyMap[$bid])) $qtyMap[$bid] = 0;
        $qtyMap[$bid] += $qty;
    }
    $stokCol = detect_barang_stok_col($conn);

    $conn->begin_transaction();
    try {
        if ($stokCol && !empty($qtyMap)) {
            $stokColEsc = $conn->real_escape_string($stokCol);
            $stmtStok = $conn->prepare("UPDATE barang SET `{$stokColEsc}` = `{$stokColEsc}` + ? WHERE id_barang = ?");
            foreach ($qtyMap as $bid => $qty) {
                $stmtStok->bind_param('ii', $qty, $bid);
                if (!$stmtStok->execute()) throw new Exception('Gagal mengembalikan stok.');
            }
        }
        if ($itemsTable && $pCol) {
            $stmt = $conn->prepare("DELETE FROM `{$itemsTable}` WHERE `{$pCol}` = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
        }
        $stmt = $conn->prepare("DELETE FROM peminjaman WHERE `{$pk}` = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $conn->commit();
        api_response(true, ['id' => $id], null);
    } catch (Exception $e) {
        $conn->rollback();
        api_response(false, null, 'Gagal menghapus peminjaman.');
    }
}

api_response(false, null, 'Action tidak valid.');
