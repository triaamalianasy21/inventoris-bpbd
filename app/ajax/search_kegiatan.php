<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../models/Koneksi.php';
$conn = Koneksi::connect();

$search = $_GET['search'] ?? '';
$search = "%$search%";

$stmt = $conn->prepare("
    SELECT * FROM kegiatan 
    WHERE nama LIKE ? 
       OR lokasi LIKE ? 
       OR tanggal LIKE ? 
       OR deskripsi LIKE ?
    ORDER BY tanggal DESC
");
$stmt->bind_param("ssss", $search, $search, $search, $search);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../views/kegiatan/tabel_kegiatan.php';
