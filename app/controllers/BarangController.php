<?php
// ===================================================
// Barang Controller — CRUD dinamis berdasarkan struktur tabel
// ===================================================

class BarangController
{
    private $conn;
    private $peminjamanModel;

    public function __construct()
    {
        require_once __DIR__ . '/../models/Koneksi.php';
        require_once __DIR__ . '/../models/PeminjamanModel.php';
        $this->conn = Koneksi::connect();
        $this->peminjamanModel = new PeminjamanModel();

        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    // Helper: deteksi primary key dari tabel
    private function detectPrimaryKey($table)
    {
        $res = $this->conn->query("SHOW COLUMNS FROM `{$table}`");
        if (!$res) return null;
        while ($col = $res->fetch_assoc()) {
            if (!empty($col['Key']) && strtoupper($col['Key']) === 'PRI') return $col['Field'];
        }
        // fallback: cari kata "id" di nama kolom
        $res->data_seek(0);
        while ($col = $res->fetch_assoc()) {
            if (preg_match('/(^|_)id$|^id(_|$)/i', $col['Field'])) return $col['Field'];
        }
        // fallback ke kolom pertama
        $res->data_seek(0);
        $first = $res->fetch_assoc();
        return $first ? $first['Field'] : null;
    }

    // Helper: ambil metadata kolom
    private function getColumns($table)
    {
        $res = $this->conn->query("SHOW COLUMNS FROM `{$table}`");
        $cols = [];
        if ($res) {
            while ($c = $res->fetch_assoc()) $cols[] = $c;
        }
        return $cols;
    }

    // Helper: ambil daftar lokasi gudang untuk select id_lokasi
    private function getLokasiOptions()
    {
        // prefer lokasi_gudang if available (matches FK barang.id_lokasi)
        $tbl = $this->conn->query("SHOW TABLES LIKE 'lokasi_gudang'");
        if ($tbl && $tbl->num_rows) {
            $cols = $this->conn->query("SHOW COLUMNS FROM `lokasi_gudang`");
            $hasId = false; $hasNama = false;
            if ($cols) {
                while ($c = $cols->fetch_assoc()) {
                    if ($c['Field'] === 'id_lokasi') $hasId = true;
                    if ($c['Field'] === 'nama_lokasi') $hasNama = true;
                }
            }
            if ($hasId && $hasNama) {
                $res = $this->conn->query("SELECT id_lokasi AS id, nama_lokasi AS label FROM lokasi_gudang ORDER BY nama_lokasi ASC");
                $rows = [];
                if ($res) {
                    while ($r = $res->fetch_assoc()) $rows[] = $r;
                }
                return $rows;
            }
        }

        $candidates = [
            ['table' => 'lokasi_gudang', 'name' => 'nama_lokasi'],
            ['table' => 'lokasi', 'name' => 'nama'],
            ['table' => 'lokasi', 'name' => 'lokasi'],
            ['table' => 'gudang', 'name' => 'nama_gudang'],
        ];

        foreach ($candidates as $c) {
            $t = $this->conn->real_escape_string($c['table']);
            $n = $this->conn->real_escape_string($c['name']);
            $tblExists = $this->conn->query("SHOW TABLES LIKE '{$t}'");
            if (!$tblExists || !$tblExists->num_rows) continue;

            $colExists = $this->conn->query("SHOW COLUMNS FROM `{$t}` LIKE '{$n}'");
            if (!$colExists || !$colExists->num_rows) continue;

            $pk = $this->detectPrimaryKey($c['table']) ?? 'id';
            $pkEsc = $this->conn->real_escape_string($pk);
            $sql = "SELECT `{$pkEsc}` AS id, `{$n}` AS label FROM `{$t}` ORDER BY `{$n}` ASC";
            $res = $this->conn->query($sql);
            $rows = [];
            if ($res) {
                while ($r = $res->fetch_assoc()) $rows[] = $r;
            }
            return $rows;
        }

        return [];
    }

    // Helper: hitung status barang berdasarkan peminjaman aktif
    private function getStatusBarangLabel($barangId)
    {
        $itemsMap = $this->peminjamanModel->getItemsMapping();
        if (empty($itemsMap['items_table']) || empty($itemsMap['peminjaman_col']) || empty($itemsMap['barang_col'])) {
            return 'Tersedia';
        }

        $it = $this->conn->real_escape_string($itemsMap['items_table']);
        $pCol = $this->conn->real_escape_string($itemsMap['peminjaman_col']);
        $bCol = $this->conn->real_escape_string($itemsMap['barang_col']);

        $sql = "SELECT MAX(CASE
                            WHEN p.status IN ('waiting_verification','pending') THEN 3
                            WHEN p.status = 'approved' THEN 2
                            ELSE 0
                          END) AS st
                FROM `{$it}` pi
                JOIN peminjaman p ON p.`{$pCol}` = pi.`{$pCol}`
                WHERE pi.`{$bCol}` = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $barangId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $st = (int)($row['st'] ?? 0);

        if ($st === 3) return 'Menunggu Verifikasi';
        if ($st === 2) return 'Menunggu Pengembalian';
        return 'Tersedia';
    }

