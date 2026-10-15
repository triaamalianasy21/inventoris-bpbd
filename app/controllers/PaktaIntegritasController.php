<?php
// ===================================================
// Pakta Integritas Controller (Pemohon)
// ===================================================
require_once __DIR__ . '/../models/Koneksi.php';
require_once __DIR__ . '/../models/PeminjamanModel.php';
require_once __DIR__ . '/../config/auth_helper.php';
require_once __DIR__ . '/../../vendor/autoload.php';

class PaktaIntegritasController
{
    private $conn;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->conn = Koneksi::connect();
    }

    public function index()
    {
        requireRole('pemohon');

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            header('Location: index.php?page=login');
            exit;
        }

        if (!$this->tableExists('pakta_integritas')) {
            die('Tabel pakta_integritas tidak ditemukan.');
        }

        $cols = $this->getColumns('pakta_integritas');
        $pk = $this->detectPrimaryKey('pakta_integritas');
        $userCol = $this->detectUserColumn($cols);
        $statusCol = $this->hasColumn($cols, 'status') ? 'status' : null;
        $peminjamanCol = $this->hasColumn($cols, 'peminjaman_id') ? 'peminjaman_id' : ($this->hasColumn($cols, 'id_pinjam') ? 'id_pinjam' : null);
        $colNama = $this->findColumn($cols, ['nama','nama_lengkap','nama_peminjam']);
        $colNip = $this->findColumn($cols, ['nip_nik','nip','nik','nrp','nip_nrp_nik']);
        $colJabatan = $this->findColumn($cols, ['jabatan','jabatan_peminjam']);
        $colBarang = $this->findColumn($cols, ['barang_digunakan','barang_milik_daerah','barang','nama_barang']);

        $peminjamanId = isset($_GET['id']) ? (int)$_GET['id'] : null;

        $data = $this->getLatestPakta($userCol, $userId, $peminjamanCol, $peminjamanId, $pk);
        $prefill = [];
        if ($peminjamanId) {
            $prefill = $this->getPrefillFromPeminjaman($peminjamanId, $colNama, $colNip, $colJabatan, $colBarang);
        }

        // Prefill dari peminjaman jika masih kosong
        if ($peminjamanId && empty($data)) {
            if (!empty($prefill)) $data = $prefill;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $fields = [];
            $values = [];
            $types = '';

            foreach ($cols as $col) {
                $name = $col['Field'];
                if ($name === $pk) continue;
                if (strpos(strtolower($col['Extra'] ?? ''), 'auto_increment') !== false) continue;
                if ($name === $userCol || $name === $statusCol || $name === 'created_at' || $name === 'verified_by' || $name === 'verified_at') continue;
                if ($name === $peminjamanCol) continue;

                if (isset($_POST[$name])) {
                    if ($peminjamanId) {
                        if ($colNama && $name === $colNama && isset($prefill[$colNama])) {
                            $_POST[$name] = $prefill[$colNama];
                        }
                        if ($colNip && $name === $colNip && isset($prefill[$colNip])) {
                            $_POST[$name] = $prefill[$colNip];
                        }
                        if ($colJabatan && $name === $colJabatan && isset($prefill[$colJabatan])) {
                            $_POST[$name] = $prefill[$colJabatan];
                        }
                    }
                    $fields[] = $name;
                    $values[] = $_POST[$name] === '' ? null : $_POST[$name];
                    if (preg_match('/^int|^tinyint|^smallint|^mediumint/i', $col['Type'])) $types .= 'i';
                    else $types .= 's';
                }
            }

            if ($userCol) {
                $fields[] = $userCol;
                $values[] = $userId;
                $types .= 'i';
            }
            if ($statusCol) {
                $fields[] = $statusCol;
                $values[] = 'filled';
                $types .= 's';
            }
            if ($peminjamanCol && $peminjamanId) {
                $fields[] = $peminjamanCol;
                $values[] = $peminjamanId;
                $types .= 'i';
            }

            if (empty($fields)) {
                $_SESSION['flash_error'] = 'Field pakta belum lengkap.';
                header('Location: index.php?page=pakta_integritas' . ($peminjamanId ? '&id=' . (int)$peminjamanId : ''));
                exit;
            }

            if (!empty($data[$pk] ?? null)) {
                $set = [];
                foreach ($fields as $f) $set[] = "`{$f}` = ?";
                $sql = "UPDATE pakta_integritas SET " . implode(', ', $set) . " WHERE `{$pk}` = ?";
                $stmt = $this->conn->prepare($sql);
                $typesUpdate = $types . 'i';
                $valuesUpdate = array_merge($values, [$data[$pk]]);
                $stmt->bind_param($typesUpdate, ...$valuesUpdate);
                $stmt->execute();
                $id = $data[$pk];
            } else {
                $sql = "INSERT INTO pakta_integritas (`" . implode('`,`', $fields) . "`) VALUES (" . rtrim(str_repeat('?,', count($fields)), ',') . ")";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param($types, ...$values);
                $stmt->execute();
                $id = $this->conn->insert_id;
            }

            if ($id) {
                header('Location: index.php?page=pakta_integritas_preview&id=' . (int)$id);
                exit;
            }
        }

        $title = "Pakta Integritas - PRANATA";
        include __DIR__ . '/../views/pakta_integritas/form.php';
    }

    public function print()
    {
        requireRole('pemohon');

        $id = $_GET['id'] ?? null;
        if (!$id) die('ID tidak ditemukan');

        $data = $this->getPaktaById($id);
        if (!$data) die('Data tidak ditemukan');

        $title = "Cetak Pakta Integritas";
        include __DIR__ . '/../views/pakta_integritas/print.php';
    }

    public function download()
    {
        requireRole('pemohon');

        $id = $_GET['id'] ?? null;
        if (!$id) die('ID tidak ditemukan');

        $data = $this->getPaktaById($id);
        if (!$data) die('Data tidak ditemukan');

        $isPdf = true;
        ob_start();
        include __DIR__ . '/../views/pakta_integritas/print.php';
        $html = ob_get_clean();

        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'Times New Roman');
        $options->set('chroot', realpath(__DIR__ . '/../../'));
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'pakta_integritas_' . (int)$id . '.pdf';
        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    }

    public function preview()
    {
        requireRole('pemohon');

        $id = $_GET['id'] ?? null;
        if (!$id) die('ID tidak ditemukan');

        $data = $this->getPaktaById($id);
        if (!$data) die('Data tidak ditemukan');

        $userId = $_SESSION['user_id'] ?? null;
        $userCol = null;
        if ($userId !== null) {
            $cols = $this->getColumns('pakta_integritas');
            $userCol = $this->detectUserColumn($cols);
        }
        if ($userId && $userCol && isset($data[$userCol]) && (int)$data[$userCol] !== (int)$userId) {
            die('Akses ditolak');
        }

        $pageTitle = 'Preview Pakta Integritas';
        $iframeSrc = 'index.php?page=pakta_integritas_print&id=' . (int)$id;
        $downloadUrl = 'index.php?page=pakta_integritas_download&id=' . (int)$id;
        $backUrl = 'peminjaman';
        include __DIR__ . '/../views/preview/print_preview.php';
    }

    private function getPaktaById($id)
    {
        $pk = $this->detectPrimaryKey('pakta_integritas');
        $stmt = $this->conn->prepare("SELECT * FROM pakta_integritas WHERE `{$pk}` = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    private function tableExists($table)
    {
        $t = $this->conn->real_escape_string($table);
        $res = $this->conn->query("SHOW TABLES LIKE '{$t}'");
        return $res && $res->num_rows > 0;
    }

    private function getColumns($table)
    {
        $res = $this->conn->query("SHOW COLUMNS FROM `{$table}`");
        $cols = [];
        if ($res) while ($c = $res->fetch_assoc()) $cols[] = $c;
        return $cols;
    }

    private function detectPrimaryKey($table)
    {
        $res = $this->conn->query("SHOW COLUMNS FROM `{$table}`");
        if (!$res) return 'id';
        $cols = [];
        while ($col = $res->fetch_assoc()) {
            $cols[] = $col;
            if (!empty($col['Key']) && strtoupper($col['Key']) === 'PRI') return $col['Field'];
        }

        $fallbacks = ['id_pakta', 'pakta_id', 'id'];
        foreach ($fallbacks as $f) {
            foreach ($cols as $col) {
                if ($col['Field'] === $f) return $f;
            }
        }
        return !empty($cols) ? $cols[0]['Field'] : 'id';
    }

    private function detectUserColumn($cols)
    {
        $candidates = ['user_id','id_user','requested_by','pemohon_id','peminjam_id','created_by','requester'];
        foreach ($candidates as $c) {
            foreach ($cols as $col) {
                if ($col['Field'] === $c) return $c;
            }
        }
        return null;
    }

    private function hasColumn($cols, $name)
    {
        foreach ($cols as $col) {
            if ($col['Field'] === $name) return true;
        }
        return false;
    }

    private function findColumn($cols, $candidates)
    {
        foreach ($candidates as $c) {
            foreach ($cols as $col) {
                if ($col['Field'] === $c) return $c;
            }
        }
        return null;
    }

    private function getLatestPakta($userCol, $userId, $peminjamanCol, $peminjamanId, $pk)
    {
        $where = [];
        $types = '';
        $values = [];
        if ($userCol) { $where[] = "`{$userCol}` = ?"; $types .= 'i'; $values[] = $userId; }
        if ($peminjamanCol && $peminjamanId) { $where[] = "`{$peminjamanCol}` = ?"; $types .= 'i'; $values[] = $peminjamanId; }
        $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $orderSql = $pk ? " ORDER BY `{$pk}` DESC" : '';
        $sql = "SELECT * FROM pakta_integritas {$whereSql}{$orderSql} LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!empty($values)) $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? ($res->fetch_assoc() ?? []) : [];
    }

    private function getPrefillFromPeminjaman($peminjamanId, $colNama, $colNip, $colJabatan, $colBarang)
    {
        if (!$colNama && !$colNip && !$colJabatan && !$colBarang) return [];

        $res = $this->conn->query("SELECT * FROM peminjaman WHERE id_pinjam = " . (int)$peminjamanId . " LIMIT 1");
        if (!$res || !$res->num_rows) {
            $res = $this->conn->query("SELECT * FROM peminjaman WHERE id = " . (int)$peminjamanId . " LIMIT 1");
        }
        $row = $res ? $res->fetch_assoc() : null;
        if (!$row) return [];

        $data = [];
        if ($colNama) $data[$colNama] = $row['nama_pihak2'] ?? ($row['nama_pihak1'] ?? '');
        if ($colNip) $data[$colNip] = $row['nip1'] ?? ($row['nip'] ?? '');
        if ($colJabatan) $data[$colJabatan] = $row['jabatan1'] ?? ($row['jabatan'] ?? '');

        if ($colBarang) {
            // gabungkan nama barang dari detail peminjaman bila ada
            $names = [];
            $pm = new PeminjamanModel();
            $items = $pm->getItems($row['id'] ?? $peminjamanId);
            foreach ($items as $it) {
                if (!empty($it['nama_barang'])) $names[] = $it['nama_barang'];
            }
            $data[$colBarang] = !empty($names) ? implode(', ', $names) : '';
        }

        return $data;
    }
}
