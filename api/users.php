<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Support JSON body (Volley JsonObjectRequest)
$rawBody = file_get_contents('php://input');
if ($rawBody) {
    $jsonBody = json_decode($rawBody, true);
    if (is_array($jsonBody)) {
        $_POST = array_merge($_POST, $jsonBody);
    }
}

$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['admin', 'kalaksa', 'pengurus_barang'], true)) {
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

function out($ok, $data = null, $error = null)
{
    $resp = ['ok' => $ok];
    if ($data !== null) $resp['data'] = $data;
    if ($error !== null) $resp['error'] = $error;
    echo json_encode($resp);
    exit;
}

function getParam($key, $default = null)
{
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

function findUserDependencies($conn, $userId)
{
    $blocked = [];
    $dbName = null;

    $dbRes = $conn->query("SELECT DATABASE() AS db");
    if ($dbRes) {
        $row = $dbRes->fetch_assoc();
        $dbName = $row['db'] ?? null;
    }

    if ($dbName) {
        $stmt = $conn->prepare("
            SELECT TABLE_NAME, COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE REFERENCED_TABLE_SCHEMA = ?
              AND REFERENCED_TABLE_NAME = 'user'
        ");
        if ($stmt) {
            $stmt->bind_param('s', $dbName);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res) {
                while ($r = $res->fetch_assoc()) {
                    $table = $r['TABLE_NAME'] ?? '';
                    $column = $r['COLUMN_NAME'] ?? '';
                    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column)) {
                        continue;
                    }
                    $check = $conn->prepare("SELECT 1 FROM `{$table}` WHERE `{$column}` = ? LIMIT 1");
                    if (!$check) {
                        continue;
                    }
                    $check->bind_param('i', $userId);
                    $check->execute();
                    $hit = $check->get_result();
                    if ($hit && $hit->num_rows > 0) {
                        $blocked[] = $table;
                    }
                    $check->close();
                }
            }
            $stmt->close();
        }
    }

    return array_values(array_unique($blocked));
}

function attachDeleteFlags($conn, $users)
{
    foreach ($users as $idx => $row) {
        $userId = (int) ($row['id_user'] ?? 0);
        $users[$idx]['can_delete'] = $userId > 0 && empty(findUserDependencies($conn, $userId));
    }
    return $users;
}

