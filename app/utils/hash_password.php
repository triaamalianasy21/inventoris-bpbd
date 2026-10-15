<?php
require_once __DIR__ . '/../models/Koneksi.php';
require_once __DIR__ . '/../models/UsersModel.php';

// Hubungkan ke database
$conn = Koneksi::connect();
$userModel = new UsersModel($conn);

// Username yang mau di-update
$username = 'admin';

// Password baru
$newPassword = 'admin123';

// Buat hash password
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

// Update password di database
$stmt = $conn->prepare("UPDATE users SET password=? WHERE username=?");
$stmt->bind_param("ss", $hashedPassword, $username);

if ($stmt->execute()) {
    echo "Password untuk $username sudah di-hash dan siap login!";
} else {
    echo "Gagal update password: " . $stmt->error;
}
