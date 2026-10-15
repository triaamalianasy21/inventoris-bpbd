<?php
require_once __DIR__ . '/_common.php';
require_once __DIR__ . '/../app/models/Koneksi.php';
require_once __DIR__ . '/../app/models/UsersModel.php';

$conn = Koneksi::connect();
$userModel = new UsersModel($conn);

$input = api_input();
$action = $_GET['action'] ?? $_POST['action'] ?? ($input['action'] ?? 'login');

if ($action === 'logout') {
    if (session_status() === PHP_SESSION_NONE) session_start();
    session_destroy();
    api_response(true, ['message' => 'Logout berhasil.'], null);
}

if ($action === 'me') {
    if (!currentUserId()) {
        api_response(false, null, 'Belum login.');
    }
    $uid = (int)currentUserId();
    $stmt = $conn->prepare("SELECT id_user, nama, username, role, no_kontak FROM user WHERE id_user = ? LIMIT 1");
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    api_response(true, $user, null);
}

// default: login
$username = trim((string)($input['username'] ?? ($_POST['username'] ?? '')));
$password = (string)($input['password'] ?? ($_POST['password'] ?? ''));

if ($username === '' || $password === '') {
    api_response(false, null, 'Username dan password wajib diisi.');
}

$user = $userModel->getByUsername($username);
if (!$user) {
    api_response(false, null, 'Username tidak ditemukan.');
}

$stored = (string)($user['password'] ?? '');
$ok = password_verify($password, $stored) || ($password === $stored);
if (!$ok) {
    api_response(false, null, 'Password salah.');
}

$status = strtolower(trim((string)($user['status'] ?? '')));
$roleCheck = strtolower(trim((string)($user['role'] ?? '')));
// Validasi hanya untuk akun pemohon (eksternal)
if ($roleCheck === 'pemohon' && $status !== '' && $status !== 'active') {
    $msg = $status === 'pending'
        ? 'Akun belum diverifikasi admin.'
        : 'Akun kamu tidak aktif.';
    api_response(false, null, $msg);
}

$roleRaw = strtolower(trim((string)($user['role'] ?? '')));
$roleMap = [
    'petugas_gudang' => 'petugas',
    'gudang' => 'petugas',
    'pengurus_barang' => 'pengurus'
];
$role = $roleMap[$roleRaw] ?? $roleRaw;

if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['user_id'] = $user['id_user'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $role;

api_response(true, [
    'id_user' => (int)$user['id_user'],
    'nama' => $user['nama'] ?? '',
    'username' => $user['username'] ?? '',
    'role' => $role,
    'no_kontak' => $user['no_kontak'] ?? null
], null);
