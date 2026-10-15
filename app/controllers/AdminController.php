<?php
// ===================================================
// CONTROLLER: ADMIN MANAGEMENT
// ===================================================

require_once __DIR__ . '/../models/Koneksi.php';

class AdminController
{
    private $conn;

    public function __construct()
    {
        // Pastikan session aktif
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Buat koneksi database
        $this->conn = Koneksi::connect();
    }

    // ===================================================
    // INDEX — Tampilkan halaman Barang (list)
    // ===================================================
    public function index()
    {
        // Untuk halaman barang kita tidak menjalankan query users di controller.
        // File view `app/views/admin/index.php` sudah melakukan query barang sendiri.

        // Kirim ke view
        include __DIR__ . '/../views/admin/index.php';
    }

    // ===================================================
    // TAMBAH DATA
    // ===================================================
    public function tambah()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nama     = trim($_POST['nama']);
            $username = trim($_POST['username']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $email    = trim($_POST['email']);
            $role     = trim($_POST['role']);
            $aktif    = isset($_POST['aktif']) ? 1 : 0;

            // Query insert dengan kolom "nama" ditambahkan
            $stmt = $this->conn->prepare("
            INSERT INTO users (nama, username, password, email, role, aktif, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
            $stmt->bind_param("sssssi", $nama, $username, $password, $email, $role, $aktif);
            $stmt->execute();

            // Redirect setelah simpan
            header("Location: index.php?page=admin");
            exit;
        }

        // Jika bukan POST, tampilkan form
        include __DIR__ . '/../views/admin/tambah.php';
    }


    // ===================================================
    // UBAH DATA
    // ===================================================
    public function ubah()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) die("ID tidak ditemukan.");

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nama     = trim($_POST['nama']);
            $username = trim($_POST['username']);
            $email    = trim($_POST['email']);
            $role     = trim($_POST['role']);
            $aktif    = isset($_POST['aktif']) ? 1 : 0;

            // Jika password diisi, update juga password
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $this->conn->prepare("
                UPDATE users
                SET nama=?, username=?, email=?, role=?, aktif=?, password=?
                WHERE id=?
            ");
                $stmt->bind_param("ssssisi", $nama, $username, $email, $role, $aktif, $password, $id);
            } else {
                $stmt = $this->conn->prepare("
                UPDATE users
                SET nama=?, username=?, email=?, role=?, aktif=?
                WHERE id=?
            ");
                $stmt->bind_param("ssssii", $nama, $username, $email, $role, $aktif, $id);
            }

            $stmt->execute();
            header("Location: index.php?page=admin");
            exit;
        }

        // Jika GET, ambil data lama dan tampilkan form
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();

        if (!$data) die("Data tidak ditemukan.");
        include __DIR__ . '/../views/admin/ubah.php';
    }


