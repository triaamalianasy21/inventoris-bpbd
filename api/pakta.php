<?php
require_once __DIR__ . '/_common.php';
require_once __DIR__ . '/../app/models/Koneksi.php';
require_once __DIR__ . '/../app/models/PeminjamanModel.php';

$conn = Koneksi::connect();
$model = new PeminjamanModel();

$input = api_input();
$action = $_GET['action'] ?? $_POST['action'] ?? ($input['action'] ?? null);
if (!$action) api_response(false, null, 'Parameter action tidak ditemukan.');

function pakta_table_exists($conn)
{
    $res = $conn->query("SHOW TABLES LIKE 'pakta_integritas'");
    return $res && $res->num_rows > 0;
}

function pakta_columns($conn)
{
    $cols = [];
    $res = $conn->query("SHOW COLUMNS FROM `pakta_integritas`");
    if ($res) while ($c = $res->fetch_assoc()) $cols[] = $c['Field'];
    return $cols;
}

function pakta_primary_key($conn)
{
    $res = $conn->query("SHOW COLUMNS FROM `pakta_integritas`");
    if (!$res) return 'id';
    $cols = [];
    while ($col = $res->fetch_assoc()) {
        $cols[] = $col['Field'];
        if (!empty($col['Key']) && strtoupper($col['Key']) === 'PRI') return $col['Field'];
    }
    foreach (['id_pakta','pakta_id','id'] as $f) {
        if (in_array($f, $cols, true)) return $f;
    }
    return $cols[0] ?? 'id';
}

function pakta_user_column($cols)
{
    foreach (['user_id','id_user','requested_by','pemohon_id','peminjam_id','created_by','requester'] as $c) {
        if (in_array($c, $cols, true)) return $c;
    }
    return null;
}

if (!pakta_table_exists($conn)) {
    api_response(false, null, 'Tabel pakta_integritas tidak ditemukan.');
}

$cols = pakta_columns($conn);
$pk = pakta_primary_key($conn);
$userCol = pakta_user_column($cols);
$statusCol = in_array('status', $cols, true) ? 'status' : (in_array('status_pakta', $cols, true) ? 'status_pakta' : null);
$peminjamanCol = in_array('peminjaman_id', $cols, true) ? 'peminjaman_id' : (in_array('id_pinjam', $cols, true) ? 'id_pinjam' : null);

if ($action === 'list') {
    require_login_api();
    $role = currentUserRole();
    $uid = currentUserId();
    $q = trim($_GET['q'] ?? ($input['q'] ?? ''));

    $where = [];
    $types = '';
    $params = [];

    if ($role === 'pemohon' && $userCol) {
        $where[] = "`{$userCol}` = ?";
        $types .= 'i';
        $params[] = $uid;
    }

    $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    $sql = "SELECT * FROM pakta_integritas {$whereSql} ORDER BY `{$pk}` DESC";
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
                $row['nama'] ?? '',
                $row['nama_lengkap'] ?? '',
                $row['nama_peminjam'] ?? '',
                $row['user_nama'] ?? '',
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

    $stmt = $conn->prepare("SELECT * FROM pakta_integritas WHERE `{$pk}` = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) api_response(false, null, 'Data tidak ditemukan.');

    if (currentUserRole() === 'pemohon' && $userCol && isset($row[$userCol]) && (int)$row[$userCol] !== (int)currentUserId()) {
        api_response(false, null, 'Akses ditolak.');
    }

    api_response(true, $row, null);
}

if ($action === 'create') {
    require_login_api();
    $role = currentUserRole();
    if (!in_array($role, ['pemohon','pengurus_barang','pengurus','admin'], true)) {
        api_response(false, null, 'Akses ditolak.');
    }

    try {
    // If pakta for this peminjaman already exists, update it instead of insert
    if (!empty($input['id_pinjam']) && $peminjamanCol) {
        $pid = (int)$input['id_pinjam'];
        if ($pid > 0) {
            $q = $conn->prepare("SELECT `{$pk}` AS id FROM pakta_integritas WHERE `{$peminjamanCol}` = ? LIMIT 1");
            if ($q) {
                $q->bind_param('i', $pid);
                $q->execute();
                $row = $q->get_result()->fetch_assoc();
                if ($row && isset($row['id'])) {
                    $id = (int)$row['id'];

                    $sets = [];
                    $values = [];
                    $types = '';
                    foreach ($cols as $col) {
                        if ($col === $pk) continue;
                        if (in_array($col, ['created_at','verified_by','verified_at'], true)) continue;
                        if ($col === $userCol || $col === $statusCol || $col === $peminjamanCol) continue;
                        if (!array_key_exists($col, $input)) continue;
                        $sets[] = "`{$col}` = ?";
                        $values[] = $input[$col] === '' ? null : $input[$col];
                        $types .= 's';
                    }
                    if ($statusCol) {
                        $sets[] = "`{$statusCol}` = 'filled'";
                    }
                    if (!empty($sets)) {
                        $values[] = $id;
                        $types .= 'i';
                        $sql = "UPDATE pakta_integritas SET " . implode(',', $sets) . " WHERE `{$pk}` = ?";
                        $stmt = $conn->prepare($sql);
                        if (!$stmt) api_response(false, null, 'Gagal menyiapkan query pakta.');
                        if ($types !== '' && !empty($values)) {
                            if (!$stmt->bind_param($types, ...$values)) {
                                api_response(false, null, 'Gagal bind data pakta.');
                            }
                        }
                        if (!$stmt->execute()) api_response(false, null, 'Gagal memperbarui pakta.');
                    }
                    api_response(true, ['id' => $id], null);
                }
            }
        }
    }

    $fields = [];
    $values = [];
    $types = '';

    foreach ($cols as $col) {
        if ($col === $pk) continue;
        if (in_array($col, ['created_at','verified_by','verified_at'], true)) continue;
        if ($col === $userCol || $col === $statusCol || $col === $peminjamanCol) continue;
        if (array_key_exists($col, $input)) {
            $fields[] = $col;
            $values[] = $input[$col] === '' ? null : $input[$col];
            $types .= 's';
        }
    }

    if ($userCol) {
        $fields[] = $userCol;
        $values[] = currentUserId();
        $types .= 'i';
    }
    if ($statusCol) {
        $fields[] = $statusCol;
        $values[] = 'filled';
        $types .= 's';
    }
    if ($peminjamanCol && !empty($input['id_pinjam'])) {
        $fields[] = $peminjamanCol;
        $values[] = (int)$input['id_pinjam'];
        $types .= 'i';
    }

    if (empty($fields)) api_response(false, null, 'Field pakta belum lengkap.');

    $sql = "INSERT INTO pakta_integritas (`" . implode('`,`', $fields) . "`) VALUES (" . rtrim(str_repeat('?,', count($fields)), ',') . ")";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        api_response(false, null, 'Gagal menyiapkan query pakta.');
    }
    if ($types !== '' && !empty($values)) {
        if (!$stmt->bind_param($types, ...$values)) {
            api_response(false, null, 'Gagal bind data pakta.');
        }
    }
    if (!$stmt->execute()) api_response(false, null, 'Gagal menyimpan pakta.');

    $newId = (int)$conn->insert_id;
    if ($newId <= 0) {
        // Fallback: cari ID pakta terakhir berdasarkan peminjaman/user
        if ($peminjamanCol && !empty($input['id_pinjam'])) {
            $pid = (int)$input['id_pinjam'];
            $q = $conn->prepare("SELECT `{$pk}` AS id FROM pakta_integritas WHERE `{$peminjamanCol}` = ? ORDER BY `{$pk}` DESC LIMIT 1");
            $q->bind_param('i', $pid);
            $q->execute();
            $row = $q->get_result()->fetch_assoc();
            if ($row && isset($row['id'])) $newId = (int)$row['id'];
        } elseif ($userCol) {
            $uid = (int)currentUserId();
            $q = $conn->prepare("SELECT `{$pk}` AS id FROM pakta_integritas WHERE `{$userCol}` = ? ORDER BY `{$pk}` DESC LIMIT 1");
            $q->bind_param('i', $uid);
            $q->execute();
            $row = $q->get_result()->fetch_assoc();
            if ($row && isset($row['id'])) $newId = (int)$row['id'];
        }
    }

    api_response(true, ['id' => $newId], null);
    } catch (Throwable $e) {
        api_response(false, null, 'Server error: ' . $e->getMessage());
    }
}

