<?php

class LoginController
{
    public function loginPage()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        require_once __DIR__ . '/../views/login.php';
    }

    public function loginProcess()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        require_once __DIR__ . '/../models/Koneksi.php';
        require_once __DIR__ . '/../models/UsersModel.php';

        $conn = Koneksi::connect();
        $userModel = new UsersModel($conn);

        $username = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $_SESSION['error'] = "Username dan password wajib diisi!";
            header('Location: index.php?page=login');
            exit;
        }

        $user = $userModel->getByUsername($username);

        if (!$user) {
            $_SESSION['error'] = "Username tidak ditemukan!";
            header('Location: index.php?page=login');
            exit;
        }

        $stored = (string)($user['password'] ?? '');
        $status = strtolower(trim((string)($user['status'] ?? '')));
        $roleRaw = strtolower(trim((string)($user['role'] ?? '')));

        // login fleksibel: hash OR plaintext
        $ok = password_verify($password, $stored) || ($password === $stored);

        if ($ok) {
            // Validasi hanya untuk akun pemohon (eksternal)
            if ($roleRaw === 'pemohon' && $status !== '' && $status !== 'active') {
                $msg = $status === 'pending'
                    ? 'Akun belum diverifikasi admin.'
                    : 'Akun kamu tidak aktif.';
                $_SESSION['error'] = $msg;
                header('Location: index.php?page=login');
                exit;
            }
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Redirect sesuai role
            $role = $user['role'] ?? '';
            switch ($role) {
                case 'pemohon':
                    // Kirim pemohon ke halaman dashboard khusus pemohon
                    $target = 'dashboard_pemohon';
                    break;

                case 'kalaksa':
                    // Kirim kalaksa ke dashboard kalaksa
                    $target = 'kalaksa_dashboard';
                    break;

                case 'pengurus_barang':
                    // Kirim pengurus barang ke dashboard pengurus
                    $target = 'pengurus_dashboard';
                    break;

                case 'petugas_gudang':
                    // Kirim petugas gudang ke dashboard petugas
                    $target = 'petugas_dashboard';
                    break;
                case 'pengurus':
                case 'admin':
                default:
                    $target = 'dashboard';
                    break;
            }

            header('Location: index.php?page=' . $target);
            exit;
        }

        $_SESSION['error'] = "Password salah!";
        header('Location: index.php?page=login');
        exit;
    }

    // ===========================
    // REGISTER
    // ===========================
    public function registerPage()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        include __DIR__ . '/../views/register.php';
    }

    public function registerProcess()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        require_once __DIR__ . '/../models/Koneksi.php';
        require_once __DIR__ . '/../models/UsersModel.php';

        $conn = Koneksi::connect();
        $userModel = new UsersModel($conn);

        $nama = trim($_POST['nama'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        // Validasi sederhana
        if ($nama === '' || $username === '' || $password === '') {
            $_SESSION['error'] = 'Semua field wajib diisi.';
            header('Location: index.php?page=register'); exit;
        }

        if (strlen($password) < 6) {
            $_SESSION['error'] = 'Password minimal 6 karakter.';
            header('Location: index.php?page=register'); exit;
        }

        // cek username unik
        $existing = $userModel->getByUsername($username);
        if ($existing) {
            $_SESSION['error'] = 'Username sudah digunakan. Pilih username lain.';
            header('Location: index.php?page=register'); exit;
        }

        // Simpan password baru dengan hash
        $storedPassword = password_hash($password, PASSWORD_DEFAULT);

        $ok = $userModel->createUser($nama, $username, $storedPassword, 'pemohon', null, 'pending');
        if ($ok) {
            $_SESSION['success'] = 'Pendaftaran berhasil. Tunggu verifikasi admin sebelum login.';
            header('Location: index.php?page=login'); exit;
        }

        $_SESSION['error'] = 'Gagal mendaftar, coba lagi.';
        header('Location: index.php?page=register'); exit;
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        session_destroy();

        header('Location: index.php?page=login');
        exit;
    }
}
