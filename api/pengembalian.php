<?php
require_once __DIR__ . '/_common.php';
require_once __DIR__ . '/../app/models/Koneksi.php';
require_once __DIR__ . '/../app/models/PeminjamanModel.php';

$conn = Koneksi::connect();
$model = new PeminjamanModel();

$input = api_input();
$action = $_GET['action'] ?? $_POST['action'] ?? ($input['action'] ?? null);
if (!$action) api_response(false, null, 'Parameter action tidak ditemukan.');

function table_columns($conn, $table)
{
    $cols = [];
    $res = $conn->query("SHOW COLUMNS FROM `{$table}`");
    if ($res) while ($c = $res->fetch_assoc()) $cols[] = $c['Field'];
    return $cols;
}

function has_col($cols, $col)
{
    return in_array($col, $cols, true);
}

function detect_detail_table($conn)
{
    $candidates = ['detail_pengembalian','detail_kembali','pengembalian_detail','pengembalian_items'];
    foreach ($candidates as $t) {
        $res = $conn->query("SHOW TABLES LIKE '{$t}'");
        if ($res && $res->num_rows > 0) return $t;
    }
    return null;
}

function pick_col($cols, $candidates)
{
    foreach ($candidates as $c) {
        if (in_array($c, $cols, true)) return $c;
    }
    return null;
}

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

function foto_pengembalian_url($path)
{
    $path = trim((string)$path);
    if ($path === '') return null;
    if (preg_match('~^https?://~i', $path)) return $path;
    if (stripos($path, 'public/') === 0) return api_base_url() . $path;
    if (stripos($path, 'uploads/') === 0) return api_base_url() . 'public/' . $path;
    return api_base_url() . 'public/uploads/pengembalian/' . $path;
}

function save_uploaded_photo($file, $prefix)
{
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return [false, 'Gagal mengunggah foto.'];
    }
    if (!empty($file['size']) && $file['size'] > 10 * 1024 * 1024) {
        return [false, 'Ukuran foto maksimal 10MB.'];
    }
    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png'], true)) {
        return [false, 'Format foto harus JPG atau PNG.'];
    }
    if (empty($file['tmp_name']) || !@getimagesize($file['tmp_name'])) {
        return [false, 'File foto tidak valid.'];
    }
    $dir = __DIR__ . '/../public/uploads/pengembalian';
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        return [false, 'Gagal menyiapkan folder upload.'];
    }
    $safe = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$prefix);
    $name = $safe . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $dir . DIRECTORY_SEPARATOR . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return [false, 'Gagal menyimpan foto.'];
    }
    return [true, 'uploads/pengembalian/' . $name];
}

function save_base64_photo($data, $prefix)
{
    if (!is_string($data) || $data === '') {
        return [false, 'Foto wajib diunggah.'];
    }
    $ext = 'jpg';
    if (preg_match('/^data:image\\/(png|jpe?g);base64,/', $data, $m)) {
        $ext = $m[1] === 'jpeg' ? 'jpg' : $m[1];
        $data = substr($data, strpos($data, ',') + 1);
    }
    $bin = base64_decode($data, true);
    if ($bin === false) {
        return [false, 'Format foto tidak valid.'];
    }
    if (strlen($bin) > 10 * 1024 * 1024) {
        return [false, 'Ukuran foto maksimal 10MB.'];
    }
    if (!@getimagesizefromstring($bin)) {
        return [false, 'File foto tidak valid.'];
    }
    $dir = __DIR__ . '/../public/uploads/pengembalian';
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        return [false, 'Gagal menyiapkan folder upload.'];
    }
    $safe = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$prefix);
    $name = $safe . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $dir . DIRECTORY_SEPARATOR . $name;
    if (file_put_contents($dest, $bin) === false) {
        return [false, 'Gagal menyimpan foto.'];
    }
    return [true, 'uploads/pengembalian/' . $name];
}

function pengembalian_status_allows_change($status)
{
    return true;
}

function pengembalian_status_allows_delete($status)
{
    $status = strtolower((string)$status);
    return in_array($status, ['pending','menunggu_verifikasi','waiting_verification','ditolak','rejected',''], true);
}

