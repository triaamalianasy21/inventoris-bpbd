<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Headers: Content-Type");

// koneksi mysqli
require_once __DIR__ . "/../app/config/Database.php";

// ambil koneksi mysqli
$db = (new Database())->getConnection();


// fungsi respons standar JSON
function jsonResponse($success, $message, $data = null)
{
    echo json_encode([
        "success" => $success,
        "message" => $message,
        "data" => $data
    ]);
    exit;
}
