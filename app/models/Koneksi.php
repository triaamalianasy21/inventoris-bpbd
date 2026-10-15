<?php

class Koneksi
{
    public static function connect()
    {
        $host = "localhost";
        $user = "root";
        $pass = "";
        $db   = "pranata_app"; // <-- DB BARU KAMU

        $conn = new mysqli($host, $user, $pass, $db);

        if ($conn->connect_error) {
            die("Koneksi database gagal: " . $conn->connect_error);
        }

        $conn->set_charset("utf8mb4");
        return $conn;
    }
}