if ($action === 'list') {
    require_login_api();
    $role = currentUserRole();
    $uid = currentUserId();
    $q = trim($_GET['q'] ?? ($input['q'] ?? ''));

    $detailTable = detect_detail_table($conn);
    $dCols = $detailTable ? table_columns($conn, $detailTable) : [];
    $dIdKembali = $detailTable ? pick_col($dCols, ['id_kembali','pengembalian_id']) : null;
    $dTgl = $detailTable ? pick_col($dCols, ['tgl_kembali','tanggal_kembali','tgl']) : null;
    $dFoto = $detailTable ? pick_col($dCols, ['foto','foto_bukti','foto_path','foto_pengembalian']) : null;
    $joinDetail = ($detailTable && $dIdKembali && ($dTgl || $dFoto)) ? "LEFT JOIN `{$detailTable}` d ON d.`{$dIdKembali}` = p.id_kembali" : "";
    $tglSelect = ($detailTable && $dIdKembali && $dTgl) ? "MIN(d.`{$dTgl}`) AS tgl_kembali" : "NULL AS tgl_kembali";
    $fotoSelect = ($detailTable && $dIdKembali && $dFoto) ? "MIN(d.`{$dFoto}`) AS foto" : "NULL AS foto";

    $pkPinjam = $model->getPkColumn() ?: 'id_pinjam';
    $joinCol = $pkPinjam;
    $sql = "SELECT p.*, pm.nama_pihak1, pm.nama_pihak2, pm.tujuan, pm.tgl_pinjam, {$tglSelect}, {$fotoSelect}
            FROM pengembalian p
            LEFT JOIN peminjaman pm ON pm.`{$joinCol}` = p.id_pinjam
            {$joinDetail}";

    $where = [];
    $params = [];
    $types = '';

    if ($role === 'pemohon') {
        $pCols = table_columns($conn, 'pengembalian');
        $uCol = $model->getUserColumn();
        $pmCols = table_columns($conn, 'peminjaman');
        $userName = null;
        if ($uid) {
            $stmtUser = $conn->prepare("SELECT nama FROM user WHERE id_user = ? LIMIT 1");
            $stmtUser->bind_param('i', $uid);
            $stmtUser->execute();
            $userRow = $stmtUser->get_result()->fetch_assoc();
            $userName = $userRow['nama'] ?? null;
        }
        if (has_col($pCols, 'id_user') && $uCol) {
            $cond = "(p.id_user = ? OR pm.`{$uCol}` = ?)";
            $types .= 'ii';
            $params[] = $uid;
            $params[] = $uid;
            if ($userName && in_array('nama_pihak2', $pmCols, true)) {
                $cond .= " OR pm.`nama_pihak2` = ?";
                $types .= 's';
                $params[] = $userName;
            }
            $where[] = $cond;
        } elseif (has_col($pCols, 'id_user')) {
            $where[] = "p.id_user = ?";
            $types .= 'i';
            $params[] = $uid;
            if ($userName && in_array('nama_pihak2', $pmCols, true)) {
                $where[] = "pm.`nama_pihak2` = ?";
                $types .= 's';
                $params[] = $userName;
            }
        } elseif ($uCol) {
            $where[] = "pm.`{$uCol}` = ?";
            $types .= 'i';
            $params[] = $uid;
            if ($userName && in_array('nama_pihak2', $pmCols, true)) {
                $where[] = "pm.`nama_pihak2` = ?";
                $types .= 's';
                $params[] = $userName;
            }
        } elseif ($userName && in_array('nama_pihak2', $pmCols, true)) {
            $where[] = "pm.`nama_pihak2` = ?";
            $types .= 's';
            $params[] = $userName;
        }
    }

    if (!empty($where)) $sql .= " WHERE " . implode(' AND ', $where);
    $sql .= " GROUP BY p.id_kembali ORDER BY p.id_kembali DESC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;

    if ($q !== '') {
        $qLower = mb_strtolower($q, 'UTF-8');
        $rows = array_values(array_filter($rows, function($row) use ($qLower) {
            $fields = [
                $row['nama_pihak1'] ?? '',
                $row['tujuan'] ?? '',
            ];
            foreach ($fields as $f) {
                if ($f === '') continue;
                if (mb_stripos((string)$f, $qLower, 0, 'UTF-8') !== false) return true;
            }
            return false;
        }));
    }

    foreach ($rows as &$row) {
        $foto = $row['foto'] ?? '';
        $row['foto_url'] = $foto ? foto_pengembalian_url($foto) : null;
        $status = $row['status'] ?? '';
        $row['can_edit'] = true;
        $row['can_delete'] = pengembalian_status_allows_delete($status);
    }
    unset($row);

    api_response(true, $rows, null);
}

