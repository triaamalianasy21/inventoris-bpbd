<?php
// ===================================================
// Peminjaman Controller (Pemohon)
// ===================================================
require_once __DIR__ . '/../models/PeminjamanModel.php';
require_once __DIR__ . '/../models/Koneksi.php';
require_once __DIR__ . '/../config/auth_helper.php';
require_once __DIR__ . '/../../vendor/autoload.php';

class PeminjamanController
{
    private $model;
    private $conn;

    public function __construct()
    {
        $this->model = new PeminjamanModel();
        $this->conn = Koneksi::connect();
    }

    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $role = $_SESSION['role'] ?? '';
        $user_id = $_SESSION['user_id'] ?? null;

        if ($role === 'admin' || $role === 'pengurus') {
            $data = $this->model->getAll();
        } elseif ($user_id) {
            $data = $this->model->getByUser($user_id);
        } else {
            $data = [];
        }

        // attach item counts for display
        $pk = $this->model->getPkColumn();
        foreach ($data as &$row) {
            $pid = $row['id'] ?? ($pk && isset($row[$pk]) ? $row[$pk] : null);
            $row['items_count'] = $pid ? $this->model->getItemsCount($pid) : 0;
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
            if ($pid) {
                $row['detail_url'] = "index.php?page=peminjaman_detail&id=" . (int)$pid;
                $row['print_url'] = "index.php?page=peminjaman_download&id=" . (int)$pid;
            }
            $statusRaw = strtolower((string)($row['status'] ?? ''));
            $row['can_edit'] = true;
            $row['can_delete'] = in_array($statusRaw, ['waiting_verification','pending','menunggu_verifikasi','menunggu','rejected','ditolak',''], true);
        }
        unset($row);