    // ===================================================
    // TAMBAH BARANG
    // ===================================================
    public function tambah()
    {
        $table = 'barang';
        $pk = $this->detectPrimaryKey($table);
        $cols = $this->getColumns($table);
        $lokasiOptions = $this->getLokasiOptions();
        $role = $_SESSION['role'] ?? '';
        $canVerify = ($role === 'kalaksa');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Kumpulkan kolom yang akan disimpan
            $fields = [];
            $placeholders = [];
            $values = [];
            $types = '';
            $hasStatusBrg = false;
            $statusBrgSet = false;

            foreach ($cols as $col) {
                $name = $col['Field'];
                // skip primary and auto_increment
                if ($name === $pk) continue;
                if (strpos(strtolower($col['Extra'] ?? ''), 'auto_increment') !== false) continue;
                if ($name === 'status_brg') {
                    $hasStatusBrg = true;
                    if (!$canVerify) continue;
                }

                // file upload handling for foto/ gambar
                if (!empty($_FILES[$name]['name'])) {
                    $folder = dirname(__DIR__, 2) . "/public/uploads/barang/";
                    if (!is_dir($folder)) mkdir($folder, 0777, true);
                    $ext = strtolower(pathinfo($_FILES[$name]['name'], PATHINFO_EXTENSION));
                    $fileName = 'barang_' . $name . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES[$name]['tmp_name'], $folder . $fileName)) {
                        $fields[] = $name;
                        $placeholders[] = '?';
                        $values[] = $fileName;
                        $types .= 's';
                    }
                    continue;
                }

