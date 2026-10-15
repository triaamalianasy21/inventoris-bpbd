<?php
// ===================================================
// Kalaksa Controller
// ===================================================
require_once __DIR__ . '/../models/Koneksi.php';
require_once __DIR__ . '/../models/PeminjamanModel.php';
require_once __DIR__ . '/../config/auth_helper.php';

class KalaksaController
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
        requireRole('kalaksa');

        // ringkas statistik dari peminjaman, pengembalian, dan barang
        $waiting = $this->countWaitingAll();
        $today = $this->countTodayAll();
        $approvedMonth = $this->countApprovedThisMonthAll();

        $title = "Dashboard Kalaksa - PRANATA";
        $data = [
            'waiting' => $waiting,
            'today' => $today,
            'approvedMonth' => $approvedMonth
        ];
        include __DIR__ . '/../views/kalaksa/dashboard.php';
    }

    public function verifikasi()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        requireRole('kalaksa');

        $data = $this->getWaitingList();
        $deletePending = $this->getDeleteRequestsPeminjaman('pending');
        if (!empty($deletePending)) {
            $data = array_merge($deletePending, $data);
        }
        $title = "Verifikasi Peminjaman - PRANATA";
        include __DIR__ . '/../views/kalaksa/verifikasi.php';
    }

    public function setujui()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        requireRole('kalaksa');

        $id = $_GET['id'] ?? null;
        if (!$id) die('ID tidak ditemukan');

        $userId = $_SESSION['user_id'] ?? null;
        $this->model->setStatus($id, 'approved', $userId);

        $row = $this->model->find((int)$id);
        $nama = $row['nama_pihak2'] ?? ($row['pemohon_nama'] ?? ($row['user_id'] ?? 'pemohon'));
        $msg = "Pengajuan peminjaman atas nama {$nama} sudah disetujui.";
        foreach ($this->getPengurusIds() as $pid) {
            notifyUser($pid, 'peminjaman:approved', ['message' => $msg, 'id' => (int)$id]);
        }
        foreach ($this->getPetugasIds() as $pid) {
            notifyUser($pid, 'peminjaman:approved', ['message' => $msg, 'id' => (int)$id]);
        }

        $_SESSION['flash_ok'] = 'Peminjaman berhasil disetujui.';
        header('Location: index.php?page=kalaksa_verifikasi');
        exit;
    }

    public function tolak()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        requireRole('kalaksa');

        $id = $_GET['id'] ?? null;
        if (!$id) die('ID tidak ditemukan');

        $userId = $_SESSION['user_id'] ?? null;
        $this->model->setStatus($id, 'ditolak', $userId);

        $row = $this->model->find((int)$id);
        $nama = $row['nama_pihak2'] ?? ($row['pemohon_nama'] ?? ($row['user_id'] ?? 'pemohon'));
        $msg = "Pengajuan peminjaman atas nama {$nama} ditolak.";
        foreach ($this->getPengurusIds() as $pid) {
            notifyUser($pid, 'peminjaman:rejected', ['message' => $msg, 'id' => (int)$id]);
        }

        $_SESSION['flash_ok'] = 'Peminjaman berhasil ditolak.';
        header('Location: index.php?page=kalaksa_verifikasi');
        exit;
    }


    public function barangVerifikasi()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        requireRole('kalaksa');

        $dataPending = $this->getBarangByStatus(['menunggu_verifikasi','pending','menunggu']);
        $deletePending = $this->getDeleteRequestsBarang('pending');
        if (!empty($deletePending)) {
            $dataPending = array_merge($deletePending, $dataPending);
        }
        $lokasiMap = $this->getLokasiMap();
        $title = "Verifikasi Barang - PRANATA";
        include __DIR__ . '/../views/kalaksa/barang_verifikasi.php';
    }

    public function barangSetujui()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        requireRole('kalaksa');

        $id = $_GET['id'] ?? null;
        if (!$id) die('ID barang tidak ditemukan');

        $stmt = $this->conn->prepare("UPDATE barang SET status_brg = 'terverifikasi' WHERE id_barang = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();

        $namaBarang = '';
        $stmt = $this->conn->prepare("SELECT nama_barang FROM barang WHERE id_barang = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row && !empty($row['nama_barang'])) {
            $namaBarang = $row['nama_barang'];
        }
        $label = $namaBarang !== '' ? $namaBarang : "ID {$id}";
        $msg = "Barang masuk {$label} sudah diverifikasi.";
        foreach ($this->getPetugasIds() as $pid) {
            notifyUser($pid, 'barang:verified', ['message' => $msg, 'id' => (int)$id]);
        }

        $_SESSION['flash_ok'] = 'Barang berhasil diverifikasi.';
        header('Location: index.php?page=kalaksa_barang');
        exit;
    }

    public function barangTolak()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        requireRole('kalaksa');

        $id = $_GET['id'] ?? null;
        if (!$id) die('ID barang tidak ditemukan');

        $stmt = $this->conn->prepare("UPDATE barang SET status_brg = 'ditolak' WHERE id_barang = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();

        $_SESSION['flash_ok'] = 'Barang ditolak. Silakan admin perbaiki data.';
        header('Location: index.php?page=kalaksa_barang');
        exit;
    }

    public function barangDetail()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        requireRole('kalaksa');

        $id = $_GET['id'] ?? null;
        if (!$id) die('ID barang tidak ditemukan');

        $stmt = $this->conn->prepare("SELECT * FROM barang WHERE id_barang = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();
        if (!$data) die('Data barang tidak ditemukan');

        $lokasiMap = $this->getLokasiMap();
        $title = "Detail Barang - PRANATA";
        include __DIR__ . '/../views/kalaksa/barang_detail.php';
    }

    public function pengembalianVerifikasi()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        requireRole('kalaksa');

        $pending = $this->getPengembalianByStatus(['pending']);
        $deletePending = $this->getDeleteRequestsPengembalian('pending');
        if (!empty($deletePending)) {
            $pending = array_merge($deletePending, $pending);
        }
        $title = "Verifikasi Pengembalian - PRANATA";
        include __DIR__ . '/../views/kalaksa/pengembalian_verifikasi.php';
    }

    public function deleteRequestApprove()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        requireRole('kalaksa');

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) die('ID tidak ditemukan');

        $req = $this->getDeleteRequestById($id);
        if (!$req || ($req['status'] ?? '') !== 'pending') {
            $_SESSION['flash_error'] = 'Permintaan penghapusan tidak ditemukan.';
            header('Location: index.php?page=kalaksa_dashboard');
            exit;
        }

        $module = $req['module'];
        $targetId = (int)$req['target_id'];
        $userId = $_SESSION['user_id'] ?? null;

        $this->conn->begin_transaction();
        try {
            $this->deleteTarget($module, $targetId);
            $stmt = $this->conn->prepare("UPDATE delete_requests SET status = 'approved', verified_by = ?, verified_at = NOW() WHERE id_request = ?");
            $stmt->bind_param('ii', $userId, $id);
            $stmt->execute();
            $this->conn->commit();
        } catch (Exception $e) {
            $this->conn->rollback();
            $_SESSION['flash_error'] = 'Gagal menyetujui penghapusan.';
            header('Location: index.php?page=' . $this->redirectByModule($module));
            exit;
        }

        $requesterRole = $this->getUserRoleById((int)($req['requested_by'] ?? 0));
        if ($requesterRole === 'pengurus_barang') {
            $msg = "Permintaan penghapusan data {$module} disetujui.";
            notifyUser((int)$req['requested_by'], 'delete:approved', ['message' => $msg, 'link' => 'index.php?page=' . $this->pengurusRedirectByModule($module)]);
        }

        $_SESSION['flash_ok'] = 'Penghapusan disetujui.';
        header('Location: index.php?page=' . $this->redirectByModule($module));
        exit;
    }

    public function deleteRequestReject()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        requireRole('kalaksa');

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) die('ID tidak ditemukan');

        $req = $this->getDeleteRequestById($id);
        if (!$req || ($req['status'] ?? '') !== 'pending') {
            $_SESSION['flash_error'] = 'Permintaan penghapusan tidak ditemukan.';
            header('Location: index.php?page=kalaksa_dashboard');
            exit;
        }

        $module = $req['module'];
        $userId = $_SESSION['user_id'] ?? null;
        $stmt = $this->conn->prepare("UPDATE delete_requests SET status = 'rejected', verified_by = ?, verified_at = NOW() WHERE id_request = ?");
        $stmt->bind_param('ii', $userId, $id);
        $stmt->execute();

        $requesterRole = $this->getUserRoleById((int)($req['requested_by'] ?? 0));
        if ($requesterRole === 'pengurus_barang') {
            $msg = "Permintaan penghapusan data {$module} ditolak.";
            notifyUser((int)$req['requested_by'], 'delete:rejected', ['message' => $msg, 'link' => 'index.php?page=' . $this->pengurusRedirectByModule($module)]);
        }

        $_SESSION['flash_ok'] = 'Penghapusan ditolak.';
        header('Location: index.php?page=' . $this->redirectByModule($module));
        exit;
    }

    public function pengembalianDetail()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        requireRole('kalaksa');

        $id = $_GET['id'] ?? null;
        if (!$id) die('ID pengembalian tidak ditemukan');

        $stmt = $this->conn->prepare("SELECT p.*, pm.* FROM pengembalian p LEFT JOIN peminjaman pm ON pm.id_pinjam = p.id_pinjam WHERE p.id_kembali = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();
        if (!$data) die('Data tidak ditemukan');

        $data['items'] = $this->model->getItems((int)($data['id_pinjam'] ?? 0));
        $detailTable = $this->detectPengembalianDetailTable();
        if ($detailTable) {
            $dCols = $this->getTableColumns($detailTable);
            $dIdKembali = $this->pickColumn($dCols, ['id_kembali','pengembalian_id']);
            $dTgl = $this->pickColumn($dCols, ['tgl_kembali','tanggal_kembali','tgl']);
            $dIdBarang = $this->pickColumn($dCols, ['id_barang','barang_id']);
            $dFoto = $this->pickColumn($dCols, ['foto','foto_bukti']);
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
        }

        $title = "Detail Pengembalian - PRANATA";
        include __DIR__ . '/../views/pengembalian/detail.php';
    }

    public function pengembalianSetujui()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        requireRole('kalaksa');

        $id = $_GET['id'] ?? null;
        if (!$id) die('ID pengembalian tidak ditemukan');

        $userId = $_SESSION['user_id'] ?? null;
        $stmt = $this->conn->prepare("UPDATE pengembalian SET status = 'completed', verified_by = ?, verified_at = NOW() WHERE id_kembali = ?");
        $stmt->bind_param('ii', $userId, $id);
        $stmt->execute();

        // update peminjaman status -> completed
        $stmt = $this->conn->prepare("SELECT id_pinjam FROM pengembalian WHERE id_kembali = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $idPinjam = (int)($row['id_pinjam'] ?? 0);

        if ($idPinjam > 0) {
            $pk = $this->model->getPkColumn() ?: 'id_pinjam';
            $stmt = $this->conn->prepare("UPDATE peminjaman SET status = 'completed' WHERE `$pk` = ?");
            $stmt->bind_param('i', $idPinjam);
            $stmt->execute();

            // return stock based on items qty
            $items = $this->model->getItems($idPinjam);
            $stokCol = $this->detectStokColumn();
            if ($stokCol) {
                $stmtUp = $this->conn->prepare("UPDATE barang SET `{$stokCol}` = `{$stokCol}` + ? WHERE id_barang = ?");
                foreach ($items as $it) {
                    $bid = (int)($it['id_barang'] ?? ($it['barang_id'] ?? 0));
                    $qty = (int)($it['qty'] ?? ($it['jumlah'] ?? 1));
                    if ($bid <= 0 || $qty <= 0) continue;
                    $stmtUp->bind_param('ii', $qty, $bid);
                    $stmtUp->execute();
                }
            }

            // set availability status if column exists
            $availCol = $this->detectAvailabilityColumn();
            if ($availCol) {
                $stmtA = $this->conn->prepare("UPDATE barang SET `{$availCol}` = 'tersedia' WHERE id_barang = ?");
                foreach ($items as $it) {
                    $bid = (int)($it['id_barang'] ?? ($it['barang_id'] ?? 0));
                    if ($bid <= 0) continue;
                    $stmtA->bind_param('i', $bid);
                    $stmtA->execute();
                }
            }

            $row = $this->model->find($idPinjam);
            $nama = $row['nama_pihak2'] ?? ($row['pemohon_nama'] ?? ($row['user_id'] ?? 'pemohon'));
            $msg = "Pengembalian atas nama {$nama} sudah disetujui.";
            foreach ($this->getPengurusIds() as $pid) {
                notifyUser($pid, 'pengembalian:completed', ['message' => $msg, 'id' => (int)$id]);
            }
            foreach ($this->getPetugasIds() as $pid) {
                notifyUser($pid, 'pengembalian:completed', ['message' => $msg, 'id' => (int)$id]);
            }
        }

        $_SESSION['flash_ok'] = 'Pengembalian disetujui.';
        header('Location: index.php?page=kalaksa_pengembalian');
        exit;
    }

    public function pengembalianTolak()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        requireRole('kalaksa');

        $id = $_GET['id'] ?? null;
        if (!$id) die('ID pengembalian tidak ditemukan');

        $userId = $_SESSION['user_id'] ?? null;
        $stmt = $this->conn->prepare("UPDATE pengembalian SET status = 'ditolak', verified_by = ?, verified_at = NOW() WHERE id_kembali = ?");
        $stmt->bind_param('ii', $userId, $id);
        $stmt->execute();

        $stmt = $this->conn->prepare("SELECT id_pinjam FROM pengembalian WHERE id_kembali = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $idPinjam = (int)($row['id_pinjam'] ?? 0);
        if ($idPinjam > 0) {
            $row = $this->model->find($idPinjam);
            $nama = $row['nama_pihak2'] ?? ($row['pemohon_nama'] ?? ($row['user_id'] ?? 'pemohon'));
            $msg = "Pengembalian atas nama {$nama} ditolak.";
            foreach ($this->getPengurusIds() as $pid) {
                notifyUser($pid, 'pengembalian:rejected', ['message' => $msg, 'id' => (int)$id]);
            }
        }

        $_SESSION['flash_ok'] = 'Pengembalian ditolak.';
        header('Location: index.php?page=kalaksa_pengembalian');
        exit;
    }

    private function getBarangByStatus($statuses)
    {
        $placeholders = implode(',', array_fill(0, count($statuses), '?'));
        $sql = "SELECT * FROM barang WHERE status_brg IN ($placeholders) ORDER BY id_barang DESC";
        $stmt = $this->conn->prepare($sql);
        $types = str_repeat('s', count($statuses));
        $stmt->bind_param($types, ...$statuses);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) $rows[] = $r;
        }
        return $rows;
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

    private function getPengurusIds()
    {
        $rows = [];
        $res = $this->conn->query("SELECT id_user FROM user WHERE role = 'pengurus_barang'");
        if ($res) {
            while ($r = $res->fetch_assoc()) $rows[] = (int)$r['id_user'];
        }
        return $rows;
    }

    private function getPetugasIds()
    {
        $rows = [];
        $res = $this->conn->query("SELECT id_user FROM user WHERE role = 'petugas_gudang'");
        if ($res) {
            while ($r = $res->fetch_assoc()) $rows[] = (int)$r['id_user'];
        }
        return $rows;
    }

    private function getWaitingList()
    {
        $pk = $this->model->getPkColumn();
        $pkCol = $pk ? $pk : 'id';

        $res = $this->conn->query("SELECT * FROM peminjaman WHERE status = 'waiting_verification' ORDER BY `$pkCol` DESC");
        $rows = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                if ($pk && !isset($r['id'])) $r['id'] = $r[$pk] ?? null;
                $rows[] = $r;
            }
        }
        return $rows;
    }

    private function getApprovedList()
    {
        $pk = $this->model->getPkColumn();
        $pkCol = $pk ? $pk : 'id';

        $res = $this->conn->query("SELECT * FROM peminjaman WHERE status = 'approved' ORDER BY `$pkCol` DESC");
        $rows = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                if ($pk && !isset($r['id'])) $r['id'] = $r[$pk] ?? null;
                $rows[] = $r;
            }
        }
        return $rows;
    }

    private function getRejectedList()
    {
        $pk = $this->model->getPkColumn();
        $pkCol = $pk ? $pk : 'id';

        $res = $this->conn->query("SELECT * FROM peminjaman WHERE status IN ('rejected','ditolak') ORDER BY `$pkCol` DESC");
        $rows = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                if ($pk && !isset($r['id'])) $r['id'] = $r[$pk] ?? null;
                $rows[] = $r;
            }
        }
        return $rows;
    }

    private function getPengembalianByStatus($statuses)
    {
        $placeholders = implode(',', array_fill(0, count($statuses), '?'));
        $sql = "SELECT p.*, pm.nama_pihak1, pm.nama_pihak2, pm.tujuan, pm.tgl_pinjam
                FROM pengembalian p
                LEFT JOIN peminjaman pm ON pm.id_pinjam = p.id_pinjam
                WHERE p.status IN ($placeholders)
                ORDER BY p.id_kembali DESC";
        $stmt = $this->conn->prepare($sql);
        $types = str_repeat('s', count($statuses));
        $stmt->bind_param($types, ...$statuses);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;
        return $rows;
    }

    private function detectStokColumn()
    {
        $candidates = ['stok','stok_barang','stock','jumlah','qty','jumlah_barang'];
        $placeholders = "'" . implode("','", $candidates) . "'";
        $res = $this->conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='barang' AND COLUMN_NAME IN ($placeholders) LIMIT 1");
        if ($res && $res->num_rows > 0) return $res->fetch_assoc()['COLUMN_NAME'];
        return null;
    }

    private function detectAvailabilityColumn()
    {
        $candidates = ['status_barang','status_ketersediaan','status_pinjam','status'];
        $placeholders = "'" . implode("','", $candidates) . "'";
        $res = $this->conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='barang' AND COLUMN_NAME IN ($placeholders) LIMIT 1");
        if ($res && $res->num_rows > 0) return $res->fetch_assoc()['COLUMN_NAME'];
        return null;
    }

    private function countByStatus($status)
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS cnt FROM peminjaman WHERE status = ?");
        $stmt->bind_param('s', $status);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        return (int)($r['cnt'] ?? 0);
    }

    private function countToday()
    {
        $cols = $this->getTableColumns('peminjaman');
        $col = $this->pickColumn($cols, ['created_at','tgl_pinjam']);
        if (!$col) return 0;

        $sql = "SELECT COUNT(*) AS cnt FROM peminjaman WHERE DATE(`$col`) = CURDATE()";
        $r = $this->conn->query($sql);
        $row = $r ? $r->fetch_assoc() : [];
        return (int)($row['cnt'] ?? 0);
    }

    private function countApprovedThisMonth()
    {
        $cols = $this->getTableColumns('peminjaman');
        $col = $this->pickColumn($cols, ['created_at','tgl_pinjam']);
        if (!$col) return 0;

        $sql = "SELECT COUNT(*) AS cnt FROM peminjaman WHERE status = 'approved' AND MONTH(`$col`) = MONTH(CURDATE()) AND YEAR(`$col`) = YEAR(CURDATE())";
        $r = $this->conn->query($sql);
        $row = $r ? $r->fetch_assoc() : [];
        return (int)($row['cnt'] ?? 0);
    }

    private function countWaitingAll()
    {
        $waitingPeminjaman = $this->countPeminjamanWaiting();
        $waitingPengembalian = $this->countPengembalianWaiting();
        $waitingBarang = $this->countBarangWaiting();
        return $waitingPeminjaman + $waitingPengembalian + $waitingBarang;
    }

    private function countTodayAll()
    {
        return $this->countPeminjamanToday() + $this->countPengembalianToday() + $this->countBarangToday();
    }

    private function countApprovedThisMonthAll()
    {
        return $this->countPeminjamanApprovedThisMonth() + $this->countPengembalianApprovedThisMonth() + $this->countBarangApprovedThisMonth();
    }

    private function countPeminjamanWaiting()
    {
        $res = $this->conn->query("SELECT COUNT(*) AS cnt FROM peminjaman WHERE status IN ('waiting_verification','pending')");
        $row = $res ? $res->fetch_assoc() : [];
        return (int)($row['cnt'] ?? 0);
    }

    private function countPeminjamanToday()
    {
        return $this->countToday();
    }

    private function countPeminjamanApprovedThisMonth()
    {
        return $this->countApprovedThisMonth();
    }

    private function countPengembalianWaiting()
    {
        $res = $this->conn->query("SELECT COUNT(*) AS cnt FROM pengembalian WHERE status = 'pending'");
        $row = $res ? $res->fetch_assoc() : [];
        return (int)($row['cnt'] ?? 0);
    }

    private function countPengembalianToday()
    {
        $detailTable = $this->detectPengembalianDetailTable();
        if (!$detailTable) return 0;
        $cols = $this->getTableColumns($detailTable);
        $dIdKembali = $this->pickColumn($cols, ['id_kembali','pengembalian_id']);
        $dTgl = $this->pickColumn($cols, ['tgl_kembali','tanggal_kembali','tgl']);
        if (!$dIdKembali || !$dTgl) return 0;

        $sql = "SELECT COUNT(DISTINCT p.id_kembali) AS cnt
                FROM pengembalian p
                INNER JOIN `{$detailTable}` d ON d.`{$dIdKembali}` = p.id_kembali
                WHERE DATE(d.`{$dTgl}`) = CURDATE()";
        $res = $this->conn->query($sql);
        $row = $res ? $res->fetch_assoc() : [];
        return (int)($row['cnt'] ?? 0);
    }

    private function countPengembalianApprovedThisMonth()
    {
        $cols = $this->getTableColumns('pengembalian');
        if (in_array('verified_at', $cols, true)) {
            $sql = "SELECT COUNT(*) AS cnt FROM pengembalian WHERE status = 'completed'
                    AND MONTH(`verified_at`) = MONTH(CURDATE()) AND YEAR(`verified_at`) = YEAR(CURDATE())";
            $res = $this->conn->query($sql);
            $row = $res ? $res->fetch_assoc() : [];
            return (int)($row['cnt'] ?? 0);
        }

        $detailTable = $this->detectPengembalianDetailTable();
        if (!$detailTable) return 0;
        $dCols = $this->getTableColumns($detailTable);
        $dIdKembali = $this->pickColumn($dCols, ['id_kembali','pengembalian_id']);
        $dTgl = $this->pickColumn($dCols, ['tgl_kembali','tanggal_kembali','tgl']);
        if (!$dIdKembali || !$dTgl) return 0;

        $sql = "SELECT COUNT(DISTINCT p.id_kembali) AS cnt
                FROM pengembalian p
                INNER JOIN `{$detailTable}` d ON d.`{$dIdKembali}` = p.id_kembali
                WHERE p.status = 'completed'
                  AND MONTH(d.`{$dTgl}`) = MONTH(CURDATE()) AND YEAR(d.`{$dTgl}`) = YEAR(CURDATE())";
        $res = $this->conn->query($sql);
        $row = $res ? $res->fetch_assoc() : [];
        return (int)($row['cnt'] ?? 0);
    }

    private function countBarangWaiting()
    {
        $res = $this->conn->query("SELECT COUNT(*) AS cnt FROM barang WHERE status_brg IN ('menunggu_verifikasi','pending','menunggu')");
        $row = $res ? $res->fetch_assoc() : [];
        return (int)($row['cnt'] ?? 0);
    }

    private function countBarangToday()
    {
        $cols = $this->getTableColumns('barang');
        $col = $this->pickColumn($cols, ['tgl_masuk','tanggal_masuk','created_at']);
        if (!$col) return 0;

        $sql = "SELECT COUNT(*) AS cnt FROM barang WHERE DATE(`$col`) = CURDATE()";
        $res = $this->conn->query($sql);
        $row = $res ? $res->fetch_assoc() : [];
        return (int)($row['cnt'] ?? 0);
    }

    private function countBarangApprovedThisMonth()
    {
        $cols = $this->getTableColumns('barang');
        $col = $this->pickColumn($cols, ['tgl_masuk','tanggal_masuk','created_at']);
        if (!$col) return 0;

        $sql = "SELECT COUNT(*) AS cnt FROM barang WHERE status_brg = 'terverifikasi'
                AND MONTH(`$col`) = MONTH(CURDATE()) AND YEAR(`$col`) = YEAR(CURDATE())";
        $res = $this->conn->query($sql);
        $row = $res ? $res->fetch_assoc() : [];
        return (int)($row['cnt'] ?? 0);
    }

    private function getDeleteRequestById($id)
    {
        if (!$this->tableExists('delete_requests')) return null;
        $stmt = $this->conn->prepare("SELECT * FROM delete_requests WHERE id_request = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    private function getDeleteRequestsPeminjaman($status)
    {
        if (!$this->tableExists('delete_requests')) return [];
        $pk = $this->model->getPkColumn() ?: 'id';
        $sql = "SELECT r.id_request AS delete_request_id,
                       r.status AS request_status,
                       r.target_id,
                       COALESCE(p.`{$pk}`, r.target_id) AS id,
                       p.nama_pihak1,
                       p.tujuan,
                       p.tgl_pinjam
                FROM delete_requests r
                LEFT JOIN peminjaman p ON p.`{$pk}` = r.target_id
                WHERE r.module = 'peminjaman' AND r.status = ?
                ORDER BY r.id_request DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $status);
        $stmt->execute();
        $rows = [];
        $res = $stmt->get_result();
        if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;
        return $rows;
    }

    private function getDeleteRequestsPengembalian($status)
    {
        if (!$this->tableExists('delete_requests')) return [];
        $sql = "SELECT r.id_request AS delete_request_id,
                       r.status AS request_status,
                       r.target_id,
                       COALESCE(p.id_kembali, r.target_id) AS id_kembali,
                       pm.nama_pihak1,
                       pm.nama_pihak2,
                       pm.tujuan,
                       pm.tgl_pinjam,
                       p.kondisi_kembali
                FROM delete_requests r
                LEFT JOIN pengembalian p ON p.id_kembali = r.target_id
                LEFT JOIN peminjaman pm ON pm.id_pinjam = p.id_pinjam
                WHERE r.module = 'pengembalian' AND r.status = ?
                ORDER BY r.id_request DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $status);
        $stmt->execute();
        $rows = [];
        $res = $stmt->get_result();
        if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;
        return $rows;
    }

    private function getDeleteRequestsBarang($status)
    {
        if (!$this->tableExists('delete_requests')) return [];
        $sql = "SELECT r.id_request AS delete_request_id,
                       r.status AS request_status,
                       r.target_id,
                       COALESCE(b.id_barang, r.target_id) AS id_barang,
                       b.nama_barang,
                       b.id_lokasi,
                       b.status_brg
                FROM delete_requests r
                LEFT JOIN barang b ON b.id_barang = r.target_id
                WHERE r.module = 'barang' AND r.status = ?
                ORDER BY r.id_request DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $status);
        $stmt->execute();
        $rows = [];
        $res = $stmt->get_result();
        if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;
        return $rows;
    }

    private function deleteTarget($module, $targetId)
    {
        if ($module === 'peminjaman') {
            $map = $this->model->getItemsMapping();
            $itemsTable = $map['items_table'] ?? null;
            $pCol = $map['peminjaman_col'] ?? null;
            $pk = $this->model->getPkColumn() ?: 'id';
            if ($itemsTable && $pCol) {
                $stmt = $this->conn->prepare("DELETE FROM `{$itemsTable}` WHERE `{$pCol}` = ?");
                $stmt->bind_param('i', $targetId);
                $stmt->execute();
            }
            $stmt = $this->conn->prepare("DELETE FROM peminjaman WHERE `{$pk}` = ?");
            $stmt->bind_param('i', $targetId);
            $stmt->execute();
            return;
        }

        if ($module === 'pengembalian') {
            $detailTable = $this->detectPengembalianDetailTable();
            $detailCols = $detailTable ? $this->getTableColumns($detailTable) : [];
            $dIdKembali = $detailTable ? $this->pickColumn($detailCols, ['id_kembali','pengembalian_id']) : null;
            if ($detailTable && $dIdKembali) {
                $stmt = $this->conn->prepare("DELETE FROM `{$detailTable}` WHERE `{$dIdKembali}` = ?");
                $stmt->bind_param('i', $targetId);
                $stmt->execute();
            }
            $stmt = $this->conn->prepare("DELETE FROM pengembalian WHERE id_kembali = ?");
            $stmt->bind_param('i', $targetId);
            $stmt->execute();
            return;
        }

        if ($module === 'barang') {
            $cols = $this->getTableColumns('barang');
            $pk = $this->detectPrimaryKey('barang') ?: 'id_barang';
            $stmt = $this->conn->prepare("SELECT * FROM barang WHERE `{$pk}` = ? LIMIT 1");
            $stmt->bind_param('i', $targetId);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_assoc();
            if ($data) {
                $folder = dirname(__DIR__, 2) . '/public/uploads/barang/';
                foreach ($cols as $col) {
                    if (preg_match('/foto|image|gambar/i', $col['Field']) && !empty($data[$col['Field']])) {
                        $value = $data[$col['Field']];
                        if (strpos($value, '/') !== false) {
                            $path = dirname(__DIR__, 2) . '/public/' . ltrim($value, '/');
                        } else {
                            $path = $folder . $value;
                        }
                        if (file_exists($path)) unlink($path);
                    }
                }
            }
            $stmt = $this->conn->prepare("DELETE FROM barang WHERE `{$pk}` = ?");
            $stmt->bind_param('i', $targetId);
            $stmt->execute();
            return;
        }

    }

    private function redirectByModule($module)
    {
        if ($module === 'peminjaman') return 'kalaksa_verifikasi';
        if ($module === 'pengembalian') return 'kalaksa_pengembalian';
        if ($module === 'barang') return 'kalaksa_barang';
        return 'kalaksa_dashboard';
    }

    private function pengurusRedirectByModule($module)
    {
        if ($module === 'peminjaman') return 'pengurus_peminjaman';
        if ($module === 'pengembalian') return 'pengurus_pengembalian';
        if ($module === 'barang') return 'admin';
        return 'pengurus_dashboard';
    }

    private function getUserRoleById($userId)
    {
        if ($userId <= 0) return null;
        $stmt = $this->conn->prepare("SELECT role FROM user WHERE id_user = ? LIMIT 1");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row['role'] ?? null;
    }

    private function tableExists($table)
    {
        $t = $this->conn->real_escape_string($table);
        $res = $this->conn->query("SHOW TABLES LIKE '{$t}'");
        return $res && $res->num_rows > 0;
    }

    private function detectPrimaryKey($table)
    {
        $t = $this->conn->real_escape_string($table);
        $res = $this->conn->query("SHOW COLUMNS FROM `{$t}`");
        if (!$res) return null;
        while ($col = $res->fetch_assoc()) {
            if (!empty($col['Key']) && strtoupper($col['Key']) === 'PRI') return $col['Field'];
        }
        $res->data_seek(0);
        while ($col = $res->fetch_assoc()) {
            if (preg_match('/(^|_)id$|^id(_|$)/i', $col['Field'])) return $col['Field'];
        }
        $res->data_seek(0);
        $first = $res->fetch_assoc();
        return $first ? $first['Field'] : null;
    }

    private function detectPengembalianDetailTable()
    {
        $candidates = ['detail_pengembalian','detail_kembali','pengembalian_detail'];
        foreach ($candidates as $tbl) {
            $res = $this->conn->query("SHOW TABLES LIKE '{$tbl}'");
            if ($res && $res->num_rows > 0) return $tbl;
        }
        return null;
    }

    private function getTableColumns($table)
    {
        $cols = [];
        $res = $this->conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}'");
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $cols[] = $r['COLUMN_NAME'];
            }
        }
        return $cols;
    }

    private function pickColumn($cols, $candidates)
    {
        foreach ($candidates as $col) {
            if (in_array($col, $cols, true)) return $col;
        }
        return null;
    }
}
