<?php
require_once __DIR__ . '/_common.php';
require_once __DIR__ . '/../app/models/Koneksi.php';
require_once __DIR__ . '/../app/models/PeminjamanModel.php';

$conn = Koneksi::connect();
$model = new PeminjamanModel();
$input = api_input();
$action = $_GET['action'] ?? $_POST['action'] ?? ($input['action'] ?? 'list');

function report_normalize_month($month)
{
    if (!$month) return null;
    $dt = DateTime::createFromFormat('Y-m', $month);
    return $dt ? $dt->format('Y-m') : null;
}

function report_table_exists($conn, $table)
{
    $t = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '{$t}'");
    return $res && $res->num_rows > 0;
}

function report_get_table_columns($conn, $table)
{
    $cols = [];
    $res = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$table}'");
    if ($res) {
        while ($r = $res->fetch_assoc()) $cols[] = $r['COLUMN_NAME'];
    }
    return $cols;
}

function report_pick_column($cols, $candidates)
{
    foreach ($candidates as $col) {
        if ($col === null || $col === '') continue;
        if (in_array($col, $cols, true)) return $col;
    }
    return null;
}

function report_get_lokasi_map($conn)
{
    $map = [];
    $tbl = $conn->query("SHOW TABLES LIKE 'lokasi_gudang'");
    if ($tbl && $tbl->num_rows) {
        $res = $conn->query("SELECT id_lokasi, nama_lokasi FROM lokasi_gudang");
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $map[(string)$r['id_lokasi']] = $r['nama_lokasi'];
            }
        }
    }
    return $map;
}

function report_format_peminjaman_status($status)
{
    $raw = strtolower((string)$status);
    if (in_array($raw, ['waiting_verification','pending'], true)) return 'Menunggu Verifikasi';
    if ($raw === 'approved') return 'Disetujui';
    if ($raw === 'rejected') return 'Ditolak';
    if ($raw === 'completed') return 'Selesai';
    return $raw ? ucwords(str_replace('_', ' ', $raw)) : '-';
}

function report_format_pengembalian_status($status)
{
    $raw = strtolower((string)$status);
    if ($raw === 'pending') return 'Menunggu Verifikasi';
    if ($raw === 'completed') return 'Disetujui';
    if ($raw === 'processing') return 'Dikembalikan';
    return $raw ? ucwords(str_replace('_', ' ', $raw)) : '-';
}

function report_format_barang_status($status)
{
    $raw = strtolower((string)$status);
    if (in_array($raw, ['menunggu_verifikasi','pending','menunggu'], true)) return 'Menunggu Verifikasi';
    if (in_array($raw, ['terverifikasi','verified'], true)) return 'Terverifikasi';
    if ($raw === 'ditolak') return 'Ditolak';
    return $raw ? ucwords(str_replace('_', ' ', $raw)) : '-';
}

function report_detect_pengembalian_detail_table($conn)
{
    $candidates = ['detail_pengembalian','detail_kembali','pengembalian_detail','pengembalian_items'];
    foreach ($candidates as $t) {
        $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME = ?");
        $stmt->bind_param('s', $t);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        if (((int)($r['cnt'] ?? 0)) > 0) return $t;
    }
    return null;
}

function report_table_has_column($conn, $table, $column)
{
    $t = $conn->real_escape_string($table);
    $c = $conn->real_escape_string($column);
    $res = $conn->query("SHOW COLUMNS FROM `{$t}` LIKE '{$c}'");
    return $res && $res->num_rows > 0;
}

if ($action !== 'list') {
    api_response(false, null, 'Action tidak valid.');
}

require_login_api();
require_role_api(['admin','pengurus_barang','pengurus']);

$jenis = strtolower(trim($_GET['jenis'] ?? ($input['jenis'] ?? 'barang')));
$allowedJenis = ['barang','peminjaman','pengembalian'];
if (!in_array($jenis, $allowedJenis, true)) $jenis = 'barang';
$monthParam = $_GET['month'] ?? ($input['month'] ?? '');
$monthStart = report_normalize_month($monthParam) ?: date('Y-m');
$startDate = $monthStart . '-01';
$endDate = (new DateTime($monthStart . '-01'))->modify('last day of this month')->format('Y-m-d');