                // normal input
                if (isset($_POST[$name])) {
                    $fields[] = $name;
                    $placeholders[] = '?';
                    $values[] = $_POST[$name] === '' ? null : $_POST[$name];
                    if ($name === 'status_brg') $statusBrgSet = true;

                    // map types crudely
                    if (preg_match('/^int|^tinyint|^smallint|^mediumint/i', $col['Type'])) $types .= 'i';
                    else $types .= 's';
                }
            }

            if ($hasStatusBrg && !$statusBrgSet) {
                $fields[] = 'status_brg';
                $placeholders[] = '?';
                $values[] = 'menunggu_verifikasi';
                $types .= 's';
            }

            if (!empty($fields)) {
                $sql = "INSERT INTO `{$table}` (" . implode(',', $fields) . ") VALUES (" . implode(',', $placeholders) . ")";
                $stmt = $this->conn->prepare($sql);
                if ($stmt) {
                    if (!empty($values)) {
                        $stmt->bind_param($types, ...$values);
                    }
                    $stmt->execute();
                } else {
                    die('Gagal mempersiapkan query: ' . $this->conn->error);
                }
            }

            header('Location: index.php?page=admin');
            exit;
        }

        include __DIR__ . '/../views/barang/tambah.php';
    }

    // ===================================================
    // UBAH BARANG
    // ===================================================
    public function ubah()
    {
        $table = 'barang';
        $pk = $this->detectPrimaryKey($table);
        $cols = $this->getColumns($table);
        $lokasiOptions = $this->getLokasiOptions();
        $role = $_SESSION['role'] ?? '';
        $canVerify = ($role === 'kalaksa');

        $id = $_GET['id'] ?? null;
        if (!$id) die('ID barang tidak ditemukan.');

        // ambil data
        $stmt = $this->conn->prepare("SELECT * FROM `{$table}` WHERE `{$pk}` = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();
        if (!$data) die('Data barang tidak ditemukan.');
        $statusBarangLabel = $this->getStatusBarangLabel((int)$id);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $setParts = [];
            $values = [];
            $types = '';
            $hasStatusBrg = false;

            foreach ($cols as $col) {
                $name = $col['Field'];
                if ($name === $pk) continue;
                if (strpos(strtolower($col['Extra'] ?? ''), 'auto_increment') !== false) continue;
                if ($name === 'status_brg') $hasStatusBrg = true;
                if ($name === 'status_brg' && !$canVerify) continue;

                // file upload handling
                if (!empty($_FILES[$name]['name'])) {
                    $folder = dirname(__DIR__, 2) . "/public/uploads/barang/";
                    if (!is_dir($folder)) mkdir($folder, 0777, true);
                    $ext = strtolower(pathinfo($_FILES[$name]['name'], PATHINFO_EXTENSION));
                    $fileName = 'barang_' . $name . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES[$name]['tmp_name'], $folder . $fileName)) {
                        // hapus lama
                        if (!empty($data[$name]) && file_exists($folder . $data[$name])) unlink($folder . $data[$name]);
                        $setParts[] = "`{$name}` = ?";
                        $values[] = $fileName; $types .= 's';
                    }
                    continue;
                }

                if (isset($_POST[$name])) {
                    $setParts[] = "`{$name}` = ?";
                    $values[] = $_POST[$name] === '' ? null : $_POST[$name];
                    if (preg_match('/^int|^tinyint|^smallint|^mediumint/i', $col['Type'])) $types .= 'i';
                    else $types .= 's';
                }
            }

            // Jika data diubah, reset status verifikasi ke menunggu_verifikasi
            if ($hasStatusBrg && !empty($setParts)) {
                $setParts[] = "`status_brg` = ?";
                $values[] = 'menunggu_verifikasi';
                $types .= 's';
            }

            if (!empty($setParts)) {
                $sql = "UPDATE `{$table}` SET " . implode(', ', $setParts) . " WHERE `{$pk}` = ?";
                $stmt = $this->conn->prepare($sql);
                if ($stmt) {
                    $types .= 'i';
                    $stmt->bind_param($types, ...array_merge($values, [$id]));
                    $stmt->execute();
                } else {
                    die('Gagal mempersiapkan query update: ' . $this->conn->error);
                }
            }

            header('Location: index.php?page=admin');
            exit;
        }

        include __DIR__ . '/../views/barang/ubah.php';
    }

    // ===================================================
    // HAPUS BARANG
    // ===================================================
    public function hapus()
    {
        $table = 'barang';
        $pk = $this->detectPrimaryKey($table);

        $id = $_GET['id'] ?? null;
        if (!$id) die('ID barang tidak ditemukan.');

        $stmt = $this->conn->prepare("SELECT status_brg FROM `{$table}` WHERE `{$pk}` = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) {
            $_SESSION['flash_error'] = 'Data barang tidak ditemukan.';
            header('Location: index.php?page=admin');
            exit;
        }
        $statusRaw = strtolower(trim((string)($row['status_brg'] ?? '')));
        $allowedDelete = in_array($statusRaw, ['pending','menunggu','menunggu verifikasi','menunggu_verifikasi','waiting_verification','ditolak','rejected',''], true);
        $loanLabel = $this->getStatusBarangLabel((int)$id);
        if (!$allowedDelete || $loanLabel !== 'Tersedia') {
            $_SESSION['flash_error'] = 'Barang tidak bisa dihapus karena status sudah diproses atau sedang dipinjam.';
            header('Location: index.php?page=admin');
            exit;
        }

        [$ok, $msg] = $this->createDeleteRequest('barang', (int)$id);
        if ($ok) {
            $_SESSION['flash_ok'] = $msg;
        } else {
            $_SESSION['flash_error'] = $msg;
        }
        header('Location: index.php?page=admin');
        exit;
    }

    // ===================================================
    // DETAIL BARANG
    // ===================================================
    public function detail()
    {
        $table = 'barang';
        $pk = $this->detectPrimaryKey($table);

        $id = $_GET['id'] ?? null;
        if (!$id) die('ID barang tidak ditemukan.');

        $stmt = $this->conn->prepare("SELECT * FROM `{$table}` WHERE `{$pk}` = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();

        include __DIR__ . '/../views/barang/detail.php';
    }

    private function tableExists($table)
    {
        $t = $this->conn->real_escape_string($table);
        $res = $this->conn->query("SHOW TABLES LIKE '{$t}'");
        return $res && $res->num_rows > 0;
    }

    private function createDeleteRequest($module, $targetId)
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return [false, 'Sesi tidak valid.'];
        }
        if (!$this->tableExists('delete_requests')) {
            return [false, 'Tabel delete_requests belum tersedia.'];
        }
        $stmt = $this->conn->prepare("SELECT id_request FROM delete_requests WHERE module = ? AND target_id = ? AND status = 'pending' LIMIT 1");
        $stmt->bind_param('si', $module, $targetId);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        if ($exists) {
            return [false, 'Permintaan penghapusan sudah diajukan dan menunggu verifikasi.'];
        }
        $stmt = $this->conn->prepare("INSERT INTO delete_requests (module, target_id, requested_by, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
        $stmt->bind_param('sii', $module, $targetId, $userId);
        if (!$stmt->execute()) {
            return [false, 'Gagal mengajukan penghapusan.'];
        }
        return [true, 'Permintaan penghapusan dikirim. Data akan dihapus setelah Kalaksa menyetujui.'];
    }
}