        // JSON API support for mobile
        $isJson = (isset($_GET['json']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'],'application/json') !== false));
        if ($isJson) {
            header('Content-Type: application/json');
            echo json_encode($data);
            return;
        }

        include __DIR__ . '/../views/peminjaman/index.php';
    }

    public function tambah()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $user_id = $_SESSION['user_id'] ?? null;
        $role = $_SESSION['role'] ?? '';
        $isPemohon = ($role === 'pemohon');
        if (!$user_id) {
            header('Location: index.php?page=login'); exit;
        }

        $prefillNamaPihak1 = '';
        $stmt = $this->conn->prepare("SELECT nama FROM user WHERE id_user = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $prefillNamaPihak1 = $row['nama'] ?? '';
        }
        // Untuk input oleh pengurus/admin, jangan auto-isi nama pemohon
        if (!$isPemohon) {
            $prefillNamaPihak1 = '';
        }

        // Cek pakta integritas (jika tabel ada)
        $pakta_ok = true;
        $res = $this->conn->query("SHOW TABLES LIKE 'pakta_integritas'");

        // detect if client expects JSON (for mobile/android)
        $isJson = (isset($_GET['json']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'],'application/json') !== false) || (strpos($_SERVER['CONTENT_TYPE'] ?? '','application/json') !== false));

        if ($res && $res->num_rows > 0) {
            // detect columns safely so we don't assume `status` exists
            $colsRes = $this->conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pakta_integritas'");
            $cols = [];
            if ($colsRes) {
                while ($c = $colsRes->fetch_assoc()) {
                    $cols[] = $c['COLUMN_NAME'];
                }
            }

            $orderCol = null;
            foreach (['id','tgl_dibuat','created_at'] as $cand) {
                if (in_array($cand, $cols, true)) { $orderCol = $cand; break; }
            }
            $statusCol = null;
            foreach (['status','status_pakta'] as $cand) {
                if (in_array($cand, $cols, true)) { $statusCol = $cand; break; }
            }
            $orderBy = $orderCol ? "ORDER BY `$orderCol` DESC" : "";

            if ($statusCol) {
                $sql = "SELECT `{$statusCol}` AS status FROM pakta_integritas WHERE user_id = ? $orderBy LIMIT 1";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $r = $stmt->get_result()->fetch_assoc();
                if ($r && ($r['status'] ?? '') !== 'filled') $pakta_ok = false;
            }
        }

        // fetch barang list for selection (detect stok-like column automatically)
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
            $map = $this->model->getItemsMapping();
            $itemsTable = $map['items_table'] ?? null;
            $pCol = $map['peminjaman_col'] ?? null;
            $bCol = $map['barang_col'] ?? null;
            $qCol = $map['qty_col'] ?? null;

            if ($itemsTable && $pCol && $bCol && $qCol) {
                $it = $this->conn->real_escape_string($itemsTable);
                $pColEsc = $this->conn->real_escape_string($pCol);
                $bColEsc = $this->conn->real_escape_string($bCol);
                $qColEsc = $this->conn->real_escape_string($qCol);
                $stokColEsc = $this->conn->real_escape_string($stokCol);
                $sql = "SELECT b.id_barang, b.nama_barang,
                               CASE
                                 WHEN (COALESCE(b.`{$stokColEsc}`,0) - COALESCE(SUM(CASE WHEN p.`{$pColEsc}` IS NOT NULL THEN pi.`{$qColEsc}` ELSE 0 END),0)) > 0
                                 THEN (COALESCE(b.`{$stokColEsc}`,0) - COALESCE(SUM(CASE WHEN p.`{$pColEsc}` IS NOT NULL THEN pi.`{$qColEsc}` ELSE 0 END),0))
                                 ELSE 0
                               END AS stok
                        FROM barang b
                        LEFT JOIN `{$it}` pi ON pi.`{$bColEsc}` = b.id_barang
                        LEFT JOIN peminjaman p ON p.`{$pColEsc}` = pi.`{$pColEsc}` AND p.status IN ('waiting_verification','pending','approved')
                        {$condFilter}
                        GROUP BY b.id_barang, b.nama_barang, b.`{$stokColEsc}`
                        HAVING stok > 0
                        ORDER BY b.nama_barang ASC";
            } else {
                $sql = "SELECT b.id_barang, b.nama_barang, `{$stokCol}` AS stok FROM barang b {$condFilter} ORDER BY b.nama_barang ASC";
            }
        } else {
            $sql = "SELECT b.id_barang, b.nama_barang FROM barang b {$condFilter} ORDER BY b.nama_barang ASC";
        }
        $r = $this->conn->query($sql);
        if ($r) while ($b = $r->fetch_assoc()) $barangList[] = $b;

        // If client requests JSON (GET), return current pakta status and barang list
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && $isJson) {
            header('Content-Type: application/json');
            echo json_encode(['pakta_ok' => $pakta_ok, 'barang' => $barangList]);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $isPengurusBarang = ($role === 'pengurus_barang');
            $redirectList = $isPengurusBarang ? 'pengurus_peminjaman' : 'peminjaman';
            $redirectForm = $isPengurusBarang ? 'pengurus_peminjaman_tambah' : 'peminjaman_tambah';
            $from = $_POST['from'] ?? ($_GET['from'] ?? '');
            if ($from === 'pengurus') {
                $redirectList = 'pengurus_peminjaman';
                $redirectForm = 'pengurus_peminjaman_tambah';
            }

            // single active loan check
            if ($isPemohon && $this->model->userHasActiveLoan($user_id)) {
                $_SESSION['flash_error'] = 'Anda masih memiliki peminjaman aktif. Selesaikan dulu sebelum membuat permintaan baru.';
                $_SESSION['old_peminjaman'] = $payload;
                header('Location: index.php?page=' . $redirectForm); exit;
            }

            if ($isPemohon && !$pakta_ok) {
                $_SESSION['flash_error'] = 'Anda harus mengisi pakta integritas sebelum melakukan peminjaman.';
                $_SESSION['old_peminjaman'] = $payload;
                header('Location: index.php?page=' . $redirectForm); exit;
            }

            // support JSON POST payload for mobile clients
            if ($isJson && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
                $reqJson = json_decode(file_get_contents('php://input'), true) ?? [];
                $keterangan = trim($reqJson['keterangan'] ?? '');
                $itemsRaw = $reqJson['items'] ?? [];
                $payload = $reqJson;
            } else {
                $keterangan = trim($_POST['keterangan'] ?? '');
                $itemsRaw = $_POST['items'] ?? [];
                $payload = $_POST;
            }

            $namaPihak2 = trim((string)($payload['nama_pihak2'] ?? ''));
            $jabatanPihak2 = trim((string)($payload['jabatan1'] ?? ''));
            if ($namaPihak2 === '' || $jabatanPihak2 === '') {
                $msg = 'Nama pemohon dan jabatan pihak 2 wajib diisi.';
                if ($isJson) {
                    header('Content-Type: application/json', true, 400);
                    echo json_encode(['ok' => false, 'error' => $msg]);
                    exit;
                }
                $_SESSION['flash_error'] = $msg;
                $_SESSION['old_peminjaman'] = $payload;
                header('Location: index.php?page=' . $redirectForm); exit;
            }

            // parse items: items[<id>][selected], items[<id>][qty]  OR JSON array of {barang_id,qty}
            $items = [];
            if (is_array($itemsRaw) && array_key_exists(0, $itemsRaw)) {
                // JSON array format
                foreach ($itemsRaw as $it) {
                    $bid = (int)($it['barang_id'] ?? 0);
                    $qty = max(1, (int)($it['qty'] ?? 1));
                    if ($bid <= 0) continue;
                    $items[] = ['barang_id' => $bid, 'qty' => $qty];
                }
            } else {
                // form format
                foreach ($itemsRaw as $bid => $it) {
                    if (empty($it['selected'])) continue;
                    $qty = max(1, (int)($it['qty'] ?? 1));
                    $items[] = ['barang_id' => (int)$bid, 'qty' => $qty];
                }
            }

            if (empty($items)) {
                $_SESSION['flash_error'] = 'Pilih setidaknya satu barang yang akan dipinjam.';
                $_SESSION['old_peminjaman'] = $payload;
                header('Location: index.php?page=' . $redirectForm); exit;
            }

            $data = [
                'keterangan' => $keterangan,
                'status' => 'waiting_verification',
                'items' => $items
            ];
            // Simpan user_id untuk memenuhi kolom NOT NULL pada tabel peminjaman
            $data['user_id'] = $user_id;
            // Simpan operator input bila kolom tersedia
            $data['requested_by'] = $user_id;

            $extraFields = [
                'nama_pihak1','nip','jabatan','nama_pihak2','nip1','jabatan1',
                'alamat','no_hp','tujuan','tgl_pinjam','tgl_jatuh_tempo','spesifikasi'
            ];
            foreach ($extraFields as $f) {
                if (isset($payload[$f])) $data[$f] = trim($payload[$f]);
            }

            $id = $this->model->create($data);

            if ($id) {
                // kirim notifikasi ke admin/pengurus sederhana: kirim ke semua admin
                $admins = $this->conn->query("SELECT id_user FROM user WHERE role IN ('admin','pengurus')");
                $namaNotif = $namaPihak2 !== '' ? $namaPihak2 : 'pemohon';
                $msg = "Pengajuan peminjaman baru atas nama {$namaNotif}.";
                while ($a = $admins->fetch_assoc()) {
                    notifyUser($a['id_user'], 'peminjaman:new', ['message' => $msg, 'id' => $id, 'by' => $user_id]);
                }
                if ($isJson) {
                    header('Content-Type: application/json');
                    echo json_encode(['ok' => true, 'id' => $id, 'status' => 'waiting_verification']);
                    exit;
                }
                header('Location: index.php?page=' . $redirectList); exit;
            } else {
                $errMsg = $this->model->getLastError();
                if (!$errMsg) {
                    $errMsg = $this->conn->error ?: null;
                }
                if ($isJson) {
                    header('Content-Type: application/json', true, 400);
                    echo json_encode(['ok' => false, 'error' => $errMsg ?: 'Gagal membuat peminjaman. Coba lagi.']);
                    exit;
                }
                $_SESSION['flash_error'] = $errMsg ?: 'Gagal membuat peminjaman. Coba lagi.';
                $_SESSION['old_peminjaman'] = $payload;
            }
        }

        include __DIR__ . '/../views/peminjaman/tambah.php';
    }

