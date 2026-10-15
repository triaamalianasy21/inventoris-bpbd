<?php
require_once __DIR__ . '/_common.php';
require_once __DIR__ . '/../app/models/Koneksi.php';
require_once __DIR__ . '/../app/models/UsersModel.php';

$conn = Koneksi::connect();
$userModel = new UsersModel($conn);

$input = api_input();

$nama = trim((string)($input['nama'] ?? ($_POST['nama'] ?? '')));
$username = trim((string)($input['username'] ?? ($_POST['username'] ?? '')));
$password = (string)($input['password'] ?? ($_POST['password'] ?? ''));
$no_kontak = trim((string)($input['no_kontak'] ?? ($_POST['no_kontak'] ?? '')));

if ($nama === '' || $username === '' || $password === '') {
    api_response(false, null, 'Nama, username, dan password wajib diisi.');
}

if (strlen($password) < 6) {
    api_response(false, null, 'Password minimal 6 karakter.');
}

$existing = $userModel->getByUsername($username);
if ($existing) {
    api_response(false, null, 'Username sudah digunakan.');
}

$storedPassword = password_hash($password, PASSWORD_DEFAULT);
$ok = $userModel->createUser($nama, $username, $storedPassword, 'pemohon', $no_kontak ?: null);
if (!$ok) {
    api_response(false, null, 'Gagal mendaftar, coba lagi.');
}

api_response(true, [
    'nama' => $nama,
    'username' => $username,
    'role' => 'pemohon'
], null);
