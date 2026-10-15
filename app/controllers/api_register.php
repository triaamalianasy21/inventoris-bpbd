<?php
header('Content-Type: application/json');
include("../model/koneksi.php");

$username = $_POST['username'];
$password = $_POST['password'];
$fullname = $_POST['fullname'];

$hashed = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users(username, password, fullname) VALUES (?,?,?)");
$stmt->bind_param("sss", $username, $hashed, $fullname);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Berhasil daftar"]);
} else {
    echo json_encode(["success" => false, "message" => "Gagal daftar"]);
}
