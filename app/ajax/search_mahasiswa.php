<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../models/Koneksi.php';
$conn = Koneksi::connect();

$search = $_GET['search'] ?? '';
$search = "%$search%";

$stmt = $conn->prepare("
    SELECT * FROM mahasiswa 
    WHERE nama LIKE ? OR nim LIKE ? OR jurusan LIKE ? OR no_hp LIKE ?
    ORDER BY id DESC
");
$stmt->bind_param("ssss", $search, $search, $search, $search);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../views/mahasiswa/tabel_mahasiswa.php';
