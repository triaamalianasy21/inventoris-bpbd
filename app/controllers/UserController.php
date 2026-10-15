<?php
// ===================================================
// CONTROLLER: USER MANAGEMENT (ADMIN)
// ===================================================

require_once __DIR__ . '/../models/Koneksi.php';

class UserController
{
    private $conn;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->conn = Koneksi::connect();
    }

    public function index()
    {
        $q = trim($_GET['q'] ?? '');

        $perPage = 10;
        $pageActive = max(1, (int)($_GET['page_active'] ?? 1));
        $pagePending = max(1, (int)($_GET['page_pending'] ?? 1));

        $activeData = $this->fetchUsersByStatusPaged('active', $q, $pageActive, $perPage);
        $activeUsers = $this->attachDeleteFlags($activeData['rows']);
        $activeData['rows'] = $activeUsers;

        $pendingData = $this->fetchUsersByStatusPaged('pending', $q, $pagePending, $perPage);
        $pendingUsers = $pendingData['rows'];

        $rejectedUsers = $this->fetchUsersByStatus('rejected', $q);
        $rejectedUsers = $this->attachDeleteFlags($rejectedUsers);

        $title = "Kelola User - PRANATA";
        include __DIR__ . '/../views/users/index.php';
    }

    public function tambah()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nama = trim($_POST['nama'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $password = (string)($_POST['password'] ?? '');
            $role = trim($_POST['role'] ?? 'pemohon');
            $noKontak = trim($_POST['no_kontak'] ?? '');
            $status = trim($_POST['status'] ?? 'active');

            if ($nama === '' || $username === '' || $password === '') {
                $_SESSION['flash_error'] = 'Nama, username, dan password wajib diisi.';
                header('Location: index.php?page=users_tambah');
                exit;
            }

            if ($this->usernameExists($username)) {
                $_SESSION['flash_error'] = 'Username sudah digunakan.';
                header('Location: index.php?page=users_tambah');
                exit;
            }

            $status = $this->normalizeStatus($status);
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $this->conn->prepare("
                INSERT INTO `user` (nama, username, password, role, no_kontak, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $noKontakParam = $noKontak !== '' ? $noKontak : null;
            $stmt->bind_param('ssssss', $nama, $username, $passwordHash, $role, $noKontakParam, $status);
            $ok = $stmt->execute();

            if ($ok) {
                $_SESSION['flash_ok'] = 'User berhasil ditambahkan.';
                header('Location: index.php?page=users');
                exit;
            }

            $_SESSION['flash_error'] = 'Gagal menambah user.';
            header('Location: index.php?page=users_tambah');
            exit;
        }

        $title = "Tambah User - PRANATA";
        include __DIR__ . '/../views/users/tambah.php';
    }

    public function ubah()
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            die('ID tidak ditemukan.');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nama = trim($_POST['nama'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $password = (string)($_POST['password'] ?? '');
            $role = trim($_POST['role'] ?? 'pemohon');
            $noKontak = trim($_POST['no_kontak'] ?? '');
            $status = trim($_POST['status'] ?? 'active');

            if ($nama === '' || $username === '') {
                $_SESSION['flash_error'] = 'Nama dan username wajib diisi.';
                header('Location: index.php?page=users_ubah&id=' . $id);
                exit;
            }

            if ($this->usernameExists($username, $id)) {
                $_SESSION['flash_error'] = 'Username sudah digunakan.';
                header('Location: index.php?page=users_ubah&id=' . $id);
                exit;
            }

            $status = $this->normalizeStatus($status);

            if ($password !== '') {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $this->conn->prepare("
                    UPDATE `user`
                    SET nama = ?, username = ?, password = ?, role = ?, no_kontak = ?, status = ?
                    WHERE id_user = ?
                ");
                $noKontakParam = $noKontak !== '' ? $noKontak : null;
                $stmt->bind_param('ssssssi', $nama, $username, $passwordHash, $role, $noKontakParam, $status, $id);
            } else {
                $stmt = $this->conn->prepare("
                    UPDATE `user`
                    SET nama = ?, username = ?, role = ?, no_kontak = ?, status = ?
                    WHERE id_user = ?
                ");
                $noKontakParam = $noKontak !== '' ? $noKontak : null;
                $stmt->bind_param('sssssi', $nama, $username, $role, $noKontakParam, $status, $id);
            }

            $ok = $stmt->execute();
            if ($ok) {
                $_SESSION['flash_ok'] = 'User berhasil diperbarui.';
                header('Location: index.php?page=users');
                exit;
            }

            $_SESSION['flash_error'] = 'Gagal memperbarui user.';
            header('Location: index.php?page=users_ubah&id=' . $id);
            exit;
        }

        $user = $this->getUserById($id);
        if (!$user) {
            die('Data user tidak ditemukan.');
        }

        $title = "Ubah User - PRANATA";
        include __DIR__ . '/../views/users/ubah.php';
    }

    public function hapus()
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            die('ID tidak ditemukan.');
        }

        $blocked = $this->findUserDependencies($id);
        if (!empty($blocked)) {
            $_SESSION['flash_error'] = 'User tidak bisa dihapus karena masih dipakai di: ' . implode(', ', $blocked) . '.';
            header('Location: index.php?page=users');
            exit;
        }

        try {
            $stmt = $this->conn->prepare("DELETE FROM `user` WHERE id_user = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $_SESSION['flash_ok'] = 'User berhasil dihapus.';
        } catch (mysqli_sql_exception $e) {
            $_SESSION['flash_error'] = 'User tidak bisa dihapus karena masih terkait data lain.';
        }

        header('Location: index.php?page=users');
        exit;
    }

    public function setStatus()
    {
        $id = (int)($_GET['id'] ?? 0);
        $status = $this->normalizeStatus($_GET['status'] ?? '');

        if ($id <= 0 || $status === '') {
            die('Data tidak valid.');
        }

        if (!in_array($status, ['active', 'rejected'], true)) {
            die('Status tidak valid.');
        }

        $stmt = $this->conn->prepare("UPDATE `user` SET status = ? WHERE id_user = ?");
        $stmt->bind_param('si', $status, $id);
        $stmt->execute();

        $_SESSION['flash_ok'] = $status === 'active'
            ? 'User berhasil diaktifkan.'
            : 'User berhasil ditolak.';
        header('Location: index.php?page=users');
        exit;
    }

    private function fetchUsersByStatus($status, $q)
    {
        $status = $this->normalizeStatus($status);
        $q = trim($q);
        if ($q !== '') {
            $like = '%' . $q . '%';
            $stmt = $this->conn->prepare("
                SELECT id_user, nama, username, role, no_kontak, status
                FROM `user`
                WHERE status = ?
                  AND (nama LIKE ? OR username LIKE ? OR role LIKE ? OR no_kontak LIKE ?)
                ORDER BY id_user DESC
            ");
            $stmt->bind_param('sssss', $status, $like, $like, $like, $like);
        } else {
            $stmt = $this->conn->prepare("
                SELECT id_user, nama, username, role, no_kontak, status
                FROM `user`
                WHERE status = ?
                ORDER BY id_user DESC
            ");
            $stmt->bind_param('s', $status);
        }

        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    private function fetchUsersByStatusPaged($status, $q, $page, $perPage)
    {
        $status = $this->normalizeStatus($status);
        $q = trim($q);
        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        $offset = ($page - 1) * $perPage;

        if ($q !== '') {
            $like = '%' . $q . '%';
            $countStmt = $this->conn->prepare("
                SELECT COUNT(*) AS total
                FROM `user`
                WHERE status = ?
                  AND (nama LIKE ? OR username LIKE ? OR role LIKE ? OR no_kontak LIKE ?)
            ");
            $countStmt->bind_param('sssss', $status, $like, $like, $like, $like);
            $countStmt->execute();
            $total = (int)($countStmt->get_result()->fetch_assoc()['total'] ?? 0);

            $stmt = $this->conn->prepare("
                SELECT id_user, nama, username, role, no_kontak, status
                FROM `user`
                WHERE status = ?
                  AND (nama LIKE ? OR username LIKE ? OR role LIKE ? OR no_kontak LIKE ?)
                ORDER BY id_user DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->bind_param('sssssii', $status, $like, $like, $like, $like, $perPage, $offset);
        } else {
            $countStmt = $this->conn->prepare("
                SELECT COUNT(*) AS total
                FROM `user`
                WHERE status = ?
            ");
            $countStmt->bind_param('s', $status);
            $countStmt->execute();
            $total = (int)($countStmt->get_result()->fetch_assoc()['total'] ?? 0);

            $stmt = $this->conn->prepare("
                SELECT id_user, nama, username, role, no_kontak, status
                FROM `user`
                WHERE status = ?
                ORDER BY id_user DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->bind_param('sii', $status, $perPage, $offset);
        }

        $stmt->execute();
        $res = $stmt->get_result();
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $pages = max(1, (int)ceil($total / $perPage));

        return [
            'rows' => $rows,
            'page' => $page,
            'pages' => $pages,
            'total' => $total
        ];
    }

    private function getUserById($id)
    {
        $stmt = $this->conn->prepare("
            SELECT id_user, nama, username, role, no_kontak, status
            FROM `user`
            WHERE id_user = ?
            LIMIT 1
        ");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    private function usernameExists($username, $excludeId = 0)
    {
        if ($excludeId > 0) {
            $stmt = $this->conn->prepare("SELECT id_user FROM `user` WHERE username = ? AND id_user != ? LIMIT 1");
            $stmt->bind_param('si', $username, $excludeId);
        } else {
            $stmt = $this->conn->prepare("SELECT id_user FROM `user` WHERE username = ? LIMIT 1");
            $stmt->bind_param('s', $username);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        return $res && $res->num_rows > 0;
    }

    private function normalizeStatus($status)
    {
        $status = strtolower(trim((string)$status));
        if (in_array($status, ['pending', 'active', 'rejected'], true)) {
            return $status;
        }
        return 'pending';
    }

    private function findUserDependencies($userId)
    {
        $blocked = [];
        $dbName = null;

        $dbRes = $this->conn->query("SELECT DATABASE() AS db");
        if ($dbRes) {
            $row = $dbRes->fetch_assoc();
            $dbName = $row['db'] ?? null;
        }

        if ($dbName) {
            $stmt = $this->conn->prepare("
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
                        $check = $this->conn->prepare("SELECT 1 FROM `{$table}` WHERE `{$column}` = ? LIMIT 1");
                        if (!$check) {
                            continue;
                        }
                        $check->bind_param('i', $userId);
                        $check->execute();
                        $hit = $check->get_result();
                        if ($hit && $hit->num_rows > 0) {
                            $blocked[] = $table;
                        }
                    }
                }
            }
        }

        return array_values(array_unique($blocked));
    }

    private function attachDeleteFlags($users)
    {
        foreach ($users as $idx => $row) {
            $userId = (int)($row['id_user'] ?? 0);
            $users[$idx]['can_delete'] = $userId > 0 && empty($this->findUserDependencies($userId));
        }
        return $users;
    }
}