if ($action === 'detail') {
    require_login_api();
    $id = (int)($_GET['id'] ?? ($input['id'] ?? 0));
    if ($id <= 0) api_response(false, null, 'ID tidak ditemukan.');

    $pkPinjam = $model->getPkColumn() ?: 'id_pinjam';
    $stmt = $conn->prepare("SELECT p.*, pm.* FROM pengembalian p LEFT JOIN peminjaman pm ON pm.`{$pkPinjam}` = p.id_pinjam WHERE p.id_kembali = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    if (!$data) {
        $stmt = $conn->prepare("SELECT p.*, pm.* FROM pengembalian p LEFT JOIN peminjaman pm ON pm.`{$pkPinjam}` = p.id_pinjam WHERE p.id_pinjam = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();
    }
    if (!$data) api_response(false, null, 'Data tidak ditemukan.');

    if (currentUserRole() === 'pemohon') {
        $pCols = table_columns($conn, 'pengembalian');
        if (has_col($pCols, 'id_user') && (int)$data['id_user'] !== (int)currentUserId()) {
            api_response(false, null, 'Akses ditolak.');
        }
    }

    if (empty($data['tgl_kembali'])) {
        $detailTable = detect_detail_table($conn);
        if ($detailTable) {
            $dCols = table_columns($conn, $detailTable);
            $dIdKembali = pick_col($dCols, ['id_kembali','pengembalian_id']);
            $dTgl = pick_col($dCols, ['tgl_kembali','tanggal_kembali','tgl']);
            if ($dIdKembali && $dTgl) {
                $stmt = $conn->prepare("SELECT MIN(`{$dTgl}`) AS tgl_kembali FROM `{$detailTable}` WHERE `{$dIdKembali}` = ?");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                if (!empty($row['tgl_kembali'])) {
                    $data['tgl_kembali'] = $row['tgl_kembali'];
                }
            }
        }
    }

    $data['can_edit'] = true;
    $data['can_delete'] = pengembalian_status_allows_delete($data['status'] ?? '');

    $data['items'] = $model->getItems((int)($data['id_pinjam'] ?? 0));
    if (!empty($data['items'])) {
        $detailTable = detect_detail_table($conn);
        if ($detailTable) {
            $dCols = table_columns($conn, $detailTable);
            $dIdKembali = pick_col($dCols, ['id_kembali','pengembalian_id']);
            $dIdBarang = pick_col($dCols, ['id_barang','barang_id']);
            $dFoto = pick_col($dCols, ['foto','foto_bukti','foto_path','foto_pengembalian']);
            if ($dIdKembali && $dIdBarang && $dFoto) {
                $stmt = $conn->prepare("SELECT `{$dIdBarang}` AS id_barang, `{$dFoto}` AS foto
                                        FROM `{$detailTable}` WHERE `{$dIdKembali}` = ?");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $res = $stmt->get_result();
                $fotoMap = [];
                if ($res) {
                    while ($r = $res->fetch_assoc()) {
                        $bid = (int)($r['id_barang'] ?? 0);
                        if ($bid > 0) $fotoMap[$bid] = $r['foto'] ?? '';
                    }
                }
                foreach ($data['items'] as &$it) {
                    $bid = (int)($it['id_barang'] ?? ($it['barang_id'] ?? 0));
                    $foto = ($bid > 0 && isset($fotoMap[$bid])) ? $fotoMap[$bid] : '';
                    $it['foto'] = $foto;
                    $it['foto_url'] = $foto ? foto_pengembalian_url($foto) : null;
                }
                unset($it);
            }
        }
    }
    api_response(true, $data, null);
}

if ($action === 'create') {
    require_login_api();
    $role = currentUserRole();
    if (!in_array($role, ['pemohon','pengurus_barang','pengurus','admin'], true)) {
        api_response(false, null, 'Akses ditolak.');
    }

    $kondisi = trim((string)($input['kondisi_kembali'] ?? ''));
    $tglKembali = trim((string)($input['tgl_kembali'] ?? ''));
    if ($kondisi === '' || $tglKembali === '') api_response(false, null, 'Kondisi dan tanggal kembali wajib diisi.');

    $idPinjam = (int)($input['id_pinjam'] ?? 0);
    $uid = currentUserId();

    if ($role === 'pemohon' && $idPinjam <= 0) {
        $uCol = $model->getUserColumn();
        if (!$uCol) api_response(false, null, 'Kolom user peminjaman tidak ditemukan.');

        $orderCol = $model->getPkColumn() ?: 'id_pinjam';
        $colsRes = $conn->query("SHOW COLUMNS FROM peminjaman");
        $cols = [];
        if ($colsRes) while ($c = $colsRes->fetch_assoc()) $cols[] = $c['Field'];
        if (in_array('tgl_pinjam', $cols, true)) $orderCol = 'tgl_pinjam';
        elseif (in_array('created_at', $cols, true)) $orderCol = 'created_at';

        $pk = $model->getPkColumn() ?: 'id_pinjam';
        $stmt = $conn->prepare("SELECT `{$pk}` AS id_pinjam FROM peminjaman WHERE `{$uCol}` = ? AND status = 'approved' ORDER BY `{$orderCol}` DESC LIMIT 1");
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $idPinjam = (int)($row['id_pinjam'] ?? 0);
    }

    if ($idPinjam <= 0) api_response(false, null, 'Peminjaman tidak ditemukan.');

    $items = $model->getItems($idPinjam);
    if (empty($items)) api_response(false, null, 'Tidak ada barang pada peminjaman ini.');

    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM pengembalian WHERE id_pinjam = ? AND status IN ('pending','processing')");
    $stmt->bind_param('i', $idPinjam);
    $stmt->execute();
    $exists = (int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
    if ($exists > 0) api_response(false, null, 'Pengembalian untuk peminjaman ini masih diproses.');

    $cols = table_columns($conn, 'pengembalian');
    $fields = ['id_pinjam','kondisi_kembali','status'];
    $values = [$idPinjam, $kondisi, 'pending'];
    $types = 'iss';

    if (has_col($cols, 'id_user')) { $fields[] = 'id_user'; $values[] = $uid; $types .= 'i'; }
    if (has_col($cols, 'requested_by')) { $fields[] = 'requested_by'; $values[] = $uid; $types .= 'i'; }

    $sql = "INSERT INTO pengembalian (`" . implode('`,`', $fields) . "`) VALUES (" . rtrim(str_repeat('?,', count($fields)), ',') . ")";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);
    if (!$stmt->execute()) api_response(false, null, 'Gagal menyimpan pengembalian.');
    $idKembali = (int)$conn->insert_id;

    $detailTable = detect_detail_table($conn);
    if (!$detailTable) api_response(false, null, 'Tabel detail pengembalian tidak ditemukan.');
    $dCols = table_columns($conn, $detailTable);
    $dIdKembali = pick_col($dCols, ['id_kembali','pengembalian_id']);
    $dIdBarang = pick_col($dCols, ['id_barang','barang_id']);
    $dTgl = pick_col($dCols, ['tgl_kembali','tanggal_kembali','tgl']);
    $dFoto = pick_col($dCols, ['foto','foto_bukti']);
    if (!$dIdKembali || !$dIdBarang || !$dTgl) api_response(false, null, 'Kolom detail pengembalian tidak lengkap.');
    if (!$dFoto) api_response(false, null, 'Kolom foto tidak ditemukan pada detail pengembalian.');

    $fotoInput = $input['foto'] ?? [];
    if (!is_array($fotoInput)) $fotoInput = [];

    $stmt = $conn->prepare("INSERT INTO `{$detailTable}` (`{$dIdKembali}`, `{$dIdBarang}`, `{$dTgl}`, `{$dFoto}`) VALUES (?, ?, ?, ?)");
    foreach ($items as $it) {
        $bid = (int)($it['id_barang'] ?? ($it['barang_id'] ?? 0));
        if ($bid <= 0) continue;
        $fotoPath = null;

        if (!empty($_FILES['foto'])) {
            $file = [
                'name' => $_FILES['foto']['name'][$bid] ?? '',
                'type' => $_FILES['foto']['type'][$bid] ?? '',
                'tmp_name' => $_FILES['foto']['tmp_name'][$bid] ?? '',
                'error' => $_FILES['foto']['error'][$bid] ?? UPLOAD_ERR_NO_FILE,
                'size' => $_FILES['foto']['size'][$bid] ?? 0
            ];
            if ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                [$okUpload, $pathOrErr] = save_uploaded_photo($file, 'kembali_' . $idPinjam . '_' . $bid);
                if (!$okUpload) api_response(false, null, $pathOrErr);
                $fotoPath = $pathOrErr;
            }
        }

        if (!$fotoPath) {
            $fotoData = $fotoInput[$bid] ?? null;
            if ($fotoData) {
                [$okUpload, $pathOrErr] = save_base64_photo($fotoData, 'kembali_' . $idPinjam . '_' . $bid);
                if (!$okUpload) api_response(false, null, $pathOrErr);
                $fotoPath = $pathOrErr;
            }
        }

        if (!$fotoPath) api_response(false, null, 'Foto untuk setiap barang wajib diunggah.');
        $stmt->bind_param('iiss', $idKembali, $bid, $tglKembali, $fotoPath);
        $stmt->execute();
    }

    api_response(true, ['id_kembali' => $idKembali], null);
}

if ($action === 'update') {
    require_login_api();
    require_role_api(['pengurus_barang','pengurus','admin']);

    $id = (int)($input['id'] ?? ($_GET['id'] ?? 0));
    if ($id <= 0) api_response(false, null, 'ID tidak ditemukan.');

    $kondisi = trim((string)($input['kondisi_kembali'] ?? ''));
    $tglKembali = trim((string)($input['tgl_kembali'] ?? ''));
    if ($kondisi === '' || $tglKembali === '') api_response(false, null, 'Kondisi dan tanggal kembali wajib diisi.');

    $stmt = $conn->prepare("SELECT status FROM pengembalian WHERE id_kembali = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) api_response(false, null, 'Data tidak ditemukan.');
    if (!pengembalian_status_allows_change($row['status'] ?? '')) {
        api_response(false, null, 'Pengembalian sudah disetujui/menunggu proses lanjut, tidak bisa diubah.');
    }

    $detailTable = detect_detail_table($conn);
    if (!$detailTable) api_response(false, null, 'Tabel detail pengembalian tidak ditemukan.');
    $dCols = table_columns($conn, $detailTable);
    $dIdKembali = pick_col($dCols, ['id_kembali','pengembalian_id']);
    $dTgl = pick_col($dCols, ['tgl_kembali','tanggal_kembali','tgl']);
    $dIdBarang = pick_col($dCols, ['id_barang','barang_id']);
    $dFoto = pick_col($dCols, ['foto','foto_bukti']);
    if (!$dIdKembali || !$dTgl) api_response(false, null, 'Kolom detail pengembalian tidak lengkap.');

    $stmt = $conn->prepare("UPDATE pengembalian SET kondisi_kembali = ?, status = 'pending', verified_by = NULL, verified_at = NULL WHERE id_kembali = ?");
    $stmt->bind_param('si', $kondisi, $id);
    $stmt->execute();

    $stmt = $conn->prepare("UPDATE `{$detailTable}` SET `{$dTgl}` = ? WHERE `{$dIdKembali}` = ?");
    $stmt->bind_param('si', $tglKembali, $id);
    $stmt->execute();

    if ($dFoto && $dIdBarang) {
        $fotoInput = $input['foto'] ?? [];
        if (!is_array($fotoInput)) $fotoInput = [];

        $stmtItems = $conn->prepare("SELECT `{$dIdBarang}` AS id_barang FROM `{$detailTable}` WHERE `{$dIdKembali}` = ?");
        $stmtItems->bind_param('i', $id);
        $stmtItems->execute();
        $resItems = $stmtItems->get_result();
        while ($rowIt = $resItems->fetch_assoc()) {
            $bid = (int)($rowIt['id_barang'] ?? 0);
            if ($bid <= 0) continue;
            $fotoPath = null;

            if (!empty($_FILES['foto'])) {
                $file = [
                    'name' => $_FILES['foto']['name'][$bid] ?? '',
                    'type' => $_FILES['foto']['type'][$bid] ?? '',
                    'tmp_name' => $_FILES['foto']['tmp_name'][$bid] ?? '',
                    'error' => $_FILES['foto']['error'][$bid] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $_FILES['foto']['size'][$bid] ?? 0
                ];
                if ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                    [$okUpload, $pathOrErr] = save_uploaded_photo($file, 'kembali_' . $id . '_' . $bid);
                    if (!$okUpload) api_response(false, null, $pathOrErr);
                    $fotoPath = $pathOrErr;
                }
            }

            if (!$fotoPath) {
                $fotoData = $fotoInput[$bid] ?? null;
                if ($fotoData) {
                    [$okUpload, $pathOrErr] = save_base64_photo($fotoData, 'kembali_' . $id . '_' . $bid);
                    if (!$okUpload) api_response(false, null, $pathOrErr);
                    $fotoPath = $pathOrErr;
                }
            }

            if ($fotoPath) {
                $stmtFoto = $conn->prepare("UPDATE `{$detailTable}` SET `{$dFoto}` = ? WHERE `{$dIdKembali}` = ? AND `{$dIdBarang}` = ?");
                $stmtFoto->bind_param('sii', $fotoPath, $id, $bid);
                $stmtFoto->execute();
            }
        }
    }

    api_response(true, ['id_kembali' => $id], null);
}

if ($action === 'delete') {
    require_login_api();
    require_role_api(['pengurus_barang','pengurus','admin']);

    $id = (int)($input['id'] ?? ($_GET['id'] ?? 0));
    if ($id <= 0) api_response(false, null, 'ID tidak ditemukan.');

    $stmt = $conn->prepare("SELECT id_pinjam, status FROM pengembalian WHERE id_kembali = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) api_response(false, null, 'Data tidak ditemukan.');
    if (!pengembalian_status_allows_delete($row['status'] ?? '')) {
        api_response(false, null, 'Pengembalian sudah disetujui/menunggu proses lanjut, tidak bisa dihapus.');
    }
    $idPinjam = (int)($row['id_pinjam'] ?? 0);

    $detailTable = detect_detail_table($conn);
    $dCols = $detailTable ? table_columns($conn, $detailTable) : [];
    $dIdKembali = $detailTable ? pick_col($dCols, ['id_kembali','pengembalian_id']) : null;

    $conn->begin_transaction();
    try {
        if ($detailTable && $dIdKembali) {
            $stmt = $conn->prepare("DELETE FROM `{$detailTable}` WHERE `{$dIdKembali}` = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
        }
        $stmt = $conn->prepare("DELETE FROM pengembalian WHERE id_kembali = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();

        // reset status peminjaman agar bisa diajukan pengembalian lagi
        if ($idPinjam > 0) {
            $pCols = table_columns($conn, 'peminjaman');
            $pkPinjam = $model->getPkColumn() ?: 'id_pinjam';
            $useCol = in_array('id_pinjam', $pCols, true) ? 'id_pinjam' : $pkPinjam;
            $stmt = $conn->prepare("UPDATE peminjaman SET status = 'approved' WHERE `{$useCol}` = ?");
            $stmt->bind_param('i', $idPinjam);
            $stmt->execute();
        }

        $conn->commit();
        api_response(true, ['id_kembali' => $id], null);
    } catch (Exception $e) {
        $conn->rollback();
        api_response(false, null, 'Gagal menghapus pengembalian.');
    }
}

api_response(false, null, 'Action tidak valid.');