// ================================
    // HAPUS DATA
    // ================================
    public function hapus()
    {
        if (!isset($_GET['id'])) {
            die("ID tidak ditemukan.");
        }

        $id = intval($_GET['id']);

        $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        header("Location: index.php?page=admin");
        exit;
    }
    // ===================================================
    // DETAIL DATA
    // ===================================================
    public function detail()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) die("ID tidak ditemukan.");

        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();

        if (!$data) die("Data tidak ditemukan.");
        include __DIR__ . '/../views/admin/detail.php';
    }

    // ===================================================
    // LAPORAN
    // ===================================================
    public function laporan()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        require_once __DIR__ . '/../config/auth_helper.php';
        requireRole('admin');

        $jenis = $_GET['jenis'] ?? 'barang';
        if ($jenis === 'pakta') $jenis = 'barang';
        $yearParam = $_GET['year'] ?? '';
        $monthNumParam = $_GET['month_num'] ?? '';
        $monthParam = $_GET['month'] ?? '';
        if (preg_match('/^\d{4}$/', (string)$yearParam) && preg_match('/^(0?[1-9]|1[0-2])$/', (string)$monthNumParam)) {
            $monthStart = sprintf('%04d-%02d', (int)$yearParam, (int)$monthNumParam);
        } else {
            $monthStart = $this->normalizeMonth($monthParam) ?: date('Y-m');
        }
        $monthEnd = $monthStart;
        $startDate = $monthStart . '-01';
        $endDate = (new DateTime($monthEnd . '-01'))->modify('last day of this month')->format('Y-m-d');

        $rows = [];
        $columns = [];
        $reportTitle = '';
        $lokasiMap = $this->getLokasiMap();

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

            $cols = $this->getTableColumns('peminjaman');
            $dateCol = $this->pickColumn($cols, ['tgl_pinjam','created_at']);
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
                $r['status_label'] = $this->formatPeminjamanStatus($r['status'] ?? '');
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
            $dateFilter = '';
            $dateParams = [];
            if ($detailTable) {
                $dCols = $this->getTableColumns($detailTable);
                $dIdKembali = $this->pickColumn($dCols, ['id_kembali','pengembalian_id']);
                $dTgl = $this->pickColumn($dCols, ['tgl_kembali','tanggal_kembali','tgl']);
                if ($dIdKembali && $dTgl) {
                    $dateFilter = "WHERE DATE(d.`{$dTgl}`) BETWEEN ? AND ?";
                    $dateParams = [$startDate, $endDate];
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
                            {$dateFilter}
                            ORDER BY p.id_kembali DESC";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->bind_param('ss', ...$dateParams);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    while ($r = $res->fetch_assoc()) {
                        $r['status_label'] = $this->formatPengembalianStatus($r['status'] ?? '');
                        $rows[] = $r;
                    }
                }
            }

            if (!$detailTable || empty($rows)) {
                $cols = $this->getTableColumns('pengembalian');
                $dateCol = $this->pickColumn($cols, ['verified_at','created_at']);
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
                    $r['status_label'] = $this->formatPengembalianStatus($r['status'] ?? '');
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

            if ($this->tableExists('pakta_integritas')) {
                $cols = $this->getTableColumns('pakta_integritas');
                $dateCol = $this->pickColumn($cols, ['tgl_dibuat','created_at']);
                $selectFields = ['nama','nip_nik','jabatan','barang_digunakan','tgl_dibuat'];
                if ($this->pickColumn($cols, ['created_at'])) $selectFields[] = 'created_at';
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

            $cols = $this->getTableColumns('barang');
            $dateCol = $this->pickColumn($cols, ['tgl_masuk','tanggal_masuk','created_at']);
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
                $r['status_brg_label'] = $this->formatBarangStatus($r['status_brg'] ?? '');
                $rows[] = $r;
            }
        }

        $isExport = isset($_GET['export']) && $_GET['export'] === '1';
        $title = "Laporan - PRANATA";
        $roleView = 'admin';
        $generatedAt = date('d/m/Y H:i');
        $startMonth = $monthStart;
        $endMonth = $monthEnd;
        $filterYear = (int)substr($monthStart, 0, 4);
        $filterMonth = (int)substr($monthStart, 5, 2);
        if ($isExport) {
            $periodLabel = $monthStart;
            $this->exportExcel("laporan_{$jenis}_{$monthStart}.xls", $columns, $rows, $reportTitle, $periodLabel);
            exit;
        }
        include __DIR__ . '/../views/laporan/index.php';
    }

    private function normalizeDate($date)
    {
        if (!$date) return null;
        $dt = DateTime::createFromFormat('Y-m-d', $date);
        return $dt ? $dt->format('Y-m-d') : null;
    }

    private function normalizeMonth($month)
    {
        if (!$month) return null;
        $dt = DateTime::createFromFormat('Y-m', $month);
        return $dt ? $dt->format('Y-m') : null;
    }

    private function tableExists($table)
    {
        $t = $this->conn->real_escape_string($table);
        $res = $this->conn->query("SHOW TABLES LIKE '{$t}'");
        return $res && $res->num_rows > 0;
    }

    private function getTableColumns($table)
    {
        $cols = [];
        $res = $this->conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}'");
        if ($res) {
            while ($r = $res->fetch_assoc()) $cols[] = $r['COLUMN_NAME'];
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

    private function detectPengembalianDetailTable()
    {
        $candidates = ['detail_pengembalian','detail_kembali','pengembalian_detail'];
        foreach ($candidates as $tbl) {
            $res = $this->conn->query("SHOW TABLES LIKE '{$tbl}'");
            if ($res && $res->num_rows > 0) return $tbl;
        }
        return null;
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

    private function formatPeminjamanStatus($status)
    {
        $raw = strtolower((string)$status);
        if (in_array($raw, ['waiting_verification','pending'], true)) return 'Menunggu Verifikasi';
        if ($raw === 'approved') return 'Disetujui';
        if ($raw === 'rejected') return 'Ditolak';
        if ($raw === 'completed') return 'Selesai';
        return $raw ? ucwords(str_replace('_', ' ', $raw)) : '-';
    }

    private function formatPengembalianStatus($status)
    {
        $raw = strtolower((string)$status);
        if ($raw === 'pending') return 'Menunggu Verifikasi';
        if ($raw === 'completed') return 'Disetujui';
        if ($raw === 'processing') return 'Dikembalikan';
        return $raw ? ucwords(str_replace('_', ' ', $raw)) : '-';
    }

    private function formatBarangStatus($status)
    {
        $raw = strtolower((string)$status);
        if (in_array($raw, ['menunggu_verifikasi','pending','menunggu'], true)) return 'Menunggu Verifikasi';
        if (in_array($raw, ['terverifikasi','verified'], true)) return 'Terverifikasi';
        if ($raw === 'ditolak') return 'Ditolak';
        return $raw ? ucwords(str_replace('_', ' ', $raw)) : '-';
    }

    private function exportExcel($filename, $columns, $rows, $reportTitle = '', $periodLabel = '')
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
}
