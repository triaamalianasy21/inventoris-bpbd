<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "applikasipertama";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode(["error" => $conn->connect_error]));
}

$result = $conn->query("SELECT id, nama FROM mahasiswa");
$mahasiswa = [];

while ($row = $result->fetch_assoc()) {
    $mahasiswa[] = $row;
}

echo json_encode($mahasiswa);
$conn->close();
?>
