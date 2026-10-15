<?php
require_once __DIR__ . '/Koneksi.php';

class PeminjamanModel
{
    private $conn;
    private $userColumn = null; // cache detected FK column name
    private $pkColumn = null; // cache primary key column

    // items table detection (support legacy table names)
    private $itemsTable = null;
    private $itemsPeminjamanCol = null; // e.g., peminjaman_id or id_pinjaman
    private $itemsBarangCol = null; // e.g., barang_id or id_barang
    private $itemsQtyCol = null; // e.g., qty or jumlah
    private $stokCol = null;
    private $lastError = null;
    private $peminjamanCols = null;
    private $tableColsCache = [];

    public function __construct()
    {
        $this->conn = Koneksi::connect();
        $this->detectUserColumn();
        $this->detectPrimaryKey();
        $this->detectItemsTable();
        $this->detectStokColumn();
    }

    // Legacy access for views that reference $model->conn
    public function getConnection()
    {
        return $this->conn;
    }

    public function __get($name)
    {
        if ($name === 'conn') return $this->conn;
        return null;
    }

    // Try to detect which column in peminjaman refers to user (common names)
    private function detectUserColumn()
    {
        $candidates = ['user_id','id_user','requested_by','pemohon_id','peminjam_id','created_by','requester'];
        $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='peminjaman'";
        $res = $this->conn->query($sql);
        $cols = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) $cols[] = $row['COLUMN_NAME'];
        }
        foreach ($candidates as $c) {
            if (in_array($c, $cols)) {
                $this->userColumn = $c;
                return $this->userColumn;
            }
        }
        // not found
        $this->userColumn = null;
        return null;
    }

    // Detect primary key column for peminjaman (use for ORDER BY / lookups)
    private function detectPrimaryKey()
    {
        // 1) find explicit primary key
        $stmt = $this->conn->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='peminjaman' AND COLUMN_KEY = 'PRI' LIMIT 1");
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $row = $res->fetch_assoc()) {
            $this->pkColumn = $row['COLUMN_NAME'];
            return $this->pkColumn;
        }

        // 2) fallback to common names
        $candidates = ['id','peminjaman_id','id_peminjaman','peminjaman_id'];
        $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='peminjaman'";
        $res = $this->conn->query($sql);
        $cols = [];
        if ($res) while ($r = $res->fetch_assoc()) $cols[] = $r['COLUMN_NAME'];
        foreach ($candidates as $c) {
            if (in_array($c, $cols)) {
                $this->pkColumn = $c;
                return $this->pkColumn;
            }
        }

        // 3) as last resort pick the first column
        if (!empty($cols)) {
            $this->pkColumn = $cols[0];
            return $this->pkColumn;
        }

        $this->pkColumn = null;
        return null;
    }

    // helper: check if peminjaman table has a particular column
    private function hasColumn($col)
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='peminjaman' AND COLUMN_NAME = ?");
        $stmt->bind_param('s', $col);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        return ((int)($r['cnt'] ?? 0)) > 0;
    }

    private function getPeminjamanColumns()
    {
        if ($this->peminjamanCols !== null) return $this->peminjamanCols;
        $cols = [];
        $res = $this->conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='peminjaman'");
        if ($res) while ($r = $res->fetch_assoc()) $cols[] = $r['COLUMN_NAME'];
        $this->peminjamanCols = $cols;
        return $cols;
    }

    private function getTableColumns($table)
    {
        if (isset($this->tableColsCache[$table])) return $this->tableColsCache[$table];
        $cols = [];
        $stmt = $this->conn->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME = ?");
        $stmt->bind_param('s', $table);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) while ($r = $res->fetch_assoc()) $cols[] = $r['COLUMN_NAME'];
        $this->tableColsCache[$table] = $cols;
        return $cols;
    }

    // detect items/detail table and its column names (support common legacy names)
    private function detectItemsTable()
    {
        $candidates = ['peminjaman_items','peminjaman_item','item_peminjaman','peminjaman_detail','peminjamanitems','detail_peminjaman','detail_pinjaman','detail_peminjam','detail_peminjam'];
        // look for existing table
        $found = null;
        foreach ($candidates as $t) {
            $stmt = $this->conn->prepare("SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME = ?");
            $stmt->bind_param('s', $t);
            $stmt->execute();
            $r = $stmt->get_result()->fetch_assoc();
            if (((int)($r['cnt'] ?? 0)) > 0) { $found = $t; break; }
        }

        if (!$found) {
            // none found; items API will be disabled (create with items will fail)
            $this->itemsTable = null;
            return null;
        }

        $this->itemsTable = $found;

        // detect column names in the items table
        $colsRes = $this->conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='" . $this->itemsTable . "'");
        $cols = [];
        if ($colsRes) while ($r = $colsRes->fetch_assoc()) $cols[] = $r['COLUMN_NAME'];

        // candidates for peminjaman FK
        $pCandidates = ['peminjaman_id','peminjamanid','id_peminjaman','id_pinjaman','id_peminjam','id_pinjam','id_pinjam'];
        foreach ($pCandidates as $c) if (in_array($c, $cols)) { $this->itemsPeminjamanCol = $c; break; }
        if (!$this->itemsPeminjamanCol) $this->itemsPeminjamanCol = $cols[1] ?? null; // second column as fallback

        // candidates for barang FK
        $bCandidates = ['barang_id','id_barang','id_brg','id_brg'];
        foreach ($bCandidates as $c) if (in_array($c, $cols)) { $this->itemsBarangCol = $c; break; }
        if (!$this->itemsBarangCol) $this->itemsBarangCol = $cols[2] ?? null;

        // candidates for qty
        $qCandidates = ['qty','jumlah','jumlah_barang','quantity','jumlah_barang'];
        foreach ($qCandidates as $c) if (in_array($c, $cols)) { $this->itemsQtyCol = $c; break; }
        if (!$this->itemsQtyCol) $this->itemsQtyCol = $cols[3] ?? 'qty';

        return $this->itemsTable;
    }

    private function detectStokColumn()
    {
        $candidates = ['stok','stok_barang','stock','jumlah','qty','jumlah_barang'];
        $placeholders = "'" . implode("','", $candidates) . "'";
        $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='barang' AND COLUMN_NAME IN ($placeholders) LIMIT 1";
        $res = $this->conn->query($sql);
        if ($res && $res->num_rows > 0) {
            $this->stokCol = $res->fetch_assoc()['COLUMN_NAME'];
            return $this->stokCol;
        }
        $this->stokCol = null;
        return null;
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    public function getAll()
    {
        $order = '';
        if ($this->pkColumn) {
            $order = "ORDER BY p.`{$this->pkColumn}` DESC";
        } elseif ($this->hasColumn('created_at')) {
            $order = "ORDER BY p.`created_at` DESC";
        }

        if ($this->userColumn) {
            $col = $this->userColumn;
            $sql = "SELECT p.*, u.nama AS pemohon_nama FROM peminjaman p LEFT JOIN user u ON u.id_user = p.`$col` $order";
        } else {
            $sql = "SELECT p.* FROM peminjaman p $order";
        }

        $res = $this->conn->query($sql);
        $rows = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                // ensure a stable 'id' field for views regardless of actual PK name
                if ($this->pkColumn && !isset($r['id'])) {
                    $r['id'] = $r[$this->pkColumn] ?? null;
                }
                $rows[] = $r;
            }
        }
        return $rows;
    }

    public function getByUser($userId)
    {
        if (!$this->userColumn) return []; // cannot filter if we don't know the column

        $col = $this->userColumn;
        $order = '';
        if ($this->pkColumn) $order = "ORDER BY `{$this->pkColumn}` DESC";
        elseif ($this->hasColumn('created_at')) $order = "ORDER BY `created_at` DESC";

        $stmt = $this->conn->prepare("SELECT * FROM peminjaman WHERE `$col` = ? $order");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) {
            if ($this->pkColumn && !isset($r['id'])) {
                $r['id'] = $r[$this->pkColumn] ?? null;
            }
            $rows[] = $r;
        }
        return $rows;
    }

    public function getPagedByStatus($statuses, $q, $page, $perPage)
    {
        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        $offset = ($page - 1) * $perPage;

        $cols = $this->getPeminjamanColumns();
        $hasStatus = in_array('status', $cols, true);

        $where = [];
        $params = [];
        $types = '';

        if ($hasStatus && !empty($statuses)) {
            $placeholders = implode(',', array_fill(0, count($statuses), '?'));
            $where[] = "LOWER(p.`status`) IN ($placeholders)";
            foreach ($statuses as $st) {
                $params[] = strtolower((string)$st);
                $types .= 's';
            }
        }

        $q = trim((string)$q);
        if ($q !== '') {
            $like = '%' . $q . '%';
            $likeParts = [];
            $searchCols = [
                'nama_pihak1','nama_pihak2','tujuan','keterangan','status',
                'tgl_pinjam','tgl_jatuh_tempo','nip','nip1'
            ];
            foreach ($searchCols as $c) {
                if (in_array($c, $cols, true)) {
                    $likeParts[] = "p.`$c` LIKE ?";
                    $params[] = $like;
                    $types .= 's';
                }
            }
            if ($this->userColumn) {
                $likeParts[] = "u.nama LIKE ?";
                $params[] = $like;
                $types .= 's';
            }
            if (!empty($likeParts)) {
                $where[] = '(' . implode(' OR ', $likeParts) . ')';
            }
        }

        $whereSql = !empty($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

        $order = '';
        if ($this->pkColumn) {
            $order = "ORDER BY p.`{$this->pkColumn}` DESC";
        } elseif ($this->hasColumn('created_at')) {
            $order = "ORDER BY p.`created_at` DESC";
        }

        if ($this->userColumn) {
            $joinSql = "FROM peminjaman p LEFT JOIN user u ON u.id_user = p.`{$this->userColumn}`";
        } else {
            $joinSql = "FROM peminjaman p";
        }

        $countSql = "SELECT COUNT(*) AS cnt {$joinSql} {$whereSql}";
        $stmt = $this->conn->prepare($countSql);
        if ($stmt && $types !== '') {
            $this->bindParams($stmt, $types, $params);
        }
        if ($stmt) $stmt->execute();
        $cntRes = $stmt ? $stmt->get_result()->fetch_assoc() : ['cnt' => 0];
        $total = (int)($cntRes['cnt'] ?? 0);
        $pages = max(1, (int)ceil($total / $perPage));

        $dataSql = "SELECT p.*" . ($this->userColumn ? ", u.nama AS pemohon_nama" : "") . " {$joinSql} {$whereSql} {$order} LIMIT ? OFFSET ?";
        $stmt2 = $this->conn->prepare($dataSql);
        $params2 = $params;
        $types2 = $types . 'ii';
        $params2[] = $perPage;
        $params2[] = $offset;
        if ($stmt2) {
            $this->bindParams($stmt2, $types2, $params2);
            $stmt2->execute();
        }

        $rows = [];
        if ($stmt2) {
            $res = $stmt2->get_result();
            while ($r = $res->fetch_assoc()) {
                if ($this->pkColumn && !isset($r['id'])) {
                    $r['id'] = $r[$this->pkColumn] ?? null;
                }
                $rows[] = $r;
            }
        }

        return [
            'rows' => $rows,
            'total' => $total,
            'pages' => $pages,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    private function bindParams($stmt, $types, $params)
    {
        $bind = [];
        $bind[] = $types;
        foreach ($params as $k => $val) {
            $bind[] = &$params[$k];
        }
        call_user_func_array([$stmt, 'bind_param'], $bind);
    }

    public function getPagedCompleted($q, $page, $perPage)
    {
        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        $offset = ($page - 1) * $perPage;

        $pk = $this->pkColumn ?: 'id_pinjam';
        $pCols = $this->getPeminjamanColumns();

        $gCols = $this->getTableColumns('pengembalian');
        $gIdPinjam = null;
        $gStatus = null;
        foreach (['id_pinjam','peminjaman_id','id_peminjaman','id_pinjaman'] as $cand) {
            if (in_array($cand, $gCols, true)) { $gIdPinjam = $cand; break; }
        }
        foreach (['status','status_pengembalian'] as $cand) {
            if (in_array($cand, $gCols, true)) { $gStatus = $cand; break; }
        }

        // Fallback: if pengembalian table/columns missing, use status on peminjaman
        if (!$gIdPinjam || !$gStatus || !in_array($pk, $pCols, true)) {
            return $this->getPagedByStatus(['completed','selesai'], $q, $page, $perPage);
        }

        $where = ["LOWER(g.`$gStatus`) IN ('completed','selesai')"];
        $params = [];
        $types = '';

        $q = trim((string)$q);
        if ($q !== '') {
            $like = '%' . $q . '%';
            $likeParts = [];
            $searchCols = [
                'nama_pihak1','nama_pihak2','tujuan','keterangan','status',
                'tgl_pinjam','tgl_jatuh_tempo','nip','nip1'
            ];
            foreach ($searchCols as $c) {
                if (in_array($c, $pCols, true)) {
                    $likeParts[] = "p.`$c` LIKE ?";
                    $params[] = $like;
                    $types .= 's';
                }
            }
            if ($this->userColumn) {
                $likeParts[] = "u.nama LIKE ?";
                $params[] = $like;
                $types .= 's';
            }
            if (!empty($likeParts)) {
                $where[] = '(' . implode(' OR ', $likeParts) . ')';
            }
        }

        $whereSql = !empty($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

        $order = '';
        if ($this->pkColumn) {
            $order = "ORDER BY p.`{$this->pkColumn}` DESC";
        } elseif ($this->hasColumn('created_at')) {
            $order = "ORDER BY p.`created_at` DESC";
        }

        $joinSql = "FROM peminjaman p JOIN pengembalian g ON g.`$gIdPinjam` = p.`$pk`";
        if ($this->userColumn) {
            $joinSql .= " LEFT JOIN user u ON u.id_user = p.`{$this->userColumn}`";
        }

        $countSql = "SELECT COUNT(DISTINCT p.`$pk`) AS cnt {$joinSql} {$whereSql}";
        $stmt = $this->conn->prepare($countSql);
        if ($stmt && $types !== '') {
            $this->bindParams($stmt, $types, $params);
        }
        if ($stmt) $stmt->execute();
        $cntRes = $stmt ? $stmt->get_result()->fetch_assoc() : ['cnt' => 0];
        $total = (int)($cntRes['cnt'] ?? 0);
        $pages = max(1, (int)ceil($total / $perPage));

        $dataSql = "SELECT p.*" . ($this->userColumn ? ", u.nama AS pemohon_nama" : "") . " {$joinSql} {$whereSql} GROUP BY p.`$pk` {$order} LIMIT ? OFFSET ?";
        $stmt2 = $this->conn->prepare($dataSql);
        $params2 = $params;
        $types2 = $types . 'ii';
        $params2[] = $perPage;
        $params2[] = $offset;
        if ($stmt2) {
            $this->bindParams($stmt2, $types2, $params2);
            $stmt2->execute();
        }

        $rows = [];
        if ($stmt2) {
            $res = $stmt2->get_result();
            while ($r = $res->fetch_assoc()) {
                if ($this->pkColumn && !isset($r['id'])) {
                    $r['id'] = $r[$this->pkColumn] ?? null;
                }
                $rows[] = $r;
            }
        }

        return [
            'rows' => $rows,
            'total' => $total,
            'pages' => $pages,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    public function find($id)
    {
        $orderBy = '';
        if ($this->userColumn) {
            $col = $this->userColumn;
            $pk = $this->pkColumn ? $this->pkColumn : null;
            if ($pk) {
                $stmt = $this->conn->prepare("SELECT p.*, u.nama AS pemohon_nama FROM peminjaman p LEFT JOIN user u ON u.id_user = p.`$col` WHERE p.`$pk` = ? LIMIT 1");
            } else {
                $stmt = $this->conn->prepare("SELECT p.*, u.nama AS pemohon_nama FROM peminjaman p LEFT JOIN user u ON u.id_user = p.`$col` WHERE p.`id` = ? LIMIT 1");
            }
        } else {
            $pk = $this->pkColumn ? $this->pkColumn : 'id';
            $stmt = $this->conn->prepare("SELECT * FROM peminjaman WHERE `$pk` = ? LIMIT 1");
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row) {
            // normalize id for views
            if ($this->pkColumn && !isset($row['id'])) {
                $row['id'] = $row[$this->pkColumn] ?? null;
            }
            // map verified_by to user name (if present)
            if (!empty($row['verified_by'])) {
                $stmt = $this->conn->prepare("SELECT nama FROM user WHERE id_user = ? LIMIT 1");
                $stmt->bind_param('i', $row['verified_by']);
                $stmt->execute();
                $u = $stmt->get_result()->fetch_assoc();
                if ($u && !empty($u['nama'])) {
                    $row['verified_by_name'] = $u['nama'];
                }
            }
            // attach items
            $row['items'] = $this->getItems($row['id']);
        }
        return $row;
    }

    // Single active loan check: returns true if user has a peminjaman not completed
    public function userHasActiveLoan($userId)
    {
        if (!$this->userColumn) return false;
        $col = $this->userColumn;
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS cnt FROM peminjaman WHERE `$col` = ? AND status <> 'completed'");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        return ((int)($res['cnt'] ?? 0)) > 0;
    }

    public function create($data)
    {
        $status = $data['status'] ?? 'waiting_verification';
        $items = $data['items'] ?? [];
        $this->lastError = null;

        if (!is_array($items) || empty($items)) {
            $this->lastError = 'Pilih setidaknya satu barang.';
            return false;
        }

        // validate stock (lock rows to prevent race)
        $qtyMap = [];
        foreach ($items as $it) {
            $bid = (int)($it['barang_id'] ?? 0);
            $qty = max(1, (int)($it['qty'] ?? 1));
            if ($bid <= 0) continue;
            if (!isset($qtyMap[$bid])) $qtyMap[$bid] = 0;
            $qtyMap[$bid] += $qty;
        }
        if (empty($qtyMap)) {
            $this->lastError = 'Barang tidak valid.';
            return false;
        }

        // Start transaction
        $this->conn->begin_transaction();
        try {
            if ($this->stokCol) {
                $ids = implode(',', array_map('intval', array_keys($qtyMap)));
                $stokColEsc = $this->conn->real_escape_string($this->stokCol);
                $sqlStok = "SELECT id_barang, `{$stokColEsc}` AS stok FROM barang WHERE id_barang IN ({$ids}) FOR UPDATE";
                $resStok = $this->conn->query($sqlStok);
                $stokMap = [];
                if ($resStok) {
                    while ($r = $resStok->fetch_assoc()) {
                        $stokMap[(int)$r['id_barang']] = (int)($r['stok'] ?? 0);
                    }
                }
                foreach ($qtyMap as $bid => $qty) {
                    $stok = $stokMap[$bid] ?? 0;
                    if ($qty > $stok) {
                        $this->lastError = "Stok barang tidak cukup (ID: {$bid}).";
                        $this->conn->rollback();
                        return false;
                    }
                }
            }

            $cols = [];
            $placeholders = [];
            $values = [];
            $types = '';

            if ($this->userColumn && isset($data['user_id'])) {
                $col = $this->userColumn;
                if ($this->hasColumn($col)) {
                    $cols[] = $col;
                    $placeholders[] = '?';
                    $values[] = (int)$data['user_id'];
                    $types .= 'i';
                }
            }

            $fieldMap = [
                'nama_pihak1' => 's',
                'nip' => 's',
                'jabatan' => 's',
                'nama_pihak2' => 's',
                'nip1' => 's',
                'jabatan1' => 's',
                'alamat' => 's',
                'no_hp' => 's',
                'tujuan' => 's',
                'tgl_pinjam' => 's',
                'tgl_jatuh_tempo' => 's',
                'spesifikasi' => 's',
                'keterangan' => 's',
                'requested_by' => 'i'
            ];

            foreach ($fieldMap as $field => $type) {
                if (array_key_exists($field, $data) && $this->hasColumn($field)) {
                    $cols[] = $field;
                    $placeholders[] = '?';
                    $values[] = $data[$field];
                    $types .= $type;
                }
            }

            if ($this->hasColumn('status')) {
                $cols[] = 'status';
                $placeholders[] = '?';
                $values[] = $status;
                $types .= 's';
            }

            if ($this->hasColumn('created_at')) {
                $cols[] = 'created_at';
                $placeholders[] = 'NOW()';
            }

            if (empty($cols)) {
                $this->lastError = 'Tidak ada kolom peminjaman yang cocok dengan data input.';
                $this->conn->rollback();
                return false;
            }

            $sql = "INSERT INTO peminjaman (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', $placeholders) . ")";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                $this->lastError = 'Gagal menyiapkan query peminjaman.';
                $this->conn->rollback();
                return false;
            }
            if (!empty($values)) {
                $bind = [];
                $bind[] = $types;
                foreach ($values as $k => $v) {
                    $bind[] = &$values[$k];
                }
                call_user_func_array([$stmt, 'bind_param'], $bind);
            }

            if (!$stmt->execute()) {
                $this->lastError = $stmt->error ?: 'Gagal menyimpan data peminjaman.';
                $this->conn->rollback();
                return false;
            }
            $id = $this->conn->insert_id;

            // insert items if provided
            if (!empty($items) && is_array($items)) {
                if (!$this->createItems($id, $items)) {
                    if (!$this->lastError) {
                        $this->lastError = 'Gagal menyimpan detail barang peminjaman.';
                    }
                    $this->conn->rollback();
                    return false;
                }
            }

            // reduce stock after items are inserted
            if ($this->stokCol) {
                $stokColEsc = $this->conn->real_escape_string($this->stokCol);
                $stmtUp = $this->conn->prepare("UPDATE barang SET `{$stokColEsc}` = `{$stokColEsc}` - ? WHERE id_barang = ?");
                foreach ($qtyMap as $bid => $qty) {
                    if ($qty <= 0) continue;
                    $stmtUp->bind_param('ii', $qty, $bid);
                    if (!$stmtUp->execute()) {
                        $this->lastError = 'Gagal mengurangi stok.';
                        $this->conn->rollback();
                        return false;
                    }
                }
            }

            $this->conn->commit();
            return $id;
        } catch (Exception $e) {
            $this->conn->rollback();
            if (!$this->lastError) {
                $this->lastError = 'Gagal membuat peminjaman.';
            }
            return false;
        }
    }

    // get items for a peminjaman
    public function getItems($peminjamanId)
    {
        if (!$this->itemsTable || !$this->itemsPeminjamanCol) return [];
        $pCol = $this->itemsPeminjamanCol;
        $bCol = $this->itemsBarangCol ?: 'barang_id';
        $qCol = $this->itemsQtyCol ?: 'qty';

        $sql = "SELECT pi.*, b.nama_barang, b.kode_barang FROM `{$this->itemsTable}` pi LEFT JOIN barang b ON b.id_barang = pi.`{$bCol}` WHERE pi.`{$pCol}` = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $peminjamanId);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        return $rows;
    }

    public function getItemsCount($peminjamanId)
    {
        if (!$this->itemsTable || !$this->itemsPeminjamanCol) return 0;
        $pCol = $this->itemsPeminjamanCol;
        $sql = "SELECT COUNT(*) AS cnt FROM `{$this->itemsTable}` WHERE `{$pCol}` = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $peminjamanId);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        return (int)($r['cnt'] ?? 0);
    }

    public function createItems($peminjamanId, $items)
    {
        if (!$this->itemsTable || !$this->itemsPeminjamanCol) {
            $this->lastError = 'Tabel detail peminjaman tidak ditemukan.';
            return false;
        }
        $pCol = $this->itemsPeminjamanCol;
        $bCol = $this->itemsBarangCol ?: 'barang_id';
        $qCol = $this->itemsQtyCol ?: 'qty';

        // detect whether items table has created_at column
        $hasCreatedAt = false;
        $colsRes = $this->conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='" . $this->itemsTable . "'");
        if ($colsRes) {
            while ($c = $colsRes->fetch_assoc()) {
                if ($c['COLUMN_NAME'] === 'created_at') { $hasCreatedAt = true; break; }
            }
        }

        if ($hasCreatedAt) {
            $sql = "INSERT INTO `{$this->itemsTable}` (`{$pCol}`, `{$bCol}`, `{$qCol}`, created_at) VALUES (?, ?, ?, NOW())";
        } else {
            $sql = "INSERT INTO `{$this->itemsTable}` (`{$pCol}`, `{$bCol}`, `{$qCol}`) VALUES (?, ?, ?)";
        }
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            $this->lastError = 'Gagal menyiapkan query detail peminjaman.';
            return false;
        }
        foreach ($items as $it) {
            $bid = (int)($it['barang_id'] ?? 0);
            $qty = max(1, (int)($it['qty'] ?? 1));
            if ($bid <= 0) continue;
            if ($hasCreatedAt) {
                $stmt->bind_param('iii', $peminjamanId, $bid, $qty);
            } else {
                $stmt->bind_param('iii', $peminjamanId, $bid, $qty);
            }
            if (!$stmt->execute()) {
                $this->lastError = $stmt->error ?: 'Gagal menyimpan detail peminjaman.';
                return false;
            }
        }
        return true;
    }

    // expose detected items mapping for diagnostics
    public function getItemsMapping()
    {
        return [
            'items_table' => $this->itemsTable,
            'peminjaman_col' => $this->itemsPeminjamanCol,
            'barang_col' => $this->itemsBarangCol,
            'qty_col' => $this->itemsQtyCol
        ];
    }

    // return detected user column name or null
    public function getUserColumn()
    {
        return $this->userColumn;
    }

    // expose detected primary key column name
    public function getPkColumn()
    {
        return $this->pkColumn;
    }

    public function setStatus($id, $status, $verifiedBy = null)
    {
        $pk = $this->pkColumn ? $this->pkColumn : 'id';
        if ($verifiedBy) {
            $stmt = $this->conn->prepare("UPDATE peminjaman SET status = ?, verified_by = ?, verified_at = NOW() WHERE `$pk` = ?");
            $stmt->bind_param('sii', $status, $verifiedBy, $id);
        } else {
            $stmt = $this->conn->prepare("UPDATE peminjaman SET status = ? WHERE `$pk` = ?");
            $stmt->bind_param('si', $status, $id);
        }
        return $stmt->execute();
    }
}