if ($action === 'update') {
    require_login_api();
    $role = currentUserRole();
    if (!in_array($role, ['pemohon','pengurus_barang','pengurus','admin'], true)) {
        api_response(false, null, 'Akses ditolak.');
    }

    try {
    $id = (int)($input['id'] ?? ($_GET['id'] ?? 0));
    if ($id <= 0) api_response(false, null, 'ID tidak ditemukan.');

    $stmt = $conn->prepare("SELECT * FROM pakta_integritas WHERE `{$pk}` = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) api_response(false, null, 'Data tidak ditemukan.');

    if ($role === 'pemohon' && $userCol && isset($row[$userCol]) && (int)$row[$userCol] !== (int)currentUserId()) {
        api_response(false, null, 'Akses ditolak.');
    }

    $sets = [];
    $values = [];
    $types = '';
    foreach ($cols as $col) {
        if ($col === $pk) continue;
        if (in_array($col, ['created_at','verified_by','verified_at'], true)) continue;
        if ($col === $userCol || $col === $statusCol || $col === $peminjamanCol) continue;
        if (!array_key_exists($col, $input)) continue;
        $sets[] = "`{$col}` = ?";
        $values[] = $input[$col] === '' ? null : $input[$col];
        $types .= 's';
    }

    if (empty($sets)) api_response(false, null, 'Tidak ada data yang diubah.');

    if ($statusCol) {
        $sets[] = "`{$statusCol}` = 'filled'";
    }

    $values[] = $id;
    $types .= 'i';
    $sql = "UPDATE pakta_integritas SET " . implode(',', $sets) . " WHERE `{$pk}` = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        api_response(false, null, 'Gagal menyiapkan query pakta.');
    }
    if ($types !== '' && !empty($values)) {
        if (!$stmt->bind_param($types, ...$values)) {
            api_response(false, null, 'Gagal bind data pakta.');
        }
    }
    if (!$stmt->execute()) api_response(false, null, 'Gagal memperbarui pakta.');

    api_response(true, ['id' => $id], null);
    } catch (Throwable $e) {
        api_response(false, null, 'Server error: ' . $e->getMessage());
    }
}

if ($action === 'delete') {
    require_login_api();
    require_role_api(['pengurus_barang','pengurus','admin']);
    $id = (int)($input['id'] ?? ($_GET['id'] ?? 0));
    if ($id <= 0) api_response(false, null, 'ID tidak ditemukan.');

    $stmt = $conn->prepare("DELETE FROM pakta_integritas WHERE `{$pk}` = ?");
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) api_response(false, null, 'Gagal menghapus pakta.');
    api_response(true, ['id' => $id], null);
}

api_response(false, null, 'Action tidak valid.');