try {
    if ($action === 'list') {
        $status = strtolower(trim(getParam('status', 'all')));
        $q = trim(getParam('q', ''));
        $page = max(1, (int) getParam('page', 1));
        $limit = max(1, (int) getParam('limit', 10));
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];
        $types = '';

        if ($status !== 'all') {
            if ($status === 'active') {
                $where[] = "status = 'active'";
            } elseif ($status === 'pending') {
                $where[] = "status = 'pending'";
            } elseif ($status === 'rejected') {
                $where[] = "status = 'rejected'";
            }
        }

        if ($q !== '') {
            $where[] = "(nama LIKE ? OR username LIKE ? OR role LIKE ? OR no_kontak LIKE ?)";
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $types .= 'ssss';
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countSql = "SELECT COUNT(*) AS total FROM `user` $whereSql";
        $stmt = $db->prepare($countSql);
        if ($types !== '') $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $total = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
        $stmt->close();

        $sql = "SELECT id_user, nama, username, role, no_kontak, status
                FROM `user`
                $whereSql
                ORDER BY id_user DESC
                LIMIT ? OFFSET ?";
        $stmt = $db->prepare($sql);

        $bindParams = $params;
        $bindTypes = $types . 'ii';
        $bindParams[] = $limit;
        $bindParams[] = $offset;

        $stmt->bind_param($bindTypes, ...$bindParams);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // attach can_delete flag (for active/rejected visibility)
        if (!empty($rows)) {
            $rows = attachDeleteFlags($db, $rows);
        }

        out(true, [
            'items' => $rows,
            'page' => $page,
            'limit' => $limit,
            'total' => $total
        ]);
    }

    if ($action === 'create') {
        $nama = trim(getParam('nama', ''));
        $username = trim(getParam('username', ''));
        $password = getParam('password', '');
        $roleUser = trim(getParam('role', 'pemohon'));
        $noKontak = trim(getParam('no_kontak', ''));
        $status = trim(getParam('status', 'active'));

        if ($nama === '' || $username === '' || $password === '') {
            out(false, null, 'Nama, username, dan password wajib diisi.');
        }

        $stmt = $db->prepare("SELECT id_user FROM `user` WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($exists) {
            out(false, null, 'Username sudah digunakan.');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO `user` (nama, username, password, role, no_kontak, status)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('ssssss', $nama, $username, $hash, $roleUser, $noKontak, $status);
        $ok = $stmt->execute();
        $stmt->close();

        if (!$ok) out(false, null, 'Gagal menambahkan user.');
        out(true, ['message' => 'User berhasil ditambahkan.']);
    }

    if ($action === 'update') {
        $id = (int) getParam('id_user', 0);
        $nama = trim(getParam('nama', ''));
        $username = trim(getParam('username', ''));
        $password = getParam('password', '');
        $roleUser = trim(getParam('role', 'pemohon'));
        $noKontak = trim(getParam('no_kontak', ''));
        $status = trim(getParam('status', 'active'));

        if ($id <= 0 || $nama === '' || $username === '') {
            out(false, null, 'Data tidak lengkap.');
        }

        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE `user`
                    SET nama=?, username=?, password=?, role=?, no_kontak=?, status=?
                    WHERE id_user=?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param('ssssssi', $nama, $username, $hash, $roleUser, $noKontak, $status, $id);
        } else {
            $sql = "UPDATE `user`
                    SET nama=?, username=?, role=?, no_kontak=?, status=?
                    WHERE id_user=?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param('sssssi', $nama, $username, $roleUser, $noKontak, $status, $id);
        }

        $ok = $stmt->execute();
        $stmt->close();

        if (!$ok) out(false, null, 'Gagal memperbarui user.');
        out(true, ['message' => 'User berhasil diperbarui.']);
    }

    if ($action === 'validate') {
        $id = (int) getParam('id_user', 0);
        $status = trim(getParam('status', 'active'));

        if ($id <= 0 || !in_array($status, ['active', 'rejected'], true)) {
            out(false, null, 'Data tidak valid.');
        }

        $sql = "UPDATE `user` SET status=? WHERE id_user=?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('si', $status, $id);
        $ok = $stmt->execute();
        $stmt->close();

        if (!$ok) out(false, null, 'Gagal mengubah status user.');
        out(true, ['message' => 'Status user diperbarui.']);
    }

    if ($action === 'delete') {
        $id = (int) getParam('id_user', getParam('id', 0));
        if ($id <= 0) out(false, null, 'ID tidak valid.');

        $blocked = findUserDependencies($db, $id);
        if (!empty($blocked)) {
            out(false, null, 'User tidak bisa dihapus karena masih dipakai di: ' . implode(', ', $blocked) . '.');
        }

        // Use direct query to avoid bind_param issues on some clients
        $sql = "DELETE FROM `user` WHERE id_user = " . $id;
        $ok = $db->query($sql);

        if (!$ok) out(false, null, 'Gagal hapus user. Pastikan tidak ada relasi transaksi.');
        out(true, ['message' => 'User berhasil dihapus.']);
    }

    if ($action === 'rehash') {
        if ($role !== 'admin') {
            out(false, null, 'Akses ditolak. Hanya admin yang boleh rehash.');
        }

        $res = $db->query("SELECT id_user, password FROM `user`");
        $updated = 0;
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $id = (int) $row['id_user'];
                $pwd = (string) ($row['password'] ?? '');
                if ($pwd === '') continue;
                if (preg_match('/^\\$2[aby]\\$/', $pwd)) continue;
                $hash = password_hash($pwd, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE `user` SET password = ? WHERE id_user = ?");
                $stmt->bind_param('si', $hash, $id);
                if ($stmt->execute()) $updated++;
                $stmt->close();
            }
        }

        out(true, ['updated' => $updated]);
    }

    out(false, null, 'Action tidak dikenali.');
} catch (Throwable $e) {
    out(false, null, $e->getMessage());
}