$rows = [];
$columns = [];
$reportTitle = '';
$lokasiMap = report_get_lokasi_map($conn);

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

    $cols = report_get_table_columns($conn, 'peminjaman');
    $dateCol = report_pick_column($cols, ['tgl_pinjam','created_at']);
    $orderCol = report_pick_column($cols, [$model->getPkColumn(), 'id_pinjam', 'id']);
    $orderCol = $orderCol ?: 'id_pinjam';
    $sql = "SELECT nama_pihak1, nama_pihak2, tujuan, tgl_pinjam, tgl_jatuh_tempo, status FROM peminjaman";
    if ($dateCol) {
        $sql .= " WHERE DATE(`{$dateCol}`) BETWEEN ? AND ?";
    }
    $sql .= " ORDER BY `{$orderCol}` DESC";
    $stmt = $conn->prepare($sql);
    if ($dateCol) {
        $stmt->bind_param('ss', $startDate, $endDate);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $r['status_label'] = report_format_peminjaman_status($r['status'] ?? '');
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

    $detailTable = report_detect_pengembalian_detail_table($conn);
    $pkPinjam = $model->getPkColumn() ?: 'id_pinjam';
    $joinCol = report_table_has_column($conn, 'peminjaman', $pkPinjam) ? $pkPinjam : 'id_pinjam';

    if ($detailTable) {
        $dCols = report_get_table_columns($conn, $detailTable);
        $dIdKembali = report_pick_column($dCols, ['id_kembali','pengembalian_id']);
        $dTgl = report_pick_column($dCols, ['tgl_kembali','tanggal_kembali','tgl']);
        if ($dIdKembali && $dTgl) {
            $sql = "SELECT p.id_kembali, p.kondisi_kembali, p.status,
                           pm.nama_pihak1, pm.nama_pihak2, pm.tujuan, pm.tgl_pinjam,
                           d.tgl_kembali
                    FROM pengembalian p
                    LEFT JOIN peminjaman pm ON pm.`{$joinCol}` = p.id_pinjam
                    LEFT JOIN (
                        SELECT `{$dIdKembali}` AS id_kembali, MIN(`{$dTgl}`) AS tgl_kembali
                        FROM `{$detailTable}`
                        GROUP BY `{$dIdKembali}`
                    ) d ON d.id_kembali = p.id_kembali
                    WHERE DATE(d.`{$dTgl}`) BETWEEN ? AND ?
                    ORDER BY p.id_kembali DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ss', $startDate, $endDate);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                $r['status_label'] = report_format_pengembalian_status($r['status'] ?? '');
                $rows[] = $r;
            }
        }
    }

    if (!$detailTable || empty($rows)) {
        $cols = report_get_table_columns($conn, 'pengembalian');
        $dateCol = report_pick_column($cols, ['verified_at','created_at']);
        $sql = "SELECT p.id_kembali, p.kondisi_kembali, p.status,
                       pm.nama_pihak1, pm.nama_pihak2, pm.tujuan, pm.tgl_pinjam
                FROM pengembalian p
                LEFT JOIN peminjaman pm ON pm.`{$joinCol}` = p.id_pinjam";
        if ($dateCol) {
            $sql .= " WHERE DATE(p.`{$dateCol}`) BETWEEN ? AND ?";
        }
        $sql .= " ORDER BY p.id_kembali DESC";
        $stmt = $conn->prepare($sql);
        if ($dateCol) {
            $stmt->bind_param('ss', $startDate, $endDate);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $r['tgl_kembali'] = $r['tgl_kembali'] ?? '-';
            $r['status_label'] = report_format_pengembalian_status($r['status'] ?? '');
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

    $cols = report_get_table_columns($conn, 'barang');
    $dateCol = report_pick_column($cols, ['tgl_masuk','tanggal_masuk','created_at']);
    $tglSelect = $dateCol ? "`{$dateCol}` AS tgl_masuk" : "NULL AS tgl_masuk";
    $sql = "SELECT id_barang, id_lokasi, kode_barang, nama_barang, status_brg, {$tglSelect} FROM barang";
    if ($dateCol) {
        $sql .= " WHERE DATE(`{$dateCol}`) BETWEEN ? AND ?";
    }
    $sql .= " ORDER BY id_barang DESC";
    $stmt = $conn->prepare($sql);
    if ($dateCol) {
        $stmt->bind_param('ss', $startDate, $endDate);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $lid = (string)($r['id_lokasi'] ?? '');
        $r['lokasi_label'] = $lokasiMap[$lid] ?? ($lid !== '' ? $lid : '-');
        $r['status_brg_label'] = report_format_barang_status($r['status_brg'] ?? '');
        $rows[] = $r;
    }
}

api_response(true, [
    'jenis' => $jenis,
    'month' => $monthStart,
    'start_date' => $startDate,
    'end_date' => $endDate,
    'report_title' => $reportTitle,
    'columns' => $columns,
    'rows' => $rows
], null);