    public function detail()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) die('ID tidak ditemukan');

        $data = $this->model->find($id);
        // JSON support
        $isJson = (isset($_GET['json']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'],'application/json') !== false));
        if ($isJson) {
            if (is_array($data)) {
                $statusRaw = strtolower((string)($data['status'] ?? ''));
                $data['can_edit'] = true;
                $data['can_delete'] = in_array($statusRaw, ['waiting_verification','pending','menunggu_verifikasi','menunggu','rejected','ditolak',''], true);
            }
            header('Content-Type: application/json');
            echo json_encode($data);
            return;
        }
        include __DIR__ . '/../views/peminjaman/detail.php';
    }

    public function print()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) die('ID tidak ditemukan');

        $data = $this->model->find($id);
        if (!$data) die('Data tidak ditemukan');
        include __DIR__ . '/../views/peminjaman/print.php';
    }

    public function preview()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        requireRole('pemohon');

        $id = $_GET['id'] ?? null;
        if (!$id) die('ID tidak ditemukan');

        $data = $this->model->find($id);
        if (!$data) die('Data tidak ditemukan');

        $userId = $_SESSION['user_id'] ?? null;
        $owner = $data['id_user'] ?? ($data['user_id'] ?? null);
        if ($userId && $owner !== null && (int)$owner !== (int)$userId) {
            die('Akses ditolak');
        }

        $pageTitle = 'Preview Peminjaman';
        $iframeSrc = 'index.php?page=peminjaman_print&id=' . (int)$id;
        $downloadUrl = 'index.php?page=peminjaman_download&id=' . (int)$id;
        $backUrl = 'peminjaman';
        include __DIR__ . '/../views/preview/print_preview.php';
    }

    public function download()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) die('ID tidak ditemukan');

        $data = $this->model->find($id);
        if (!$data) die('Data tidak ditemukan');

        $isPdf = true;
        ob_start();
        include __DIR__ . '/../views/peminjaman/print.php';
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

        $filename = 'peminjaman_' . (int)$id . '.pdf';
        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    }

    public function pengembalianPemohon()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        requireRole('pemohon');

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            header('Location: index.php?page=login');
            exit;
        }

        $pinjaman = $this->model->getByUser($userId);
        $ids = [];
        foreach ($pinjaman as $row) {
            $ids[] = (int)($row['id'] ?? 0);
        }
        $ids = array_values(array_filter($ids));

        $pengembalianMap = [];
        if (!empty($ids)) {
            $detailTable = $this->detectPengembalianDetailTable();
            $dIdKembali = $detailTable ? $this->pickColumn($this->getTableColumns($detailTable), ['id_kembali','pengembalian_id']) : null;
            $dTgl = $detailTable ? $this->pickColumn($this->getTableColumns($detailTable), ['tgl_kembali','tanggal_kembali','tgl']) : null;
            $dFoto = $detailTable ? $this->pickColumn($this->getTableColumns($detailTable), ['foto','foto_bukti']) : null;

            $in = implode(',', $ids);
            $tglSelect = ($detailTable && $dIdKembali && $dTgl) ? "MIN(d.`{$dTgl}`) AS tgl_kembali" : "NULL AS tgl_kembali";
            $fotoSelect = ($detailTable && $dIdKembali && $dFoto) ? "MIN(d.`{$dFoto}`) AS foto" : "NULL AS foto";
            $joinDetail = ($detailTable && $dIdKembali) ? "LEFT JOIN `{$detailTable}` d ON d.`{$dIdKembali}` = p.id_kembali" : "";
            $sql = "SELECT p.*, {$tglSelect}, {$fotoSelect}
                    FROM pengembalian p
                    {$joinDetail}
                    WHERE p.id_pinjam IN ({$in})
                    GROUP BY p.id_kembali
                    ORDER BY p.id_kembali DESC";
            $res = $this->conn->query($sql);
            if ($res) {
                while ($r = $res->fetch_assoc()) {
                    $pid = (int)($r['id_pinjam'] ?? 0);
                    if ($pid > 0 && !isset($pengembalianMap[$pid])) {
                        $pengembalianMap[$pid] = $r;
                    }
                }
            }
        }

        $data = [];
        foreach ($pinjaman as $row) {
            $pid = (int)($row['id'] ?? 0);
            $ret = $pengembalianMap[$pid] ?? null;
            $data[] = [
                'id_pinjam' => $pid,
                'pemohon' => $row['nama_pihak2'] ?? ($row['pemohon_nama'] ?? '-'),
                'tujuan' => $row['tujuan'] ?? '-',
                'tgl_pinjam' => $row['tgl_pinjam'] ?? ($row['created_at'] ?? '-'),
                'status_peminjaman' => $row['status'] ?? '-',
                'id_kembali' => $ret['id_kembali'] ?? null,
                'status_pengembalian' => $ret['status'] ?? '-',
                'kondisi_kembali' => $ret['kondisi_kembali'] ?? '-',
                'tgl_kembali' => $ret['tgl_kembali'] ?? '-',
                'foto' => $ret['foto'] ?? null
            ];
        }

        $hasApproved = false;
        $hasPendingReturn = false;
        foreach ($data as $row) {
            if (($row['status_peminjaman'] ?? '') === 'approved') {
                $hasApproved = true;
                $statusRet = strtolower((string)($row['status_pengembalian'] ?? ''));
                if (in_array($statusRet, ['pending','processing'], true)) {
                    $hasPendingReturn = true;
                    break;
                }
            }
        }

        if (!$hasApproved) {
            $returnBlock = true;
            $returnBlockReason = 'Anda belum memiliki peminjaman aktif.';
        } elseif ($hasPendingReturn) {
            $returnBlock = true;
            $returnBlockReason = 'Anda tidak bisa mengajukan pengembalian yang sama dua kali. Silakan tunggu proses verifikasi selesai.';
        } else {
            $returnBlock = false;
            $returnBlockReason = '';
        }

        $title = "Pengembalian - PRANATA";
        include __DIR__ . '/../views/pemohon/pengembalian.php';
    }

    private function getPemohonPengembalianData($id, $userId)
    {
        $stmt = $this->conn->prepare("SELECT p.*, pm.* FROM pengembalian p LEFT JOIN peminjaman pm ON pm.id_pinjam = p.id_pinjam WHERE p.id_kembali = ? AND p.id_user = ? LIMIT 1");
        $stmt->bind_param('ii', $id, $userId);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();
        if (!$data) return null;

        $data['items'] = $this->model->getItems((int)($data['id_pinjam'] ?? 0));
        $detailTable = $this->detectPengembalianDetailTable();
        if ($detailTable) {
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
        }
        return $data;
    }

    public function pengembalianPemohonPrint()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        requireRole('pemohon');

        $id = $_GET['id'] ?? null;
        if (!$id) die('ID pengembalian tidak ditemukan');

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) die('User tidak ditemukan');

        $data = $this->getPemohonPengembalianData((int)$id, (int)$userId);
        if (!$data) die('Data tidak ditemukan');

        include __DIR__ . '/../views/pengembalian/print.php';
    }

    public function pengembalianPemohonPreview()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        requireRole('pemohon');

        $id = $_GET['id'] ?? null;
        if (!$id) die('ID pengembalian tidak ditemukan');

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) die('User tidak ditemukan');

        $data = $this->getPemohonPengembalianData((int)$id, (int)$userId);
        if (!$data) die('Data tidak ditemukan');

        $pageTitle = 'Preview Pengembalian';
        $iframeSrc = 'index.php?page=pemohon_pengembalian_print&id=' . (int)$id;
        $downloadUrl = 'index.php?page=pemohon_pengembalian_download&id=' . (int)$id;
        $backUrl = 'pemohon_pengembalian';
        include __DIR__ . '/../views/preview/print_preview.php';
    }

    public function pengembalianPemohonDownload()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        requireRole('pemohon');

        $id = $_GET['id'] ?? null;
        if (!$id) die('ID pengembalian tidak ditemukan');

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) die('User tidak ditemukan');

        $data = $this->getPemohonPengembalianData((int)$id, (int)$userId);
        if (!$data) die('Data tidak ditemukan');

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

    public function pengembalianPemohonTambah()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        requireRole('pemohon');

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            header('Location: index.php?page=login');
            exit;
        }

        $err = null;
        $pinjamanList = array_values(array_filter($this->model->getByUser($userId), function($row) {
            return ($row['status'] ?? '') === 'approved';
        }));
        $activePinjam = $pinjamanList[0] ?? null;
        $selectedPinjam = (int)($activePinjam['id'] ?? 0);
        $items = $selectedPinjam ? $this->model->getItems($selectedPinjam) : [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idPinjam = $selectedPinjam;
            $kondisi = trim($_POST['kondisi_kembali'] ?? '');
            $tglKembali = trim($_POST['tgl_kembali'] ?? '');

            if ($idPinjam <= 0) $err = 'Tidak ada peminjaman aktif yang bisa dikembalikan.';
            if ($kondisi === '') $err = 'Kondisi kembali wajib diisi.';
            if ($tglKembali === '') $err = 'Tanggal kembali wajib diisi.';

            if (!$err && !$activePinjam) $err = 'Peminjaman belum disetujui atau sudah selesai.';

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
                        $cols = $this->getTableColumns('pengembalian');
                        $fields = ['id_pinjam','kondisi_kembali','status'];
                        $values = [$idPinjam, $kondisi, 'pending'];
                        $types = 'iss';

                        if ($this->hasTableColumn($cols, 'id_user')) {
                            $fields[] = 'id_user';
                            $values[] = $userId;
                            $types .= 'i';
                        }
                        if ($this->hasTableColumn($cols, 'requested_by')) {
                            $fields[] = 'requested_by';
                            $values[] = $userId;
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
                        $_SESSION['flash_ok'] = 'Pengajuan pengembalian berhasil dikirim.';
                        header('Location: index.php?page=pemohon_pengembalian');
                        exit;
                    } catch (Exception $e) {
                        $this->conn->rollback();
                        $err = $e->getMessage();
                    }
            }
        }

        $title = "Ajukan Pengembalian - PRANATA";
        include __DIR__ . '/../views/pemohon/pengembalian_tambah.php';
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

    private function hasTableColumn($cols, $name)
    {
        foreach ($cols as $c) {
            if ($c['Field'] === $name) return true;
        }
        return false;
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
        return [true, 'public/uploads/pengembalian/' . $name];
    }

    // approve by admin/pengurus
    public function approve()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        requireRole(['admin','pengurus']);
        $id = $_GET['id'] ?? null;
        if (!$id) die('ID tidak ditemukan');

        $userId = $_SESSION['user_id'] ?? null;
        $this->model->setStatus($id, 'approved', $userId);

        // Pemohon tidak menerima notifikasi; status ditampilkan di daftar peminjaman.

        header('Location: index.php?page=peminjaman'); exit;
    }

    public function reject()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        requireRole(['admin','pengurus']);
        $id = $_GET['id'] ?? null;
        if (!$id) die('ID tidak ditemukan');

        $userId = $_SESSION['user_id'] ?? null;
        $this->model->setStatus($id, 'rejected', $userId);

        // Pemohon tidak menerima notifikasi; status ditampilkan di daftar peminjaman.

        header('Location: index.php?page=peminjaman'); exit;
    }
}
