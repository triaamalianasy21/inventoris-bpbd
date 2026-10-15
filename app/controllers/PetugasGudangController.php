<?php
// ===================================================
// Petugas Gudang Controller
// ===================================================
require_once __DIR__ . '/../models/Koneksi.php';
require_once __DIR__ . '/../models/PeminjamanModel.php';
require_once __DIR__ . '/../config/auth_helper.php';

class PetugasGudangController
{
    private $conn;
    private $model;

    public function __construct()
    {
        $this->conn = Koneksi::connect();
        $this->model = new PeminjamanModel();
    }

    public function dashboard()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        requireRole('petugas_gudang');

        $uid = currentUserId();
        $peminjamanToday = $this->countNotifsToday($uid, 'peminjaman:');
        $barangMasukToday = $this->countNotifsToday($uid, 'barang:');

        $title = "Dashboard Petugas Gudang - PRANATA";
        $data = [
            'peminjamanToday' => $peminjamanToday,
            'barangMasukToday' => $barangMasukToday
        ];
        include __DIR__ . '/../views/petugas_gudang/dashboard.php';
    }

    public function notifikasi()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        requireRole('petugas_gudang');

        $uid = currentUserId();
        if (!$uid) {
            header('Location: index.php?page=login');
            exit;
        }

        $notifs = getNotifications($uid);
        $title = "Notifikasi - PRANATA";
        $backUrl = 'petugas_dashboard';
        include __DIR__ . '/../views/notifications/index.php';
    }

    public function detail()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        requireRole('petugas_gudang');

        $id = $_GET['id'] ?? null;
        if (!$id) die('ID tidak ditemukan');

        $backUrl = 'petugas_notifikasi';
        $kind = $_GET['kind'] ?? 'barang';
        $id = (int)$id;

        if ($kind === 'pengembalian') {
            $detailTable = $this->detectPengembalianDetailTable();
            if (!$detailTable) {
                $_SESSION['flash_error'] = 'Detail pengembalian tidak ditemukan.';
                header('Location: index.php?page=petugas_notifikasi');
                exit;
            }

            $stmt = $this->conn->prepare("SELECT p.*, pm.* FROM pengembalian p LEFT JOIN peminjaman pm ON pm.id_pinjam = p.id_pinjam WHERE p.id_kembali = ? LIMIT 1");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_assoc();
            if (!$data) {
                $_SESSION['flash_error'] = 'Data pengembalian tidak ditemukan.';
                header('Location: index.php?page=petugas_notifikasi');
                exit;
            }

            $data['items'] = $this->model->getItems((int)($data['id_pinjam'] ?? 0));
            $dCols = $this->getTableColumns($detailTable);
            $dIdKembali = $this->pickColumn($dCols, ['id_kembali','pengembalian_id']);
            $dTgl = $this->pickColumn($dCols, ['tgl_kembali','tanggal_kembali','tgl']);
            $dFoto = $this->pickColumn($dCols, ['foto','foto_bukti']);
            $dIdBarang = $this->pickColumn($dCols, ['id_barang','barang_id']);
            if ($dIdKembali && $dTgl) {
                $stmt = $this->conn->prepare("SELECT MIN(`{$dTgl}`) AS tgl_kembali FROM `{$detailTable}` WHERE `{$dIdKembali}` = ?");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $data['tgl_kembali'] = $row['tgl_kembali'] ?? ($data['tgl_kembali'] ?? '-');
            }
            if ($dIdKembali && $dIdBarang && $dFoto) {
                $fotoMap = [];
                $stmt = $this->conn->prepare("SELECT `{$dIdBarang}` AS id_barang, `{$dFoto}` AS foto FROM `{$detailTable}` WHERE `{$dIdKembali}` = ?");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res) {
                    while ($r = $res->fetch_assoc()) {
                        $bid = (int)($r['id_barang'] ?? 0);
                        if ($bid > 0) $fotoMap[$bid] = $r['foto'] ?? '';
                    }
                }
                $data['foto_map'] = $fotoMap;
            }

            $title = "Detail Pengembalian - PRANATA";
            include __DIR__ . '/../views/pengembalian/detail.php';
            return;
        }

        $stmt = $this->conn->prepare("SELECT * FROM barang WHERE id_barang = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();
        if ($data) {
            $lokasiMap = $this->getLokasiMap();
            $title = "Detail Barang - PRANATA";
            include __DIR__ . '/../views/petugas_gudang/detail.php';
            return;
        }

        $row = $this->model->find($id);
        if (!$row) {
            $_SESSION['flash_error'] = 'Data barang tidak ditemukan.';
            header('Location: index.php?page=petugas_notifikasi');
            exit;
        }
        $title = "Detail Barang - PRANATA";
        include __DIR__ . '/../views/petugas_gudang/detail.php';
    }

    private function getByStatus($status)
    {
        $pk = $this->model->getPkColumn();
        $pkCol = $pk ? $pk : 'id';

        $stmt = $this->conn->prepare("SELECT * FROM peminjaman WHERE status = ? ORDER BY `$pkCol` DESC");
        $stmt->bind_param('s', $status);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) {
            if ($pk && !isset($r['id'])) $r['id'] = $r[$pk] ?? null;
            $rows[] = $r;
        }
        return $rows;
    }

    private function countByStatus($status)
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS cnt FROM peminjaman WHERE status = ?");
        $stmt->bind_param('s', $status);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        return (int)($r['cnt'] ?? 0);
    }

    private function countApprovedToday()
    {
        $col = null;
        $colsRes = $this->conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='peminjaman' AND COLUMN_NAME IN ('created_at','tgl_pinjam') LIMIT 1");
        if ($colsRes && $colsRes->num_rows > 0) {
            $col = $colsRes->fetch_assoc()['COLUMN_NAME'];
        }
        if (!$col) return 0;

        $sql = "SELECT COUNT(*) AS cnt FROM peminjaman WHERE status = 'approved' AND DATE(`$col`) = CURDATE()";
        $r = $this->conn->query($sql);
        $row = $r ? $r->fetch_assoc() : [];
        return (int)($row['cnt'] ?? 0);
    }

    private function countNotifsToday($userId, $typePrefix)
    {
        if (!$userId) return 0;
        $like = $typePrefix . '%';
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = ? AND type LIKE ? AND DATE(created_at) = CURDATE()");
        $stmt->bind_param('is', $userId, $like);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        return (int)($r['cnt'] ?? 0);
    }

    private function getLokasiMap()
    {
        $map = [];
        $tbl = $this->conn->query("SHOW TABLES LIKE 'lokasi_gudang'");
        if ($tbl && $tbl->num_rows) {
            $res = $this->conn->query("SELECT id_lokasi, nama_lokasi FROM lokasi_gudang");
            if ($res) {
                while ($r = $res->fetch_assoc()) {
                    $map[(string)$r['id_lokasi']] = $r['nama_lokasi'];
                }
            }
        }
        return $map;
    }

    private function detectPengembalianDetailTable()
    {
        $candidates = ['detail_pengembalian','detail_kembali','pengembalian_detail','pengembalian_items'];
        foreach ($candidates as $t) {
            $stmt = $this->conn->prepare("SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME = ?");
            $stmt->bind_param('s', $t);
            $stmt->execute();
            $r = $stmt->get_result()->fetch_assoc();
            if (((int)($r['cnt'] ?? 0)) > 0) return $t;
        }
        return null;
    }

    private function getTableColumns($table)
    {
        $res = $this->conn->query("SHOW COLUMNS FROM `{$table}`");
        $cols = [];
        if ($res) while ($c = $res->fetch_assoc()) $cols[] = $c;
        return $cols;
    }

    private function pickColumn($cols, $candidates)
    {
        foreach ($candidates as $c) {
            foreach ($cols as $col) {
                if ($col['Field'] === $c) return $c;
            }
        }
        return null;
    }
}
