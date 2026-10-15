<?php
header("Content-Type: application/json");

require_once __DIR__ . "/../models/Koneksi.php";

$conn = Koneksi::connect();

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (!$conn) {
    echo json_encode([
        "success" => false,
        "message" => "Gagal koneksi database!"
    ]);
    exit;
}

$stmt = $conn->prepare("SELECT id, username, password, nama, role FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();

$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {

    if (password_verify($password, $row['password'])) {

        echo json_encode([
            "success" => true,
            "message" => "Login berhasil!",
            "data" => [
                "id" => $row['id'],
                "username" => $row['username'],
                "nama" => $row['nama'],
                "role" => $row['role']
            ]
        ]);
        exit;
    }
}

// Jika username/password salah
echo json_encode([
    "success" => false,
    "message" => "Username atau password salah!"
]);
exit;
