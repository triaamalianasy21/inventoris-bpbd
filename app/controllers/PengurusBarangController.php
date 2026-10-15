<?php
// ===================================================
// Pengurus Barang Controller
// ===================================================
require_once __DIR__ . '/../models/Koneksi.php';
require_once __DIR__ . '/../models/PeminjamanModel.php';
require_once __DIR__ . '/../config/auth_helper.php';

class PengurusBarangController
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
        requireRole('pengurus_barang');

        $waiting = $this->countByStatus('waiting_verification');
        $approved = $this->countByStatus('approved');
        $rejected = $this->countByStatus('rejected');

        $title = "Dashboard Pengurus Barang - PRANATA";
        $data = [
            'waiting' => $waiting,
            'approved' => $approved,
            'rejected' => $rejected
        ];
        include __DIR__ . '/../views/pengurus/dashboard.php';
    }

    public function peminjaman()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        requireRole(['pengurus_barang', 'pengurus']);

        $q = trim($_GET['q'] ?? '');
        $perPage = 10;
        $pageActive = max(1, (int)($_GET['page_active'] ?? 1));
        $pageDone = max(1, (int)($_GET['page_done'] ?? 1));

        $activeStatuses = ['waiting_verification','pending','approved','rejected','menunggu_verifikasi','menunggu','ditolak'];
        $doneStatuses = ['completed','selesai'];

        $activeData = $this->model->getPagedByStatus($activeStatuses, $q, $pageActive, $perPage);
        $doneData = $this->model->getPagedCompleted($q, $pageDone, $perPage);

        foreach ([$activeData, $doneData] as $idx => $bucket) {
            $rows = $bucket['rows'];
            $pk = $this->model->getPkColumn();
            foreach ($rows as &$row) {
                $pid = $row['id'] ?? ($pk && isset($row[$pk]) ? $row[$pk] : null);
                $itemsList = $pid ? $this->model->getItems((int)$pid) : [];
                if (!empty($itemsList)) {
                    $names = [];
                    foreach ($itemsList as $it) {
                        if (!empty($it['nama_barang'])) $names[] = $it['nama_barang'];
                    }
                    $row['items_summary'] = implode(', ', $names);
                } else {
                    $row['items_summary'] = '';
                }
            }
            unset($row);
            if ($idx === 0) {
                $activeData['rows'] = $rows;
            } else {
                $doneData['rows'] = $rows;
            }
        }

        $title = "Data Peminjaman - PRANATA";
        include __DIR__ . '/../views/pengurus/peminjaman.php';
    }

    public function peminjamanEdit()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        requireRole('pengurus_barang');

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) die('ID tidak ditemukan');

        $data = $this->model->find($id);
        if (!$data) die('Data tidak ditemukan');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cols = $this->getTableColumns('peminjaman');
            $fieldMap = [
                'nama_pihak1','nip','jabatan','nama_pihak2','nip1','jabatan1',
                'alamat','no_hp','tujuan','tgl_pinjam','tgl_jatuh_tempo','spesifikasi','keterangan'
            ];

            $sets = [];
            $values = [];
            $types = '';
            if ($this->hasTableColumn($cols, 'status')) {
                $sets[] = "`status` = 'waiting_verification'";
            }
            if ($this->hasTableColumn($cols, 'verified_by')) {
                $sets[] = "`verified_by` = NULL";
            }
            if ($this->hasTableColumn($cols, 'verified_at')) {
                $sets[] = "`verified_at` = NULL";
            }
            foreach ($fieldMap as $f) {
                if ($this->hasTableColumn($cols, $f)) {
                    $sets[] = "`{$f}` = ?";
                    $values[] = trim($_POST[$f] ?? '');
                    $types .= 's';
                }
            }

            $pk = $this->model->getPkColumn() ?: 'id';
            $values[] = $id;
            $types .= 'i';

            $itemsSelected = [];
            $postedItems = $_POST['items'] ?? [];
            foreach ($postedItems as $bid => $vals) {
                if (!is_array($vals)) continue;
                if (($vals['selected'] ?? '') !== '1') continue;
                $qty = max(1, (int)($vals['qty'] ?? 1));
                $itemsSelected[] = ['barang_id' => (int)$bid, 'qty' => $qty];
            }

            $this->conn->begin_transaction();
            try {
                if (!empty($sets)) {
                    $sql = "UPDATE peminjaman SET " . implode(',', $sets) . " WHERE `$pk` = ?";
                    $stmt = $this->conn->prepare($sql);
                    $bind = [];
                    $bind[] = $types;
                    foreach ($values as $k => $v) $bind[] = &$values[$k];
                    call_user_func_array([$stmt, 'bind_param'], $bind);
                    if (!$stmt->execute()) throw new Exception('Gagal memperbarui data.');
                }

                if (!empty($itemsSelected)) {
                    $map = $this->model->getItemsMapping();
                    $itemsTable = $map['items_table'] ?? null;
                    $pCol = $map['peminjaman_col'] ?? null;
                    $bCol = $map['barang_col'] ?? null;
                    $qCol = $map['qty_col'] ?? null;
                    if ($itemsTable && $pCol && $bCol && $qCol) {
                        $stmt = $this->conn->prepare("DELETE FROM `{$itemsTable}` WHERE `{$pCol}` = ?");
                        $stmt->bind_param('i', $id);
                        $stmt->execute();

                        $stmt = $this->conn->prepare("INSERT INTO `{$itemsTable}` (`{$pCol}`, `{$bCol}`, `{$qCol}`) VALUES (?, ?, ?)");
                        foreach ($itemsSelected as $it) {
                            $bid = (int)$it['barang_id'];
                            $qty = (int)$it['qty'];
                            if ($bid <= 0) continue;
                            $stmt->bind_param('iii', $id, $bid, $qty);
                            if (!$stmt->execute()) throw new Exception('Gagal memperbarui items.');
                        }
                    }
                }

                $this->conn->commit();
                $_SESSION['flash_ok'] = 'Peminjaman berhasil diperbarui.';
                header('Location: index.php?page=pengurus_peminjaman');
                exit;
            } catch (Exception $e) {
                $this->conn->rollback();
                $_SESSION['flash_error'] = $e->getMessage();
            }
        }

        $items = $this->model->getItems($id);
        $itemsMap = [];
        foreach ($items as $it) {
            $bid = (int)($it['id_barang'] ?? ($it['barang_id'] ?? 0));
            if ($bid <= 0) continue;
            $itemsMap[$bid] = (int)($it['qty'] ?? ($it['jumlah'] ?? 1));
        }

        $barangList = [];
        $stokCandidates = ['stok','stok_barang','stock','jumlah','qty','jumlah_barang'];
        $placeholders = "'" . implode("','", $stokCandidates) . "'";
        $colRes = $this->conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='barang' AND COLUMN_NAME IN ($placeholders) LIMIT 1");
        $stokCol = null;
        if ($colRes && $colRes->num_rows > 0) {
            $stokCol = $colRes->fetch_assoc()['COLUMN_NAME'];
        }
        $condParts = [];
        $condRes = $this->conn->query("SHOW COLUMNS FROM barang LIKE 'kondisi'");
        if ($condRes && $condRes->num_rows > 0) {
            $condParts[] = "(b.kondisi IS NULL OR LOWER(b.kondisi) <> 'rusak')";
        }
        $statusRes = $this->conn->query("SHOW COLUMNS FROM barang LIKE 'status_brg'");
        if ($statusRes && $statusRes->num_rows > 0) {
            $condParts[] = "(b.status_brg IS NULL OR LOWER(b.status_brg) NOT IN ('menunggu_verifikasi','pending','menunggu','waiting_verification'))";
        }
        $condFilter = '';
        if (!empty($condParts)) {
            $condFilter = "WHERE " . implode(' AND ', $condParts);
        }
        if ($stokCol) {
            $sql = "SELECT b.id_barang, b.nama_barang, `{$stokCol}` AS stok FROM barang b {$condFilter} ORDER BY b.nama_barang ASC";
        } else {
            $sql = "SELECT b.id_barang, b.nama_barang FROM barang b {$condFilter} ORDER BY b.nama_barang ASC";
        }
        $r = $this->conn->query($sql);
        if ($r) while ($b = $r->fetch_assoc()) $barangList[] = $b;

        $old = $data;
        $title = "Ubah Peminjaman - PRANATA";
        include __DIR__ . '/../views/pengurus/peminjaman_edit.php';
    }

    public function peminjamanDelete()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        requireRole('pengurus_barang');

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) die('ID tidak ditemukan');

        $row = $this->model->find($id);
        if (!$row) die('Data tidak ditemukan');

        $statusNow = strtolower((string)($row['status'] ?? ''));
        if (!in_array($statusNow, ['waiting_verification','pending','menunggu_verifikasi','menunggu','rejected','ditolak',''], true)) {
            $_SESSION['flash_error'] = 'Peminjaman yang sudah disetujui/selesai tidak bisa dihapus.';
            header('Location: index.php?page=pengurus_peminjaman');
            exit;
        }

        $map = $this->model->getItemsMapping();
        $itemsTable = $map['items_table'] ?? null;
        $pCol = $map['peminjaman_col'] ?? null;
        $pk = $this->model->getPkColumn() ?: 'id';
        $items = $this->model->getItems($id);
        $qtyMap = [];
        foreach ($items as $it) {
            $bid = (int)($it['id_barang'] ?? ($it['barang_id'] ?? 0));
            $qty = (int)($it['qty'] ?? ($it['jumlah'] ?? 1));
            if ($bid <= 0 || $qty <= 0) continue;
            if (!isset($qtyMap[$bid])) $qtyMap[$bid] = 0;
            $qtyMap[$bid] += $qty;
        }
        $stokCol = $this->detectBarangStokColumn();

        $this->conn->begin_transaction();
        try {
            if ($stokCol && !empty($qtyMap)) {
                $stokColEsc = $this->conn->real_escape_string($stokCol);
                $stmtStok = $this->conn->prepare("UPDATE barang SET `{$stokColEsc}` = `{$stokColEsc}` + ? WHERE id_barang = ?");
                foreach ($qtyMap as $bid => $qty) {
                    $stmtStok->bind_param('ii', $qty, $bid);
                    if (!$stmtStok->execute()) throw new Exception('Gagal mengembalikan stok.');
                }
            }
            if ($itemsTable && $pCol) {
                $stmt = $this->conn->prepare("DELETE FROM `{$itemsTable}` WHERE `{$pCol}` = ?");
                $stmt->bind_param('i', $id);
                $stmt->execute();
            }
            $stmt = $this->conn->prepare("DELETE FROM peminjaman WHERE `{$pk}` = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $this->conn->commit();
            $_SESSION['flash_ok'] = 'Peminjaman berhasil dihapus.';
        } catch (Exception $e) {
            $this->conn->rollback();
            $_SESSION['flash_error'] = 'Gagal menghapus peminjaman.';
        }
        header('Location: index.php?page=pengurus_peminjaman');
        exit;
    }

    private function detectBarangStokColumn()
    {
        $candidates = ['stok','stok_barang','stock','jumlah','qty','jumlah_barang'];
        $placeholders = "'" . implode("','", array_map([$this->conn, 'real_escape_string'], $candidates)) . "'";
        $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='barang' AND COLUMN_NAME IN ($placeholders) LIMIT 1";
        $res = $this->conn->query($sql);
        if ($res && ($row = $res->fetch_assoc())) return $row['COLUMN_NAME'];
        return null;
    }

    public function tambah()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        requireRole('pengurus_barang');

        // reuse PeminjamanController logic for barang list + submit
        require_once __DIR__ . '/../controllers/PeminjamanController.php';
        $pc = new PeminjamanController();
        $pc->tambah();
    }

    public function pengembalian()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        requireRole('pengurus_barang');

        $q = trim($_GET['q'] ?? '');
        $perPage = 10;
        $page = max(1, (int)($_GET['page_pengembalian'] ?? 1));

        $paged = $this->getPengembalianListPaged($q, $page, $perPage);
        $data = $paged['rows'];
        $page = $paged['page'];
        $pages = $paged['pages'];

        $title = "Pengembalian - PRANATA";
        include __DIR__ . '/../views/pengurus/pengembalian.php';
    }

    public function pengembalianTambah()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        requireRole('pengurus_barang');

        $err = null;
        $selectedPinjam = (int)($_GET['id_pinjam'] ?? ($_POST['id_pinjam'] ?? 0));
        $items = $selectedPinjam ? $this->model->getItems($selectedPinjam) : [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idPinjam = (int)($_POST['id_pinjam'] ?? 0);
            $kondisi = trim($_POST['kondisi_kembali'] ?? '');
            $tglKembali = trim($_POST['tgl_kembali'] ?? '');

            if ($idPinjam <= 0) $err = 'Pilih peminjaman yang akan dikembalikan.';
            if ($kondisi === '') $err = 'Kondisi kembali wajib diisi.';
            if ($tglKembali === '') $err = 'Tanggal kembali wajib diisi.';

            if (!$err) {
                $items = $this->model->getItems($idPinjam);
                if (empty($items)) $err = 'Tidak ada barang pada peminjaman ini.';
            }

            if (!$err) {
                $stmt = $this->conn->prepare("SELECT COUNT(*) AS cnt FROM pengembalian WHERE id_pinjam = ? AND status IN ('pending','processing')");
                $stmt->bind_param('i', $idPinjam);
                $stmt->execute();
                $exists = (int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
                if ($exists > 0) $err = 'Pengembalian untuk peminjaman ini masih diproses.';
            }

            if (!$err) {
                $pkPinjam = $this->model->getPkColumn() ?: 'id';
                $userCol = $this->model->getUserColumn();
                $userId = null;
                if ($userCol) {
                    $stmt = $this->conn->prepare("SELECT `$userCol` AS user_id FROM peminjaman WHERE `$pkPinjam` = ? LIMIT 1");
                    $stmt->bind_param('i', $idPinjam);
                    $stmt->execute();
                    $userId = $stmt->get_result()->fetch_assoc()['user_id'] ?? null;
                }

                $detailTable = $this->detectPengembalianDetailTable();
                if (!$detailTable) {
                    $err = 'Tabel detail pengembalian tidak ditemukan.';
                } else {
                    $detailCols = $this->getTableColumns($detailTable);
                    $dIdKembali = $this->pickColumn($detailCols, ['id_kembali','pengembalian_id']);
                    $dIdBarang = $this->pickColumn($detailCols, ['id_barang','barang_id']);
                    $dTgl = $this->pickColumn($detailCols, ['tgl_kembali','tanggal_kembali','tgl']);
                    $dFoto = $this->pickColumn($detailCols, ['foto','foto_bukti']);
                    if (!$dIdKembali || !$dIdBarang || !$dTgl) {
                        $err = 'Kolom detail pengembalian tidak lengkap.';
                    } elseif (!$dFoto) {
                        $err = 'Kolom foto tidak ditemukan pada detail pengembalian.';
                    }
                }
            }

            $fotoMap = [];
            if (!$err) {
                if (empty($_FILES['foto'])) {
                    $err = 'Foto pengembalian wajib diunggah.';
                } else {
                    foreach ($items as $it) {
                        $bid = (int)($it['id_barang'] ?? ($it['barang_id'] ?? 0));
                        if ($bid <= 0) continue;
                        $file = [
                            'name' => $_FILES['foto']['name'][$bid] ?? '',
                            'type' => $_FILES['foto']['type'][$bid] ?? '',
                            'tmp_name' => $_FILES['foto']['tmp_name'][$bid] ?? '',
                            'error' => $_FILES['foto']['error'][$bid] ?? UPLOAD_ERR_NO_FILE,
                            'size' => $_FILES['foto']['size'][$bid] ?? 0
                        ];
                        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                            $err = 'Foto untuk setiap barang wajib diunggah.';
                            break;
                        }
                        [$okUpload, $pathOrErr] = $this->savePengembalianPhoto($file, 'kembali_' . $idPinjam . '_' . $bid);
                        if (!$okUpload) {
                            $err = $pathOrErr;
                            break;
                        }
                        $fotoMap[$bid] = $pathOrErr;
                    }
                }
            }

            if (!$err) {
                $this->conn->begin_transaction();
                try {
                        $reqBy = $_SESSION['user_id'] ?? null;
                        $cols = $this->getTableColumns('pengembalian');
                        $fields = ['id_pinjam','kondisi_kembali','status'];
                        $values = [$idPinjam, $kondisi, 'pending'];
                        $types = 'iss';

                        if ($this->hasTableColumn($cols, 'id_user') && $userId !== null) {
                            $fields[] = 'id_user';
                            $values[] = $userId;
                            $types .= 'i';
                        }
                        if ($this->hasTableColumn($cols, 'requested_by') && $reqBy !== null) {
                            $fields[] = 'requested_by';
                            $values[] = $reqBy;
                            $types .= 'i';
                        }

                        $sql = "INSERT INTO pengembalian (`" . implode('`,`', $fields) . "`) VALUES (" . rtrim(str_repeat('?,', count($fields)), ',') . ")";
                        $stmt = $this->conn->prepare($sql);
                        $stmt->bind_param($types, ...$values);
                        $stmt->execute();
                        $idKembali = $this->conn->insert_id;

                        $sqlD = "INSERT INTO `{$detailTable}` (`{$dIdKembali}`, `{$dIdBarang}`, `{$dTgl}`, `{$dFoto}`) VALUES (?, ?, ?, ?)";
                        $stmtD = $this->conn->prepare($sqlD);
                        foreach ($items as $it) {
                            $bid = (int)($it['id_barang'] ?? ($it['barang_id'] ?? 0));
                            if ($bid <= 0) continue;
                            $fotoVal = $fotoMap[$bid] ?? '';
                            $stmtD->bind_param('iiss', $idKembali, $bid, $tglKembali, $fotoVal);
                            $stmtD->execute();
                        }

                        $this->conn->commit();
                        $_SESSION['flash_ok'] = 'Pengembalian berhasil ditambahkan.';
                        header('Location: index.php?page=pengurus_pengembalian');
                        exit;
                    } catch (Exception $e) {
                        $this->conn->rollback();
                        $err = $e->getMessage();
                    }
            }
        }

        $pinjamanList = $this->getReturnEligibleList();
        $title = "Input Pengembalian - PRANATA";
        include __DIR__ . '/../views/pengurus/pengembalian_tambah.php';
    }

    public function pengembalianEdit()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        requireRole('pengurus_barang');

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) die('ID tidak ditemukan');

        $detailTable = $this->detectPengembalianDetailTable();
        if (!$detailTable) die('Tabel detail pengembalian tidak ditemukan');

        $detailCols = $this->getTableColumns($detailTable);
        $dIdKembali = $this->pickColumn($detailCols, ['id_kembali','pengembalian_id']);
        $dTgl = $this->pickColumn($detailCols, ['tgl_kembali','tanggal_kembali','tgl']);
        $dIdBarang = $this->pickColumn($detailCols, ['id_barang','barang_id']);
        $dFoto = $this->pickColumn($detailCols, ['foto','foto_bukti']);
        if (!$dIdKembali || !$dTgl) die('Kolom detail pengembalian tidak lengkap');

        $stmt = $this->conn->prepare("
            SELECT p.*, pm.nama_pihak2
            FROM pengembalian p
            LEFT JOIN peminjaman pm ON pm.id_pinjam = p.id_pinjam
            WHERE p.id_kembali = ?
            LIMIT 1
        ");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();
        if (!$data) die('Data tidak ditemukan');

        $statusNow = strtolower((string)($data['status'] ?? ''));

        $tglRow = $this->conn->query("SELECT MIN(`{$dTgl}`) AS tgl_kembali FROM `{$detailTable}` WHERE `{$dIdKembali}` = " . (int)$id)->fetch_assoc();
        $data['tgl_kembali'] = $tglRow['tgl_kembali'] ?? ($data['tgl_kembali'] ?? date('Y-m-d'));

        $fotoMap = [];
        if ($dFoto && $dIdBarang) {
            $stmt = $this->conn->prepare("SELECT `{$dIdBarang}` AS id_barang, `{$dFoto}` AS foto FROM `{$detailTable}` WHERE `{$dIdKembali}` = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $resFoto = $stmt->get_result();
            if ($resFoto) {
                while ($r = $resFoto->fetch_assoc()) {
                    $bid = (int)($r['id_barang'] ?? 0);
                    if ($bid > 0) $fotoMap[$bid] = $r['foto'] ?? '';
                }
            }
        }

        $items = $this->model->getItems((int)($data['id_pinjam'] ?? 0));

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $kondisi = trim($_POST['kondisi_kembali'] ?? '');
            $tglKembali = trim($_POST['tgl_kembali'] ?? '');
            if ($kondisi === '' || $tglKembali === '') {
                $_SESSION['flash_error'] = 'Kondisi dan tanggal kembali wajib diisi.';
            } else {
                $stmt = $this->conn->prepare("UPDATE pengembalian SET kondisi_kembali = ?, status = 'pending', verified_by = NULL, verified_at = NULL WHERE id_kembali = ?");
                $stmt->bind_param('si', $kondisi, $id);
                $stmt->execute();

                $stmt = $this->conn->prepare("UPDATE `{$detailTable}` SET `{$dTgl}` = ? WHERE `{$dIdKembali}` = ?");
                $stmt->bind_param('si', $tglKembali, $id);
                $stmt->execute();

                // Re-open peminjaman when pengembalian is edited (needs re-verification)
                if (!empty($data['id_pinjam'])) {
                    $this->updatePeminjamanStatusByIdPinjam((int)$data['id_pinjam'], 'approved');
                }

                if ($dFoto && $dIdBarang && !empty($_FILES['foto'])) {
                    foreach ($items as $it) {
                        $bid = (int)($it['id_barang'] ?? ($it['barang_id'] ?? 0));
                        if ($bid <= 0) continue;
                        $file = [
                            'name' => $_FILES['foto']['name'][$bid] ?? '',
                            'type' => $_FILES['foto']['type'][$bid] ?? '',
                            'tmp_name' => $_FILES['foto']['tmp_name'][$bid] ?? '',
                            'error' => $_FILES['foto']['error'][$bid] ?? UPLOAD_ERR_NO_FILE,
                            'size' => $_FILES['foto']['size'][$bid] ?? 0
                        ];
                        if ($file['error'] === UPLOAD_ERR_NO_FILE) continue;
                        [$okUpload, $pathOrErr] = $this->savePengembalianPhoto($file, 'kembali_' . $id . '_' . $bid);
                        if (!$okUpload) {
                            $_SESSION['flash_error'] = $pathOrErr;
                            header('Location: index.php?page=pengurus_pengembalian_edit&id=' . (int)$id);
                            exit;
                        }
                        $stmtFoto = $this->conn->prepare("UPDATE `{$detailTable}` SET `{$dFoto}` = ? WHERE `{$dIdKembali}` = ? AND `{$dIdBarang}` = ?");
                        $stmtFoto->bind_param('sii', $pathOrErr, $id, $bid);
                        $stmtFoto->execute();
                    }
                }

                $_SESSION['flash_ok'] = 'Pengembalian berhasil diperbarui.';
                header('Location: index.php?page=pengurus_pengembalian');
                exit;
            }
        }

        $title = "Ubah Pengembalian - PRANATA";
        include __DIR__ . '/../views/pengurus/pengembalian_edit.php';
    }

    public function pengembalianDelete()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        requireRole('pengurus_barang');

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) die('ID tidak ditemukan');

        $stmt = $this->conn->prepare("SELECT status FROM pengembalian WHERE id_kembali = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) die('Data tidak ditemukan');

        $statusNow = strtolower((string)($row['status'] ?? ''));
        if (!in_array($statusNow, ['pending','menunggu_verifikasi','waiting_verification','rejected','ditolak',''], true)) {
            $_SESSION['flash_error'] = 'Pengembalian yang sudah diproses tidak bisa dihapus.';
            header('Location: index.php?page=pengurus_pengembalian');
            exit;
        }

        $detailTable = $this->detectPengembalianDetailTable();
        $dCols = $detailTable ? $this->getTableColumns($detailTable) : [];
        $dIdKembali = $detailTable ? $this->pickColumn($dCols, ['id_kembali','pengembalian_id']) : null;

        $this->conn->begin_transaction();
        try {
            // fetch related peminjaman id for status rollback
            $pid = null;
            $stmt = $this->conn->prepare("SELECT id_pinjam FROM pengembalian WHERE id_kembali = ? LIMIT 1");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if ($row) $pid = (int)($row['id_pinjam'] ?? 0);

            if ($detailTable && $dIdKembali) {
                $stmt = $this->conn->prepare("DELETE FROM `{$detailTable}` WHERE `{$dIdKembali}` = ?");
                $stmt->bind_param('i', $id);
                $stmt->execute();
            }
            $stmt = $this->conn->prepare("DELETE FROM pengembalian WHERE id_kembali = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();

            // restore peminjaman status to approved if pengembalian is removed
            if ($pid) {
                $this->updatePeminjamanStatusByIdPinjam($pid, 'approved');
            }
            $this->conn->commit();
            $_SESSION['flash_ok'] = 'Pengembalian berhasil dihapus.';
        } catch (Exception $e) {
            $this->conn->rollback();
            $_SESSION['flash_error'] = 'Gagal menghapus pengembalian.';
        }
        header('Location: index.php?page=pengurus_pengembalian');
        exit;
    }

    public function pengembalianDownload()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        requireRole('pengurus_barang');

        $id = $_GET['id'] ?? null;
        if (!$id) die('ID pengembalian tidak ditemukan');

        $detailTable = $this->detectPengembalianDetailTable();
        if (!$detailTable) die('Tabel detail pengembalian tidak ditemukan');

        $stmt = $this->conn->prepare("SELECT p.*, pm.* FROM pengembalian p LEFT JOIN peminjaman pm ON pm.id_pinjam = p.id_pinjam WHERE p.id_kembali = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();
        if (!$data) die('Data tidak ditemukan');

        $data['items'] = $this->model->getItems((int)($data['id_pinjam'] ?? 0));
        $dCols = $this->getTableColumns($detailTable);
        $dIdKembali = $this->pickColumn($dCols, ['id_kembali','pengembalian_id']);
        $dTgl = $this->pickColumn($dCols, ['tgl_kembali','tanggal_kembali','tgl']);
        if ($dIdKembali && $dTgl) {
            $stmt = $this->conn->prepare("SELECT MIN(`{$dTgl}`) AS tgl_kembali FROM `{$detailTable}` WHERE `{$dIdKembali}` = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $data['tgl_kembali'] = $row['tgl_kembali'] ?? ($data['tgl_kembali'] ?? '-');
        }

        $isPdf = true;
        ob_start();
        include __DIR__ . '/../views/pengembalian/print.php';
        $html = ob_get_clean();

        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'Times New Roman');
        $options->set('chroot', realpath(__DIR__ . '/../../'));
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('Legal', 'portrait');
        $dompdf->render();

        $filename = 'pengembalian_' . (int)$id . '.pdf';
        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    }


    public function paktaIntegritas()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        requireRole('pengurus_barang');

        $data = [];
        $q = trim($_GET['q'] ?? '');
        $pk = null;
        $tblExists = $this->conn->query("SHOW TABLES LIKE 'pakta_integritas'");
        if ($tblExists && $tblExists->num_rows > 0) {
            $pk = $this->detectPrimaryKey('pakta_integritas');
            $order = $pk ? "`{$pk}`" : ($this->hasColumn('pakta_integritas', 'tgl_dibuat') ? '`tgl_dibuat`' : ($this->hasColumn('pakta_integritas', 'created_at') ? '`created_at`' : null));
            $orderSql = $order ? "ORDER BY {$order} DESC" : "";
            if ($this->hasColumn('pakta_integritas', 'user_id')) {
                $res = $this->conn->query("SELECT p.*, u.nama AS user_nama FROM pakta_integritas p LEFT JOIN user u ON u.id_user = p.user_id {$orderSql}");
            } else {
                $res = $this->conn->query("SELECT * FROM pakta_integritas {$orderSql}");
            }
            if ($res) while ($r = $res->fetch_assoc()) $data[] = $r;
        }

        if ($q !== '') {
            $qLower = mb_strtolower($q, 'UTF-8');
            $data = array_values(array_filter($data, function($row) use ($qLower) {
                $fields = [
                    $row['user_nama'] ?? '',
                    $row['nama'] ?? '',
                    $row['nama_lengkap'] ?? '',
                    $row['nama_peminjam'] ?? '',
                    $row['nip_nik'] ?? '',
                    $row['nip'] ?? '',
                    $row['nik'] ?? '',
                    $row['jabatan'] ?? '',
                    $row['barang_digunakan'] ?? '',
                    $row['barang_milik_daerah'] ?? '',
                    $row['status'] ?? '',
                    $row['tgl_dibuat'] ?? '',
                    $row['created_at'] ?? ''
                ];
                foreach ($fields as $f) {
                    if ($f === '') continue;
                    if (mb_stripos((string)$f, $qLower, 0, 'UTF-8') !== false) return true;
                }
                return false;
            }));
        }

        $title = "Pakta Integritas - PRANATA";
        $pkCol = $pk;
        include __DIR__ . '/../views/pengurus/pakta_integritas.php';
    }

    public function paktaTambah()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        requireRole('pengurus_barang');

        if (!$this->hasColumn('pakta_integritas', $this->detectPrimaryKey('pakta_integritas'))) {
            die('Tabel pakta_integritas tidak ditemukan.');
        }

        $pk = $this->detectPrimaryKey('pakta_integritas');
        $cols = $this->getTableColumns('pakta_integritas');

        $paktaPinjamCol = $this->pickColumn($cols, ['id_pinjam','id_pinjaman','peminjaman_id','id_peminjaman']);

        $pinjamanList = $this->getApprovedList();
        foreach ($pinjamanList as &$row) {
            $pid = (int)($row['id_pinjam'] ?? ($row['id'] ?? 0));
            $itemsList = $pid ? $this->model->getItems($pid) : [];
            if (!empty($itemsList)) {
                $names = [];
                foreach ($itemsList as $it) {
                    if (!empty($it['nama_barang'])) $names[] = $it['nama_barang'];
                }
                $row['items_summary'] = implode(', ', $names);
            } else {
                $row['items_summary'] = '';
            }
        }
        unset($row);

        if ($paktaPinjamCol) {
            $exists = [];
            $res = $this->conn->query("SELECT DISTINCT `{$paktaPinjamCol}` AS pid FROM pakta_integritas WHERE `{$paktaPinjamCol}` IS NOT NULL");
            if ($res) {
                while ($r = $res->fetch_assoc()) {
                    $pid = (int)($r['pid'] ?? 0);
                    if ($pid > 0) $exists[$pid] = true;
                }
            }
            if (!empty($exists)) {
                $pinjamanList = array_values(array_filter($pinjamanList, function($row) use ($exists) {
                    $pid = (int)($row['id_pinjam'] ?? ($row['id'] ?? 0));
                    return $pid > 0 && empty($exists[$pid]);
                }));
            }
        }

        $selectedPinjamId = (int)($_POST['id_pinjam'] ?? ($_GET['id_pinjam'] ?? 0));
        if ($selectedPinjamId <= 0 && !empty($pinjamanList)) {
            $selectedPinjamId = (int)($pinjamanList[0]['id_pinjam'] ?? ($pinjamanList[0]['id'] ?? 0));
        }

        $selectedPinjam = null;
        foreach ($pinjamanList as $row) {
            $pid = (int)($row['id_pinjam'] ?? ($row['id'] ?? 0));
            if ($pid === $selectedPinjamId) {
                $selectedPinjam = $row;
                break;
            }
        }

        $data = [];
        if ($selectedPinjam) {
            $data = [
                'nama' => $selectedPinjam['nama_pihak2'] ?? '',
                'nip_nik' => $selectedPinjam['nip1'] ?? '',
                'jabatan' => $selectedPinjam['jabatan1'] ?? '',
                'barang_digunakan' => $selectedPinjam['items_summary'] ?? '',
                'tgl_dibuat' => ''
            ];
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($selectedPinjamId <= 0) {
                $_SESSION['flash_error'] = 'Pilih peminjaman terlebih dahulu.';
            } else {
                $editable = ['nama','nama_lengkap','nama_peminjam','nip_nik','nip','nik','nrp','jabatan','jabatan_peminjam','barang_digunakan','barang_milik_daerah','barang','nama_barang','tgl_dibuat','created_at'];
            $fields = [];
            $values = [];
            $types = '';

            if ($paktaPinjamCol) {
                $fields[] = $paktaPinjamCol;
                $values[] = $selectedPinjamId;
                $types .= 'i';
            }
            foreach ($editable as $f) {
                if ($this->hasColumn('pakta_integritas', $f) && array_key_exists($f, $_POST)) {
                    $fields[] = $f;
                    $val = trim($_POST[$f]);
                    if ($val === '' && array_key_exists($f, $data)) {
                        $val = (string)$data[$f];
                    }
                    $values[] = $val;
                    $types .= 's';
                }
            }
            if (empty($fields)) {
                $_SESSION['flash_error'] = 'Field pakta belum lengkap.';
            } else {
                $sql = "INSERT INTO pakta_integritas (`" . implode('`,`', $fields) . "`) VALUES (" . rtrim(str_repeat('?,', count($fields)), ',') . ")";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param($types, ...$values);
                $stmt->execute();
                $_SESSION['flash_ok'] = 'Pakta integritas berhasil ditambahkan.';
                header('Location: index.php?page=pengurus_pakta');
                exit;
            }
            }
        }

        $title = "Tambah Pakta Integritas - PRANATA";
        include __DIR__ . '/../views/pengurus/pakta_tambah.php';
    }

    public function paktaEdit()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        requireRole('pengurus_barang');

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) die('ID tidak ditemukan');

        if (!$this->hasColumn('pakta_integritas', $this->detectPrimaryKey('pakta_integritas'))) {
            die('Tabel pakta_integritas tidak ditemukan.');
        }

        $pk = $this->detectPrimaryKey('pakta_integritas');
        $cols = $this->getTableColumns('pakta_integritas');
        $stmt = $this->conn->prepare("SELECT * FROM pakta_integritas WHERE `{$pk}` = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();
        if (!$data) die('Data tidak ditemukan');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $editable = ['nama','nama_lengkap','nama_peminjam','nip_nik','nip','nik','nrp','jabatan','jabatan_peminjam','barang_digunakan','barang_milik_daerah','barang','nama_barang','tgl_dibuat','created_at'];
            $sets = [];
            $values = [];
            $types = '';
            foreach ($editable as $f) {
                if ($this->hasColumn('pakta_integritas', $f) && array_key_exists($f, $_POST)) {
                    $sets[] = "`{$f}` = ?";
                    $values[] = trim($_POST[$f]);
                    $types .= 's';
                }
            }
            if (!empty($sets)) {
                $values[] = $id;
                $types .= 'i';
                $sql = "UPDATE pakta_integritas SET " . implode(',', $sets) . " WHERE `{$pk}` = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param($types, ...$values);
                $stmt->execute();
                $_SESSION['flash_ok'] = 'Pakta integritas berhasil diperbarui.';
                header('Location: index.php?page=pengurus_pakta');
                exit;
            }
        }

        $title = "Ubah Pakta Integritas - PRANATA";
        include __DIR__ . '/../views/pengurus/pakta_edit.php';
    }

    public function paktaDelete()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        requireRole('pengurus_barang');

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) die('ID tidak ditemukan');

        $pk = $this->detectPrimaryKey('pakta_integritas');
        if (!$pk) {
            $_SESSION['flash_error'] = 'Gagal menghapus pakta: kolom ID tidak ditemukan.';
            header('Location: index.php?page=pengurus_pakta');
            exit;
        }
        $stmt = $this->conn->prepare("DELETE FROM pakta_integritas WHERE `{$pk}` = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $_SESSION['flash_ok'] = 'Pakta integritas berhasil dihapus.';
        header('Location: index.php?page=pengurus_pakta');
        exit;
    }

    public function laporan()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        requireRole(['pengurus_barang', 'pengurus']);

        $jenis = $_GET['jenis'] ?? 'barang';
        if ($jenis === 'pakta') $jenis = 'barang';
        $yearParam = $_GET['year'] ?? '';
        $monthNumParam = $_GET['month_num'] ?? '';
        $monthParam = $_GET['month'] ?? '';
        if (preg_match('/^\d{4}$/', (string)$yearParam) && preg_match('/^(0?[1-9]|1[0-2])$/', (string)$monthNumParam)) {
            $monthStart = sprintf('%04d-%02d', (int)$yearParam, (int)$monthNumParam);
        } else {
            $monthStart = $this->reportNormalizeMonth($monthParam) ?: date('Y-m');
        }
        $monthEnd = $monthStart;
        $startDate = $monthStart . '-01';
        $endDate = (new DateTime($monthEnd . '-01'))->modify('last day of this month')->format('Y-m-d');

        $rows = [];
        $columns = [];
        $reportTitle = '';
        $lokasiMap = $this->reportGetLokasiMap();

        if ($jenis === 'peminjaman') {
            $reportTitle = 'Laporan Peminjaman';
            $columns = [
                ['key' => 'nama_pihak1', 'label' => 'Nama Pihak 1'],
                ['key' => 'nama_pihak2', 'label' => 'Nama Pihak 2'],
                ['key' => 'tujuan', 'label' => 'Tujuan'],
                ['key' => 'tgl_pinjam', 'label' => 'Tgl Pinjam'],
                ['key' => 'tgl_jatuh_tempo', 'label' => 'Tgl Jatuh Tempo'],
                ['key' => 'status_label', 'label' => 'Status'],
            ];

            $cols = $this->reportGetTableColumns('peminjaman');
            $dateCol = $this->reportPickColumn($cols, ['tgl_pinjam','created_at']);
            $sql = "SELECT nama_pihak1, nama_pihak2, tujuan, tgl_pinjam, tgl_jatuh_tempo, status FROM peminjaman";
            if ($dateCol) {
                $sql .= " WHERE DATE(`{$dateCol}`) BETWEEN ? AND ?";
            }
            $sql .= " ORDER BY id_pinjam DESC";
            $stmt = $this->conn->prepare($sql);
            if ($dateCol) {
                $stmt->bind_param('ss', $startDate, $endDate);
            }
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                $r['status_label'] = $this->reportFormatPeminjamanStatus($r['status'] ?? '');
                $rows[] = $r;
            }
        } elseif ($jenis === 'pengembalian') {
            $reportTitle = 'Laporan Pengembalian';
            $columns = [
                ['key' => 'nama_pihak1', 'label' => 'Nama Pihak 1'],
                ['key' => 'nama_pihak2', 'label' => 'Nama Pihak 2'],
                ['key' => 'tujuan', 'label' => 'Tujuan'],
                ['key' => 'tgl_pinjam', 'label' => 'Tgl Pinjam'],
                ['key' => 'tgl_kembali', 'label' => 'Tgl Kembali'],
                ['key' => 'kondisi_kembali', 'label' => 'Kondisi'],
                ['key' => 'status_label', 'label' => 'Status'],
            ];

            $detailTable = $this->detectPengembalianDetailTable();
            if ($detailTable) {
                $dCols = $this->reportGetTableColumns($detailTable);
                $dIdKembali = $this->reportPickColumn($dCols, ['id_kembali','pengembalian_id']);
                $dTgl = $this->reportPickColumn($dCols, ['tgl_kembali','tanggal_kembali','tgl']);
                if ($dIdKembali && $dTgl) {
                    $sql = "SELECT p.id_kembali, p.kondisi_kembali, p.status,
                                   pm.nama_pihak1, pm.nama_pihak2, pm.tujuan, pm.tgl_pinjam,
                                   d.tgl_kembali
                            FROM pengembalian p
                            LEFT JOIN peminjaman pm ON pm.id_pinjam = p.id_pinjam
                            LEFT JOIN (
                                SELECT `{$dIdKembali}` AS id_kembali, MIN(`{$dTgl}`) AS tgl_kembali
                                FROM `{$detailTable}`
                                GROUP BY `{$dIdKembali}`
                            ) d ON d.id_kembali = p.id_kembali
                            WHERE DATE(d.`{$dTgl}`) BETWEEN ? AND ?
                            ORDER BY p.id_kembali DESC";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->bind_param('ss', $startDate, $endDate);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    while ($r = $res->fetch_assoc()) {
                        $r['status_label'] = $this->reportFormatPengembalianStatus($r['status'] ?? '');
                        $rows[] = $r;
                    }
                }
            }

            if (!$detailTable || empty($rows)) {
                $cols = $this->reportGetTableColumns('pengembalian');
                $dateCol = $this->reportPickColumn($cols, ['verified_at','created_at']);
                $sql = "SELECT p.id_kembali, p.kondisi_kembali, p.status,
                               pm.nama_pihak1, pm.nama_pihak2, pm.tujuan, pm.tgl_pinjam
                        FROM pengembalian p
                        LEFT JOIN peminjaman pm ON pm.id_pinjam = p.id_pinjam";
                if ($dateCol) {
                    $sql .= " WHERE DATE(p.`{$dateCol}`) BETWEEN ? AND ?";
                }
                $sql .= " ORDER BY p.id_kembali DESC";
                $stmt = $this->conn->prepare($sql);
                if ($dateCol) {
                    $stmt->bind_param('ss', $startDate, $endDate);
                }
                $stmt->execute();
                $res = $stmt->get_result();
                while ($r = $res->fetch_assoc()) {
                    $r['tgl_kembali'] = $r['tgl_kembali'] ?? '-';
                    $r['status_label'] = $this->reportFormatPengembalianStatus($r['status'] ?? '');
                    $rows[] = $r;
                }
            }
        } elseif ($jenis === 'pakta') {
            $reportTitle = 'Laporan Pakta Integritas';
            $columns = [
                ['key' => 'nama', 'label' => 'Nama'],
                ['key' => 'nip_nik', 'label' => 'NIP/NRP/NIK'],
                ['key' => 'jabatan', 'label' => 'Jabatan'],
                ['key' => 'barang_digunakan', 'label' => 'Barang Digunakan'],
                ['key' => 'tgl_dibuat', 'label' => 'Tanggal'],
            ];

            if ($this->reportTableExists('pakta_integritas')) {
                $cols = $this->reportGetTableColumns('pakta_integritas');
                $dateCol = $this->reportPickColumn($cols, ['tgl_dibuat','created_at']);
                $selectFields = ['nama','nip_nik','jabatan','barang_digunakan','tgl_dibuat'];
                if ($this->reportPickColumn($cols, ['created_at'])) $selectFields[] = 'created_at';
                $sql = "SELECT " . implode(',', $selectFields) . " FROM pakta_integritas";
                if ($dateCol) {
                    $sql .= " WHERE DATE(`{$dateCol}`) BETWEEN ? AND ?";
                }
                $sql .= " ORDER BY {$dateCol} DESC";
                $stmt = $this->conn->prepare($sql);
                if ($dateCol) {
                    $stmt->bind_param('ss', $startDate, $endDate);
                }
                $stmt->execute();
                $res = $stmt->get_result();
                while ($r = $res->fetch_assoc()) {
                    $r['tgl_dibuat'] = $r['tgl_dibuat'] ?? ($r['created_at'] ?? '-');
                    $rows[] = $r;
                }
            }
        } else {
            $reportTitle = 'Laporan Barang';
            $columns = [
                ['key' => 'kode_barang', 'label' => 'Kode Barang'],
                ['key' => 'nama_barang', 'label' => 'Nama Barang'],
                ['key' => 'lokasi_label', 'label' => 'Lokasi'],
                ['key' => 'status_brg_label', 'label' => 'Status Verifikasi'],
                ['key' => 'tgl_masuk', 'label' => 'Tgl Masuk'],
            ];

            $cols = $this->reportGetTableColumns('barang');
            $dateCol = $this->reportPickColumn($cols, ['tgl_masuk','tanggal_masuk','created_at']);
            $sql = "SELECT id_barang, id_lokasi, kode_barang, nama_barang, status_brg, {$dateCol} AS tgl_masuk FROM barang";
            if ($dateCol) {
                $sql .= " WHERE DATE(`{$dateCol}`) BETWEEN ? AND ?";
            }
            $sql .= " ORDER BY id_barang DESC";
            $stmt = $this->conn->prepare($sql);
            if ($dateCol) {
                $stmt->bind_param('ss', $startDate, $endDate);
            }
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                $lid = (string)($r['id_lokasi'] ?? '');
                $r['lokasi_label'] = $lokasiMap[$lid] ?? ($lid !== '' ? $lid : '-');
                $r['status_brg_label'] = $this->reportFormatBarangStatus($r['status_brg'] ?? '');
                $rows[] = $r;
            }
        }

        $isExport = isset($_GET['export']) && $_GET['export'] === '1';
        $title = "Laporan - PRANATA";
        $roleView = 'pengurus';
        $generatedAt = date('d/m/Y H:i');
        $startMonth = $monthStart;
        $endMonth = $monthEnd;
        $filterYear = (int)substr($monthStart, 0, 4);
        $filterMonth = (int)substr($monthStart, 5, 2);
        if ($isExport) {
            $periodLabel = $monthStart;
            $this->reportExportExcel("laporan_{$jenis}_{$monthStart}.xls", $columns, $rows, $reportTitle, $periodLabel);
            exit;
        }
        include __DIR__ . '/../views/laporan/index.php';
    }

    private function reportNormalizeDate($date)
    {
        if (!$date) return null;
        $dt = DateTime::createFromFormat('Y-m-d', $date);
        return $dt ? $dt->format('Y-m-d') : null;
    }

    private function reportNormalizeMonth($month)
    {
        if (!$month) return null;
        $dt = DateTime::createFromFormat('Y-m', $month);
        return $dt ? $dt->format('Y-m') : null;
    }

    private function reportTableExists($table)
    {
        $t = $this->conn->real_escape_string($table);
        $res = $this->conn->query("SHOW TABLES LIKE '{$t}'");
        return $res && $res->num_rows > 0;
    }

    private function reportGetTableColumns($table)
    {
        $cols = [];
        $res = $this->conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}'");
        if ($res) {
            while ($r = $res->fetch_assoc()) $cols[] = $r['COLUMN_NAME'];
        }
        return $cols;
    }

    private function reportPickColumn($cols, $candidates)
    {
        foreach ($candidates as $col) {
            if (in_array($col, $cols, true)) return $col;
        }
        return null;
    }

    private function reportGetLokasiMap()
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

    private function reportFormatPeminjamanStatus($status)
    {
        $raw = strtolower((string)$status);
        if (in_array($raw, ['waiting_verification','pending'], true)) return 'Menunggu Verifikasi';
        if ($raw === 'approved') return 'Disetujui';
        if ($raw === 'rejected') return 'Ditolak';
        if ($raw === 'completed') return 'Selesai';
        return $raw ? ucwords(str_replace('_', ' ', $raw)) : '-';
    }

    private function reportFormatPengembalianStatus($status)
    {
        $raw = strtolower((string)$status);
        if ($raw === 'pending') return 'Menunggu Verifikasi';
        if ($raw === 'completed') return 'Disetujui';
        if ($raw === 'processing') return 'Dikembalikan';
        return $raw ? ucwords(str_replace('_', ' ', $raw)) : '-';
    }

    private function reportFormatBarangStatus($status)
    {
        $raw = strtolower((string)$status);
        if (in_array($raw, ['menunggu_verifikasi','pending','menunggu'], true)) return 'Menunggu Verifikasi';
        if (in_array($raw, ['terverifikasi','verified'], true)) return 'Terverifikasi';
        if ($raw === 'ditolak') return 'Ditolak';
        return $raw ? ucwords(str_replace('_', ' ', $raw)) : '-';
    }

    private function reportExportExcel($filename, $columns, $rows, $reportTitle = '', $periodLabel = '')
    {
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $title = $reportTitle ? $reportTitle : 'Laporan';
        if ($periodLabel) {
            $title .= ' - ' . $periodLabel;
        }

        $colCount = max(1, count($columns));
        $mergeAcross = $colCount - 1;

        echo "<?xml version=\"1.0\"?>\n";
        echo "<?mso-application progid=\"Excel.Sheet\"?>\n";
        echo "<Workbook xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\"\n";
        echo " xmlns:o=\"urn:schemas-microsoft-com:office:office\"\n";
        echo " xmlns:x=\"urn:schemas-microsoft-com:office:excel\"\n";
        echo " xmlns:ss=\"urn:schemas-microsoft-com:office:spreadsheet\"\n";
        echo " xmlns:html=\"http://www.w3.org/TR/REC-html40\">\n";

        echo "<Styles>\n";
        echo " <Style ss:ID=\"sTitle\">\n";
        echo "  <Alignment ss:Horizontal=\"Center\" ss:Vertical=\"Center\"/>\n";
        echo "  <Font ss:Bold=\"1\"/>\n";
        echo "  <Borders>\n";
        echo "   <Border ss:Position=\"Bottom\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\"/>\n";
        echo "   <Border ss:Position=\"Left\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\"/>\n";
        echo "   <Border ss:Position=\"Right\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\"/>\n";
        echo "   <Border ss:Position=\"Top\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\"/>\n";
        echo "  </Borders>\n";
        echo " </Style>\n";
        echo " <Style ss:ID=\"sHeader\">\n";
        echo "  <Font ss:Bold=\"1\"/>\n";
        echo "  <Borders>\n";
        echo "   <Border ss:Position=\"Bottom\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\"/>\n";
        echo "   <Border ss:Position=\"Left\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\"/>\n";
        echo "   <Border ss:Position=\"Right\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\"/>\n";
        echo "   <Border ss:Position=\"Top\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\"/>\n";
        echo "  </Borders>\n";
        echo " </Style>\n";
        echo " <Style ss:ID=\"sCell\">\n";
        echo "  <Borders>\n";
        echo "   <Border ss:Position=\"Bottom\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\"/>\n";
        echo "   <Border ss:Position=\"Left\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\"/>\n";
        echo "   <Border ss:Position=\"Right\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\"/>\n";
        echo "   <Border ss:Position=\"Top\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\"/>\n";
        echo "  </Borders>\n";
        echo " </Style>\n";
        echo "</Styles>\n";

        echo "<Worksheet ss:Name=\"Laporan\">\n";
        echo "<Table>\n";
        echo " <Row>\n";
        echo "  <Cell ss:MergeAcross=\"{$mergeAcross}\" ss:StyleID=\"sTitle\"><Data ss:Type=\"String\">" . htmlspecialchars($title) . "</Data></Cell>\n";
        echo " </Row>\n";
        echo " <Row>\n";
        foreach ($columns as $col) {
            echo "  <Cell ss:StyleID=\"sHeader\"><Data ss:Type=\"String\">" . htmlspecialchars($col['label']) . "</Data></Cell>\n";
        }
        echo " </Row>\n";
        foreach ($rows as $row) {
            echo " <Row>\n";
            foreach ($columns as $col) {
                $val = $row[$col['key']] ?? '';
                echo "  <Cell ss:StyleID=\"sCell\"><Data ss:Type=\"String\">" . htmlspecialchars((string)$val) . "</Data></Cell>\n";
            }
            echo " </Row>\n";
        }
        echo "</Table>\n";
        echo "<WorksheetOptions xmlns=\"urn:schemas-microsoft-com:office:excel\">\n";
        echo " <DisplayGridlines/>\n";
        echo "</WorksheetOptions>\n";
        echo "</Worksheet>\n";
        echo "</Workbook>\n";
    }

    private function countByStatus($status)
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS cnt FROM peminjaman WHERE status = ?");
        $stmt->bind_param('s', $status);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        return (int)($r['cnt'] ?? 0);
    }

    private function getApprovedList()
    {
        $pk = $this->model->getPkColumn();
        $pkCol = $pk ? $pk : 'id';

        $res = $this->conn->query("SELECT * FROM peminjaman WHERE LOWER(TRIM(status)) IN ('approved','disetujui','setuju','completed','selesai') ORDER BY `$pkCol` DESC");
        $rows = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                if ($pk && !isset($r['id'])) $r['id'] = $r[$pk] ?? null;
                $rows[] = $r;
            }
        }
        return $rows;
    }

    private function getReturnEligibleList()
    {
        $pk = $this->model->getPkColumn();
        $pkCol = $pk ? $pk : 'id';

        // approved/active peminjaman only
        $res = $this->conn->query("SELECT * FROM peminjaman WHERE LOWER(TRIM(status)) IN ('approved','disetujui','setuju') ORDER BY `$pkCol` DESC");
        $pinjaman = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                if ($pk && !isset($r['id'])) $r['id'] = $r[$pk] ?? null;
                $pinjaman[] = $r;
            }
        }

        if (empty($pinjaman)) return [];

        // map latest pengembalian status per id_pinjam
        $pengCols = $this->getTableColumns('pengembalian');
        if (empty($pengCols)) return $pinjaman;

        $idPinjamCol = $this->pickColumn($pengCols, ['id_pinjam','peminjaman_id','id_peminjaman','id_pinjaman']);
        $idKembaliCol = $this->pickColumn($pengCols, ['id_kembali','pengembalian_id','id']);
        $statusCol = $this->pickColumn($pengCols, ['status','status_pengembalian']);

        if (!$idPinjamCol || !$idKembaliCol || !$statusCol) return $pinjaman;

        $ids = [];
        foreach ($pinjaman as $row) {
            $pid = (int)($row['id_pinjam'] ?? ($row['id'] ?? 0));
            if ($pid > 0) $ids[] = $pid;
        }
        $ids = array_values(array_unique($ids));
        if (empty($ids)) return $pinjaman;

        $idList = implode(',', array_map('intval', $ids));
        $sql = "
            SELECT p.`{$idPinjamCol}` AS pid, p.`{$statusCol}` AS status
            FROM pengembalian p
            JOIN (
                SELECT `{$idPinjamCol}` AS pid, MAX(`{$idKembaliCol}`) AS max_id
                FROM pengembalian
                WHERE `{$idPinjamCol}` IN ({$idList})
                GROUP BY `{$idPinjamCol}`
            ) t ON t.pid = p.`{$idPinjamCol}` AND t.max_id = p.`{$idKembaliCol}`
        ";
        $latest = [];
        $res = $this->conn->query($sql);
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $pid = (int)($r['pid'] ?? 0);
                if ($pid > 0) {
                    $latest[$pid] = strtolower(trim((string)($r['status'] ?? '')));
                }
            }
        }

        $blocked = ['completed','disetujui','approved','selesai'];
        $allowedRetry = ['rejected','ditolak','pending','processing','menunggu_verifikasi','menunggu'];
        $eligible = [];
        foreach ($pinjaman as $row) {
            $pid = (int)($row['id_pinjam'] ?? ($row['id'] ?? 0));
            $st = $latest[$pid] ?? '';
            if ($st === '') {
                $eligible[] = $row; // belum ada pengembalian
                continue;
            }
            if (in_array($st, $blocked, true)) {
                continue;
            }
            if (in_array($st, $allowedRetry, true)) {
                $eligible[] = $row;
                continue;
            }
        }

        return $eligible;
    }

    private function getPengembalianList()
    {
        $detailTable = $this->detectPengembalianDetailTable();
        if (!$detailTable) return [];

        $pkPinjam = $this->model->getPkColumn() ?: 'id_pinjam';
        $joinCol = $this->hasColumn('peminjaman', $pkPinjam) ? $pkPinjam : 'id_pinjam';
        $detailCols = $this->getTableColumns($detailTable);
        $dIdKembali = $this->pickColumn($detailCols, ['id_kembali','pengembalian_id']);
        $dTgl = $this->pickColumn($detailCols, ['tgl_kembali','tanggal_kembali','tgl']);
        $dFoto = $this->pickColumn($detailCols, ['foto','foto_bukti']);
        if (!$dIdKembali || !$dTgl) return [];

        $fotoSelect = $dFoto ? "MIN(d.`{$dFoto}`) AS foto" : "NULL AS foto";
        $sql = "SELECT p.*, pm.nama_pihak2, pm.nama_pihak1, pm.tujuan, pm.tgl_pinjam,
                       MIN(d.`{$dTgl}`) AS tgl_kembali,
                       {$fotoSelect}
                FROM pengembalian p
                LEFT JOIN peminjaman pm ON pm.`{$joinCol}` = p.id_pinjam
                LEFT JOIN `{$detailTable}` d ON d.`{$dIdKembali}` = p.id_kembali
                GROUP BY p.id_kembali
                ORDER BY p.id_kembali DESC";
        $rows = [];
        $res = $this->conn->query($sql);
        if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;

        // Attach all photos per pengembalian for horizontal preview
        if (!empty($rows) && $dIdKembali && $dFoto) {
            $ids = array_values(array_unique(array_filter(array_map(function($r){
                return (int)($r['id_kembali'] ?? 0);
            }, $rows))));
            if (!empty($ids)) {
                $idList = implode(',', array_map('intval', $ids));
                $sqlFotos = "SELECT `{$dIdKembali}` AS id_kembali, `{$dFoto}` AS foto
                             FROM `{$detailTable}`
                             WHERE `{$dIdKembali}` IN ({$idList})";
                $fotoMap = [];
                $resF = $this->conn->query($sqlFotos);
                if ($resF) {
                    while ($r = $resF->fetch_assoc()) {
                        $kid = (int)($r['id_kembali'] ?? 0);
                        $foto = $r['foto'] ?? '';
                        if ($kid <= 0 || $foto === '') continue;
                        if (!isset($fotoMap[$kid])) $fotoMap[$kid] = [];
                        $fotoMap[$kid][] = $foto;
                    }
                }
                foreach ($rows as &$row) {
                    $kid = (int)($row['id_kembali'] ?? 0);
                    $row['foto_list'] = $fotoMap[$kid] ?? [];
                }
                unset($row);
            }
        }
        return $rows;
    }

    private function getPengembalianListPaged($q, $page, $perPage)
    {
        $detailTable = $this->detectPengembalianDetailTable();
        if (!$detailTable) {
            return ['rows' => [], 'page' => 1, 'pages' => 1, 'total' => 0];
        }

        $pkPinjam = $this->model->getPkColumn() ?: 'id_pinjam';
        $joinCol = $this->hasColumn('peminjaman', $pkPinjam) ? $pkPinjam : 'id_pinjam';
        $detailCols = $this->getTableColumns($detailTable);
        $dIdKembali = $this->pickColumn($detailCols, ['id_kembali','pengembalian_id']);
        $dTgl = $this->pickColumn($detailCols, ['tgl_kembali','tanggal_kembali','tgl']);
        $dFoto = $this->pickColumn($detailCols, ['foto','foto_bukti','foto_path','foto_pengembalian']);

        $joinDetail = ($dIdKembali && ($dTgl || $dFoto)) ? "LEFT JOIN `{$detailTable}` d ON d.`{$dIdKembali}` = p.id_kembali" : "";
        $tglSelect = ($dIdKembali && $dTgl) ? "MIN(d.`{$dTgl}`) AS tgl_kembali" : "NULL AS tgl_kembali";
        $fotoSelect = ($dIdKembali && $dFoto) ? "MIN(d.`{$dFoto}`) AS foto" : "NULL AS foto";

        $where = [];
        $types = '';
        $params = [];
        if ($q !== '') {
            $like = '%' . $q . '%';
            $where[] = "(pm.nama_pihak2 LIKE ? OR pm.nama_pihak1 LIKE ? OR pm.tujuan LIKE ? OR p.kondisi_kembali LIKE ? OR p.status LIKE ?)";
            $types .= 'sssss';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        $whereSql = !empty($where) ? " WHERE " . implode(' AND ', $where) : '';

        $sqlCount = "SELECT COUNT(DISTINCT p.id_kembali) AS cnt
                     FROM pengembalian p
                     LEFT JOIN peminjaman pm ON pm.`{$joinCol}` = p.id_pinjam
                     {$joinDetail}
                     {$whereSql}";
        $stmtCount = $this->conn->prepare($sqlCount);
        if (!empty($params)) $stmtCount->bind_param($types, ...$params);
        $stmtCount->execute();
        $total = (int)($stmtCount->get_result()->fetch_assoc()['cnt'] ?? 0);
        $pages = max(1, (int)ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT p.*, pm.nama_pihak2, pm.nama_pihak1, pm.tujuan, pm.tgl_pinjam, {$tglSelect}, {$fotoSelect}
                FROM pengembalian p
                LEFT JOIN peminjaman pm ON pm.`{$joinCol}` = p.id_pinjam
                {$joinDetail}
                {$whereSql}
                GROUP BY p.id_kembali
                ORDER BY p.id_kembali DESC
                LIMIT ? OFFSET ?";
        $typesData = $types . 'ii';
        $paramsData = $params;
        $paramsData[] = $perPage;
        $paramsData[] = $offset;

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($typesData, ...$paramsData);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;

        if (!empty($rows) && $dIdKembali && $dFoto) {
            $ids = array_values(array_unique(array_filter(array_map(function($r){
                return (int)($r['id_kembali'] ?? 0);
            }, $rows))));
            if (!empty($ids)) {
                $idList = implode(',', array_map('intval', $ids));
                $sqlFotos = "SELECT `{$dIdKembali}` AS id_kembali, `{$dFoto}` AS foto
                             FROM `{$detailTable}`
                             WHERE `{$dIdKembali}` IN ({$idList})";
                $fotoMap = [];
                $resF = $this->conn->query($sqlFotos);
                if ($resF) {
                    while ($r = $resF->fetch_assoc()) {
                        $kid = (int)($r['id_kembali'] ?? 0);
                        $foto = $r['foto'] ?? '';
                        if ($kid <= 0 || $foto === '') continue;
                        if (!isset($fotoMap[$kid])) $fotoMap[$kid] = [];
                        $fotoMap[$kid][] = $foto;
                    }
                }
                foreach ($rows as &$row) {
                    $kid = (int)($row['id_kembali'] ?? 0);
                    $row['foto_list'] = $fotoMap[$kid] ?? [];
                }
                unset($row);
            }
        }

        return ['rows' => $rows, 'page' => $page, 'pages' => $pages, 'total' => $total];
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

    private function hasTableColumn($cols, $name)
    {
        foreach ($cols as $c) {
            if ($c['Field'] === $name) return true;
        }
        return false;
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

    private function detectPrimaryKey($table)
    {
        $t = $this->conn->real_escape_string($table);
        $res = $this->conn->query("SHOW COLUMNS FROM `{$t}`");
        if (!$res) return null;
        $cols = [];
        while ($col = $res->fetch_assoc()) {
            $cols[] = $col['Field'];
            if (!empty($col['Key']) && strtoupper($col['Key']) === 'PRI') {
                return $col['Field'];
            }
        }
        // fallback to common id columns
        $candidates = ['id_pakta_integritas','id_pakta','pakta_id','id'];
        foreach ($candidates as $c) {
            if (in_array($c, $cols, true)) return $c;
        }
        return null;
    }

    private function updatePeminjamanStatusByIdPinjam($idPinjam, $status)
    {
        if (!$this->hasColumn('peminjaman', 'status')) return false;
        $idPinjam = (int)$idPinjam;
        if ($idPinjam <= 0) return false;

        // Prefer explicit id_pinjam column if it exists; otherwise fallback to detected PK
        if ($this->hasColumn('peminjaman', 'id_pinjam')) {
            $stmt = $this->conn->prepare("UPDATE peminjaman SET status = ? WHERE `id_pinjam` = ?");
            $stmt->bind_param('si', $status, $idPinjam);
            return $stmt->execute();
        }

        $pk = $this->model->getPkColumn();
        if ($pk) {
            $stmt = $this->conn->prepare("UPDATE peminjaman SET status = ? WHERE `{$pk}` = ?");
            $stmt->bind_param('si', $status, $idPinjam);
            return $stmt->execute();
        }

        return false;
    }

    private function hasColumn($table, $column)
    {
        $t = $this->conn->real_escape_string($table);
        $c = $this->conn->real_escape_string($column);
        $res = $this->conn->query("SHOW COLUMNS FROM `{$t}` LIKE '{$c}'");
        return $res && $res->num_rows > 0;
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

    private function savePengembalianPhoto($file, $prefix)
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
        $dir = __DIR__ . '/../../public/uploads/pengembalian';
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
}
