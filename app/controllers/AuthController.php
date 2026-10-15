<?php
class AuthController
{

    // Tampilkan halaman login
    public function loginPage()
    {
        require_once __DIR__ . '/../views/login.php';
    }

    // Proses login
    public function loginProcess()
    {
        session_start();

        require_once __DIR__ . '/../models/Koneksi.php';
        require_once __DIR__ . '/../models/UsersModel.php';

        $conn = Koneksi::connect();
        $userModel = new UsersModel($conn);

        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        $user = $userModel->getByUsername($username);

        if ($user && password_verify($password, $user['password'])) {
            // Login sukses
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            header('Location: index.php?page=dashboard');
            exit;
        } else {
            // Login gagal
            $_SESSION['error'] = 'Username atau password salah';
            header('Location: index.php?page=login');
            exit;
        }
    }

    // Logout
    public function logout()
    {
        session_start();
        session_destroy();
        header('Location: index.php');
        exit;
    }
}
